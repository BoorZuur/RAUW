import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { MapPin, Settings, User, Loader2 } from 'lucide-react';
import U_Nav from '../components/U_Nav';
import axios from 'axios';
import Footer from "../components/Footer.jsx";

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

    useEffect(() => {
        const fetchData = async () => {
            setLoading(true);
            const token = localStorage.getItem('auth_token');

            if (!token) {
                setError('Je bent niet ingelogd.');
                setLoading(false);
                return;
            }

            const config = {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
            };

            try {
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

    const filteredReports = reports.filter(r => r.status === statusMap[activeTab]);

    const getGreeting = () => {
        const hour = new Date().getHours();
        return hour < 12 ? "Goedemorgen" : hour < 18 ? "Fijne middag" : "Goedenavond";
    };

    if (loading) return <div className="flex h-screen items-center justify-center bg-primary-bg"><Loader2 className="w-8 h-8 animate-spin text-primary-accent" /></div>;
    if (error) return <div className="text-center p-10 text-red-500 bg-primary-bg h-screen">{error}</div>;

    return (
        <div className="min-h-screen bg-primary-bg text-primary-text transition-colors duration-300">
            {/* Pop-up voor details */}
            {selectedReport && (
                <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-primary-border shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                        <h2 className="text-2xl font-black uppercase mb-2 text-primary-text">{selectedReport.title}</h2>
                        <p className="text-sm text-secondary-text mb-6">Status: <span className="font-bold text-primary-accent">{selectedReport.status}</span></p>

                        <div className="space-y-4">
                            <div>
                                <h4 className="font-bold text-xs uppercase text-secondary-text mb-1">Omschrijving</h4>
                                <p className="text-sm text-primary-text">{selectedReport.content || 'Geen omschrijving meegegeven.'}</p>
                            </div>

                            {/* bg-secondary-bg is hier vervangen door bg-primary-bg voor het juiste contrast */}
                            <div className="p-4 bg-primary-bg border border-primary-border rounded-xl mt-4">
                                <h4 className="font-bold text-sm mb-2 text-primary-text">Reacties</h4>
                                <p className="text-xs text-secondary-text">Nog geen updates vanuit de handhaving.</p>
                            </div>
                        </div>

                        <button
                            onClick={() => setSelectedReport(null)}
                            className="w-full mt-8 p-3 border-2 border-primary-border rounded-xl font-bold uppercase hover:bg-primary-bg text-primary-text transition-colors"
                        >
                            Sluiten
                        </button>
                    </div>
                </div>
            )}

            <div className="fixed top-0 w-full z-50"><U_Nav/></div>

            <main className="pt-32 p-8 max-w-4xl mx-auto space-y-8">
                {/* Profile card: bg-secondary-bg vervangen door bg-primary-bg bij de icoon container */}
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

                    <div className="space-y-4">
                        {filteredReports.length > 0 ? (
                            filteredReports.map(report => (
                                <div
                                    key={report.id}
                                    onClick={() => setSelectedReport(report)}
                                    className="p-6 bg-primary-bg-cards rounded-xl border border-primary-border flex gap-5 cursor-pointer hover:border-primary-accent shadow-sm transition-all"
                                >
                                    {/* Icon container omgezet naar bg-primary-bg */}
                                    <div className="p-2 bg-primary-bg border border-primary-border rounded-lg flex items-center justify-center"><MapPin className="w-5 h-5 text-primary-accent"/></div>
                                    <div>
                                        <h3 className="font-bold text-primary-text text-lg">{report.title}</h3>
                                        <p className="text-sm text-secondary-text">{report.address || 'Geen adres opgegeven'}</p>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="p-10 text-center border-2 border-dashed border-primary-border bg-primary-bg-cards rounded-xl">
                                <p className="text-secondary-text">Geen meldingen gevonden in deze categorie.</p>
                            </div>
                        )}
                    </div>
                </section>
            </main>

            <Footer/>
        </div>
    );
}