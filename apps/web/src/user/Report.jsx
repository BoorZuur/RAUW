import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom'; // Toegevoegd
import U_Nav from '../components/U_Nav';
import NativeLeafletMap from '../components/MapComponent.jsx';
import Footer from '../components/Footer.jsx';

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

const getDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371e3;
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

export default function ReportIssue() {
    const navigate = useNavigate(); // TOEGEVOEGD
    const [formData, setFormData] = useState({
        title: '',
        category_id: 1,
        address: '',
        content: '',
        is_anonymous: false
    });

    const [images, setImages] = useState([]);
    const [position, setPosition] = useState(null);
    const [error, setError] = useState(null);
    const [categories, setCategories] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [success, setSuccess] = useState(false);

    useEffect(() => {
        Promise.all([
            apiClient.get('/api/categories'),
            apiClient.get('/api/districts')
        ])
            .then(([catRes, distRes]) => {
                const catData = Array.isArray(catRes.data) ? catRes.data : (catRes.data.data || []);
                const distData = Array.isArray(distRes.data) ? distRes.data : (distRes.data.data || []);
                setCategories(catData);
                setDistricts(distData);
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
                params: { q: `${formData.address}, Rotterdam`, format: 'json', limit: 1 }
            });

            if (response.data && response.data.length > 0) {
                const result = response.data[0];
                const lat = parseFloat(result.lat);
                const lon = parseFloat(result.lon);

                setPosition({ lat, lng: lon });

                const neighborhood = result.address?.suburb || result.address?.neighbourhood || "";
                setFormData(prev => ({ ...prev, neighborhood }));
            }
        } catch (err) {
            setError("Kon locatie niet automatisch ophalen.");
        }
    };

    const handleSubmit = async () => {
        // 1. Zorg voor een actuele positie: als die er niet is, probeer geocode direct als backup
        let activePosition = position;

        if (!activePosition && formData.address) {
            try {
                const response = await axios.get(`https://nominatim.openstreetmap.org/search`, {
                    params: { q: `${formData.address}, Rotterdam`, format: 'json', limit: 1 }
                });

                if (response.data && response.data.length > 0) {
                    activePosition = {
                        lat: parseFloat(response.data[0].lat),
                        lng: parseFloat(response.data[0].lon)
                    };
                    // Update de state zodat de marker ook op de kaart verschijnt
                    setPosition(activePosition);
                }
            } catch (err) {
                setError("Kon adres niet automatisch vinden, controleer het adres.");
                setShowConfirm(false);
                return;
            }
        }

        // 2. Als er na de backup check nog steeds geen positie is, breek af
        if (!activePosition) {
            setError("Selecteer een locatie op de kaart of vul een geldig adres in.");
            setShowConfirm(false);
            return;
        }

        // 3. Geofencing check met de verruimde marge (gebruik activePosition)
        let closestDistrict = null;
        let minDistance = Infinity;

        districts.forEach(d => {
            const dist = getDistance(activePosition.lat, activePosition.lng, d.center_lat, d.center_lng);
            const searchRadius = d.radius_meters + 2000;

            if (dist < searchRadius && dist < minDistance) {
                minDistance = dist;
                closestDistrict = d;
            }
        });

        if (!closestDistrict) {
            setError("De gekozen locatie valt buiten een bekend wijkgebied.");
            setShowConfirm(false);
            return;
        }

        // 4. Data voorbereiden voor verzending
        const data = new FormData();
        Object.keys(formData).forEach(key => data.append(key, formData[key]));
        data.append('latitude', activePosition.lat);
        data.append('longitude', activePosition.lng);
        data.append('is_anonymous', formData.is_anonymous ? 1 : 0);
        data.append('category_id', parseInt(formData.category_id));

        data.set('district_id', closestDistrict.id);
        data.set('neighborhood', closestDistrict.name);

        images.forEach((file, index) => {
            data.append(`images[${index}]`, file);
        });

        try {
            await apiClient.post('/api/issues', data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            setSuccess(true);
            setShowConfirm(false);
            setTimeout(() => navigate('/feed'), 2000);
        } catch (err) {
            setError("Verzenden mislukt: " + (err.response?.data?.message || err.message));
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
                        <p className="text-sm opacity-70 mb-8">Je melding is succesvol ontvangen. Je wordt omgeleid...</p>
                    </div>
                </div>
            )}

            <div className="z-50 relative"><U_Nav /></div>

            <main className="z-10 grow w-full max-w-6xl mx-auto px-6 pt-26 mt-8 pb-12 grid grid-cols-1 md:grid-cols-2 gap-10">
                <section className="h-137.5 bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-3 shadow-sm">
                    <div className="w-full h-full rounded-2xl overflow-hidden border border-primary-border/50">
                        <NativeLeafletMap position={position} setPosition={setPosition} setFormData={setFormData} />
                    </div>
                </section>

                <section className="bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-6 shadow-sm">
                    <h2 className="text-xl font-black mb-6 uppercase tracking-widest text-primary-text">Signaal Melden</h2>
                    {error && <p className="text-red-600 font-bold mb-4 p-3 bg-red-100 rounded-lg text-sm">{error}</p>}

                    <div className="space-y-4">
                        <div>
                            <label className="block text-[9px] font-black mb-1 uppercase tracking-widest text-secondary-text">Titel</label>
                            <input className="w-full p-3 bg-primary-bg border-2 border-primary-border rounded-lg text-sm"
                                   value={formData.title} onChange={e => setFormData({...formData, title: e.target.value})} />
                        </div>

                        <div className="relative">
                            <label className="block text-[9px] font-black mb-1 uppercase tracking-widest text-secondary-text">Categorie</label>
                            <div onClick={() => setIsDropdownOpen(!isDropdownOpen)} className="w-full p-3 bg-primary-bg border-2 border-primary-border rounded-lg cursor-pointer flex justify-between items-center text-sm">
                                <span>{getSelectedCategoryName()}</span>
                            </div>
                            {isDropdownOpen && (
                                <div className="absolute z-50 w-full mt-1 max-h-48 overflow-y-auto bg-primary-bg border-2 border-primary-border rounded-lg p-1 shadow-xl text-xs">
                                    {categories.map(mainCat => (
                                        <div key={mainCat.id} onClick={() => { setFormData({...formData, category_id: mainCat.id}); setIsDropdownOpen(false); }} className="p-2 font-black uppercase cursor-pointer hover:bg-primary-border/20 rounded">
                                            {mainCat.name}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div>
                            <div>
                                <label className="block text-[9px] font-black mb-1 uppercase tracking-widest text-secondary-text">Adres</label>
                                <input className="w-full p-3 bg-primary-bg border-2 border-primary-border rounded-lg text-sm"
                                       value={formData.address} onChange={e => setFormData({...formData, address: e.target.value})} onBlur={handleGeocode} />
                            </div>
                        </div>

                        <div>
                            <label className="block text-[9px] font-black mb-1 uppercase tracking-widest text-secondary-text">Uitleg</label>
                            <textarea className="w-full p-3 h-20 bg-primary-bg border-2 border-primary-border rounded-lg text-sm"
                                      placeholder="Context & Behoefte" value={formData.content} onChange={e => setFormData({...formData, content: e.target.value})} />
                        </div>

                        <div>
                            <label className="block text-[9px] font-black mb-1 uppercase tracking-widest text-secondary-text">Foto (optioneel)</label>
                            <input
                                type="file"
                                accept="image/*"
                                onChange={(e) => setImages(Array.from(e.target.files))}
                                className="w-full p-2 bg-primary-bg border-2 border-primary-border rounded-lg text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-primary-text file:text-primary-bg hover:file:cursor-pointer"
                            />
                        </div>

                        <button
                            onClick={() => setShowConfirm(true)}
                            className="w-full h-10 mt-2 bg-primary-text text-primary-bg hover:bg-primary-accent font-black uppercase text-xs tracking-widest rounded-lg transition-all shadow-md active:scale-[0.98]"
                        >
                            Verstuur melding
                        </button>
                    </div>
                </section>
            </main>
            <Footer/>
        </div>
    );
}