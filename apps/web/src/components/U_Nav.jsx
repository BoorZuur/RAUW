import React, { useState } from 'react';
import { useLocation, Link, useNavigate } from 'react-router-dom';
import { Map, Megaphone, Newspaper, LayoutGrid, Bell, User, Moon, Sun, X } from 'lucide-react';
import RauwLogoImg from '../assets/LogoRAUW.png';

export default function Navbar({ currentTheme, toggleTheme }) {
    const location = useLocation();
    const navigate = useNavigate();
    const [showNotifications, setShowNotifications] = useState(false);

    const isActive = (path) => location.pathname === path;

    const tabs = [
        { name: 'Feed', path: '/feed', icon: LayoutGrid },
        { name: 'Nieuws', path: '/nieuws', icon: Newspaper },
        { name: 'Melden', path: '/meld', icon: Megaphone },
        { name: 'Kaart', path: '/map', icon: Map }
    ];

    return (
        <nav className="fixed top-6 left-6 right-6 z-50 flex items-center justify-between px-8 py-4 rounded-3xl border border-primary-border shadow-sm backdrop-blur-xl bg-primary-bg-cards/80 transition-all duration-500">

            {/* Logo */}
            <div className="cursor-pointer shrink-0 transition-transform hover:scale-102" onClick={() => navigate('/')}>
                <img src={RauwLogoImg} alt="RAUW Logo" className="h-12 w-auto object-contain" />
            </div>

            {/* Tabs */}
            <div className="flex items-center gap-1">
                {tabs.map((tab) => (
                    <Link key={tab.name} to={tab.path}
                          className={`flex items-center gap-2.5 px-5 py-2.5 rounded-2xl transition-all duration-300 font-medium text-sm
                          ${isActive(tab.path)
                              ? 'bg-primary-border text-primary-text'
                              : 'bg-transparent text-primary-text opacity-60 hover:opacity-100'}`}>
                        <tab.icon className="w-4 h-4" />
                        <span>{tab.name}</span>
                    </Link>
                ))}
            </div>

            {/* Utilities */}
            <div className="flex items-center gap-2 text-primary-text">
                <button onClick={toggleTheme} className="p-3 rounded-2xl hover:bg-black/5 dark:hover:bg-white/5 transition-all">
                    {currentTheme === 'dark' ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                </button>

                <div className="relative">
                    <button onClick={() => setShowNotifications(!showNotifications)} className="p-3 rounded-2xl hover:bg-black/5 dark:hover:bg-white/5 transition-all">
                        <Bell className="w-5 h-5" />
                    </button>
                    {showNotifications && (
                        <div className="absolute top-16 right-0 w-72 p-6 rounded-3xl shadow-2xl border border-primary-border bg-primary-bg-cards animate-in fade-in zoom-in-95 duration-200">
                            <div className="flex justify-between items-center mb-4">
                                <span className="font-bold text-base text-primary-text">Meldingen</span>
                                <button onClick={() => setShowNotifications(false)} className="p-1 hover:bg-black/5 rounded-full"><X className="w-4 h-4" /></button>
                            </div>
                            <p className="text-sm text-secondary-text">Geen nieuwe berichten.</p>
                        </div>
                    )}
                </div>

                <button onClick={() => navigate('/account')} className="p-3 rounded-2xl hover:bg-black/5 dark:hover:bg-white/5 transition-all">
                    <User className="w-5 h-5" />
                </button>
            </div>
        </nav>
    );
}