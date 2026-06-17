import "../App.css";
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ReportCard from "../components/H_SignalCard.jsx";
import HM_Nav from "../components/HM_Nav.jsx";
import IncidentMap from "../components/IncidentMap.jsx";
import { useNavigate } from "react-router-dom";
import { fetchParticipants } from "../services/issueParticipantService";
import { openChat } from "../services/issueChatService";
import "./Handhaver_styling.css"
import "../components/MapComponent.jsx"
import NativeLeafletMap from "../components/MapComponent.jsx";
import { Search, TriangleAlert, Siren } from 'lucide-react';

export default function CommandCenter() {
    const navigate = useNavigate();
    const [issues, setIssues] = useState([]);
    const [loading, setLoading] = useState(true);
    const [categories, setCategories] = useState([]);
    const [searchQuery, setSearchQuery] = useState("");
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [statusFilter, setStatusFilter] = useState("");
    const [categoryFilter, setCategoryFilter] = useState("");
    const [selectedIssue, setSelectedIssue] = useState(null);
    const [officer, setOfficer] = useState(null);

    // Modal States
    const [activeTab, setActiveTab] = useState('details'); // 'details', 'updates', 'resolution', 'communicatie'
    const [officerUpdates, setOfficerUpdates] = useState([]);
    const [issueParticipants, setIssueParticipants] = useState([]);
    const [loadingParticipants, setLoadingParticipants] = useState(false);
    const [officerResolution, setOfficerResolution] = useState(null);
    const [updateTitle, setUpdateTitle] = useState("");
    const [updateText, setUpdateText] = useState("");
    const [updateFiles, setUpdateFiles] = useState(null);
    const [resolutionTitle, setResolutionTitle] = useState("");
    const [resolutionText, setResolutionText] = useState("");
    const [resolutionFiles, setResolutionFiles] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Debounce search
    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedSearch(searchQuery);
        }, 500);
        return () => clearTimeout(handler);
    }, [searchQuery]);

    // Fetch categories and officer profile
    useEffect(() => {
        const fetchBaseData = async () => {
            try {
                const token = localStorage.getItem('auth_token');
                const [catRes, meRes] = await Promise.all([
                    axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/categories`, {
                        headers: {'Authorization': `Bearer ${token}`}
                    }),
                    axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me`, {
                        headers: {'Authorization': `Bearer ${token}`}
                    })
                ]);
                const catData = catRes.data.data || catRes.data;
                setCategories(Array.isArray(catData) ? catData : []);
                setOfficer(meRes.data?.profile || meRes.data);
            } catch (error) {
                console.error("Error fetching base data:", error);
            }
        };
        fetchBaseData();
    }, []);

    const fetchIssues = async () => {
        setLoading(true);
        try {
            const token = localStorage.getItem('auth_token');
            const params = new URLSearchParams();
            if (debouncedSearch) params.append('search', debouncedSearch);
            if (statusFilter) params.append('status', statusFilter);
            if (categoryFilter) params.append('category_id', categoryFilter);

            const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/issues?${params.toString()}`, {
                headers: {'Authorization': `Bearer ${token}`}
            });

            const issuesData = response.data.data || response.data;
            setIssues(Array.isArray(issuesData) ? issuesData : []);
        } catch (error) {
            console.error("Error fetching issues:", error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchIssues();
    }, [debouncedSearch, statusFilter, categoryFilter]);

    const fetchIssueDetails = async (issueId) => {
        try {
            const token = localStorage.getItem('auth_token');
            const [updRes, resRes] = await Promise.all([
                axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${issueId}/officer-updates`, {headers: {'Authorization': `Bearer ${token}`}}),
                axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${issueId}/officer-resolution`, {headers: {'Authorization': `Bearer ${token}`}}).catch(() => ({data: null}))
            ]);
            setOfficerUpdates(updRes.data?.data || []);
            setOfficerResolution(resRes.data?.data || resRes.data || null);
        } catch (error) {
            console.error("Error fetching issue details:", error);
        }
    };

    const handleSelectIssue = (issue) => {
        setSelectedIssue(issue);
        setActiveTab('details');
        setUpdateTitle("");
        setUpdateText("");
        setUpdateFiles(null);
        setResolutionTitle("");
        setResolutionText("");
        setResolutionFiles(null);
        fetchIssueDetails(issue.id);
    };

    useEffect(() => {
        if (activeTab === 'communicatie' && selectedIssue) {
            setLoadingParticipants(true);
            fetchParticipants(selectedIssue.id)
                .then(data => setIssueParticipants(data))
                .catch(err => console.error(err))
                .finally(() => setLoadingParticipants(false));
        }
    }, [activeTab, selectedIssue]);

    const handleAssignSelf = async (issueId) => {
        try {
            const token = localStorage.getItem('auth_token');
            await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${issueId}/assign-self`, {}, {
                headers: {'Authorization': `Bearer ${token}`}
            });
            alert("Succesvol toegewezen!");
            fetchIssues();
            setSelectedIssue(null);
        } catch (error) {
            console.error("Error assigning:", error);
            alert("Fout bij toewijzen.");
        }
    };

    const handleUnassignSelf = async (issueId) => {
        try {
            const token = localStorage.getItem('auth_token');
            await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${issueId}/unassign-self`, {}, {
                headers: {'Authorization': `Bearer ${token}`}
            });
            alert("Taak succesvol teruggegeven!");
            fetchIssues();
            setSelectedIssue(null);
        } catch (error) {
            console.error("Error unassigning:", error);
            alert("Fout bij teruggeven taak.");
        }
    };

    const handleChangeStatus = async (issueId, newStatus) => {
        try {
            const token = localStorage.getItem('auth_token');
            await axios.patch(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${issueId}/status`, {
                status: newStatus
            }, {
                headers: {'Authorization': `Bearer ${token}`}
            });
            alert(`Status succesvol gewijzigd naar ${newStatus}!`);
            fetchIssues();
            setSelectedIssue(null);
        } catch (error) {
            console.error("Error updating status:", error);
            alert("Fout bij wijzigen status.");
        }
    };

    const handleSubmitUpdate = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        try {
            const token = localStorage.getItem('auth_token');
            const formData = new FormData();
            formData.append('title', updateTitle || "Update");
            formData.append('content', updateText);
            if (updateFiles) {
                Array.from(updateFiles).forEach(file => formData.append('files[]', file));
            }
            await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${selectedIssue.id}/officer-updates`, formData, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'multipart/form-data'
                }
            });
            setUpdateTitle("");
            setUpdateText("");
            setUpdateFiles(null);
            fetchIssueDetails(selectedIssue.id);
            alert("Update toegevoegd!");
        } catch (error) {
            console.error("Error submitting update:", error);
            alert("Fout bij toevoegen update.");
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSubmitResolution = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        try {
            const token = localStorage.getItem('auth_token');
            const formData = new FormData();
            formData.append('title', resolutionTitle || "Resolutie");
            formData.append('content', resolutionText);
            if (resolutionFiles) {
                Array.from(resolutionFiles).forEach(file => formData.append('files[]', file));
            }
            await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${selectedIssue.id}/officer-resolution`, formData, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'multipart/form-data'
                }
            });
            fetchIssueDetails(selectedIssue.id);
            alert("Resolutie succesvol ingediend!");
        } catch (error) {
            console.error("Error submitting resolution:", error);
            alert("Fout bij indienen resolutie.");
        } finally {
            setIsSubmitting(false);
        }
    };

    const activeIssuesCount = issues.filter(issue => issue.status === 'open' || issue.status === 'in_behandeling').length;
    const urgentIssuesCount = issues.filter(issue => issue.priority === 1 && (issue.status === 'open' || issue.status === 'in_behandeling')).length;


    return (
        <>
            <div
                className="flex flex-col md:flex-row min-h-screen bg-primary-bg font-label antialiased text-primary-text">
                <HM_Nav></HM_Nav>

                <main className="flex-1 p-4 md:p-8 flex flex-col gap-5 relative overflow-x-hidden w-full">

                    <section className="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                        <div
                            className="bg-primary-bg-cards border border-primary-border rounded-2xl p-5 md:p-6 flex justify-between items-center shadow-sm">
                            <div className="flex flex-col gap-2 md:gap-3">
                                <span
                                    className="text-[10px] md:text-[11px] font-bold text-secondary-text uppercase tracking-wider">ACTIEVE WIJKUITDAGINGEN</span>
                                <span
                                    className="text-3xl md:text-4xl font-bold font-headline text-primary-text leading-none">{activeIssuesCount}</span>
                            </div>
                            <div
                                className="w-12 h-12 rounded-xl flex items-center justify-center bg-primary-bg border border-primary-border text-xl shrink-0">
                                <TriangleAlert />
                            </div>
                        </div>

                        <div
                            className="bg-primary-bg-cards border rounded-2xl p-5 md:p-6 flex justify-between items-center shadow-sm"
                            style={{borderColor: '#fecaca'}}>
                            <div className="flex flex-col gap-2 md:gap-3">
                                <span className="text-[10px] md:text-[11px] font-bold uppercase tracking-wider"
                                      style={{color: '#dc2626'}}>URGENTE MELDINGEN</span>
                                <span className="text-3xl md:text-4xl font-bold font-headline leading-none"
                                      style={{color: '#dc2626'}}>{urgentIssuesCount}</span>
                            </div>
                            <div className="w-12 h-12 rounded-xl flex items-center justify-center bg-primary-bg border border-primary-border text-xl shrink-0">
                                <Siren />
                            </div>
                        </div>
                    </section>

                    <section className="flex flex-col sm:flex-row gap-3 w-full">
                        <div className="relative flex-1 w-full">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-primary-text opacity-50 pointer-events-none" />
                            <input
                                type="text"
                                placeholder="Zoek meldingen..."
                                className="w-full py-3 pl-10 pr-3 border border-primary-border rounded-xl bg-primary-bg-cards text-primary-text text-sm outline-none focus:border-primary-accent"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                        <div className="flex gap-2 w-full sm:w-auto">
                            <select
                                className="flex-1 sm:flex-initial py-3 px-4 border border-primary-border rounded-xl bg-primary-bg-cards text-primary-text text-sm cursor-pointer min-w-30 focus:outline-none focus:border-primary-accent"
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                            >
                                <option value="">Alle Statussen</option>
                                <option value="open">Open</option>
                                <option value="in_behandeling">In behandeling</option>
                                <option value="opgelost">Afgehandeld</option>
                                <option value="gesloten">Gesloten</option>
                            </select>
                            <select
                                className="flex-1 sm:flex-initial py-3 px-4 border border-primary-border rounded-xl bg-primary-bg-cards text-primary-text text-sm cursor-pointer min-w-30 focus:outline-none focus:border-primary-accent"
                                value={categoryFilter}
                                onChange={(e) => setCategoryFilter(e.target.value)}
                            >
                                <option value="">Alle Categorieën</option>
                                {categories.map(c => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>
                        </div>
                    </section>

                    <div className="flex flex-col lg:flex-row gap-6 flex-1 items-start w-full">

                        <div
                            className="w-full lg:flex-7 h-87.5 sm:h-112.5 lg:h-150 rounded-2xl overflow-hidden border border-primary-border shadow-sm relative z-0 bg-primary-bg-cards">
                            <IncidentMap
                                issues={issues}
                                onSelectIssue={handleSelectIssue}
                            />
                        </div>

                        <aside
                            className="w-full lg:flex-3 flex flex-col gap-4 items-start h-100 lg:h-150 relative bg-primary-bg-cards border border-primary-border rounded-2xl p-4 shadow-sm">
                            <h3 className="text-base font-bold text-primary-text w-full text-left font-headline">
                                Incidentenwachtrij <span
                                className="text-secondary-text font-medium font-label">({issues.length})</span>
                            </h3>
                            <div className="flex flex-col gap-3 w-full flex-1 overflow-y-auto pr-1 custom-scrollbar">
                                <div className="grid gap-3 w-full">
                                    {loading ? (
                                        <p className="text-secondary-text text-sm font-body">Actuele meldingen
                                            laden...</p>
                                    ) : issues.length === 0 ? (
                                        <p className="text-secondary-text text-sm font-body">Geen meldingen
                                            gevonden.</p>
                                    ) : (
                                        issues.map((issue) => {
                                            let statusText = issue.status;
                                            if (issue.status === 'open') statusText = 'Open';
                                            if (issue.status === 'in_behandeling') statusText = 'In behandeling';
                                            if (issue.status === 'opgelost') statusText = 'Afgehandeld';
                                            if (issue.status === 'gesloten') statusText = 'Gesloten';

                                            const timeStr = issue.created_at ? new Date(issue.created_at).toLocaleTimeString([], {
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            }) : '';

                                            return (
                                                <ReportCard
                                                    key={issue.id}
                                                    title={issue.title || "Onbekend"}
                                                    status={statusText}
                                                    priority={issue.priority === 1 ? "red" : "green"}
                                                    description={issue.content || ""}
                                                    location={issue.address || issue.district?.name || "Locatie onbekend"}
                                                    time={timeStr}
                                                    reporter={issue.author?.display_name || "Anoniem"}
                                                    tags={issue.category?.name ? [issue.category.name] : []}
                                                    onClick={() => handleSelectIssue(issue)}
                                                />
                                            );
                                        })
                                    )}
                                </div>
                            </div>
                        </aside>

                    </div>

                    {selectedIssue && (
                        <div
                            className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 backdrop-blur-sm">
                            <div
                                className="bg-primary-bg-cards border border-primary-border rounded-t-2xl sm:rounded-2xl max-w-2xl w-full shadow-xl flex flex-col overflow-hidden h-[85vh] sm:h-auto"
                                style={{maxHeight: '90vh'}}>
                                <div className="p-5 md:p-6 border-b border-primary-border">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h2 className="text-xl md:text-2xl font-bold font-headline text-primary-text wrap-break-word line-clamp-2">{selectedIssue.title}</h2>
                                            <p className="text-secondary-text text-xs md:text-sm mt-1">{selectedIssue.address || selectedIssue.district?.name} •
                                                Gemeld door {selectedIssue.author?.display_name || "Anoniem"}</p>
                                        </div>
                                        <button onClick={() => setSelectedIssue(null)}
                                                className="text-secondary-text hover:text-primary-text p-1.5 rounded-lg transition-colors shrink-0 ml-2">
                                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                                 stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div
                                        className="flex gap-4 overflow-x-auto no-scrollbar border-b border-transparent">
                                        <button
                                            onClick={() => setActiveTab('details')}
                                            className={`font-semibold pb-2 border-b-2 text-sm transition-colors whitespace-nowrap ${activeTab === 'details' ? 'border-primary-accent text-primary-text' : 'border-transparent text-secondary-text hover:text-primary-text'}`}
                                        >
                                            Details
                                        </button>
                                        <button
                                            onClick={() => setActiveTab('updates')}
                                            className={`font-semibold pb-2 border-b-2 text-sm transition-colors whitespace-nowrap ${activeTab === 'updates' ? 'border-primary-accent text-primary-text' : 'border-transparent text-secondary-text hover:text-primary-text'}`}
                                        >
                                            Updates ({officerUpdates.length})
                                        </button>
                                        <button
                                            onClick={() => setActiveTab('resolution')}
                                            className={`font-semibold pb-2 border-b-2 text-sm transition-colors whitespace-nowrap ${activeTab === 'resolution' ? 'border-primary-accent text-primary-text' : 'border-transparent text-secondary-text hover:text-primary-text'}`}
                                        >
                                            Resolutie
                                        </button>
                                        <button
                                            onClick={() => setActiveTab('communicatie')}
                                            className={`font-semibold pb-2 border-b-2 text-sm transition-colors whitespace-nowrap ${activeTab === 'communicatie' ? 'border-primary-accent text-primary-text' : 'border-transparent text-secondary-text hover:text-primary-text'}`}
                                        >
                                            Communicatie
                                        </button>
                                    </div>
                                </div>

                                <div className="p-5 md:p-6 overflow-y-auto flex-1 bg-primary-bg custom-scrollbar">
                                    {activeTab === 'details' && (
                                        <div className="space-y-4">
                                            <div>
                                                <h4 className="font-bold font-headline text-primary-text mb-1.5 uppercase text-xs tracking-wider">Beschrijving</h4>
                                                <p className="text-primary-text font-body text-sm whitespace-pre-line bg-primary-bg-cards p-4 rounded-xl border border-primary-border shadow-inner">{selectedIssue.content}</p>
                                            </div>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div
                                                    className="bg-primary-bg-cards p-4 rounded-xl border border-primary-border">
                                                    <span
                                                        className="text-xs font-bold text-secondary-text uppercase tracking-wide">Status</span>
                                                    <p className="text-primary-text font-semibold mt-0.5 text-sm">{selectedIssue.status}</p>
                                                </div>
                                                <div
                                                    className="bg-primary-bg-cards p-4 rounded-xl border border-primary-border">
                                                    <span
                                                        className="text-xs font-bold text-secondary-text uppercase tracking-wide">Categorie</span>
                                                    <p className="text-primary-text font-semibold mt-0.5 text-sm">{selectedIssue.category?.name || "Geen"}</p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {activeTab === 'updates' && (
                                        <div className="space-y-6">
                                            {officerUpdates.length > 0 ? (
                                                <div className="space-y-4">
                                                    {officerUpdates.map(update => (
                                                        <div key={update.id}
                                                             className="bg-primary-bg-cards p-4 rounded-xl border border-primary-border shadow-sm">
                                                            <div
                                                                className="flex justify-between items-center mb-2 text-xs gap-2">
                                                                <span
                                                                    className="font-bold text-primary-text truncate">{update.officer?.username || "Handhaver"}</span>
                                                                <span
                                                                    className="text-secondary-text shrink-0">{new Date(update.created_at).toLocaleString()}</span>
                                                            </div>
                                                            {update.title &&
                                                                <h5 className="font-bold font-headline text-primary-text mb-1 text-sm">{update.title}</h5>}
                                                            <p className="text-primary-text font-body text-sm">{update.content}</p>
                                                            {update.attachments && update.attachments.length > 0 && (
                                                                <div className="mt-3 flex gap-2 flex-wrap">
                                                                    {update.attachments.map(att => (
                                                                        <div key={att.id}
                                                                             className="text-xs bg-primary-bg text-secondary-text px-2 py-1 rounded border border-primary-border flex items-center gap-1 max-w-full truncate">
                                                                            📎 <span
                                                                            className="truncate">{att.original_name}</span>
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <p className="text-secondary-text text-center py-4 font-body text-sm">Nog
                                                    geen updates geplaatst.</p>
                                            )}

                                            {selectedIssue.assigned_officer_id === officer?.id && selectedIssue.status === 'in_behandeling' && (
                                                <form onSubmit={handleSubmitUpdate}
                                                      className="bg-primary-bg-cards p-4 rounded-xl border border-primary-border shadow-sm mt-6">
                                                    <h4 className="font-bold font-headline text-primary-text mb-3 uppercase text-xs tracking-wider">Nieuwe
                                                        Update Plaatsen</h4>
                                                    <input
                                                        type="text"
                                                        className="w-full bg-primary-bg border border-primary-border rounded-lg p-3 text-sm mb-3 focus:outline-none focus:border-primary-accent font-semibold text-primary-text"
                                                        placeholder="Titel (bijv: Voortgang...)"
                                                        value={updateTitle}
                                                        onChange={e => setUpdateTitle(e.target.value)}
                                                        required
                                                    />
                                                    <textarea
                                                        className="w-full bg-primary-bg border border-primary-border rounded-lg p-3 text-sm mb-3 focus:outline-none focus:border-primary-accent text-primary-text font-body"
                                                        rows="3"
                                                        placeholder="Beschrijf de voortgang..."
                                                        value={updateText}
                                                        onChange={e => setUpdateText(e.target.value)}
                                                        required
                                                    ></textarea>
                                                    <div
                                                        className="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                                                        <input
                                                            type="file"
                                                            multiple
                                                            className="text-xs text-secondary-text file:mr-4 file:py-2 file:px-4 file:rounded-full file:border file:border-primary-border file:text-xs file:font-semibold file:bg-primary-bg file:text-primary-text hover:file:bg-primary-border cursor-pointer transition-colors max-w-full"
                                                            onChange={e => setUpdateFiles(e.target.files)}
                                                        />
                                                        <button
                                                            type="submit"
                                                            disabled={isSubmitting}
                                                            className="bg-secondary-accent text-white hover:opacity-90 font-bold py-2.5 px-4 rounded-lg transition-colors text-sm w-full sm:w-auto disabled:opacity-50"
                                                        >
                                                            {isSubmitting ? "Bezig..." : "Update Toevoegen"}
                                                        </button>
                                                    </div>
                                                </form>
                                            )}
                                        </div>
                                    )}

                                    {activeTab === 'resolution' && (
                                        <div className="space-y-6">
                                            {officerResolution ? (
                                                <div
                                                    className="bg-primary-bg-cards p-4 md:p-5 rounded-xl border border-primary-border shadow-sm">
                                                    <div
                                                        className="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 border-b border-primary-border pb-2 gap-1">
                                                        <h3 className="font-bold font-headline text-base text-primary-text break-words">{officerResolution.title}</h3>
                                                        <span
                                                            className="text-xs text-secondary-text shrink-0">{new Date(officerResolution.created_at).toLocaleString()}</span>
                                                    </div>
                                                    <p className="text-primary-text font-body text-sm whitespace-pre-line">{officerResolution.content}</p>
                                                    {officerResolution.attachments && officerResolution.attachments.length > 0 && (
                                                        <div
                                                            className="mt-4 pt-4 border-t border-primary-border flex gap-2 flex-wrap">
                                                            {officerResolution.attachments.map(att => (
                                                                <div key={att.id}
                                                                     className="text-sm bg-primary-bg text-primary-text px-3 py-1.5 rounded-lg border border-primary-border flex items-center gap-2 max-w-full truncate">
                                                                    📎 <span
                                                                    className="truncate">{att.original_name}</span>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            ) : selectedIssue.assigned_officer_id === officer?.id && selectedIssue.status === 'in_behandeling' ? (
                                                <form onSubmit={handleSubmitResolution}
                                                      className="bg-primary-bg-cards p-4 md:p-5 rounded-xl border border-primary-border shadow-sm">
                                                    <p className="text-xs text-secondary-text mb-4 font-body">Je kunt de
                                                        resolutie slechts één keer indienen. Zorg dat het rapport
                                                        compleet is.</p>
                                                    <input
                                                        type="text"
                                                        className="w-full bg-primary-bg border border-primary-border rounded-lg p-3 text-sm mb-3 focus:outline-none focus:border-primary-accent font-semibold text-primary-text"
                                                        placeholder="Titel (bijv: Probleem Opgelost)"
                                                        value={resolutionTitle}
                                                        onChange={e => setResolutionTitle(e.target.value)}
                                                        required
                                                    />
                                                    <textarea
                                                        className="w-full bg-primary-bg border border-primary-border rounded-lg p-3 text-sm mb-3 focus:outline-none focus:border-primary-accent text-primary-text font-body"
                                                        rows="5"
                                                        placeholder="Uitgebreide resolutie of bevindingen..."
                                                        value={resolutionText}
                                                        onChange={e => setResolutionText(e.target.value)}
                                                        required
                                                    ></textarea>
                                                    <div
                                                        className="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 mt-2">
                                                        <input
                                                            type="file"
                                                            multiple
                                                            className="text-xs text-secondary-text file:mr-4 file:py-2 file:px-4 file:rounded-full file:border file:border-primary-border file:text-xs file:font-semibold file:bg-primary-bg file:text-primary-text hover:file:bg-primary-border cursor-pointer transition-colors max-w-full"
                                                            onChange={e => setResolutionFiles(e.target.files)}
                                                        />
                                                        <button
                                                            type="submit"
                                                            disabled={isSubmitting}
                                                            className="bg-secondary-accent text-white hover:opacity-90 font-bold py-2.5 px-6 rounded-lg transition-colors w-full sm:w-auto disabled:opacity-50"
                                                        >
                                                            {isSubmitting ? "Bezig..." : "Resolutie Indienen"}
                                                        </button>
                                                    </div>
                                                </form>
                                            ) : (
                                                <div
                                                    className="bg-primary-bg-cards p-8 rounded-xl border border-primary-border text-center text-secondary-text flex flex-col items-center justify-center gap-2">
                                                    <svg className="w-12 h-12 text-secondary-text opacity-50"
                                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round"
                                                              strokeWidth={1}
                                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    <p className="font-body text-sm">Nog geen resolutie beschikbaar.</p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {activeTab === 'communicatie' && (
                                        <div className="space-y-6">
                                            <h4 className="font-bold font-headline text-primary-text mb-3 uppercase text-xs tracking-wider">Gesprek Starten</h4>
                                            {selectedIssue.assigned_officer_id !== officer?.id ? (
                                                <div className="bg-orange-50 p-4 rounded-xl border border-orange-200 text-orange-800 text-sm">
                                                    Je kunt alleen een gesprek starten voor een melding die aan jou is toegewezen. Neem deze taak eerst aan.
                                                </div>
                                            ) : (
                                                <>
                                                    <p className="text-sm text-secondary-text mb-4 font-body">
                                                        Kies een deelnemer uit de onderstaande lijst om een 1-op-1 gesprek te openen in de chat interface.
                                                    </p>
                                                    {loadingParticipants ? (
                                                        <p className="text-sm text-secondary-text font-body">Deelnemers laden...</p>
                                                    ) : issueParticipants.length === 0 ? (
                                                        <p className="text-sm text-secondary-text font-body">Geen deelnemers gevonden voor deze melding.</p>
                                                    ) : (
                                                        <div className="divide-y divide-primary-border border border-primary-border rounded-xl overflow-hidden bg-primary-bg-cards shadow-sm">
                                                            {issueParticipants.map(p => (
                                                                <div key={p.id} className="p-4 flex items-center justify-between hover:bg-primary-bg transition-colors">
                                                                    <div>
                                                                        <p className="text-sm font-bold text-primary-text font-headline">
                                                                            {p.user?.username || p.user?.display_name || `Deelnemer #${p.id}`}
                                                                        </p>
                                                                        {p.is_anonymous && <p className="text-xs text-secondary-text mt-0.5">Anoniem</p>}
                                                                    </div>
                                                                    <button 
                                                                        onClick={async () => {
                                                                            try {
                                                                                const payload = p.user?.id ? { user_id: p.user.id } : { participant_id: p.id };
                                                                                await openChat(selectedIssue.id, payload);
                                                                                navigate('/handhaverchat', { state: { selectedIssueId: selectedIssue.id } });
                                                                            } catch (error) {
                                                                                console.error('Failed to open chat:', error);
                                                                                alert('Kon gesprek niet starten.');
                                                                            }
                                                                        }}
                                                                        className="bg-primary-accent hover:opacity-90 text-white text-xs font-bold py-2 px-4 rounded-lg transition-colors"
                                                                    >
                                                                        Start Gesprek
                                                                    </button>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </>
                                            )}
                                        </div>
                                    )}
                                </div>

                                <div
                                    className="p-4 border-t border-primary-border bg-primary-bg-cards flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 items-stretch sm:items-center pb-8 sm:pb-4">
                                    {selectedIssue.assigned_officer_id !== officer?.id ? (
                                        <button
                                            onClick={() => handleAssignSelf(selectedIssue.id)}
                                            className="bg-primary-accent text-white font-bold py-2.5 px-6 rounded-lg hover:opacity-90 transition-colors text-sm text-center"
                                        >
                                            Zelf toewijzen
                                        </button>
                                    ) : (
                                        <>
                                            <button
                                                onClick={() => handleUnassignSelf(selectedIssue.id)}
                                                className="text-secondary-text hover:text-primary-text font-semibold py-2.5 px-4 transition-colors text-sm text-center order-3 sm:order-1"
                                            >
                                                Taak teruggeven
                                            </button>
                                            {selectedIssue.status === 'in_behandeling' && (
                                                <button
                                                    onClick={() => handleChangeStatus(selectedIssue.id, 'opgelost')}
                                                    disabled={!officerResolution}
                                                    title={!officerResolution ? "Voeg eerst een resolutie toe" : ""}
                                                    className={`font-bold py-2.5 px-6 rounded-lg transition-colors text-sm text-center order-2 ${!officerResolution ? 'bg-primary-border text-secondary-text opacity-40 cursor-not-allowed' : 'bg-secondary-accent text-white hover:opacity-90'}`}
                                                >
                                                    Markeer als Opgelost
                                                </button>
                                            )}
                                            {selectedIssue.status === 'opgelost' && (
                                                <button
                                                    onClick={() => handleChangeStatus(selectedIssue.id, 'gesloten')}
                                                    className="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-lg transition-colors text-sm text-center order-2"
                                                >
                                                    Markeer als Gesloten
                                                </button>
                                            )}
                                            {selectedIssue.status === 'gesloten' && (
                                                <span
                                                    className="text-secondary-text font-bold px-4 text-sm text-center py-2 order-2">Issue is gesloten</span>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}