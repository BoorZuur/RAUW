import React, { useState, useEffect } from 'react';
import { ArrowLeft, Bell, Loader2, User, Trash2, Palette, MapPin } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import { useTheme } from '../ThemeContext.jsx';
import U_Nav from '../components/U_Nav';
import Footer from "../components/Footer.jsx";

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { 'Accept': 'application/json' }
});

apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

export default function Instellingen() {
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const navigate = useNavigate();
    const { isDark } = useTheme();
    const [loading, setLoading] = useState(false);
    const [pageLoading, setPageLoading] = useState(true);
    const [status, setStatus] = useState({ type: '', message: '' });
    const [colorMode, setColorMode] = useState(localStorage.getItem('color_mode') || 'default');
    const [allDistricts, setAllDistricts] = useState([]);
    const [selectedDistrictIds, setSelectedDistrictIds] = useState([]);

    const [settings, setSettings] = useState({
        username: '',
        notify_status_changes: true,
        notify_district_news: true,
    });

    useEffect(() => {
        const loadSettings = async () => {
            try {
                const [meRes, settingsRes, districtsRes] = await Promise.all([
                    apiClient.get('/auth/me'),
                    apiClient.get('/user/settings'),
                    apiClient.get('/districts'),
                ]);

                const profile = meRes.data?.profile || meRes.data;
                const prefs = settingsRes.data?.data || settingsRes.data;
                const districtsData = districtsRes.data?.data || districtsRes.data || [];

                setSettings({
                    username: profile?.username || '',
                    notify_status_changes: prefs?.notify_status_changes ?? true,
                    notify_district_news: prefs?.notify_district_news ?? true,
                });
                setSelectedDistrictIds((profile?.districts || []).map((d) => d.id));
                setAllDistricts(
                    (Array.isArray(districtsData) ? districtsData : []).filter((d) => d.is_active !== false)
                );
            } catch (err) {
                setStatus({ type: 'error', message: 'Instellingen laden mislukt.' });
            } finally {
                setPageLoading(false);
            }
        };

        loadSettings();
    }, []);

    useEffect(() => {
        const root = document.documentElement;
        const modes = ['mode-protanopia', 'mode-deuteranopia', 'mode-tritanopia', 'mode-achromatopsia'];
        root.classList.remove(...modes);
        if (colorMode !== 'default') {
            root.classList.add(`mode-${colorMode}`);
        }
        localStorage.setItem('color_mode', colorMode);
    }, [colorMode]);

    const toggleDistrict = (id) => {
        setSelectedDistrictIds((prev) =>
            prev.includes(id) ? prev.filter((d) => d !== id) : [...prev, id]
        );
    };

    const handleSave = async () => {
        setLoading(true);
        setStatus({ type: '', message: '' });
        try {
            await Promise.all([
                apiClient.patch('/auth/me/feed-districts', { district_ids: selectedDistrictIds }),
                apiClient.patch('/user/settings', {
                    notify_status_changes: settings.notify_status_changes,
                    notify_district_news: settings.notify_district_news,
                }),
            ]);
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
            await apiClient.delete('/user/account');
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        } catch (err) {
            setStatus({ type: 'error', message: "Kon account niet verwijderen. Probeer het later opnieuw." });
            setIsDeleting(false);
            setShowDeleteConfirm(false);
        }
    };

    return (
        <div className={`min-h-screen bg-primary-bg transition-colors duration-300 overflow-x-hidden ${isDark ? 'dark' : ''}`}>
            <U_Nav />

            {/* Flex wrapper voor de zijkanten + hoofdcontent */}
            <div className="grow flex w-full pt-26">

                {/* ==================== LINKER PANEL: HANDHAVING & TOEZICHT KETEN ==================== */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300"></div>

                    {/* Verticale stapeling van Toezicht iconen */}
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">

                        {/* Icoon 1: Schild (Veiligheid) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <circle cx="12" cy="11" r="2" className="opacity-60" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 2: Handdruk (Samenwerking) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 3: Checkmark (Afgehandeld) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="22 11.08 12 19 9 16" />
                                <path d="M22 4L12 14.01l-3-3" className="opacity-40" />
                                <circle cx="12" cy="12" r="10" strokeDasharray="3 3" className="opacity-50" />
                            </svg>
                        </div>
                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300"></div>
                </div>

                {/* ==================== MIDDEN: INSTELLINGEN FORMULIEREN ==================== */}
                <main className="z-10 grow w-full max-w-xl mx-auto px-6 mt-8 space-y-10 pb-20">
                    <button onClick={() => navigate('/account')} className="flex items-center gap-2 text-secondary-text hover:text-primary-text transition-colors text-xs font-bold uppercase tracking-widest">
                        <ArrowLeft size={14} /> Terug naar account
                    </button>

                    <h1 className="font-headline text-primary-text">Instellingen</h1>

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

                    {/* Wijken */}
                    <section className="p-8 bg-primary-bg-cards border border-primary-border rounded-2xl">
                        <div className="flex items-center gap-3 mb-6 text-primary-accent">
                            <MapPin size={20} />
                            <h2 className="text-sm font-black uppercase tracking-widest text-primary-text">Wijken</h2>
                        </div>
                        <p className="text-xs text-secondary-text mb-4">
                            Selecteer de wijken waarvan je wijknieuws wilt ontvangen. Zonder geselecteerde wijken zie je geen wijknieuws in je feed of notificaties.
                        </p>
                        <div className="mb-4 px-4 py-3 rounded-xl bg-primary-bg border border-primary-border text-sm text-primary-text">
                            <strong>{selectedDistrictIds.length}</strong> van {allDistricts.length} wijken geselecteerd
                        </div>
                        {pageLoading ? (
                            <div className="flex justify-center py-8">
                                <Loader2 className="animate-spin text-primary-accent" size={24} />
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-64 overflow-y-auto custom-scrollbar">
                                {allDistricts.map((district) => (
                                    <button
                                        key={district.id}
                                        type="button"
                                        onClick={() => toggleDistrict(district.id)}
                                        className={`px-3 py-2 rounded-lg text-xs font-bold border transition-all cursor-pointer ${
                                            selectedDistrictIds.includes(district.id)
                                                ? 'bg-primary-accent text-primary-bg border-primary-accent'
                                                : 'bg-primary-bg text-primary-text border-primary-border hover:border-primary-accent'
                                        }`}
                                    >
                                        {district.name}
                                    </button>
                                ))}
                            </div>
                        )}
                    </section>

                    {/* Notificaties */}
                    <section className="p-8 bg-primary-bg-cards border border-primary-border rounded-2xl">
                        <div className="flex items-center gap-3 mb-8 text-primary-accent">
                            <Bell size={20} />
                            <h2 className="text-sm font-black uppercase tracking-widest text-primary-text">Notificaties</h2>
                        </div>
                        <div className="space-y-8">
                            {[
                                {
                                    key: 'notify_status_changes',
                                    label: 'Statuswijzigingen',
                                    desc: 'Meldingen over updates van handhavers op jouw meldingen',
                                },
                                {
                                    key: 'notify_district_news',
                                    label: 'Wijknieuws',
                                    desc: 'Berichten van handhavers in de wijken die je volgt.',
                                },
                            ].map((item) => (
                                <div key={item.key} className="flex justify-between items-center">
                                    <div>
                                        <p className="text-sm font-bold text-primary-text">{item.label}</p>
                                        <p className="text-xs text-secondary-text">{item.desc}</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setSettings((prev) => ({ ...prev, [item.key]: !prev[item.key] }))}
                                        className={`w-14 h-7 rounded-full border-2 transition-all relative cursor-pointer ${settings[item.key] ? 'bg-primary-accent border-primary-accent' : 'bg-primary-border border-primary-border'}`}
                                    >
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
                            type="button"
                            onClick={() => setShowDeleteConfirm(true)}
                            className="flex items-center justify-center w-full gap-3 text-red-500 font-bold hover:text-red-600 transition-all cursor-pointer active:scale-[0.98]"
                        >
                            <Trash2 size={18} /> Account definitief verwijderen
                        </button>
                        <p className="text-xs text-secondary-text mt-4">Dit is een onomkeerbare actie.</p>

                        {/* Bevestigings-pop-up */}
                        {showDeleteConfirm && (
                            <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                                <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-red-500/30 shadow-2xl max-w-sm w-full">
                                    <h3 className="text-xl font-black uppercase mb-4 text-red-500">Weet je het zeker?</h3>
                                    <p className="text-sm text-primary-text opacity-70 mb-8">Deze actie is onomkeerbaar. Al je gegevens worden permanent verwijderd.</p>
                                    <div className="flex gap-4">
                                        <button
                                            type="button"
                                            onClick={() => setShowDeleteConfirm(false)}
                                            className="flex-1 p-3 border-2 border-primary-border text-primary-text rounded-xl cursor-pointer"
                                        >
                                            Annuleren
                                        </button>
                                        <button
                                            type="button"
                                            onClick={handleDeleteAccount}
                                            disabled={isDeleting}
                                            className="flex-1 p-3 bg-red-600 text-white rounded-xl font-black uppercase tracking-widest hover:bg-red-700 cursor-pointer disabled:opacity-50"
                                        >
                                            {isDeleting ? 'Bezig...' : 'Verwijder'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </section>

                    <button onClick={handleSave} className="w-full h-12 mt-4 bg-primary-text hover:bg-primary-accent text-primary-bg font-medium rounded-xl transition-all shadow-md cursor-pointer flex items-center justify-center active:scale-[0.98]">
                        {loading ? <Loader2 className="animate-spin text-current" size={20} /> : 'Wijzigingen opslaan'}
                    </button>

                    {status.message && (
                        <p className={`text-center text-xs font-bold mt-4 ${status.type === 'error' ? 'text-red-500' : 'text-primary-text'}`}>
                            {status.message}
                        </p>
                    )}
                </main>

                {/* ==================== RECHTER PANEL: ROTTERDAM IDENTITEIT ==================== */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300 self-end"></div>

                    {/* Verticale stapeling van Rotterdamse iconen */}
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">

                        {/* Icoon 1: Erasmusbrug */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M4 20 L15 4 L18 5 L10 20" />
                                <line x1="2" y1="20" x2="22" y2="20" />
                                <line x1="14" y1="6" x2="20" y2="20" className="opacity-40" />
                                <line x1="13" y1="9" x2="17" y2="20" className="opacity-40" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 2: Stad / Architectuur (De Wijken) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <rect x="2" y="10" width="6" height="11" />
                                <rect x="9" y="3" width="6" height="18" />
                                <rect x="16" y="8" width="6" height="13" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 3: Maritiem / Haven */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="8" y1="9" x2="16" y2="9" />
                                <path d="M5 12a7 7 0 0 0 14 0" />
                                <circle cx="12" cy="4" r="1" />
                            </svg>
                        </div>
                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300 self-end"></div>
                </div>

            </div>

            <div className="z-10 bg-primary-bg">
                <Footer />
            </div>
        </div>
    );
}