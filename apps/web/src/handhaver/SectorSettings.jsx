import React, { useState, useEffect } from 'react';
import axios from 'axios';
import "../App.css"
import HM_Nav from "../components/HM_Nav.jsx";
import "./Handhaver_styling.css"

export default function SectorSettings() {
    const [allDistricts, setAllDistricts] = useState([]);
    const [selectedDistricts, setSelectedDistricts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState("");

    useEffect(() => {
        const fetchData = async () => {
            try {
                const token = localStorage.getItem('auth_token');
                
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
                
                setAllDistricts(Array.isArray(districtsData) ? districtsData.filter(d => !officerHubId || d.hub_id === officerHubId) : []);
                setSelectedDistricts(myDistricts.map(d => d.id));
            } catch (error) {
                console.error("Error fetching districts:", error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

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
            await axios.patch(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me/districts`, {
                district_ids: selectedDistricts
            }, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            setMessage("Instellingen succesvol opgeslagen!");
            setTimeout(() => setMessage(""), 3000);
        } catch (error) {
            console.error("Error saving districts:", error);
            setMessage("Fout bij het opslaan van instellingen.");
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <div className="app-layout">
            <HM_Nav></HM_Nav>
            <main className="main-content">
                {/*🌟: the header has the cards in them, we might wanna make these into components*/}
                <header className="page-header">
                    <div className="header-left">
                        <div className="header-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24">
                                <path fill="currentColor"
                                      d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c-.19-.15-.24-.42-.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49-.12-.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3-1.07-3-3.5s1.07-3.5 3-3.5 3 1.07 3 3.5-1.07 3.5-3 3.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h1>Sector Instellingen</h1>
                            <p className="subtitle">Selecteer jouw zorgwijken in Rotterdam</p>
                            {message && <p className={`text-sm mt-1 ${message.includes('succesvol') ? 'text-green-600' : 'text-red-600'}`}>{message}</p>}
                        </div>
                    </div>
                    <button className="btn-save" onClick={handleSave} disabled={saving}>
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor"
                                  d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        {saving ? "Opslaan..." : "Opslaan"}
                    </button>
                </header>

                <div className="counter-banner">
                    <span className="location-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-313-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    </span>
                    <p>
                        <strong id="selected-count">{selectedDistricts.length}</strong> van {allDistricts.length || 74} Rotterdamse wijken geselecteerd als jouw zorggebied
                    </p>
                </div>

                <section className="selection-card">
                    <h2>Alle Wijken Rotterdam</h2>
                    <p className="instruction">Klik op een wijk om deze aan jouw zorggebied toe te voegen of te
                        verwijderen</p>

                    <div className="wijken-grid">
                        {loading ? (
                            <p>Wijken laden...</p>
                        ) : (
                            allDistricts.map(district => (
                                <button 
                                    key={district.id} 
                                    className={`wijk-item ${selectedDistricts.includes(district.id) ? 'selected' : ''}`}
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
        </>
    );
}