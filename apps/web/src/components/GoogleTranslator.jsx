import React, { useState, useEffect, useRef } from 'react';
import { Languages, ChevronDown } from 'lucide-react';

export default function GoogleTranslator() {
    const [isOpen, setIsOpen] = useState(false);
    const [currentLang, setCurrentLang] = useState('Nederlands');
    const dropdownRef = useRef(null);

    const languages = [
        // Je originele basistalen
        { code: 'nl', name: 'Nederlands' },
        { code: 'en', name: 'English' },
        { code: 'es', name: 'Español' },
        { code: 'de', name: 'Deutsch' },
        { code: 'fr', name: 'Français' },
        { code: 'zh-CN', name: '中文' },

        // Arabische & Berbertalen
        { code: 'ar', name: 'العربية' },
        { code: 'ber', name: 'Tamaziɣt' },

        // Turkse talen (Hoofdtaal + familieleden die Google ondersteunt)
        { code: 'tr', name: 'Türkçe' },
        { code: 'az', name: 'Azərbaycan' },
        { code: 'uz', name: 'Oʻzbekcha' },
        { code: 'kk', name: 'Қазақ тілі' },

        // Oost-Europa (Pools & Russisch)
        { code: 'pl', name: 'Polski' },
        { code: 'ru', name: 'Русский' },

        // Caribisch & Nederlandse streektalen
        { code: 'pap', name: 'Papiamentu)' },
        { code: 'fy', name: 'Frysk (Fries)' },
        { code: 'li', name: 'Limburgs' },

        // Extra veelgevraagde talen in Nederland (Tip)
        { code: 'uk', name: 'Українська' },
        { code: 'tr', name: 'Tigrinya' }
    ];

    // Functie om een cookie te lezen
    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    };

    useEffect(() => {
        const googleCookie = getCookie('googtrans');
        if (googleCookie) {
            const langCode = googleCookie.split('/').pop();
            const foundLang = languages.find(l => l.code === langCode);
            if (foundLang) setCurrentLang(foundLang.name);
        }

        if (!document.getElementById('google-translate-script')) {
            window.googleTranslateElementInit = () => {
                new window.google.translate.TranslateElement(
                    {
                        pageLanguage: 'nl',
                        layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE,
                        autoDisplay: false
                    },
                    'hidden_google_element'
                );
            };

            const script = document.createElement('script');
            script.id = 'google-translate-script';
            script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
            script.async = true;
            document.body.appendChild(script);
        }

        // Een simpele interval die elke 200ms checkt of Google de body naar beneden drukt
        // Dit blokkeert de vertaalmotor niet, maar herstelt wel direct je layout
        const layoutFixer = setInterval(() => {
            if (document.body && document.body.style.top !== '0px' && document.body.style.top !== '') {
                document.body.style.setProperty('top', '0px', 'important');
            }
            if (document.documentElement && document.documentElement.style.top !== '0px' && document.documentElement.style.top !== '') {
                document.documentElement.style.setProperty('top', '0px', 'important');
            }
        }, 200);

        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);

        return () => {
            clearInterval(layoutFixer);
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const handleLanguageChange = (lang) => {
        setCurrentLang(lang.name);
        setIsOpen(false);
        const cookieValue = `/nl/${lang.code}`;

        document.cookie = `googtrans=${cookieValue}; path=/;`;
        document.cookie = `googtrans=${cookieValue}; path=/; domain=.${window.location.hostname};`;

        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            document.cookie = `googtrans=${cookieValue}; path=/; domain=;`;
        }

        window.location.reload();
    };

    return (
        <div className="relative" ref={dropdownRef}>
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="flex items-center gap-2 p-3 rounded-2xl hover:bg-black/10 dark:hover:bg-white/10 transition-all font-bold text-sm"
                aria-label="Wissel van taal"
            >
                <Languages className="w-5 h-5" />
                <span className="hidden md:inline text-xs opacity-80">{currentLang}</span>
                <ChevronDown className={`w-3 h-3 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} />
            </button>

            {isOpen && (
                <div className="absolute top-16 right-0 w-48 p-2 rounded-3xl shadow-2xl border border-primary-border bg-primary-bg-cards animate-in fade-in zoom-in-95 duration-200 z-50">
                    <div className="max-h-60 overflow-y-auto custom-scrollbar">
                        {languages.map((lang) => (
                            <button
                                key={lang.code}
                                onClick={() => handleLanguageChange(lang)}
                                className={`w-full text-left px-4 py-2.5 rounded-xl text-sm font-semibold transition-all
                                    ${currentLang === lang.name
                                    ? 'bg-primary-border text-primary-text'
                                    : 'text-secondary-text hover:text-primary-text hover:bg-black/5 dark:hover:bg-white/5'
                                }`}
                            >
                                {lang.name}
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <div id="hidden_google_element" style={{ display: 'none' }} />
        </div>
    );
}