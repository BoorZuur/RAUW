import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

import RauwLogoImg from '../assets/LogoRAUW.png';
import BgRotterdam2 from "../assets/AchtergrondRotterdam5.webp";

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = (e) => {
        e.preventDefault();
        // Route to the central incident and notification feed
        navigate('/feed');
    };

    return (
        /* Viewport root layout wrapper. min-h-screen locks layout baseline */
        <div className="min-h-screen w-full m-0 p-0 flex flex-col md:flex-row bg-(--primary-bg) text-(--primary-text) antialiased select-none overflow-hidden">

            {/* Left Column: Visual branding and regional text container (Perfectly centered content layout) */}
            <div
                className="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-center items-center p-12 bg-no-repeat bg-cover bg-center overflow-hidden transition-all duration-1000"
                style={{
                    backgroundImage: `linear-gradient(to bottom, rgba(18, 24, 32, 0.85), rgba(18, 24, 32, 0.95)), url('${BgRotterdam2}')`
                }}
            >
                {/* Brand asset placement - absolute to keep it out of the flex centering flow */}
                <div className="absolute top-12 left-12 w-20 h-20 flex items-center justify-center z-20">
                    <img
                        src={RauwLogoImg}
                        alt="RAUW Rotterdam"
                        className="w-full h-full object-contain"
                    />
                </div>

                {/* Typography perfectly centered horizontally and vertically inside the column ALSJEBLIEFT RENEE*/}
                <div className="max-w-md text-center z-10 text-(--primary-text-d)">
                    <h1 className="text-4xl lg:text-5xl tracking-tight leading-[1.15] mb-6 whitespace-pre-line text-balance font-(--font-family-headline)">
                        Welkom terug op<br />de straat.
                    </h1>
                    <p className="text-sm lg:text-base text-(--secondary-text-d) leading-relaxed font-(--font-family-label)">
                        Log in om direct overlast te melden, actieve meldingen in jouw wijk te bekijken of updates van handhavers te volgen.
                    </p>
                </div>

                {/* Institutional timestamp footer - absolute to keep it out of the flex centering flow */}
                <div className="absolute bottom-12 left-12 text-[11px] text-(--secondary-text-d)/40 tracking-wide font-medium z-10">
                    © {new Date().getFullYear()} RAUW ROTTERDAM
                </div>
            </div>

            {/* Right Side: Credentials intake container */}
            <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-32 bg-(--primary-bg) relative overflow-y-auto md:overflow-hidden">

                {/* Simplified layout representation for smaller devices */}
                <div className="md:hidden absolute top-6 left-6 flex items-center space-x-2">
                    <img
                        src={RauwLogoImg}
                        alt="RAUW"
                        className="w-8 h-8 object-contain"
                    />
                    <span className="text-xl tracking-wider text-(--primary-text)">
                        RAUW
                    </span>
                </div>

                {/* Form container wrapper centered */}
                <div className="w-full max-w-sm mx-auto text-center">
                    <div className="mb-8">
                        <h2
                            className="text-3xl font-black tracking-tight mb-2"
                            style={{ color: 'var(--primary-text)' }}
                        >
                            Welkom terug
                        </h2>
                        <p
                            className="text-sm"
                            style={{ color: 'var(--secondary-text)' }}
                        >
                            Laat je stem horen in je buurt.
                        </p>
                    </div>

                    {/* Standard procedural account form handler (Aligned left internally) */}
                    <form onSubmit={handleSubmit} className="space-y-4">

                        {/* Core Identity Access Intake Area */}
                        <div className="text-left">
                            <div className="flex justify-between items-center mb-1.5">
                                <label className="block text-xs font-bold tracking-wider text-(--primary-text) uppercase">
                                    E-mailadres
                                </label>
                            </div>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 17.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5H4.5a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                </span>
                                <input
                                    type="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="jij@voorbeeld.nl"
                                    className="w-full h-12 pl-11 pr-4 bg-(--primary-bg-cards) border border-(--primary-border-cards) rounded-xl text-sm transition-all focus:outline-none focus:border-(--primary-text) focus:bg-white placeholder-neutral-400 text-(--primary-text)"
                                />
                            </div>
                        </div>

                        {/* Security Password Input Interface with Toggling Mechanics */}
                        <div className="text-left">
                            <div className="flex justify-between items-center mb-1.5">
                                <label className="block text-xs font-bold tracking-wider text-(--primary-text) uppercase">
                                    Wachtwoord
                                </label>
                                <button
                                    type="button"
                                    onClick={() => navigate('/forgot-password')}
                                    className="text-xs font-medium text-(--secondary-text) hover:text-(--primary-text) hover:underline bg-transparent border-none cursor-pointer"
                                >
                                    Vergeten?
                                </button>
                            </div>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v-6.75a2.25 2.25 0 002.25-2.25z" />
                                    </svg>
                                </span>
                                <input
                                    type={showPassword ? "text" : "password"}
                                    required
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="Jouw wachtwoord"
                                    className="w-full h-12 pl-11 pr-12 bg-(--primary-bg-cards) border border-(--primary-border-cards) rounded-xl text-sm transition-all focus:outline-none focus:border-(--primary-text) focus:bg-white placeholder-neutral-400 text-(--primary-text)"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute inset-y-0 right-0 flex items-center pr-4 text-(--secondary-text) hover:text-(--primary-text) cursor-pointer"
                                >
                                    {showPassword ? (
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    ) : (
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    )}
                                </button>
                            </div>
                        </div>

                        {/* Interactive Verification CTA Trigger Element */}
                        <button
                            type="submit"
                            className="w-full h-12 mt-4 bg-[#2c3e50] hover:bg-[#1A2530] text-white font-medium rounded-xl transition-all flex items-center justify-center space-x-2 shadow-md active:scale-[0.98] cursor-pointer"
                        >
                            <span>Inloggen</span>
                        </button>
                    </form>

                    {/* Secondary Navigation Contextual Toggle Area */}
                    <div className="mt-6 text-center">
                        <p className="text-sm text-(--secondary-text)">
                            Nog geen account?{' '}
                            <button
                                onClick={() => navigate('/register')}
                                className="text-(--primary-text) font-bold hover:underline ml-0.5 bg-transparent border-none cursor-pointer text-sm"
                            >
                                Registreren
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}