import React, {useEffect, useState} from 'react';
import {Link} from 'react-router-dom';
import {BookOpen, CheckCircle, MapPin, MessageSquare, Settings, User} from 'lucide-react';
import {useTheme} from '../ThemeContext.jsx';
import U_Nav from '../components/U_Nav';
import axios from 'axios';

export default function Dashboard() {
    const [activeTab, setActiveTab] = useState('nieuw');
    const [user, setUser] = useState({name: 'Buurtgenoot', total_reports: 0, solved_reports: 0});
    const [reports, setReports] = useState([]);

    // Gebruik de context in plaats van lokale state
    const {isDark, toggleTheme} = useTheme();

    const tabs = ['nieuw', 'in behandeling', 'surveillance-route', 'afgehandeld', 'verhalen'];

    useEffect(() => {
        const fetchData = async () => {
            try {
                const [userRes, reportsRes] = await Promise.all([
                    axios.get('/api/user/profile'),
                    axios.get('/api/user/reports')
                ]);
                setUser(userRes.data);
                setReports(reportsRes.data);
            } catch (err) {
                console.error("Kon de buurtgegevens niet ophalen");
            }
        };
        fetchData();
    }, []);

    const filteredReports = Array.isArray(reports) ? reports.filter(r => r.status === activeTab) : [];

    const getGreeting = () => {
        const hour = new Date().getHours();
        return hour < 12 ? "Goedemorgen" : hour < 18 ? "Fijne middag" : "Goedenavond";
    };

    return (
        <div className="min-h-screen bg-primary-bg transition-colors duration-300">
            <div className="fixed top-0 w-full z-50">
                {/* Geef de context waarden mee aan je Nav */}
                <U_Nav/>
            </div>

            <main className="pt-32 p-8 max-w-4xl mx-auto space-y-8">
                {/* Account Balkje */}
                <section
                    className="flex items-center justify-between p-4 bg-primary-bg-cards border border-primary-border rounded-2xl">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-secondary-bg rounded-full">
                            <User className="w-5 h-5 text-primary-text"/>
                        </div>
                        <span className="font-bold text-primary-text">{user.name}</span>
                    </div>
                    <Link to="/instellingen"
                          className="flex items-center gap-2 px-4 py-2 text-sm font-bold text-primary-text hover:bg-secondary-bg rounded-lg transition-all">
                        <Settings className="w-4 h-4"/> Instellingen
                    </Link>
                </section>

                {/* Welkomstsectie */}
                <section className="bg-primary-bg-cards p-8 rounded-2xl border border-primary-border shadow-sm">
                    <h1 className="text-4xl font-extrabold text-primary-text mb-4 leading-tight">
                        {getGreeting()}, {user.name}!
                    </h1>
                    <p className="text-lg text-primary-text/80 font-medium max-w-2xl leading-relaxed">
                        Wat goed dat je er bent. Samen houden we onze buurt leefbaar.
                        Je hebt al <strong>{user.total_reports}</strong> meldingen gedaan.
                        {user.solved_reports > 0 && ` Samen hebben we al ${user.solved_reports} zaken opgelost.`}
                    </p>
                </section>

                {/* Navigatie */}
                <section>
                    <nav className="flex flex-wrap justify-center gap-3 mb-8">
                        {tabs.map((tab) => (
                            <button
                                key={tab}
                                onClick={() => setActiveTab(tab)}
                                aria-pressed={activeTab === tab}
                                className={`px-6 py-3 rounded-full text-sm font-bold capitalize transition-all border-2 
                                    ${activeTab === tab
                                    ? 'bg-primary-text text-primary-bg border-primary-text'
                                    : 'bg-primary-bg-cards text-primary-text border-primary-border hover:border-primary-text/50'}`}
                            >
                                {tab}
                            </button>
                        ))}
                    </nav>

                    {/* Logboek */}
                    <div className="space-y-4">
                        {activeTab === 'verhalen' ? (
                            <div
                                className="p-10 w-full border border-primary-border rounded-xl bg-primary-bg-cards flex flex-col items-center text-center">
                                <BookOpen className="w-8 h-8 text-primary-text/30 mb-3"/>
                                <h3 className="font-bold text-lg mb-2">Buurtverhalen die je volgt</h3>
                                <p className="text-primary-text/70 max-w-sm">
                                    Hier vind je binnenkort updates over buurtinitiatieven en verhalen die je met een
                                    hartje hebt gemarkeerd.
                                </p>
                            </div>
                        ) : filteredReports.length > 0 ? (
                            filteredReports.map(report => (
                                <div key={report.id}
                                     className="p-6 bg-primary-bg-cards rounded-xl border border-primary-border flex items-start gap-5 hover:border-primary-text/30 transition-all">
                                    <div className="mt-1 p-2 bg-secondary-bg rounded-lg text-primary-text">
                                        <MapPin className="w-5 h-5"/>
                                    </div>
                                    <div className="flex-1">
                                        <h3 className="font-bold text-primary-text text-lg mb-1">{report.title}</h3>
                                        <p className="text-sm text-primary-text/70">{report.location} • {report.date}</p>
                                        {report.status === 'afgehandeld' && (
                                            <div
                                                className="mt-3 flex items-center gap-2 text-green-700 font-bold text-sm">
                                                <CheckCircle className="w-5 h-5"/>
                                                <span>Dankjewel voor je melding, dit is inmiddels opgelost!</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div
                                className="p-10 text-center border-2 border-dashed border-primary-border rounded-xl bg-primary-bg-cards">
                                <MessageSquare className="w-8 h-8 mx-auto text-primary-text/30 mb-3"/>
                                <p className="text-primary-text/70">Geen meldingen in deze categorie. Alles rustig in de
                                    buurt?</p>
                            </div>
                        )}
                    </div>
                </section>
            </main>
        </div>
    );
}