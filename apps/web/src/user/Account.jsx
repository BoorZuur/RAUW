import React, {useEffect, useState} from 'react';
import {useNavigate} from 'react-router-dom';
import {MapPin, Settings, User, Loader2} from 'lucide-react';
import {useTheme} from '../ThemeContext.jsx';
import U_Nav from '../components/U_Nav';
import axios from 'axios';

export default function Dashboard() {
    const navigate = useNavigate();
    const [activeTab, setActiveTab] = useState('nieuw');
    const [user, setUser] = useState(null);
    const [reports, setReports] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const {isDark} = useTheme();

    const tabs = ['nieuw', 'in behandeling', 'surveillance-route', 'afgehandeld', 'verhalen'];

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
                const response = await axios.get('http://localhost:8001/api/auth/me', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                setUser(response.data.profile);

            } catch (err) {
                setError('Kon gegevens niet ophalen.');
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    const filteredReports = Array.isArray(reports) ? reports.filter(r => r.status === activeTab) : [];

    const getGreeting = () => {
        const hour = new Date().getHours();
        return hour < 12 ? "Goedemorgen" : hour < 18 ? "Fijne middag" : "Goedenavond";
    };

    // Loading scherm
    if (loading) return <div className="flex h-screen items-center justify-center"><Loader2 className="w-8 h-8 animate-spin text-primary-accent" /></div>;

    // Error scherm
    if (error) return <div className="text-center p-10 text-red-500">{error}</div>;

    return (
        <div className="min-h-screen bg-primary-bg transition-colors duration-300">
            <div className="fixed top-0 w-full z-50">
                <U_Nav/>
            </div>

            <main className="pt-32 p-8 max-w-4xl mx-auto space-y-8">
                <section className="flex items-center justify-between p-4 bg-primary-bg-cards border border-primary-border rounded-2xl">
                    {/* Gebruikersinformatie */}
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-secondary-bg rounded-full">
                            <User className="w-5 h-5 text-primary-text"/>
                        </div>
                        <span className="font-bold text-primary-text">
                            {user?.username}
                        </span>
                    </div>

                    <button
                        onClick={() => navigate('/instellingen')}
                        className="p-2.5 rounded-xl border border-primary-border hover:bg-secondary-bg hover:border-accent text-primary-text transition-all"
                        aria-label="Instellingen"
                    >
                        <Settings className="w-5 h-5" />
                    </button>
                </section>

                {/* Welkomstsectie */}
                <section className="bg-primary-bg-cards p-8 rounded-2xl border border-primary-border shadow-sm">
                    <h1 className="text-4xl font-extrabold text-primary-text mb-4">
                        {getGreeting()}, {user?.username}!
                    </h1>
                    <p className="text-lg text-secondary-text font-medium">
                        Welkom terug op de straat.
                    </p>
                </section>

                {/* Navigatie & Content */}
                <section>
                    <nav className="flex flex-wrap justify-center gap-3 mb-8">
                        {tabs.map((tab) => (
                            <button key={tab} onClick={() => setActiveTab(tab)}
                                    className={`px-6 py-3 rounded-full text-sm font-bold capitalize transition-all border-2 
                                    ${activeTab === tab ? 'bg-primary-text text-primary-bg border-primary-text' : 'bg-primary-bg-cards text-primary-text border-primary-border'}`}>
                                {tab}
                            </button>
                        ))}
                    </nav>

                    <div className="space-y-4">
                        {filteredReports.length > 0 ? (
                            filteredReports.map(report => (
                                <div key={report.id} className="p-6 bg-primary-bg-cards rounded-xl border border-primary-border flex gap-5">
                                    <div className="p-2 bg-secondary-bg rounded-lg"><MapPin className="w-5 h-5"/></div>
                                    <div>
                                        <h3 className="font-bold text-primary-text text-lg">{report.title}</h3>
                                        <p className="text-sm text-secondary-text">{report.location} • {report.date}</p>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="p-10 text-center border-2 border-dashed border-primary-border rounded-xl">
                                <p className="text-secondary-text">Geen meldingen in deze categorie.</p>
                            </div>
                        )}
                    </div>
                </section>
            </main>
        </div>
    );
}