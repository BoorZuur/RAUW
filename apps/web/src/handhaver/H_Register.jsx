import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

import RauwLogoImg from '../assets/LogoRAUW.png';
import BgRotterdam1 from '../assets/BOAachtergrond.jpg';
import {Eye, EyeClosed, Lock, Mail, User, IdCard} from "lucide-react";

export default function BoaRegister() {
    const [username, setUsername] = useState('');
    const [badgeNumber, setBadgeNumber] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [confirmPassword, setConfirmPassword] = useState('');
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');

        if (password !== confirmPassword) {
            setError('Wachtwoorden komen niet overeen.');
            return;
        }

        try {
            //Request Backend
            await axios.post('http://localhost:8001/api/auth/register/officer', {
                username: username,
                badge_number: badgeNumber,
                email: email,
                password: password,
                confirm_password: confirmPassword,
            }, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });

            //Send to dashboard once registered
            navigate('/handhaver_login');
        } catch (err) {
            if (err.response && err.response.data.errors) {
                const messages = Object.values(err.response.data.errors);
                setError(messages[0][0]);
            } else {
                setError('Registratie mislukt. Controleer de velden.');
            }
        }
    };

    return (
        <div className="h-screen w-full m-0 p-0 flex flex-col md:flex-row bg-(--primary-bg) text-(--primary-text) antialiased select-none overflow-hidden">

            {/* Left Side: Branding */}
            <div className="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-center items-center p-12 bg-no-repeat bg-cover bg-center overflow-hidden transition-all duration-1000 text-center"
                 style={{ backgroundImage: `linear-gradient(to bottom, rgba(18, 24, 32, 0.85), rgba(18, 24, 32, 0.95)), url('${BgRotterdam1}')` }}>
                <div className="absolute top-12 left-12 w-20 h-20 flex items-center justify-center z-20">
                    <img src={RauwLogoImg} alt="RAUW Rotterdam" className="w-full h-full object-contain" />
                </div>
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

            {/* Right Side: Form */}
            <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-32 bg-(--primary-bg) relative overflow-y-auto md:overflow-hidden">
                <div className="w-full max-w-sm mx-auto text-center">
                    <div className="mb-8">
                        <h2 className="text-3xl font-black tracking-tight mb-2" style={{color: 'var(--primary-text)'}}>Dienstaccount aanmaken</h2>
                        <p className="text-sm" style={{color: 'var(--secondary-text)'}}>Registreer als BOA of veiligheidsregisseur.</p>
                    </div>

                    {/* Foutmelding weergave */}
                    {error && <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-xs font-bold text-center">{error}</div>}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Username */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                Gebruikersnaam
                            </label>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    <User />
                                </span>
                                <input
                                    type="username"
                                    required value={username}
                                    onChange={(e) => setUsername(e.target.value)}
                                    placeholder="Gebruikersnaam"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400 border border-(--primary-border-cards)"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                            </div>
                        </div>

                        {/* Badgenumber */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                Badgenummer / Dienstnummer
                            </label>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    <IdCard />
                                </span>
                                <input
                                    type="badgeNumber"
                                    required value={badgeNumber}
                                    onChange={(e) => setBadgeNumber(e.target.value)}
                                    placeholder="R-T-00000"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400 border border-(--primary-border-cards)"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                            </div>
                        </div>

                        {/* Email */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                E-mailadres
                            </label>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    <Mail />
                                </span>
                                <input
                                    type="email"
                                    required value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="jij@voorbeeld.nl"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400 border border-(--primary-border-cards)"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                            </div>
                        </div>

                        {/* Password */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                Wachtwoord
                            </label>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    < Lock/>
                                </span>
                                <input
                                    type={showPassword ? "text" : "password"}
                                    required value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="Jouw wachtwoord"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400 border border-(--primary-border-cards)"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                                {/* Toggle Button */}
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute inset-y-0 right-0 flex items-center pr-4 text-(--secondary-text) hover:text-(--primary-text) cursor-pointer"
                                >
                                    {showPassword ? (
                                        <Eye />
                                    ) : (
                                        <EyeClosed />
                                    )}
                                </button>
                            </div>
                        </div>

                        {/* Confirm Password */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider uppercase mb-1.5" style={{color: 'var(--primary-text)'}}>
                                Wachtwoord herhalen
                            </label>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    < Lock/>
                                </span>
                                <input
                                    type={showPassword ? "text" : "password"}
                                    required value={confirmPassword}
                                    onChange={(e) => setConfirmPassword(e.target.value)}
                                    placeholder="Herhaal wachtwoord"
                                    className="w-full h-12 pl-11 pr-4 rounded-xl text-sm transition-all focus:outline-none focus:bg-white placeholder-neutral-400 border border-(--primary-border-cards)"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute inset-y-0 right-0 flex items-center pr-4 text-(--secondary-text) hover:text-(--primary-text) cursor-pointer"
                                >
                                    {showPassword ? (
                                        <Eye />
                                    ) : (
                                        <EyeClosed />
                                    )}
                                </button>
                            </div>
                        </div>

                        <button type="submit" className="w-full h-12 mt-4 bg-(--primary-text) hover:bg-(--primary-accent) text-white font-medium rounded-xl transition-all flex items-center justify-center space-x-2 shadow-md active:scale-[0.98] cursor-pointer">
                            <span>Registreren</span>
                        </button>
                    </form>

                    <div className="mt-6 text-center">
                        <p className="text-sm text-(--secondary-text)">
                            Al een dienstaccount?{' '}
                            <button onClick={() => navigate('/handhaver_login')} className="text-(--primary-text) font-bold hover:underline ml-0.5 bg-transparent border-none cursor-pointer text-sm">
                                Inloggen
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}