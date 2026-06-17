import React, { useState, useEffect } from 'react';
import {Link, useLocation} from 'react-router-dom';
import {navLinks} from '../config/navConfig.js';
import axios from 'axios';
import "./HM_styling.css";

export default function HM_Nav({role = 'handhaver'}) {
    const location = useLocation();
    const [isHubActive, setIsHubActive] = useState(false);
    const [startingShift, setStartingShift] = useState(false);
    const [shiftError, setShiftError] = useState("");

    useEffect(() => {
        if (role === 'handhaver') {
            const checkShiftStatus = async () => {
                try {
                    const token = localStorage.getItem('auth_token');
                    if (!token) return;
                    
                    const res = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me`, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    const profile = res.data?.profile || res.data;
                    setIsHubActive(profile.hub_active === true);
                } catch (err) {
                    console.error("Failed to fetch shift status", err);
                }
            };
            checkShiftStatus();
        }
    }, [role]);

    const handleStartShift = () => {
        setStartingShift(true);
        setShiftError("");
        
        if (!navigator.geolocation) {
            setShiftError("Geolocatie wordt niet ondersteund");
            setStartingShift(false);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                try {
                    const token = localStorage.getItem('auth_token');
                    await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/auth/start-shift`, {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    }, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    setIsHubActive(true);
                } catch (err) {
                    console.error(err);
                    setShiftError(err.response?.data?.message || "Kan shift niet starten");
                    setTimeout(() => setShiftError(""), 5000);
                } finally {
                    setStartingShift(false);
                }
            },
            (err) => {
                console.error(err);
                setShiftError("Locatietoegang geweigerd");
                setStartingShift(false);
                setTimeout(() => setShiftError(""), 5000);
            }
        );
    };

    // Filter de links zodat alleen de links voor de huidige rol getoond worden
    const filteredLinks = navLinks.filter(link => link.roles.includes(role));

    return (
        <section className="sidebar">
            <div className="logo-container">
                <div className="logo-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24">
                        <path fill="currentColor"
                              d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm0-12c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z"/>
                    </svg>
                </div>
                <div className="logo-text">
                    <h2>RAUW</h2>
                    <p>{role === 'manager' ? 'MANAGEMENT' : 'BOA COMMAND'}</p>
                </div>
            </div>
            <nav className="nav-menu">
                {filteredLinks.map((link) => {
                    const isActive = location.pathname === link.to;
                    return (
                        <Link
                            key={link.id}
                            to={link.to}
                            className={`nav-item ${isActive ? 'active' : ''}`}
                        >
                            <svg className="nav-icon" viewBox="0 0 24 24">
                                <path fill="currentColor" d={link.iconPath}/>
                            </svg>
                            <span>{link.title}</span>
                            {isActive && <span className="dot-indicator"></span>}
                        </Link>
                    );
                })}
            </nav>

            <hr/>
            <div className="sidebar-footer">
                <div className="toggle-container">
                    <span className="toggle-label">Dagmodus</span>
                    <label className="switch">
                        <input type="checkbox" id="mode-toggle"/>
                        <span className="slider"></span>
                    </label>
                </div>

                {role === 'handhaver' && (
                    <div className="px-3">
                        {shiftError && <p className="text-red-500 text-xs mb-2 text-center">{shiftError}</p>}
                        {isHubActive ? (
                            <div className="flex items-center justify-center gap-2 py-2 px-4 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-sm border border-emerald-200">
                                <span className="relative flex h-3 w-3">
                                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span className="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                Shift Actief
                            </div>
                        ) : (
                            <button 
                                onClick={handleStartShift} 
                                disabled={startingShift}
                                className="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition-colors disabled:opacity-50"
                            >
                                <svg viewBox="0 0 24 24" width="16" height="16">
                                    <path fill="currentColor" d="M8 5v14l11-7z"/>
                                </svg>
                                {startingShift ? "Starten..." : "Start Shift"}
                            </button>
                        )}
                    </div>
                )}

                <Link to="/handhaver_login" className="nav-item logout-btn">
                    <svg className="nav-icon" viewBox="0 0 24 24">
                        <path fill="currentColor"
                              d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                    </svg>
                    <span>Uitloggen</span>
                </Link>
            </div>
        </section>
    );
}