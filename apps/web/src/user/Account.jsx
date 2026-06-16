import React, { useEffect, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { Settings, User, Loader2, LogOut } from 'lucide-react';
import axios from 'axios';
import U_Nav from '../components/U_Nav';
import Footer from "../components/Footer.jsx";
import USignalCard from '../components/U_SignalCard';
import AccountDetailModal from '../modal/AccountDetailModal.jsx';
import { getMyIssues, getFollowedIssues, deleteIssue } from '../services/issueService';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { 'Accept': 'application/json' }
});

apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

const FOLLOWED_TAB = 'verhalen die u volgt';
const tabs = ['nieuw', 'in behandeling', 'opgelost', 'afgehandeld', FOLLOWED_TAB];

const statusMap = {
    'nieuw': 'open',
    'in behandeling': 'in_behandeling',
    'opgelost': 'opgelost',
    'afgehandeld': 'gesloten',
};

function normalizeIssue(issue) {
    return { ...issue, attachments: issue.attachments || [] };
}

export default function Dashboard() {
    const navigate = useNavigate();
    const location = useLocation();
    const [activeTab, setActiveTab] = useState(() => (
        tabs.includes(location.state?.tab) ? location.state.tab : 'nieuw'
    ));
    const [user, setUser] = useState(null);
    const [reports, setReports] = useState([]);
    const [followedReports, setFollowedReports] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedReport, setSelectedReport] = useState(null);

    const refetchFollowedReports = async () => {
        try {
            const followedData = await getFollowedIssues();
            setFollowedReports(followedData.map(normalizeIssue));
        } catch (err) {
            console.error('Kon gevolgde verhalen niet ophalen:', err);
        }
    };

    useEffect(() => {
        const fetchData = async () => {
            setLoading(true);
            try {
                const [userRes, mineData, followedData] = await Promise.all([
                    apiClient.get('/auth/me'),
                    getMyIssues(),
                    getFollowedIssues(),
                ]);
                setUser(userRes.data.profile);
                setReports(mineData.map(normalizeIssue));
                setFollowedReports(followedData.map(normalizeIssue));
            } catch (err) {
                console.error(err);
                setError('Kon gegevens niet ophalen.');
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    const handleSelectIssue = async (issue) => {
        try {
            const issueResponse = await apiClient.get(`/issues/${issue.id}`);
            const fullIssueData = issueResponse.data.data || issueResponse.data;
            let allComments = [];
            let nextPageUrl = `/issues/${issue.id}/comments`;
            try {
                while (nextPageUrl) {
                    const commentResponse = await apiClient.get(nextPageUrl);
                    const data = commentResponse.data.data || commentResponse.data;
                    allComments = [...allComments, ...data];
                    nextPageUrl = commentResponse.data.next_page_url;
                }
            } catch (commentErr) {
                console.warn("Kon (sommige) comments niet ophalen:", commentErr);
            }
            setSelectedReport({
                ...fullIssueData,
                comments: allComments
            });

        } catch (err) {
            console.error('Kon issue details niet ophalen:', err);
            setSelectedReport(issue);
        }
    };

    const handleAddComment = async (issueId, commentText, isAnonymous = false) => {
        try {
            const response = await apiClient.post(`/issues/${issueId}/comments`, {
                content: commentText,
                is_anonymous: isAnonymous,
            });
            const newComment = response.data.data || response.data;
            setSelectedReport(prev => ({
                ...prev,
                comments: [...(prev.comments || []), newComment]
            }));
            return newComment;
        } catch (err) {
            console.error('Kon reactie niet plaatsen:', err.response?.data || err);
            throw err;
        }
    };

    const filteredReports = activeTab === FOLLOWED_TAB
        ? followedReports
        : reports.filter((report) => {
            const targetStatus = statusMap[activeTab];
            if (!targetStatus) return false;
            return report.status?.toLowerCase() === targetStatus.toLowerCase()
                || report.status?.toLowerCase() === activeTab.toLowerCase();
        });

    const getGreeting = () => {
        const hour = new Date().getHours();
        return hour < 12 ? "Goedemorgen" : hour < 18 ? "Fijne middag" : "Goedenavond";
    };

    if (loading) return <div className="flex h-screen items-center justify-center bg-primary-bg"><Loader2 className="w-8 h-8 animate-spin text-primary-accent" /></div>;
    if (error) return <div className="text-center p-10 text-red-500 bg-primary-bg h-screen">{error}</div>;

    const handleLogout = () => {
        localStorage.removeItem('auth_token');
        navigate('/login');
    };

    const handleDeleteIssue = async (issueId, { leaveParticipation } = {}) => {
        try {
            await deleteIssue(issueId, { leaveParticipation });
            setReports(prev => prev.filter(r => r.id !== issueId));
            setSelectedReport(null);
            await refetchFollowedReports();
        } catch (err) {
            alert('Kon het issue niet verwijderen.');
        }
    };

    const handleUpdateIssue = async (issueId, updatedData) => {
        try {
            await apiClient.put(`/issues/${issueId}`, updatedData);
            setReports(prev => prev.map(r => r.id === issueId ? { ...r, ...updatedData } : r));
            alert("Succesvol bijgewerkt!");
        } catch (err) {
            console.error("Update mislukt:", err);
            alert("Kon het issue niet bijwerken.");
        }
    };

    const handleParticipationChange = (updatedIssue) => {
        setSelectedReport((prev) => (prev ? { ...prev, ...updatedIssue, comments: prev.comments } : updatedIssue));

        const patch = {
            participant_count: updatedIssue.participant_count,
            is_participant: updatedIssue.is_participant,
            default_comment_is_anonymous: updatedIssue.default_comment_is_anonymous,
            title: updatedIssue.title,
            content: updatedIssue.content,
        };

        setReports((prev) => prev.map((report) => (
            report.id === updatedIssue.id ? { ...report, ...patch } : report
        )));

        if (!updatedIssue.is_participant) {
            setFollowedReports((prev) => prev.filter((report) => report.id !== updatedIssue.id));
        } else {
            setFollowedReports((prev) => prev.map((report) => (
                report.id === updatedIssue.id ? { ...report, ...patch } : report
            )));
        }
    };

    return (
        <div className="min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300 overflow-x-hidden">
            <div className="fixed top-0 w-full z-50">
                <U_Nav/>
            </div>

            <div className="grow flex w-full pt-26">
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300"></div>
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><circle cx="12" cy="11" r="2" className="opacity-60" /></svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500"
                                 viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><polyline points="22 11.08 12 19 9 16" /><path d="M22 4L12 14.01l-3-3" className="opacity-40" /><circle cx="12" cy="12" r="10" strokeDasharray="3 3" className="opacity-50" /></svg>
                        </div>
                    </div>
                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300"></div>
                </div>

                <main className="z-10 grow w-full max-w-4xl mx-auto px-6 mt-8 space-y-8 pb-12">
                    <section className="flex items-center justify-between p-4 bg-primary-bg-cards border border-primary-border rounded-2xl shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="p-2 bg-primary-bg border border-primary-border rounded-full">
                                <User className="w-5 h-5 text-primary-text"/>
                            </div>
                            <span className="font-bold text-primary-text">{user?.username}</span>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => navigate('/instellingen')}
                                className="p-2.5 rounded-xl border border-primary-border bg-primary-bg hover:border-primary-accent text-primary-text transition-all cursor-pointer"
                            >
                                <Settings className="w-5 h-5" />
                            </button>

                            <button
                                onClick={handleLogout}
                                className="p-2.5 rounded-xl border border-primary-border bg-primary-bg hover:bg-red-500/10 hover:border-red-500/50 text-primary-text hover:text-red-500 transition-all cursor-pointer"
                                title="Uitloggen"
                            >
                                <LogOut className="w-5 h-5" />
                            </button>
                        </div>
                    </section>

                    <section className="bg-primary-bg-cards p-8 rounded-2xl border border-primary-border shadow-sm">
                        <h1 className="text-4xl font-extrabold text-primary-text mb-4">{getGreeting()}, {user?.username}!</h1>
                        <p className="text-secondary-text">Hopelijk bent u weer klaar om de straat op te gaan!</p>
                    </section>
                    <section>
                        <nav className="flex flex-wrap justify-center gap-3 mb-8">
                            {tabs.map((tab) => (
                                <button key={tab} onClick={() => setActiveTab(tab)} className={`px-6 py-3 rounded-full text-sm font-bold capitalize transition-all border-2 cursor-pointer ${activeTab === tab ? 'bg-primary-text text-primary-bg border-primary-text' : 'bg-primary-bg-cards text-primary-text border-primary-border hover:border-primary-accent'}`}>{tab}</button>
                            ))}
                        </nav>
                        <div className="space-y-3">
                            {activeTab === FOLLOWED_TAB && filteredReports.length === 0 ? (
                                <div className="py-12 px-6 bg-primary-bg-cards border border-primary-border rounded-2xl flex justify-center">
                                    <div className="max-w-md text-center">
                                        <p className="font-headline font-black text-lg text-primary-text mb-2">Je volgt nog geen verhalen</p>
                                        <p className="text-sm text-secondary-text">
                                            Volg verhalen via de feed met de knop <span className="font-bold text-primary-text">Volgen</span>, of koppel je melding aan een bestaand verhaal tijdens het melden.
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                filteredReports.map((report) => (
                                    <USignalCard
                                        key={report.id}
                                        issue={report}
                                        onClick={() => handleSelectIssue(report)}
                                        subtitle={report.is_duplicate_child ? 'Gekoppeld aan een verhaal' : undefined}
                                    />
                                ))
                            )}
                        </div>
                    </section>
                </main>

                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300 self-end"></div>
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300"><svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2"><path d="M4 20 L15 4 L18 5 L10 20" /><line x1="2" y1="20" x2="22" y2="20" /><line x1="14" y1="6" x2="20" y2="20" className="opacity-40" /><line x1="13" y1="9" x2="17" y2="20" className="opacity-40" /></svg></div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75"><svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2"><rect x="2" y="10" width="6" height="11" /><rect x="9" y="3" width="6" height="18" /><rect x="16" y="8" width="6" height="13" /></svg></div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150"><svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="8" y1="9" x2="16" y2="9" /><path d="M5 12a7 7 0 0 0 14 0" /><circle cx="12" cy="4" r="1" /></svg></div>
                    </div>
                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300 self-end"></div>
                </div>
            </div>
            <div className="z-10 bg-primary-bg"><Footer/></div>
            {selectedReport && (
                <AccountDetailModal
                    issue={selectedReport}
                    onClose={() => setSelectedReport(null)}
                    onAddComment={handleAddComment}
                    onDeleteIssue={handleDeleteIssue}
                    onUpdateIssue={handleUpdateIssue}
                    currentUserId={user?.id}
                    onParticipationChange={handleParticipationChange}
                />
            )}
        </div>
    );
}
