import React, { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { navLinks } from '../config/navConfig.js';
import axios from 'axios';
import { useTheme } from '../ThemeContext.jsx';
import { LogOut, Sun, Moon, Play, CheckCircle, Menu, X } from 'lucide-react';
import RauwLogoImg from "../assets/LogoRAUW.png";

export default function HM_Nav({ role = 'handhaver' }) {
    const location = useLocation();
    const { isDark, toggleTheme } = useTheme();
    const [isHubActive, setIsHubActive] = useState(false);
    const [startingShift, setStartingShift] = useState(false);
    const [shiftError, setShiftError] = useState("");

    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    useEffect(() => {
        setIsMobileMenuOpen(false);
    }, [location]);

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
        <>
            <header className="md:hidden sticky top-0 w-full h-16 bg-primary-bg-cards border-b border-primary-border flex items-center justify-between px-4 z-50 shadow-sm font-label">
                <div className="flex items-center gap-3">
                    <img src={RauwLogoImg} alt="RAUW Logo" translate="no" className="h-9 w-auto object-contain"/>
                    <div>
                        <h2 className="font-bold text-sm leading-none text-primary-text">RAUW</h2>
                        <p className="text-[8px] text-secondary-text tracking-wider uppercase font-bold mt-0.5">
                            {role === 'manager' ? 'Management' : 'Handhaving'}
                        </p>
                    </div>
                </div>

                <button
                    onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                    className="p-2 text-primary-text hover:bg-primary-bg border border-transparent hover:border-primary-border rounded-xl transition-colors cursor-pointer"
                    aria-label="Menu handhaving openen"
                >
                    {isMobileMenuOpen ? <X size={22} /> : <Menu size={22} />}
                </button>
            </header>

            {isMobileMenuOpen && (
                <div className="md:hidden fixed inset-x-0 bottom-0 top-16 bg-primary-bg-cards/95 backdrop-blur-md z-40 flex flex-col justify-between p-6 overflow-y-auto font-label border-t border-primary-border animate-fade-in">
                    <nav className="space-y-1.5">
                        {filteredLinks.map((link) => {
                            const isActive = location.pathname === link.to;
                            const Icon = link.icon;
                            return (
                                <Link
                                    key={link.id}
                                    to={link.to}
                                    className={`flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all ${
                                        isActive
                                            ? 'bg-primary-accent text-white shadow-md'
                                            : 'text-secondary-text hover:bg-primary-bg hover:text-primary-text'
                                    }`}
                                >
                                    <Icon className="w-5 h-5" strokeWidth={2} />
                                    <span className="font-semibold text-sm">{link.title}</span>
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="space-y-5 pt-6 border-t border-primary-border/60 mt-6">
                        {/* Dag / Nacht switch */}
                        <div className="flex bg-primary-bg p-1 rounded-xl border border-primary-border">
                            <button onClick={toggleTheme} className={`flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-bold transition-all ${!isDark ? 'bg-primary-bg-cards shadow-sm text-primary-text' : 'text-secondary-text'}`}>
                                <Sun size={14} /> Dag
                            </button>
                            <button onClick={toggleTheme} className={`flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-bold transition-all ${isDark ? 'bg-primary-bg-cards shadow-sm text-primary-text' : 'text-secondary-text'}`}>
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
                                    <button onClick={handleStartShift} disabled={startingShift} className="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-primary-accent text-white font-black uppercase tracking-wider text-xs hover:brightness-105 transition-all shadow-sm">
                                        <Play size={14} fill="currentColor" /> {startingShift ? "Starten..." : "Start Mijn Shift"}
                                    </button>
                                )}
                                {shiftError && <p className="text-red-500 text-[10px] mt-2 text-center">{shiftError}</p>}
                            </div>
                        )}

                        {/* Uitloggen */}
                        <Link to="/loginhandhaver" className="flex items-center justify-center gap-3 text-secondary-text hover:text-red-500 bg-primary-bg/50 border border-primary-border/40 py-3 rounded-xl transition-colors w-full">
                            <LogOut size={16} />
                            <span className="font-bold text-xs uppercase tracking-wider">Uitloggen</span>
                        </Link>
                    </div>
                </div>
            )}

            <section className="hidden md:flex h-screen w-72 bg-primary-bg-cards border-r border-primary-border flex-col py-6 sticky top-0 shadow-xl z-20 transition-colors duration-300 font-label shrink-0">
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

                <div className="px-6 space-y-6">
                    <div className="flex items-center justify-between bg-primary-bg p-2 rounded-xl border border-primary-border">
                        <button onClick={toggleTheme} className={`flex-1 flex items-center justify-center gap-2 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer ${!isDark ? 'bg-primary-border shadow-sm text-primary-text' : 'text-secondary-text'}`}>
                            <Sun size={14} /> Dag
                        </button>
                        <button onClick={toggleTheme} className={`flex-1 flex items-center justify-center gap-2 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer ${isDark ? 'bg-primary-border shadow-sm text-primary-text' : 'text-secondary-text'}`}>
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
                                <button onClick={handleStartShift} disabled={startingShift} className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-primary-accent text-white font-bold text-sm hover:brightness-110 transition-all cursor-pointer">
                                    <Play size={16} fill="currentColor" /> {startingShift ? "Starten..." : "Start Shift"}
                                </button>
                            )}
                            {shiftError && <p className="text-red-500 text-[10px] mt-2 text-center">{shiftError}</p>}
                        </div>
                    )}

                    <Link to="/loginhandhaver" className="flex items-center gap-3 text-secondary-text hover:text-red-500 transition-colors w-full px-2 py-1">
                        <LogOut size={18} />
                        <span className="font-semibold text-sm">Uitloggen</span>
                    </Link>
                </div>
            </section>
        </>
    );
}