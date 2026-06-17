import "../App.css";
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ReportCard from "../components/H_SignalCard.jsx";
import HM_Nav from "../components/HM_Nav.jsx";
import IncidentMap from "../components/IncidentMap.jsx";
import "./Handhaver_styling.css"
import "../components/MapComponent.jsx"
import NativeLeafletMap from "../components/MapComponent.jsx";

function CommandCenter() {
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
    const [activeTab, setActiveTab] = useState('details'); // 'details', 'updates', 'resolution'
    const [officerUpdates, setOfficerUpdates] = useState([]);
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
                        headers: { 'Authorization': `Bearer ${token}` }
                    }),
                    axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me`, {
                        headers: { 'Authorization': `Bearer ${token}` }
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
                headers: { 'Authorization': `Bearer ${token}` }
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
                axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${issueId}/officer-updates`, { headers: { 'Authorization': `Bearer ${token}` } }),
                axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${issueId}/officer-resolution`, { headers: { 'Authorization': `Bearer ${token}` } }).catch(() => ({ data: null }))
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

    const handleAssignSelf = async (issueId) => {
        try {
            const token = localStorage.getItem('auth_token');
            await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/issues/${issueId}/assign-self`, {}, {
                headers: { 'Authorization': `Bearer ${token}` }
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
                headers: { 'Authorization': `Bearer ${token}` }
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
                headers: { 'Authorization': `Bearer ${token}` }
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
        <div className="app-layout">
            <HM_Nav></HM_Nav>
            <main className="main-content dashboard-container relative">

                <section className="metrics-row">
                    <div className="metric-card">
                        <div className="metric-info">
                            <span className="metric-title">ACTIEVE WIJKUITDAGINGEN</span>
                            <span className="metric-value">{activeIssuesCount}</span>
                        </div>
                        <div className="metric-icon warning">⚠️</div>
                    </div>

                    <div className="metric-card" style={{ borderColor: '#fecaca' }}>
                        <div className="metric-info">
                            <span className="metric-title" style={{ color: '#dc2626' }}>URGENTE MELDINGEN</span>
                            <span className="metric-value" style={{ color: '#dc2626' }}>{urgentIssuesCount}</span>
                        </div>
                        <div className="metric-icon" style={{ backgroundColor: '#fef2f2', color: '#dc2626' }}>🚨</div>
                    </div>
                </section>

                <section className="filter-bar">
                    <div className="search-wrapper">
                        <span className="search-icon">🔍</span>
                        <input 
                            type="text" 
                            placeholder="Zoek meldingen..." 
                            className="search-input" 
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                        />
                    </div>
                    <select className="filter-select" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
                        <option value="">Alle Statussen</option>
                        <option value="open">Open</option>
                        <option value="in_behandeling">In behandeling</option>
                        <option value="opgelost">Afgehandeld</option>
                        <option value="gesloten">Gesloten</option>
                    </select>
                    <select className="filter-select" value={categoryFilter} onChange={(e) => setCategoryFilter(e.target.value)}>
                        <option value="">Alle Categorieën</option>
                        {categories.map(c => (
                            <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                    </select>
                </section>

                <div className="dashboard-workspace">

                    {/* Map Component */}
                    <div className="map-panel rounded-2xl overflow-hidden shadow-sm relative z-0">
                        <IncidentMap 
                            issues={issues} 
                            onSelectIssue={handleSelectIssue} 
                        />
                    </div>

                    <aside className="incident-panel relative">
                        <h3 className="queue-title">Incidentenwachtrij <span className="queue-count">({issues.length})</span></h3>
                        <div className="incident-queue-list">
                                <div className="grid gap-4">
                                    {loading ? (
                                        <p className="text-stone-500">Actuele meldingen laden...</p>
                                    ) : issues.length === 0 ? (
                                        <p className="text-stone-500">Geen meldingen gevonden.</p>
                                    ) : (
                                        issues.map((issue) => {
                                            let statusText = issue.status;
                                            if (issue.status === 'open') statusText = 'Open';
                                            if (issue.status === 'in_behandeling') statusText = 'In behandeling';
                                            if (issue.status === 'opgelost') statusText = 'Afgehandeld';
                                            if (issue.status === 'gesloten') statusText = 'Gesloten';

                                            const timeStr = issue.created_at ? new Date(issue.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
                                            
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
                    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                        <div className="bg-white rounded-2xl max-w-2xl w-full shadow-xl flex flex-col" style={{ maxHeight: '90vh' }}>
                            <div className="p-6 border-b border-stone-200">
                                <div className="flex justify-between items-start mb-4">
                                    <div>
                                        <h2 className="text-2xl font-bold text-stone-900">{selectedIssue.title}</h2>
                                        <p className="text-stone-500 text-sm">{selectedIssue.address || selectedIssue.district?.name} • Gemeld door {selectedIssue.author?.display_name || "Anoniem"}</p>
                                    </div>
                                    <button onClick={() => setSelectedIssue(null)} className="text-stone-400 hover:text-stone-600">
                                        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div className="flex gap-4">
                                    <button 
                                        onClick={() => setActiveTab('details')}
                                        className={`font-semibold pb-2 border-b-2 transition-colors ${activeTab === 'details' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-stone-500 hover:text-stone-700'}`}
                                    >
                                        Details
                                    </button>
                                    <button 
                                        onClick={() => setActiveTab('updates')}
                                        className={`font-semibold pb-2 border-b-2 transition-colors ${activeTab === 'updates' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-stone-500 hover:text-stone-700'}`}
                                    >
                                        Updates ({officerUpdates.length})
                                    </button>
                                    <button 
                                        onClick={() => setActiveTab('resolution')}
                                        className={`font-semibold pb-2 border-b-2 transition-colors ${activeTab === 'resolution' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-stone-500 hover:text-stone-700'}`}
                                    >
                                        Resolutie
                                    </button>
                                </div>
                            </div>
                            
                            <div className="p-6 overflow-y-auto flex-1 bg-stone-50">
                                {activeTab === 'details' && (
                                    <div className="space-y-4">
                                        <div>
                                            <h4 className="font-bold text-stone-900 mb-1">Beschrijving</h4>
                                            <p className="text-stone-700 whitespace-pre-line bg-white p-4 rounded-xl border border-stone-200">{selectedIssue.content}</p>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="bg-white p-4 rounded-xl border border-stone-200">
                                                <span className="text-xs font-bold text-stone-400 uppercase">Status</span>
                                                <p className="text-stone-900 font-semibold">{selectedIssue.status}</p>
                                            </div>
                                            <div className="bg-white p-4 rounded-xl border border-stone-200">
                                                <span className="text-xs font-bold text-stone-400 uppercase">Categorie</span>
                                                <p className="text-stone-900 font-semibold">{selectedIssue.category?.name || "Geen"}</p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {activeTab === 'updates' && (
                                    <div className="space-y-6">
                                        {officerUpdates.length > 0 ? (
                                            <div className="space-y-4">
                                                {officerUpdates.map(update => (
                                                    <div key={update.id} className="bg-white p-4 rounded-xl border border-stone-200 shadow-sm">
                                                        <div className="flex justify-between items-center mb-2">
                                                            <span className="font-bold text-stone-900">{update.officer?.username || "Handhaver"}</span>
                                                            <span className="text-xs text-stone-500">{new Date(update.created_at).toLocaleString()}</span>
                                                        </div>
                                                        {update.title && <h5 className="font-bold text-stone-800 mb-1">{update.title}</h5>}
                                                        <p className="text-stone-700">{update.content}</p>
                                                        {update.attachments && update.attachments.length > 0 && (
                                                            <div className="mt-3 flex gap-2 flex-wrap">
                                                                {update.attachments.map(att => (
                                                                    <div key={att.id} className="text-xs bg-stone-100 text-stone-600 px-2 py-1 rounded border border-stone-200 flex items-center gap-1">
                                                                        📎 {att.original_name}
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <p className="text-stone-500 text-center py-4">Nog geen updates geplaatst.</p>
                                        )}

                                        {selectedIssue.assigned_officer_id === officer?.id && selectedIssue.status === 'in_behandeling' && (
                                            <form onSubmit={handleSubmitUpdate} className="bg-white p-4 rounded-xl border border-emerald-100 shadow-sm mt-6">
                                                <h4 className="font-bold text-stone-900 mb-3">Nieuwe Update Plaatsen</h4>
                                                <input 
                                                    type="text" 
                                                    className="w-full border border-stone-200 rounded-lg p-3 text-sm mb-3 focus:outline-none focus:border-emerald-500 font-semibold text-[var(--text-dark)]"
                                                    placeholder="Titel (bijv: Voortgang...)"
                                                    value={updateTitle}
                                                    onChange={e => setUpdateTitle(e.target.value)}
                                                    required
                                                />
                                                <textarea 
                                                    className="w-full border border-stone-200 rounded-lg p-3 text-sm mb-3 focus:outline-none focus:border-emerald-500 text-[var(--text-dark)]"
                                                    rows="3"
                                                    placeholder="Beschrijf de voortgang..."
                                                    value={updateText}
                                                    onChange={e => setUpdateText(e.target.value)}
                                                    required
                                                ></textarea>
                                                <div className="flex justify-between items-center">
                                                    <input 
                                                        type="file" 
                                                        multiple 
                                                        className="text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200 cursor-pointer"
                                                        onChange={e => setUpdateFiles(e.target.files)}
                                                    />
                                                    <button 
                                                        type="submit" 
                                                        disabled={isSubmitting}
                                                        className="bg-emerald-100 text-emerald-800 hover:bg-emerald-200 font-bold py-2 px-4 rounded-lg transition-colors text-sm"
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
                                            <div className="bg-white p-5 rounded-xl border border-emerald-200 shadow-sm">
                                                <div className="flex justify-between items-center mb-4">
                                                    <h3 className="font-bold text-lg text-emerald-900">{officerResolution.title}</h3>
                                                    <span className="text-xs text-stone-500">{new Date(officerResolution.created_at).toLocaleString()}</span>
                                                </div>
                                                <p className="text-stone-700 whitespace-pre-line">{officerResolution.content}</p>
                                                {officerResolution.attachments && officerResolution.attachments.length > 0 && (
                                                    <div className="mt-4 pt-4 border-t border-stone-100 flex gap-2 flex-wrap">
                                                        {officerResolution.attachments.map(att => (
                                                            <div key={att.id} className="text-sm bg-stone-100 text-stone-700 px-3 py-1.5 rounded-lg border border-stone-200 flex items-center gap-2">
                                                                📎 {att.original_name}
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        ) : selectedIssue.assigned_officer_id === officer?.id && selectedIssue.status === 'in_behandeling' ? (
                                            <form onSubmit={handleSubmitResolution} className="bg-white p-5 rounded-xl border border-stone-200 shadow-sm">
                                                <p className="text-sm text-stone-500 mb-4">Je kunt de resolutie slechts één keer indienen. Zorg dat het rapport compleet is.</p>
                                                <input 
                                                    type="text" 
                                                    className="w-full border border-stone-200 rounded-lg p-3 text-sm mb-3 focus:outline-none focus:border-emerald-500 font-semibold text-[var(--text-dark)]"
                                                    placeholder="Titel (bijv: Probleem Opgelost)"
                                                    value={resolutionTitle}
                                                    onChange={e => setResolutionTitle(e.target.value)}
                                                    required
                                                />
                                                <textarea 
                                                    className="w-full border border-stone-200 rounded-lg p-3 text-sm mb-3 focus:outline-none focus:border-emerald-500 text-[var(--text-dark)]"
                                                    rows="5"
                                                    placeholder="Uitgebreide resolutie of bevindingen..."
                                                    value={resolutionText}
                                                    onChange={e => setResolutionText(e.target.value)}
                                                    required
                                                ></textarea>
                                                <div className="flex justify-between items-center mt-2">
                                                    <input 
                                                        type="file" 
                                                        multiple 
                                                        className="text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-stone-100 file:text-stone-700 hover:file:bg-stone-200 cursor-pointer"
                                                        onChange={e => setResolutionFiles(e.target.files)}
                                                    />
                                                    <button 
                                                        type="submit" 
                                                        disabled={isSubmitting}
                                                        className="bg-emerald-600 text-white hover:bg-emerald-700 font-bold py-2 px-6 rounded-lg transition-colors"
                                                    >
                                                        {isSubmitting ? "Bezig..." : "Resolutie Indienen"}
                                                    </button>
                                                </div>
                                            </form>
                                        ) : (
                                            <div className="bg-white p-8 rounded-xl border border-stone-200 text-center text-stone-500">
                                                <svg className="w-12 h-12 mx-auto mb-3 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p>Nog geen resolutie beschikbaar.</p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                            
                            <div className="p-4 border-t border-stone-200 bg-white rounded-b-2xl flex justify-end gap-3 items-center">
                                {selectedIssue.assigned_officer_id !== officer?.id ? (
                                    <button 
                                        onClick={() => handleAssignSelf(selectedIssue.id)}
                                        className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded-lg transition-colors"
                                    >
                                        Zelf toewijzen
                                    </button>
                                ) : (
                                    <>
                                        <button 
                                            onClick={() => handleUnassignSelf(selectedIssue.id)}
                                            className="text-stone-500 hover:text-stone-800 font-semibold py-2 px-4 transition-colors"
                                        >
                                            Taak teruggeven
                                        </button>
                                        {selectedIssue.status === 'in_behandeling' && (
                                            <button 
                                                onClick={() => handleChangeStatus(selectedIssue.id, 'opgelost')}
                                                disabled={!officerResolution}
                                                title={!officerResolution ? "Voeg eerst een resolutie toe" : ""}
                                                className={`font-bold py-2 px-6 rounded-lg transition-colors ${!officerResolution ? 'bg-stone-300 text-stone-500 cursor-not-allowed' : 'bg-stone-800 hover:bg-stone-900 text-white'}`}
                                            >
                                                Markeer als Opgelost
                                            </button>
                                        )}
                                        {selectedIssue.status === 'opgelost' && (
                                            <button 
                                                onClick={() => handleChangeStatus(selectedIssue.id, 'gesloten')}
                                                className="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors"
                                            >
                                                Markeer als Gesloten
                                            </button>
                                        )}
                                        {selectedIssue.status === 'gesloten' && (
                                            <span className="text-stone-400 font-bold px-4">Issue is gesloten</span>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </main>
        </div>
    );
}

export default CommandCenter;