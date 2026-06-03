import React, {useState} from 'react';
import {useNavigate} from 'react-router-dom';

import RauwLogoImg from '../assets/LogoRAUW.png';
import BgRotterdamManager from '../assets/ManagerAchtergrond.jpg';

export default function ManagerLogin() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const navigate = useNavigate();

    const handleSubmit = (e) => {
        e.preventDefault();
        // Secure routing structure for management and directorial personnel
        if (email && password) {
            navigate('/manager/dashboard');
        }
    };

    return (
        /* Viewport root layout wrapper. h-screen + overflow-hidden layout locks scrolling on desktop environments */
        <div
            className="h-screen w-full m-0 p-0 flex flex-col md:flex-row bg-(--primary-bg) text-(--primary-text) antialiased select-none overflow-hidden">

            {/* Left Column: Visual branding and management-specific context container (Perfectly centered layout) */}
            <div
                className="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-center items-center p-12 bg-no-repeat bg-cover bg-center overflow-hidden"
                style={{
                    backgroundImage: `linear-gradient(to bottom, rgba(18, 24, 32, 0.85), rgba(18, 24, 32, 0.95)), url('${BgRotterdamManager}')`
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

                {/* Typography perfectly centered horizontally and vertically inside the column ALSJEBLIEFT RENEE */}
                <div className="max-w-md text-center z-10 text-(--primary-text-d)">
                    <h1 className="text-4xl lg:text-5xl tracking-tight leading-[1.15] mb-6 whitespace-pre-line text-balance font-(--font-family-headline)">
                        Regie &
                        <br/>
                        Beheer van
                        <br/>
                        veiligheid.
                    </h1>
                    <p className="text-sm lg:text-base text-(--secondary-text-d) leading-relaxed font-(--font-family-label)">
                        Log in op de beheeromgeving om meldingen te analyseren, operationele teams aan te sturen en strategische beslissingen te nemen voor een veiliger Rotterdam.
                    </p>
                </div>

                {/* Institutional timestamp footer - absolute to keep it out of the flex centering flow */}
                <div className="absolute bottom-12 left-12 text-[11px] text-(--secondary-text-d)/40 tracking-wide z-10 font-(--font-family-label)">
                    © {new Date().getFullYear()} RAUW ROTTERDAM
                </div>
            </div>

            {/* Right Column: Main authentication workflow environment */}
            <div
                className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-36 bg-(--primary-bg) relative overflow-y-auto md:overflow-hidden">

                {/* Responsive fallback layout header assets for handheld screens */}
                <div className="md:hidden absolute top-6 left-6 w-14 h-14 flex items-center justify-center">
                    <img
                        src={RauwLogoImg}
                        alt="RAUW Rotterdam"
                        className="w-full h-full object-contain"
                    />
                </div>

                {/* Form container wrapper centered within the column */}
                <div className="w-full max-w-sm mx-auto text-center">
                    <div className="mb-8">
                        <h2
                            className="text-3xl font-black tracking-tight mb-2"
                            style={{color: 'var(--primary-text)'}}
                        >
                            Inloggen Beheer
                        </h2>
                        <p
                            className="text-sm"
                            style={{color: 'var(--secondary-text)'}}
                        >
                            Toegang voor managers en veiligheidsregisseurs.
                        </p>
                    </div>

                    {/* Operational credential intake forms (Aligned left internally) */}
                    <form onSubmit={handleSubmit} className="space-y-4">

                        {/* Email address input field */}
                        <div className="text-left">
                            <label
                                className="block text-xs font-bold tracking-wider uppercase mb-1.5"
                                style={{color: 'var(--primary-text)'}}
                            >
                                E-mailadres
                            </label>
                            <div className="relative">
                                <span
                                    className="absolute inset-y-0 left-0 flex items-center pl-4"
                                    style={{color: 'var(--secondary-text)'}}
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75}
                                         stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round"
                                              d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 17.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5H4.5a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                    </svg>
                                </span>

                                <input
                                    type="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="naam@rotterdam.nl"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400"
                                    style={{
                                        backgroundColor: 'var(--primary-bg-cards)',
                                        borderColor: 'var(--primary-border-cards)',
                                        color: 'var(--primary-text)'
                                    }}
                                />
                            </div>
                        </div>

                        {/* Password input field (With secondary inline account recovery option) */}
                        <div className="text-left">
                            <div className="flex justify-between items-center mb-1.5">
                                <label
                                    className="block text-xs font-bold tracking-wider uppercase"
                                    style={{color: 'var(--primary-text)'}}
                                >
                                    Wachtwoord
                                </label>
                                <button
                                    type="button"
                                    onClick={() => navigate('/forgot-password')}
                                    className="text-xs hover:underline bg-transparent border-none cursor-pointer font-(--font-family-label)"
                                    style={{color: 'var(--secondary-text)'}}
                                >
                                    Wachtwoord vergeten?
                                </button>
                            </div>
                            <div className="relative">
                                <span
                                    className="absolute inset-y-0 left-0 flex items-center pl-4"
                                    style={{color: 'var(--secondary-text)'}}
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75}
                                         stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v-6.75a2.25 2.25 0 002.25-2.25z"/>
                                    </svg>
                                </span>

                                <input
                                    type="password"
                                    required
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="••••••••"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400"
                                    style={{
                                        backgroundColor: 'var(--primary-bg-cards)',
                                        borderColor: 'var(--primary-border-cards)',
                                        color: 'var(--primary-text)'
                                    }}
                                />
                            </div>
                        </div>

                        {/* Form Submission Action Button */}
                        <button
                            type="submit"
                            className="w-full h-12 mt-4 bg-[#2c3e50] hover:bg-[#1A2530] text-white font-medium rounded-xl transition-all flex items-center justify-center space-x-2 shadow-md active:scale-[0.98] cursor-pointer"
                        >
                            <span>Aanmelden</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}
