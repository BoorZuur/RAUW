import React, { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { navLinks } from '../config/navConfig.js';
import axios from 'axios';
import { useTheme } from '../ThemeContext.jsx';
import { LogOut, Sun, Moon, Play, CheckCircle } from 'lucide-react';
import RauwLogoImg from "../assets/LogoRAUW.png";

export default function HM_Nav({ role = 'handhaver' }) {
    const location = useLocation();
    const { isDark, toggleTheme } = useTheme();
    const [isHubActive, setIsHubActive] = useState(false);
    const [startingShift, setStartingShift] = useState(false);
    const [shiftError, setShiftError] = useState("");

    // Dark Mode class toevoegen aan html voor Tailwind dark-mode
    useEffect(() => {
        document.documentElement.classList.toggle('dark', isDark);
    }, [isDark]);

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
                    setShiftError(err.response?.data?.message || "Kan shift niet starten");
                    setTimeout(() => setShiftError(""), 5000);
                } finally {
                    setStartingShift(false);
                }
            },
            () => {
                setShiftError("Locatietoegang geweigerd");
                setStartingShift(false);
                setTimeout(() => setShiftError(""), 5000);
            }
        );
    };

    const filteredLinks = navLinks.filter(link => link.roles.includes(role));

    return (
        <section className="h-screen w-72 bg-primary-bg-cards border-r border-primary-border flex flex-col py-6 sticky top-0 shadow-xl z-20 transition-colors duration-300 font-label">
            {/* Logo Section */}
            <div className="px-8 mb-10">
                <div className="flex items-center gap-3">
                    <div className="cursor-pointer shrink-0 transition-transform hover:scale-102">
                        <img src={RauwLogoImg} alt="RAUW Logo" translate="no" className="h-12 w-auto object-contain"/>
                    </div>
                    <div>
                        <h2 className="font-bold text-lg leading-tight tracking-tight text-primary-text">RAUW</h2>
                        <p className="text-[9px] text-secondary-text tracking-[0.2em] uppercase font-bold">{role === 'manager' ? 'Management' : 'Handhaving'}</p>
                    </div>
                </div>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-4 space-y-1">
                {filteredLinks.map((link) => {
                    const isActive = location.pathname === link.to;
                    const Icon = link.icon;
                    return (
                        <Link
                            key={link.id}
                            to={link.to}
                            className={`group flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all duration-300 ${
                                isActive
                                    ? 'bg-primary-accent text-white shadow-md'
                                    : 'text-secondary-text hover:bg-primary-border/50 hover:text-primary-text'
                            }`}
                        >
                            <Icon className="w-5 h-5" strokeWidth={2} />
                            <span className="font-medium text-sm">{link.title}</span>
                        </Link>
                    );
                })}
            </nav>

            {/* Footer */}
            <div className="px-6 space-y-6">
                <div className="flex items-center justify-between bg-primary-bg p-2 rounded-xl border border-primary-border">
                    <button onClick={toggleTheme} className={`flex-1 flex items-center justify-center gap-2 py-1.5 rounded-lg text-xs font-bold transition-all ${!isDark ? 'bg-primary-border shadow-sm text-primary-text' : 'text-secondary-text'}`}>
                        <Sun size={14} /> Dag
                    </button>
                    <button onClick={toggleTheme} className={`flex-1 flex items-center justify-center gap-2 py-1.5 rounded-lg text-xs font-bold transition-all ${isDark ? 'bg-primary-border shadow-sm text-primary-text' : 'text-secondary-text'}`}>
                        <Moon size={14} /> Nacht
                    </button>
                </div>

                {role === 'handhaver' && (
                    <div className="relative">
                        {isHubActive ? (
                            <div className="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-secondary-accent/10 border border-secondary-accent/20 text-secondary-accent text-xs font-bold">
                                <CheckCircle size={16} /> Shift Actief
                            </div>
                        ) : (
                            <button onClick={handleStartShift} disabled={startingShift} className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-primary-accent text-white font-bold text-sm hover:brightness-110 transition-all">
                                <Play size={16} fill="currentColor" /> {startingShift ? "Starten..." : "Start Shift"}
                            </button>
                        )}
                        {shiftError && <p className="text-red-500 text-[10px] mt-2 text-center">{shiftError}</p>}
                    </div>
                )}

                <Link to="/handhaver_login" className="flex items-center gap-3 text-secondary-text hover:text-red-500 transition-colors w-full px-2">
                    <LogOut size={18} />
                    <span className="font-semibold text-sm">Uitloggen</span>
                </Link>
            </div>
        </section>
    );
}