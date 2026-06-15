import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Settings, User, Loader2 } from 'lucide-react';
import U_Nav from '../components/U_Nav';
import axios from 'axios';
import Footer from "../components/Footer.jsx";
import USignalCard from '../components/U_SignalCard';
import AccountSignalDetailModal from '../components/AccountSignalDetailModal.jsx';

export default function Dashboard() {
    const navigate = useNavigate();
    const [activeTab, setActiveTab] = useState('nieuw');
    const [user, setUser] = useState(null);
    const [reports, setReports] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedReport, setSelectedReport] = useState(null);

    const statusMap = {
        'nieuw': 'open',
        'in behandeling': 'in_behandeling',
        'opgelost': 'opgelost',
        'afgehandeld': 'gesloten',
    };

    const tabs = ['nieuw', 'in behandeling', 'opgelost', 'afgehandeld', 'verhalen die u volgt'];

    const getAuthConfig = () => {
        const token = localStorage.getItem('auth_token');
        return {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        };
    };

    useEffect(() => {
        const fetchData = async () => {
            setLoading(true);
            const token = localStorage.getItem('auth_token');

            if (!token) {
                setError('Je bent niet ingelogd.');
                setLoading(false);
                return;
            }

            try {
                const config = getAuthConfig();
                const userRes = await axios.get('http://localhost:8001/api/auth/me', config);
                setUser(userRes.data.profile);
                const issuesRes = await axios.get('http://localhost:8001/api/issues?mine=1', config);
                setReports(issuesRes.data.data || issuesRes.data);

            } catch (err) {
                console.error(err);
                setError('Kon gegevens niet ophalen.');
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    const handleAddComment = async (issueId, commentText) => {
        try {
            const config = getAuthConfig();
            const response = await axios.post(`http://localhost:8001/api/issues/${issueId}/comments`, {
                body: commentText
            }, config);

            const newComment = response.data.data || response.data;

            setReports(prevReports => prevReports.map(report => {
                if (report.id === issueId) {
                    return { ...report, comments: [...(report.comments || []), newComment] };
                }
                return report;
            }));

            setSelectedReport(prev => ({
                ...prev,
                comments: [...(prev.comments || []), newComment]
            }));

        } catch (err) {
            console.error('Kon reactie niet plaatsen:', err);
        }
    };

    const filteredReports = reports.filter(r => {
        const targetStatus = statusMap[activeTab];
        if (!targetStatus) return false;
        return r.status?.toLowerCase() === targetStatus.toLowerCase() ||
            r.status?.toLowerCase() === activeTab.toLowerCase();
    });

    const getGreeting = () => {
        const hour = new Date().getHours();
        return hour < 12 ? "Goedemorgen" : hour < 18 ? "Fijne middag" : "Goedenavond";
    };

    if (loading) return <div className="flex h-screen items-center justify-center bg-primary-bg"><Loader2 className="w-8 h-8 animate-spin text-primary-accent" /></div>;
    if (error) return <div className="text-center p-10 text-red-500 bg-primary-bg h-screen">{error}</div>;

    return (
        <div className="min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300">

            <div className="fixed top-0 w-full z-50"><U_Nav/></div>

            <main className="z-10 flex-grow w-full max-w-4xl mx-auto px-6 pt-32 space-y-8 pb-12">

                {/* Profile card */}
                <section className="flex items-center justify-between p-4 bg-primary-bg-cards border border-primary-border rounded-2xl shadow-sm">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-primary-bg border border-primary-border rounded-full">
                            <User className="w-5 h-5 text-primary-text"/>
                        </div>
                        <span className="font-bold text-primary-text">{user?.username}</span>
                    </div>
                    <button
                        onClick={() => navigate('/instellingen')}
                        className="p-2.5 rounded-xl border border-primary-border bg-primary-bg hover:border-primary-accent text-primary-text transition-all cursor-pointer"
                        aria-label="Instellingen"
                    >
                        <Settings className="w-5 h-5" />
                    </button>
                </section>

                <section className="bg-primary-bg-cards p-8 rounded-2xl border border-primary-border shadow-sm">
                    <h1 className="text-4xl font-extrabold text-primary-text mb-4">
                        {getGreeting()}, {user?.username}!
                    </h1>
                    <p className="text-secondary-text">Hopelijk bent u weer klaar om de straat op te gaan!</p>
                </section>

                <section>
                    <nav className="flex flex-wrap justify-center gap-3 mb-8">
                        {tabs.map((tab) => (
                            <button key={tab} onClick={() => setActiveTab(tab)}
                                    className={`px-6 py-3 rounded-full text-sm font-bold capitalize transition-all border-2 cursor-pointer
                                    ${activeTab === tab ? 'bg-primary-text text-primary-bg border-primary-text' : 'bg-primary-bg-cards text-primary-text border-primary-border hover:border-primary-accent'}`}>
                                {tab}
                            </button>
                        ))}
                    </nav>

                    <div className="space-y-3">
                        {filteredReports.length > 0 ? (
                            filteredReports.map(report => (
                                <USignalCard
                                    key={report.id}
                                    issue={report}
                                    onClick={() => setSelectedReport(report)}
                                />
                            ))
                        ) : (
                            <div className="p-10 text-center border border-dashed border-primary-border bg-primary-bg-cards rounded-xl">
                                <p className="text-secondary-text font-label text-sm font-medium">Geen meldingen gevonden in deze categorie.</p>
                            </div>
                        )}
                    </div>
                </section>
            </main>

            <Footer/>

            {/* Gecorrigeerd: Modal onderaan geplaatst, net als in Feed.jsx, buiten de main structuur */}
            {selectedReport && (
                <AccountSignalDetailModal
                    issue={{
                        ...selectedReport,
                        image_url: selectedReport.image_url || selectedReport.images?.[0]?.path
                    }}
                    onClose={() => setSelectedReport(null)}
                    onAddComment={handleAddComment}
                />
            )}
        </div>
    );
}