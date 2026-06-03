import React, {useState} from 'react';
import {useNavigate} from 'react-router-dom';

import RauwLogoImg from '../assets/LogoRAUW.png';
import BgRotterdam1 from '../assets/BOAachtergrond.jpg';

export default function BoaRegister() {
    const [badgeNumber, setBadgeNumber] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const navigate = useNavigate();

    const handleSubmit = (e) => {
        e.preventDefault();
        if (password === confirmPassword) {
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
                    <img
                        src={RauwLogoImg}
                        alt="RAUW Rotterdam"
                        className="w-full h-full object-contain"
                    />
                </div>

                {/* Typography perfectly centered horizontally and vertically inside the column ALSJEBLIEFT RENEE*/}
                <div className="max-w-md z-10 text-(--primary-text-d)">
                    <h1 className="text-4xl lg:text-5xl font-bold tracking-tight leading-[1.15] mb-6 whitespace-pre-line text-balance">
                        Maak een<br/>verschil in<br/>jouw wijk.
                    </h1>
                    <p className="text-sm lg:text-base text-(--secondary-text-d) leading-relaxed font-medium">
                        Met een account kun je meldingen live volgen, updates doorgeven aan bewoners en direct reageren op situaties in jouw Rotterdamse buurt.
                    </p>
                </div>

                <div className="absolute bottom-12 left-12 text-[11px] text-(--secondary-text-d)/40 tracking-wide font-medium z-10">
                    © {new Date().getFullYear()} RAUW ROTTERDAM
                </div>
            </div>

            {/* Right Side: Professional Verification Form Wrapper */}
            <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-32 bg-(--primary-bg) relative overflow-y-auto md:overflow-hidden">
                <div className="md:hidden absolute top-6 left-6 flex items-center space-x-2">
                    <img src={RauwLogoImg} alt="RAUW" className="w-8 h-8 object-contain" />
                    <span className="font-(--font-family-headline) text-xl tracking-wider text-(--primary-text)">RAUW</span>
                </div>

                <div className="w-full max-w-sm mx-auto text-center">
                    <div className="mb-8">
                        <h2 className="text-3xl font-black tracking-tight mb-2" style={{color: 'var(--primary-text)'}}>
                            Dienstaccount aanmaken
                        </h2>
                        <p className="text-sm" style={{color: 'var(--secondary-text)'}}>
                            Registreer als BOA of veiligheidsregisseur.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">

                        {/* Badgenumber */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                Badgenummer / Dienstnummer
                            </label>
                            <div className="relative w-full">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4" style={{color: 'var(--secondary-text)'}}>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    required
                                    value={badgeNumber}
                                    onChange={(e) => setBadgeNumber(e.target.value)}
                                    placeholder="b.v. RT-1094"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400 uppercase tracking-wider"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                            </div>
                        </div>

                        {/* Email */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                Dienst e-mailadres
                            </label>
                            <div className="relative w-full">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4" style={{color: 'var(--secondary-text)'}}>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 17.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5H4.5a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                    </svg>
                                </span>
                                <input
                                    type="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="naam@rotterdam.nl"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                            </div>
                        </div>

                        {/* Password */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                Wachtwoord
                            </label>
                            <div className="relative w-full">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4" style={{color: 'var(--secondary-text)'}}>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
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
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                            </div>
                        </div>

                        {/* Confirm Password */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                Wachtwoord Bevestigen
                            </label>
                            <div className="relative w-full">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4" style={{color: 'var(--secondary-text)'}}>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.75} stroke="currentColor" className="w-4 h-4">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v-6.75a2.25 2.25 0 002.25-2.25z"/>
                                    </svg>
                                </span>
                                <input
                                    type="password"
                                    required
                                    value={confirmPassword}
                                    onChange={(e) => setConfirmPassword(e.target.value)}
                                    placeholder="••••••••"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                            </div>
                        </div>

                        <button type="submit" className="w-full h-12 mt-4 bg-[#2c3e50] hover:bg-[#1A2530] text-white font-(--font-family-label) rounded-xl transition-all flex items-center justify-center space-x-2 shadow-md active:scale-[0.98] cursor-pointer">
                            <span>Account Aanmaken</span>
                        </button>
                    </form>

                    <div className="mt-6 text-center">
                        <p className="text-sm text-(--secondary-text) font-(--font-family-label)">
                            Al een dienstaccount?{' '}
                            <button
                                onClick={() => navigate('/handhaver_login')}
                                className="text-(--primary-text) font-bold hover:underline ml-0.5 bg-transparent border-none cursor-pointer text-sm"
                            >
                                Inloggen
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}