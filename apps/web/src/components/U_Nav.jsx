import React, { useState, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { MessageCircle, Map, Megaphone, Moon, Newspaper, Sun, User, Menu, X } from 'lucide-react';
import RauwLogoImg from '../assets/LogoRAUW.png';
import { useTheme } from '../ThemeContext.jsx';
import GoogleTranslator from './GoogleTranslator.jsx';
import NotificationTray from './NotificationTray.jsx';

export default function Navbar() {
    const { isDark, toggleTheme } = useTheme();
    const location = useLocation();
    const navigate = useNavigate();

    // FIX: State voor het openen/sluiten van het mobiele menu
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    useEffect(() => {
        if (!document.getElementById('google-translate-script')) {
            window.googleTranslateElementInit = () => {
                new window.google.translate.TranslateElement(
                    {
                        pageLanguage: 'nl',
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

    // FIX: Sluit het menu automatisch zodra de gebruiker naar een andere pagina navigeert
    useEffect(() => {
        setIsMenuOpen(false);
    }, [location.pathname]);

    const isActive = (path) => location.pathname === path;

    const tabs = [
        { name: 'Nieuws', path: '/nieuws', icon: Newspaper },
        { name: 'Melden', path: '/meld', icon: Megaphone },
        { name: 'Kaart', path: '/map', icon: Map },
        { name: 'Chat', path: '/chat', icon: MessageCircle },
    ];

    return (
        <header className="font-label antialiased">

            {/* ==================== MOBIELE TOP BAR ==================== */}
            <div className="md:hidden fixed top-0 inset-x-0 h-16 bg-primary-bg-cards/90 backdrop-blur-md border-b border-primary-border px-6 flex items-center justify-between z-50 shadow-sm">
                {/* Logo links */}
                <div className="shrink-0 cursor-pointer" onClick={() => navigate('/feed')} role="button" aria-label="Ga naar homepage">
                    <img src={RauwLogoImg} alt="RAUW Logo" translate="no" className="h-9 w-auto object-contain"/>
                </div>

                {/* Hamburger Toggle Knop */}
                <button
                    onClick={() => setIsMenuOpen(!isMenuOpen)}
                    aria-label={isMenuOpen ? "Menu sluiten" : "Menu openen"}
                    className="p-2 -mr-2 rounded-xl text-primary-text hover:bg-black/5 dark:hover:bg-white/5 active:scale-95 transition-all focus:outline-none"
                >
                    {isMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
                </button>
            </div>

            {/* ==================== MOBIEL HAMBURGER MENU OVERLAY ==================== */}
            <div className={`md:hidden fixed inset-0 top-16 bg-primary-bg/98 backdrop-blur-lg z-40 flex flex-col justify-between p-6 transition-all duration-300 ease-in-out ${
                isMenuOpen ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-full pointer-events-none'
            }`}>
                {/* Navigatie Links */}
                <nav className="flex flex-col gap-3 mt-4" aria-label="Mobiele hamburger navigatie">
                    {tabs.map((tab) => {
                        const active = isActive(tab.path);
                        return (
                            <Link
                                key={tab.name}
                                to={tab.path}
                                aria-current={active ? 'page' : undefined}
                                className={`flex items-center gap-4 h-14 px-5 rounded-2xl font-bold text-base transition-all ${
                                    active
                                        ? 'bg-primary-border text-primary-text'
                                        : 'text-secondary-text hover:text-primary-text hover:bg-black/5 dark:hover:bg-white/5'
                                }`}
                            >
                                <tab.icon className="w-5 h-5" strokeWidth={active ? 2.5 : 2} />
                                <span>{tab.name}</span>
                            </Link>
                        );
                    })}
                </nav>

                {/* Mobiele Utilities & Instellingen (Onderaan het menu geplaatst voor duimbereik) */}
                <div className="border-t border-primary-border pt-6 pb-safe flex flex-col gap-4">
                    <div className="flex items-center justify-between bg-primary-bg-cards p-3 rounded-2xl border border-primary-border">
                        <span className="text-sm font-bold text-secondary-text pl-2">Taal selecteren</span>
                        <GoogleTranslator />
                    </div>

                    <div className="flex items-center justify-around bg-primary-bg-cards py-2 rounded-2xl border border-primary-border text-primary-text">
                        <button onClick={toggleTheme} aria-label="Thema wisselen" className="flex flex-col items-center justify-center flex-1 py-2 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 gap-1 text-xs font-bold text-secondary-text">
                            {isDark ? <Sun className="w-5 h-5 text-primary-text"/> : <Moon className="w-5 h-5 text-primary-text"/>}
                            <span>{isDark ? 'Licht' : 'Donker'}</span>
                        </button>

                        <div className="w-px h-8 bg-primary-border"></div>

                        <div className="flex flex-col items-center justify-center flex-1 py-1 gap-1 text-xs font-bold text-secondary-text">
                            <NotificationTray />
                            <span className="mt-0.5">Updates</span>
                        </div>

                        <div className="w-px h-8 bg-primary-border"></div>

                        <button onClick={() => navigate('/account')} aria-label="Account" className="flex flex-col items-center justify-center flex-1 py-2 rounded-xl hover:bg-black/5 dark:hover:bg-white/5 gap-1 text-xs font-bold text-secondary-text">
                            <User className="w-5 h-5 text-primary-text"/>
                            <span>Account</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* ==================== DESKTOP NAVIGATIE ==================== */}
            <nav
                className="hidden md:flex fixed top-6 left-6 right-6 z-50 items-center justify-between px-8 py-4 rounded-3xl border border-primary-border shadow-md backdrop-blur-xl bg-primary-bg-cards/90 transition-all duration-500 max-w-7xl mx-auto"
                role="navigation"
                aria-label="Hoofdnavigatie desktop"
            >
                <div className="cursor-pointer shrink-0 transition-transform hover:scale-102"
                     onClick={() => navigate('/feed')}
                     role="button"
                     aria-label="Ga naar de homepage">
                    <img src={RauwLogoImg} alt="RAUW Logo" translate="no" className="h-12 w-auto object-contain"/>
                </div>

                <div className="flex items-center gap-1">
                    {tabs.map((tab) => (
                        <Link key={tab.name} to={tab.path}
                              aria-current={isActive(tab.path) ? 'page' : undefined}
                              className={`flex items-center gap-2.5 px-5 py-2.5 rounded-2xl transition-all duration-300 font-bold text-sm
                              ${isActive(tab.path)
                                  ? 'bg-primary-border text-primary-text shadow-sm'
                                  : 'bg-transparent text-secondary-text hover:text-primary-text hover:bg-black/5 dark:hover:bg-white/5'}`}>
                            <tab.icon className="w-4 h-4"/>
                            <span>{tab.name}</span>
                        </Link>
                    ))}
                </div>

                <div className="flex items-center gap-2 text-primary-text">
                    <GoogleTranslator />

                    <button onClick={toggleTheme}
                            aria-label={isDark ? "Schakel over naar lichte modus" : "Schakel over naar donkere modus"}
                            className="p-3 rounded-2xl hover:bg-black/10 dark:hover:bg-white/10 transition-all cursor-pointer">
                        {isDark ? <Sun className="w-5 h-5"/> : <Moon className="w-5 h-5"/>}
                    </button>

                    <NotificationTray />

                    <button onClick={() => navigate('/account')}
                            aria-label="Ga naar accountinstellingen"
                            className="p-3 rounded-2xl hover:bg-black/10 dark:hover:bg-white/10 transition-all cursor-pointer">
                        <User className="w-5 h-5"/>
                    </button>
                </div>
            </nav>
        </header>
    );
}