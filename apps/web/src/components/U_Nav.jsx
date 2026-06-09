import React, { useState, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Bell, Map, Megaphone, Moon, Newspaper, Sun, User, X } from 'lucide-react';
import RauwLogoImg from '../assets/LogoRAUW.png';
import { useTheme } from '../ThemeContext.jsx';
import GoogleTranslator from './GoogleTranslator.jsx';


export default function Navbar() {
    const { isDark, toggleTheme } = useTheme();
    const location = useLocation();
    const navigate = useNavigate();
    const [showNotifications, setShowNotifications] = useState(false);

    // Google Translate Script dynamisch inladen via useEffect
    useEffect(() => {
        if (!document.getElementById('google-translate-script')) {
            window.googleTranslateElementInit = () => {
                new window.google.translate.TranslateElement(
                    {
                        pageLanguage: 'nl', // Je database/app basistaal is Nederlands
                        layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE,
                    },
                    'google_translate_element'
                );
            };

            const script = document.createElement('script');
            script.id = 'google-translate-script';
            script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
            script.async = true;
            document.body.appendChild(script);
        }
    }, []);

    const isActive = (path) => location.pathname === path;

    const tabs = [
        { name: 'Nieuws', path: '/nieuws', icon: Newspaper },
        { name: 'Melden', path: '/meld', icon: Megaphone },
        { name: 'Kaart', path: '/map', icon: Map }
    ];

    return (
        <nav
            className="fixed top-6 left-6 right-6 z-50 flex items-center justify-between px-8 py-4 rounded-3xl border border-primary-border shadow-sm backdrop-blur-xl bg-primary-bg-cards/90 transition-all duration-500"
            role="navigation"
            aria-label="Hoofdnavigatie"
        >

            {/* Logo */}
            <div className="cursor-pointer shrink-0 transition-transform hover:scale-102"
                 onClick={() => navigate('/feed')}
                 role="button"
                 aria-label="Ga naar de homepage">
                <img src={RauwLogoImg} alt="RAUW Logo" translate="no" className="h-12 w-auto object-contain"/>
            </div>

            {/* Tabs */}
            <div className="flex items-center gap-1">
                {tabs.map((tab) => (
                    <Link key={tab.name} to={tab.path}
                          aria-current={isActive(tab.path) ? 'page' : undefined}
                          className={`flex items-center gap-2.5 px-5 py-2.5 rounded-2xl transition-all duration-300 font-bold text-sm
                          ${isActive(tab.path)
                              ? 'bg-primary-border text-primary-text'
                              : 'bg-transparent text-secondary-text hover:text-primary-text hover:bg-black/5 dark:hover:bg-white/5'}`}>
                        <tab.icon className="w-4 h-4"/>
                        <span>{tab.name}</span>
                    </Link>
                ))}
            </div>

            {/* Utilities */}
            <div className="flex items-center gap-2 text-primary-text">

                {/* GOOGLE TRANSLATE KNOP */}
                <GoogleTranslator />

                <button onClick={toggleTheme}
                        aria-label={isDark ? "Schakel over naar lichte modus" : "Schakel over naar donkere modus"}
                        className="p-3 rounded-2xl hover:bg-black/10 dark:hover:bg-white/10 transition-all">
                    {isDark ? <Sun className="w-5 h-5"/> : <Moon className="w-5 h-5"/>}
                </button>

                <div className="relative">
                    <button onClick={() => setShowNotifications(!showNotifications)}
                            aria-label="Open meldingen"
                            aria-expanded={showNotifications}
                            className="p-3 rounded-2xl hover:bg-black/10 dark:hover:bg-white/10 transition-all">
                        <Bell className="w-5 h-5"/>
                    </button>
                    {showNotifications && (
                        <div
                            className="absolute top-16 right-0 w-72 p-6 rounded-3xl shadow-2xl border border-primary-border bg-primary-bg-cards animate-in fade-in zoom-in-95 duration-200">
                            <div className="flex justify-between items-center mb-4">
                                <span className="font-bold text-base text-primary-text">Meldingen</span>
                                <button onClick={() => setShowNotifications(false)}
                                        aria-label="Sluit meldingen"
                                        className="p-1 hover:bg-black/10 rounded-full"><X className="w-4 h-4"/></button>
                            </div>
                            <p className="text-sm text-secondary-text">Geen nieuwe berichten.</p>
                        </div>
                    )}
                </div>

                <button onClick={() => navigate('/account')}
                        aria-label="Ga naar accountinstellingen"
                        className="p-3 rounded-2xl hover:bg-black/10 dark:hover:bg-white/10 transition-all">
                    <User className="w-5 h-5"/>
                </button>
            </div>
        </nav>
    );
}