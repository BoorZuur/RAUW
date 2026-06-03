import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

import RauwLogoImg from '../assets/LogoRAUW.png';
import BgRotterdam1 from '../assets/BOAachtergrond.jpg';

export default function BoaLogin() {
    const [identifier, setIdentifier] = useState('');
    const [password, setPassword] = useState('');
    const navigate = useNavigate();

    const handleSubmit = (e) => {
        e.preventDefault();
        if (identifier && password) {
            navigate('/dashboard');
        }
    };

    return (
        <div className="h-screen w-full m-0 p-0 flex flex-col md:flex-row bg-(--primary-bg) text-(--primary-text) antialiased select-none overflow-hidden">

            {/* Left Side: Branding and Contextual Information */}
            <div
                className="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-center items-center p-12 bg-no-repeat bg-cover bg-center overflow-hidden transition-all duration-1000 text-center"
                style={{
                    backgroundImage: `linear-gradient(to bottom, rgba(18, 24, 32, 0.85), rgba(18, 24, 32, 0.95)), url('${BgRotterdam1}')`
                }}
            >
                <div className="absolute top-12 left-12 w-20 h-20 flex items-center justify-center z-20">
                    <img src={RauwLogoImg} alt="RAUW Rotterdam" className="w-full h-full object-contain" />
                </div>

                {/* Typography perfectly centered horizontally and vertically inside the column ALSJEBLIEFT RENEE*/}
                <div className="max-w-md z-10 text-(--primary-text-d)">
                    <h1 className="text-4xl lg:text-5xl font-bold tracking-tight leading-[1.15] mb-6 whitespace-pre-line text-balance">
                        Toezicht &<br />Handhaving<br />Rotterdam.
                    </h1>
                    <p className="text-sm lg:text-base text-(--secondary-text-d) leading-relaxed">
                        Log in met je dienstgegevens om toegang te krijgen tot het centrale commandocentrum, actuele incidenten en directe communicatie.
                    </p>
                </div>

                <div className="absolute bottom-12 left-12 text-[11px] text-(--secondary-text-d)/40 tracking-wide z-10">
                    © {new Date().getFullYear()} RAUW COMMAND CENTER
                </div>
            </div>

            {/* Right Side: Professional Verification Form Wrapper */}
            <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-32 bg-(--primary-bg) relative overflow-y-auto md:overflow-hidden">
                <div className="md:hidden absolute top-6 left-6 flex items-center space-x-2">
                    <img src={RauwLogoImg} alt="RAUW" className="w-8 h-8 object-contain" />
                    <span className="text-xl tracking-wider text-(--primary-text)">RAUW</span>
                </div>

                <div className="w-full max-w-sm mx-auto">
                    <div className="mb-8">
                        <h2 className="text-3xl font-black tracking-tight mb-2" style={{ color: 'var(--primary-text)' }}>
                            Inloggen Dienstaccount
                        </h2>
                        <p className="text-sm" style={{ color: 'var(--secondary-text)' }}>
                            Toegang voor BOA's en veiligheidsregisseurs.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">

                        {/* E-mail or Badgenumber */}
                        <div>
                            <div className="flex justify-between items-center mb-1.5">
                                <label className="block text-xs font-bold tracking-wider text-(--primary-text) uppercase">
                                    E-mailadres of Dienstnummer
                                </label>
                            </div>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    required
                                    value={identifier}
                                    onChange={(e) => setIdentifier(e.target.value)}
                                    placeholder="naam@rotterdam.nl of RT-1094"
                                    className="w-full h-12 pl-11 pr-4 bg-(--primary-bg-cards) border border-(--primary-border-cards) rounded-xl text-sm transition-all focus:outline-none focus:border-(--primary-text) focus:bg-white placeholder-neutral-400 text-(--primary-text)"
                                />
                            </div>
                        </div>

                        {/* Password */}
                        <div>
                            <div className="flex justify-between items-center mb-1.5">
                                <label className="block text-xs font-bold tracking-wider text-(--primary-text) uppercase">
                                    Wachtwoord
                                </label>
                                <button
                                    type="button"
                                    onClick={() => navigate('/wachtwoord-vergeten')}
                                    className="text-xs text-(--secondary-text) hover:underline bg-transparent border-none cursor-pointer"
                                >
                                    Wachtwoord vergeten?
                                </button>
                            </div>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v-6.75a2.25 2.25 0 002.25-2.25z" />
                                    </svg>
                                </span>
                                <input
                                    type="password"
                                    required
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="••••••••"
                                    className="w-full h-12 pl-11 pr-4 bg-(--primary-bg-cards) border border-(--primary-border-cards) rounded-xl text-sm transition-all focus:outline-none focus:border-(--primary-text) focus:bg-white placeholder-neutral-400 text-(--primary-text)"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            className="w-full h-12 mt-4 bg-[#2c3e50] hover:bg-[#1A2530] text-white font-medium rounded-xl transition-all flex items-center justify-center space-x-2 shadow-md active:scale-[0.98] cursor-pointer"
                        >
                            <span>Aanmelden</span>
                        </button>
                    </form>

                    <div className="mt-6 text-center">
                        <p className="text-sm text-(--secondary-text)">
                            Nieuw op het command center?{' '}
                            <button
                                onClick={() => navigate('/handhaver_register')}
                                className="text-(--primary-text) font-bold hover:underline ml-0.5 bg-transparent border-none cursor-pointer text-sm"
                            >
                                Dienstaccount aanmaken
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}