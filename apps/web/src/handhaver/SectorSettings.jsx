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
                    headers: {'Authorization': `Bearer ${token}`}
                });
                const hubsData = hubsRes.data.data || hubsRes.data;
                setAllHubs(Array.isArray(hubsData) ? hubsData : []);

                // Fetch all districts
                const distRes = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/districts`, {
                    headers: {'Authorization': `Bearer ${token}`}
                });
                const districtsData = distRes.data.data || distRes.data;
                setAllDistricts(Array.isArray(districtsData) ? districtsData : []);

                // Fetch my profile to get assigned districts
                const meRes = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me`, {
                    headers: {'Authorization': `Bearer ${token}`}
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
                    headers: {'Authorization': `Bearer ${token}`}
                });
                setInitialHubId(selectedHubId);
            }

            await axios.patch(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me/districts`, {
                district_ids: selectedDistricts
            }, {
                headers: {'Authorization': `Bearer ${token}`}
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
        ({currentLocation, nextLocation}) =>
            isDirty && currentLocation.pathname !== nextLocation.pathname
    );

    return (
        <>
            <div
                className="flex flex-col md:flex-row min-h-screen bg-primary-bg font-label antialiased text-primary-text">
                <HM_Nav></HM_Nav>

                <main className="flex-1 p-4 sm:p-6 md:p-8 w-full max-w-7xl mx-auto flex flex-col gap-6">

                    <header
                        className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-2 border-b border-primary-border/50 sm:border-none">
                        <div className="flex items-center gap-4 w-full sm:w-auto">
                            <div
                                className="p-3 bg-primary-bg-cards rounded-xl border border-primary-border text-primary-text shrink-0">
                                <Settings className="w-6 h-6"/>
                            </div>
                            <div className="min-w-0">
                                <h1 className="text-xl sm:text-2xl font-bold font-headline text-primary-text truncate">Sector
                                    Instellingen</h1>
                                <p className="text-secondary-text text-sm truncate">Selecteer jouw zorgwijken in
                                    Rotterdam</p>
                                {message &&
                                    <p className="text-xs sm:text-sm mt-1 text-emerald-600 font-medium animate-fade-in">{message}</p>}
                            </div>
                        </div>

                        <button
                            className="flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3 sm:py-2 bg-primary-accent text-white rounded-xl font-black uppercase tracking-wider text-sm relative transition-all shadow-md active:scale-[0.98] cursor-pointer"
                            onClick={handleSave}
                            disabled={saving}
                        >
                            {isDirty && !saving && (
                                <span className="absolute -top-1 -right-1 flex h-3 w-3">
                                <span
                                    className="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                            </span>
                            )}
                            {saving ? "Opslaan..." : "Opslaan"}
                        </button>
                    </header>

                    <div
                        className="bg-primary-bg-cards border border-primary-border p-4 rounded-xl flex items-center gap-3 shadow-sm">
                        <Pin className="w-5 h-5 text-primary-text shrink-0"/>
                        <p className="text-primary-text text-sm sm:text-base">
                            <strong id="selected-count" className="font-bold">{selectedDistricts.length}</strong>
                            van {allDistricts.length || 74} Rotterdamse wijken geselecteerd
                        </p>
                    </div>

                    <section
                        className="bg-primary-bg-cards border border-primary-border p-5 sm:p-6 rounded-2xl shadow-sm">
                        <h2 className="font-bold font-headline text-primary-text mb-1">Jouw Hub</h2>
                        <p className="text-secondary-text text-xs sm:text-sm mb-3">Kies de hoofdlocatie van waaruit je
                            werkt.</p>
                        <select
                            className="w-full border border-primary-border rounded-lg p-3 text-sm text-primary-text bg-primary-bg focus:outline-none focus:border-primary-accent cursor-pointer appearance-none"
                            value={selectedHubId || ''}
                            onChange={handleHubChange}
                        >
                            <option value="" disabled>Selecteer een hub</option>
                            {allHubs.map(hub => (
                                <option key={hub.id} value={hub.id}>{hub.name}</option>
                            ))}
                        </select>
                    </section>

                    <section
                        className="bg-primary-bg-cards border border-primary-border p-5 sm:p-6 rounded-2xl shadow-sm flex-1">
                        <h2 className="font-bold font-headline text-primary-text mb-1">Alle Wijken Rotterdam</h2>
                        <p className="text-secondary-text text-xs sm:text-sm mb-4">Klik op een wijk om deze toe te
                            voegen of te verwijderen</p>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                            {loading ? (
                                <p className="text-secondary-text text-sm font-body">Gegevens laden...</p>
                            ) : (
                                allDistricts
                                    .filter(d => selectedHubId ? d.hub_id === selectedHubId : true)
                                    .map(district => (
                                        <button
                                            key={district.id}
                                            className={`p-3 rounded-lg border text-xs sm:text-sm font-semibold transition-all text-left sm:text-center break-words cursor-pointer ${
                                                selectedDistricts.includes(district.id)
                                                    ? 'bg-primary-accent text-white border-primary-accent shadow-sm'
                                                    : 'bg-primary-bg border-primary-border text-primary-text hover:bg-primary-bg-cards'
                                            }`}
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
                <div
                    className="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4">
                    <div
                        className="bg-primary-bg-cards p-6 rounded-t-2xl sm:rounded-2xl shadow-xl max-w-md w-full border border-primary-border pb-8 sm:pb-6">
                        <h2 className="text-lg font-bold font-headline text-primary-text mb-2">Hub Wijzigen</h2>
                        <p className="text-sm text-secondary-text mb-6">
                            Weet je zeker dat je van hub wilt wisselen? Dit beëindigt direct je huidige shift en wist je
                            momenteel geselecteerde wijken.
                        </p>
                        <div className="flex flex-col sm:flex-row justify-end gap-2">
                            <button onClick={cancelHubChange}
                                    className="w-full sm:w-auto order-2 sm:order-1 px-4 py-2.5 text-sm text-secondary-text font-semibold hover:bg-primary-bg rounded-lg transition-colors">Annuleren
                            </button>
                            <button onClick={confirmHubChange}
                                    className="w-full sm:w-auto order-1 sm:order-2 px-5 py-2.5 text-sm bg-primary-accent text-white font-bold rounded-lg transition-colors">Bevestigen
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {blocker.state === "blocked" && (
                <div
                    className="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4">
                    <div
                        className="bg-primary-bg-cards p-6 rounded-t-2xl sm:rounded-2xl shadow-xl max-w-md w-full border border-primary-border pb-8 sm:pb-6">
                        <h2 className="text-lg font-bold font-headline text-primary-text mb-2">Onopgeslagen
                            Wijzigingen</h2>
                        <p className="text-sm text-secondary-text mb-6">
                            Je hebt wijzigingen gemaakt die nog niet zijn opgeslagen. Weet je zeker dat je deze pagina
                            wilt verlaten?
                        </p>
                        <div className="flex flex-col sm:flex-row justify-end gap-2">
                            <button onClick={() => blocker.proceed()}
                                    className="w-full sm:w-auto order-2 sm:order-1 px-4 py-2.5 text-sm text-red-500 font-semibold hover:bg-red-500/10 rounded-lg transition-colors">Verlaten
                            </button>
                            <button onClick={() => blocker.reset()}
                                    className="w-full sm:w-auto order-1 sm:order-2 px-5 py-2.5 text-sm bg-primary-accent text-white font-bold rounded-lg transition-colors">Blijven
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}