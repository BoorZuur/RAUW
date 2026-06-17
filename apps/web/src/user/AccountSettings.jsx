import React, { useState, useEffect } from 'react';
import { ArrowLeft, Bell, Loader2, User, Trash2, Palette } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useTheme } from '../ThemeContext.jsx';
import U_Nav from '../components/U_Nav';
import axios from 'axios';
import Footer from "../components/Footer.jsx";

export default function Instellingen() {
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const navigate = useNavigate();
    const { isDark } = useTheme();
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState({ type: '', message: '' });
    const [colorMode, setColorMode] = useState(localStorage.getItem('color_mode') || 'default');

    const [settings, setSettings] = useState({
        username: 'Buurtgenoot',
        status_updates: true,
        weekly_update: false,
        new_alerts: false
    });

    // Update de document klasse wanneer colorMode verandert
    useEffect(() => {
        const root = document.documentElement;
        const modes = ['mode-protanopia', 'mode-deuteranopia', 'mode-tritanopia', 'mode-achromatopsia'];
        root.classList.remove(...modes);
        if (colorMode !== 'default') {
            root.classList.add(`mode-${colorMode}`);
        }
        localStorage.setItem('color_mode', colorMode);
    }, [colorMode]);

    const handleSave = async () => {
        setLoading(true);
        try {
            const token = localStorage.getItem('auth_token');
            await axios.post('http://localhost:8001/api/user/settings', { ...settings, colorMode }, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            setStatus({ type: 'success', message: 'Instellingen succesvol opgeslagen.' });
        } catch (err) {
            setStatus({ type: 'error', message: 'Opslaan mislukt.' });
        } finally {
            setLoading(false);
        }
    };

    const handleDeleteAccount = async () => {
        setIsDeleting(true);
        try {
            await apiClient.delete('/api/user/account');
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        } catch (err) {
            setError("Kon account niet verwijderen. Probeer het later opnieuw.");
            setIsDeleting(false);
            setShowDeleteConfirm(false);
        }
    };

    return (
        <div className={`min-h-screen bg-primary-bg transition-colors duration-300 ${isDark ? 'dark' : ''}`}>
            <U_Nav />

            <main className="pt-40 pb-20 px-6 max-w-xl mx-auto space-y-10">
                <button onClick={() => navigate('/account')} className="flex items-center gap-2 text-secondary-text hover:text-primary-text transition-colors text-xs font-bold uppercase tracking-widest">
                    <ArrowLeft size={14} /> Terug naar account
                </button>

                <h1 className="font-headline">Instellingen</h1>

                {/* Profiel */}
                <section className="p-8 bg-primary-bg-cards border border-primary-border rounded-2xl">
                    <div className="flex items-center gap-3 mb-6 text-primary-accent">
                        <User size={20} />
                        <h2 className="text-sm font-black uppercase tracking-widest text-primary-text">Profiel</h2>
                    </div>
                    <label className="text-[10px] font-black uppercase tracking-widest text-secondary-text">Gebruikersnaam</label>
                    <input
                        value={settings.username}
                        onChange={(e) => setSettings({...settings, username: e.target.value})}
                        className="w-full mt-2 px-4 py-3 bg-primary-bg border border-primary-border rounded-lg outline-none focus:ring-2 focus:ring-primary-accent text-sm font-medium text-primary-text transition-all"
                    />
                </section>

                {/* Toegankelijkheid (Color Mode) */}
                <section className="p-8 bg-primary-bg-cards border border-primary-border rounded-2xl">
                    <div className="flex items-center gap-3 mb-6 text-primary-accent">
                        <Palette size={20} />
                        <h2 className="text-sm font-black uppercase tracking-widest text-primary-text">Toegankelijkheid</h2>
                    </div>
                    <label className="text-[10px] font-black uppercase tracking-widest text-secondary-text">Kleurmodus interface</label>
                    <select
                        value={colorMode}
                        onChange={(e) => setColorMode(e.target.value)}
                        className="w-full mt-2 px-4 py-3 bg-primary-bg border border-primary-border rounded-lg text-sm font-medium text-primary-text outline-none focus:ring-2 focus:ring-primary-accent transition-all"
                    >
                        <option value="default">Standaard weergave</option>
                        <option value="protanopia">Protanopia (Rood-blind)</option>
                        <option value="deuteranopia">Deuteranopia (Groen-blind)</option>
                        <option value="tritanopia">Tritanopia (Blauw-blind)</option>
                        <option value="achromatopsia">Achromatopsia (Grayscale)</option>
                    </select>
                </section>

                {/* Notificaties */}
                <section className="p-8 bg-primary-bg-cards border border-primary-border rounded-2xl">
                    <div className="flex items-center gap-3 mb-8 text-primary-accent">
                        <Bell size={20} />
                        <h2 className="text-sm font-black uppercase tracking-widest text-primary-text">Notificaties</h2>
                    </div>
                    <div className="space-y-8">
                        {[{key: 'status_updates', label: 'Statuswijzigingen', desc: 'Updates van BOA acties'},
                            {key: 'weekly_update', label: 'Wekelijkse update', desc: 'Veiligheidssamenvatting'},
                            {key: 'new_alerts', label: 'Nieuwe signalen', desc: 'Directe buurtmeldingen'}].map(item => (
                            <div key={item.key} className="flex justify-between items-center">
                                <div>
                                    <p className="text-sm font-bold text-primary-text">{item.label}</p>
                                    <p className="text-xs text-secondary-text">{item.desc}</p>
                                </div>
                                <button onClick={() => setSettings(prev => ({...prev, [item.key]: !prev[item.key]}))}
                                        className={`w-14 h-7 rounded-full border-2 transition-all relative ${settings[item.key] ? 'bg-primary-accent border-primary-accent' : 'bg-primary-border border-primary-border'}`}>
                                    <div className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full border border-neutral-300 transition-transform ${settings[item.key] ? 'translate-x-7' : 'translate-x-0'}`} />
                                </button>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Danger Zone */}
                <section className="p-8 bg-primary-bg-cards border border-red-500/30 rounded-2xl">
                    <h2 className="text-sm font-black uppercase tracking-widest text-red-500 mb-6">Danger Zone</h2>

                    <button
                        onClick={() => setShowDeleteConfirm(true)}
                        className="flex items-center justify-center w-full gap-3 text-red-500 font-bold hover:text-red-600 transition-all active:scale-[0.98]"
                    >
                        <Trash2 size={18} /> Account definitief verwijderen
                    </button>
                    <p className="text-xs text-secondary-text mt-4">Dit is een onomkeerbare actie.</p>

                    {/* Bevestigings-pop-up */}
                    {showDeleteConfirm && (
                        <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                            <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-red-500/30 shadow-2xl max-w-sm w-full">
                                <h3 className="text-xl font-black uppercase mb-4 text-red-500">Weet je het zeker?</h3>
                                <p className="text-sm opacity-70 mb-8">Deze actie is onomkeerbaar. Al je gegevens worden permanent verwijderd.</p>
                                <div className="flex gap-4">
                                    <button
                                        onClick={() => setShowDeleteConfirm(false)}
                                        className="flex-1 p-3 border-2 border-primary-border rounded-xl"
                                    >
                                        Annuleren
                                    </button>
                                    <button
                                        onClick={handleDeleteAccount}
                                        disabled={isDeleting}
                                        className="flex-1 p-3 bg-red-600 text-white rounded-xl font-black uppercase tracking-widest hover:bg-red-700 disabled:opacity-50"
                                    >
                                        {isDeleting ? 'Bezig...' : 'Verwijder'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </section>

                <button onClick={handleSave} className="w-full h-12 mt-4 bg-primary-text hover:bg-primary-accent font-medium rounded-xl transition-all shadow-md active:scale-[0.98]">
                    {loading ? <Loader2 className="animate-spin" /> : 'Wijzigingen opslaan'}
                </button>

                {status.message && <p className="text-center text-xs font-bold text-primary-text">{status.message}</p>}
            </main>

            <Footer/>

        </div>
    );
}