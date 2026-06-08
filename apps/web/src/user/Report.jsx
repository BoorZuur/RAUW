import React, { useState, useEffect } from 'react';
import axios from 'axios';
import U_Nav from '../components/U_Nav';
import NativeLeafletMap from '../components/MapComponent.jsx';
import { useNavigate } from 'react-router-dom';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

export default function ReportIssue() {
    const [formData, setFormData] = useState({
        title: '',
        category_id: 1,
        address: '',
        neighborhood: '',
        content: '',
        is_anonymous: false
    });

    const [position, setPosition] = useState(null);
    const [error, setError] = useState(null);
    const [categories, setCategories] = useState([]);
    const [districts, setDistricts] = useState([]); // Toegevoegd voor validatie
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [success, setSuccess] = useState(false);

    useEffect(() => {
        // Categorieën én Districten ophalen voor validatie
        Promise.all([
            apiClient.get('/api/categories'),
            apiClient.get('/api/districts')
        ])
            .then(([catRes, distRes]) => {
                setCategories(Array.isArray(catRes.data) ? catRes.data : (catRes.data.data || []));
                setDistricts(Array.isArray(distRes.data) ? distRes.data : (distRes.data.data || []));
            })
            .catch(err => {
                console.error("API Error details:", err.response || err);
                setError("Kon gegevens niet laden.");
            });
    }, []);

    const getSelectedCategoryName = () => {
        const allCategories = categories.flatMap(c => [c, ...(c.children || [])]);
        const found = allCategories.find(c => c.id === formData.category_id);
        return found ? found.name : "Selecteer een categorie...";
    };

    const handleGeocode = async () => {
        if (!formData.address) return;
        try {
            const response = await axios.get(`https://nominatim.openstreetmap.org/search`, {
                params: {
                    q: `${formData.address}, Rotterdam`,
                    format: 'json',
                    addressdetails: 1,
                    limit: 1
                }
            });

            if (response.data && response.data.length > 0) {
                const result = response.data[0];
                const lat = parseFloat(result.lat);
                const lon = parseFloat(result.lon);
                const neighborhood = result.address.suburb || result.address.neighbourhood || "Onbekend";

                setPosition({ lat, lng: lon });
                setFormData(prev => ({ ...prev, neighborhood }));
            } else {
                setError("Adres niet gevonden op de kaart.");
            }
        } catch (err) {
            console.error("Geocoding fout:", err);
            setError("Kon locatie niet automatisch ophalen.");
        }
    };

    const handleSubmit = async () => {
        if (!position) {
            setError("Selecteer een locatie op de kaart of vul een adres in.");
            setShowConfirm(false);
            return;
        }

        // --- STRIKTE VALIDATIE LOGICA ---
        // Controleer of de ingevulde neighborhood in de database lijst (districts) staat
        const isValidDistrict = districts.some(d =>
            formData.neighborhood.toLowerCase().includes(d.name.toLowerCase())
        );

        if (!isValidDistrict) {
            setError("De ingevulde buurt is niet geldig of niet bekend in ons systeem.");
            setShowConfirm(false);
            return;
        }
        // --------------------------------

        try {
            await apiClient.post('/api/issues', {
                ...formData,
                latitude: position.lat,
                longitude: position.lng,
                is_anonymous: formData.is_anonymous ? 1 : 0,
                category_id: parseInt(formData.category_id),
                district_id: 1 // (Blijft zoals je had)
            });

            setSuccess(true);
            setShowConfirm(false);
        } catch (err) {
            if (err.response && err.response.status === 422) {
                setError("Controleer de velden: " + JSON.stringify(err.response.data.errors));
            } else {
                setError("Verzenden mislukt: " + (err.response?.data?.message || err.message));
            }
            setShowConfirm(false);
        }
    };

    return (
        <div className="min-h-screen bg-primary-bg text-primary-text flex flex-col transition-colors duration-300">
            {showConfirm && (
                <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-primary-border shadow-2xl max-w-sm w-full">
                        <h3 className="text-xl font-black uppercase mb-4">Bevestig melding</h3>
                        <p className="text-sm opacity-70 mb-8">Weet je zeker dat je deze melding wilt versturen?</p>
                        <div className="flex gap-4">
                            <button onClick={() => setShowConfirm(false)} className="flex-1 p-3 border-2 border-primary-border rounded-xl">Annuleren</button>
                            <button onClick={handleSubmit} className="flex-1 p-3 bg-secondary-accent text-white rounded-xl font-black uppercase tracking-widest hover:brightness-110">Verstuur</button>
                        </div>
                    </div>
                </div>
            )}

            {success && (
                <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-primary-border shadow-2xl max-w-sm w-full text-center">
                        <h3 className="text-xl font-black uppercase mb-4">Verzonden!</h3>
                        <p className="text-sm opacity-70 mb-8">Je melding is succesvol ontvangen.</p>
                        <button onClick={() => { setSuccess(false); window.location.reload(); }} className="w-full p-3 bg-secondary-accent text-white rounded-xl font-black uppercase tracking-widest">Sluiten</button>
                    </div>
                </div>
            )}

            <div className="z-50 relative"><U_Nav /></div>

            <main className="z-10 grow w-full max-w-6xl mx-auto px-6 pt-32 mt-8 pb-12 grid grid-cols-1 md:grid-cols-2 gap-10">
                <section className="h-137.5 bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-3 shadow-sm">
                    <div className="w-full h-full rounded-2xl overflow-hidden border border-primary-border/50">
                        <NativeLeafletMap position={position} setPosition={setPosition} setFormData={setFormData} />
                    </div>
                </section>

                <section className="bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-8 shadow-sm">
                    <h2 className="text-2xl font-black mb-8 uppercase tracking-widest text-primary-text">Signaal Melden</h2>
                    {error && <p className="text-red-600 font-bold mb-6 p-4 bg-red-100 rounded-xl">{error}</p>}

                    <div className="space-y-6">
                        <div>
                            <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Titel</label>
                            <input className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl focus:border-primary-accent outline-none"
                                   value={formData.title} onChange={e => setFormData({...formData, title: e.target.value})} />
                        </div>

                        <div className="relative">
                            <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Categorie</label>
                            <div onClick={() => setIsDropdownOpen(!isDropdownOpen)} className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl cursor-pointer flex justify-between items-center hover:border-primary-accent transition-all">
                                <span className="text-sm font-medium">{getSelectedCategoryName()}</span>
                                <span>▼</span>
                            </div>
                            {isDropdownOpen && (
                                <div className="absolute z-50 w-full mt-2 max-h-60 overflow-y-auto bg-primary-bg border-2 border-primary-border rounded-xl p-2 shadow-2xl">
                                    {categories.map(mainCat => (
                                        <React.Fragment key={mainCat.id}>
                                            <div onClick={() => { setFormData({...formData, category_id: mainCat.id}); setIsDropdownOpen(false); }} className="p-3 font-black uppercase text-[10px] tracking-widest mt-2 cursor-pointer rounded-lg hover:bg-primary-border/20">
                                                {mainCat.name}
                                            </div>
                                            {mainCat.children && mainCat.children.map(sub => (
                                                <div key={sub.id} onClick={() => { setFormData({...formData, category_id: sub.id}); setIsDropdownOpen(false); }} className="pl-10 p-3 text-xs opacity-70 cursor-pointer flex items-center rounded-lg hover:bg-primary-border/20">
                                                    <span className="mr-2">↳</span> {sub.name}
                                                </div>
                                            ))}
                                        </React.Fragment>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Adres</label>
                                <input className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl"
                                       value={formData.address} onChange={e => setFormData({...formData, address: e.target.value})} onBlur={handleGeocode} />
                            </div>
                            <div>
                                <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Buurt</label>
                                <input className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl"
                                       value={formData.neighborhood} onChange={e => setFormData({...formData, neighborhood: e.target.value})} />
                            </div>
                        </div>

                        <textarea className="w-full p-4 h-32 bg-primary-bg border-2 border-primary-border rounded-xl" placeholder="Context & Behoefte" value={formData.content} onChange={e => setFormData({...formData, content: e.target.value})} />

                        <div onClick={() => setFormData({...formData, is_anonymous: !formData.is_anonymous})}
                             className={`cursor-pointer p-4 border-2 rounded-xl flex items-center justify-between transition-all ${formData.is_anonymous ? 'border-secondary-accent bg-secondary-accent/10' : 'border-primary-border bg-primary-bg'}`}>
                            <span className="font-bold text-sm">Anoniem signaleren</span>
                            <div className={`w-10 h-5 rounded-full relative transition-colors ${formData.is_anonymous ? 'bg-secondary-accent' : 'bg-primary-border'}`}>
                                <div className={`absolute top-1 w-3 h-3 rounded-full bg-white transition-all ${formData.is_anonymous ? 'left-6' : 'left-1'}`} />
                            </div>
                        </div>

                        <button onClick={() => setShowConfirm(true)} className="w-full h-12 mt-4 bg-primary-text hover:bg-primary-accent text-white font-black rounded-xl uppercase">
                            Verstuur melding
                        </button>
                    </div>
                </section>
            </main>
        </div>
    );
}