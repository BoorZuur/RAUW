import React, { useState, useEffect } from 'react';
import { useBlocker } from 'react-router-dom';
import axios from 'axios';
import "../App.css"
import HM_Nav from "../components/HM_Nav.jsx";
import { Pin, Settings } from 'lucide-react';

export default function SectorSettings() {
    const [allHubs, setAllHubs] = useState([]);
    const [allDistricts, setAllDistricts] = useState([]);
    const [selectedHubId, setSelectedHubId] = useState(null);
    const [initialHubId, setInitialHubId] = useState(null);
    const [selectedDistricts, setSelectedDistricts] = useState([]);
    const [initialDistricts, setInitialDistricts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState("");

    const [pendingHubId, setPendingHubId] = useState(null);
    const [showHubModal, setShowHubModal] = useState(false);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const token = localStorage.getItem('auth_token');
                
                // Fetch all hubs
                const hubsRes = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/hubs`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const hubsData = hubsRes.data.data || hubsRes.data;
                setAllHubs(Array.isArray(hubsData) ? hubsData : []);

                // Fetch all districts
                const distRes = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/districts`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const districtsData = distRes.data.data || distRes.data;
                setAllDistricts(Array.isArray(districtsData) ? districtsData : []);

                // Fetch my profile to get assigned districts
                const meRes = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const profile = meRes.data?.profile || meRes.data;
                const myDistricts = profile.districts || [];
                const officerHubId = profile.hub_id;
                
                setSelectedHubId(officerHubId);
                setInitialHubId(officerHubId);
                const districtIds = myDistricts.map(d => d.id);
                setSelectedDistricts(districtIds);
                setInitialDistricts(districtIds);
            } catch (error) {
                console.error("Error fetching data:", error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    const handleHubChange = (e) => {
        const newHubId = parseInt(e.target.value, 10);
        if (newHubId !== selectedHubId) {
            setPendingHubId(newHubId);
            setShowHubModal(true);
        }
    };

    const confirmHubChange = () => {
        setSelectedHubId(pendingHubId);
        setSelectedDistricts([]);
        setShowHubModal(false);
        setPendingHubId(null);
    };

    const cancelHubChange = () => {
        setShowHubModal(false);
        setPendingHubId(null);
    };

    const toggleDistrict = (id) => {
        setSelectedDistricts(prev => 
            prev.includes(id) ? prev.filter(d => d !== id) : [...prev, id]
        );
    };

    const handleSave = async () => {
        setSaving(true);
        setMessage("");
        try {
            const token = localStorage.getItem('auth_token');
            
            if (selectedHubId !== initialHubId) {
                await axios.patch(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me/hub`, {
                    hub_id: selectedHubId
                }, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                setInitialHubId(selectedHubId);
            }

            await axios.patch(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me/districts`, {
                district_ids: selectedDistricts
            }, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            setInitialDistricts(selectedDistricts);
            setMessage("Instellingen succesvol opgeslagen!");
            setTimeout(() => setMessage(""), 5000);
        } catch (error) {
            console.error("Error saving settings:", error);
            setMessage("Fout bij het opslaan van instellingen.");
        } finally {
            setSaving(false);
        }
    };

    const isDirty = 
        selectedHubId !== initialHubId || 
        [...selectedDistricts].sort().join(',') !== [...initialDistricts].sort().join(',');

    const blocker = useBlocker(
        ({ currentLocation, nextLocation }) =>
            isDirty && currentLocation.pathname !== nextLocation.pathname
    );

    return (
        <>
            <div className="flex min-h-screen bg-primary-bg">
                <HM_Nav></HM_Nav>
                <main className="flex-1 p-8">
                    <header className="flex justify-between items-center mb-8">
                        <div className="flex items-center gap-4">
                            <div className="p-3 bg-primary-bg-cards rounded-xl border border-primary-border text-primary-text">
                                <Settings className="w-6 h-6" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-primary-text">Sector Instellingen</h1>
                                <p className="text-secondary-text">Selecteer jouw zorgwijken in Rotterdam</p>
                                {message && <p className="text-sm mt-1 text-emerald-600 font-medium">{message}</p>}
                            </div>
                        </div>
                        <button className="flex items-center gap-2 px-6 py-2 bg-primary-accent text-white rounded-xl font-bold relative" onClick={handleSave} disabled={saving}>
                            {isDirty && !saving && (
                                <span className="absolute -top-1 -right-1 flex h-3 w-3">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                            </span>
                            )}
                            {saving ? "Opslaan..." : "Opslaan"}
                        </button>
                    </header>

                    <div className="bg-primary-bg-cards border border-primary-border p-4 rounded-xl mb-6 flex items-center gap-3">
                        {/* Icoon vervangen door de Pin component */}
                        <Pin className="w-5 h-5 text-primary-text" />

                        <p className="text-primary-text">
                            <strong id="selected-count">{selectedDistricts.length}</strong> van {allDistricts.length || 74} Rotterdamse wijken geselecteerd
                        </p>
                    </div>

                    <section className="bg-primary-bg-cards border border-primary-border p-6 rounded-2xl mb-6">
                        <h2 className="font-bold text-primary-text mb-2">Jouw Hub</h2>
                        <p className="text-secondary-text text-sm mb-3">Kies de hoofdlocatie van waaruit je werkt.</p>
                        <select
                            className="w-full border border-primary-border rounded-lg p-3 text-primary-text bg-primary-bg focus:outline-none focus:border-primary-accent"
                            value={selectedHubId || ''}
                            onChange={handleHubChange}
                        >
                            <option value="" disabled>Selecteer een hub</option>
                            {allHubs.map(hub => (
                                <option key={hub.id} value={hub.id}>{hub.name}</option>
                            ))}
                        </select>
                    </section>

                    <section className="bg-primary-bg-cards border border-primary-border p-6 rounded-2xl">
                        <h2 className="font-bold text-primary-text mb-2">Alle Wijken Rotterdam</h2>
                        <p className="text-secondary-text text-sm mb-4">Klik op een wijk om deze toe te voegen of te verwijderen</p>

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                            {loading ? (
                                <p className="text-primary-text">Gegevens laden...</p>
                            ) : (
                                allDistricts
                                    .filter(d => selectedHubId ? d.hub_id === selectedHubId : true)
                                    .map(district => (
                                        <button
                                            key={district.id}
                                            className={`p-3 rounded-lg border text-sm font-semibold transition-all ${selectedDistricts.includes(district.id) ? 'bg-primary-accent text-white border-primary-accent' : 'bg-primary-bg border-primary-border text-primary-text'}`}
                                            onClick={() => toggleDistrict(district.id)}
                                        >
                                            {district.name}
                                        </button>
                                    ))
                            )}
                        </div>
                    </section>
                </main>
            </div>

            {showHubModal && (
                <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50">
                    <div className="bg-primary-bg-cards p-6 rounded-lg shadow-xl max-w-md w-full mx-4 border border-primary-border">
                        <h2 className="text-lg font-bold text-primary-text mb-4">Hub Wijzigen</h2>
                        <p className="text-secondary-text mb-6">
                            Weet je zeker dat je van hub wilt wisselen? Dit beëindigt direct je huidige shift en wist je momenteel geselecteerde wijken.
                        </p>
                        <div className="flex justify-end gap-3">
                            <button onClick={cancelHubChange} className="px-4 py-2 text-secondary-text font-medium hover:bg-primary-border rounded-lg">Annuleren</button>
                            <button onClick={confirmHubChange} className="px-4 py-2 bg-primary-accent text-white font-medium rounded-lg">Bevestigen</button>
                        </div>
                    </div>
                </div>
            )}

            {blocker.state === "blocked" && (
                <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50">
                    <div className="bg-primary-bg-cards p-6 rounded-lg shadow-xl max-w-md w-full mx-4 border border-primary-border">
                        <h2 className="text-lg font-bold text-primary-text mb-4">Onopgeslagen Wijzigingen</h2>
                        <p className="text-secondary-text mb-6">
                            Je hebt wijzigingen gemaakt die nog niet zijn opgeslagen. Weet je zeker dat je deze pagina wilt verlaten?
                        </p>
                        <div className="flex justify-end gap-3">
                            <button onClick={() => blocker.proceed()} className="px-4 py-2 text-red-500 font-medium hover:bg-primary-border rounded-lg">Verlaten</button>
                            <button onClick={() => blocker.reset()} className="px-4 py-2 bg-primary-accent text-white font-medium rounded-lg">Blijven</button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}