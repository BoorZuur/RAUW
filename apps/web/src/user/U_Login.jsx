import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

import RauwLogoImg from '../assets/LogoRAUW.png';
import BgRotterdam2 from "../assets/AchtergrondRotterdam5.webp";
import {Eye, EyeClosed, Lock, Mail} from "lucide-react";

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');

        try {
            const response = await axios.post('http://localhost:8001/api/auth/login', {
                email: email,
                password: password,
            }, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });

            if (response.data.token) {
                localStorage.setItem('auth_token', response.data.token);
            }
            navigate('/feed');
        } catch (err) {
            //Checks for  specific error code
            if (err.response && err.response.status === 401) {
                setError('E-mailadres of wachtwoord is onjuist.');
            } else if (err.response && err.response.data.message) {
                setError(err.response.data.message);
            } else {
                setError('Er is een verbindingsfout opgetreden. Probeer het later opnieuw.');
            }
        }
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

                    {/* Error container */}
                    {error && (
                        <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-xs font-medium text-center">
                            {error}
                        </div>
                    )}

                    {/* Standard procedural account form handler (Aligned left internally) */}
                    <form onSubmit={handleSubmit} className="space-y-4">

                        {/* Core Identity Access Intake Area */}
                        <div className="text-left">
                            <label className="block text-xs font-bold tracking-wider text-(--primary-text) uppercase mb-1.5">
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
                                    className="w-full h-12 pl-11 pr-4 bg-(--primary-bg-cards) border border-(--primary-border-cards) rounded-xl text-sm transition-all focus:outline-none focus:border-(--primary-text) focus:bg-white placeholder-neutral-400 text-(--primary-text)"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                            </div>
                        </div>

                        {/* Security Password Input Interface */}
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
                                {/* Lock Icoon */}
                                <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-(--secondary-text)">
                                    < Lock/>
                                </span>
                                <input
                                    type={showPassword ? "text" : "password"}
                                    required value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="Jouw wachtwoord"
                                    className="w-full h-12 pl-11 pr-12 bg-(--primary-bg-cards) border border-(--primary-border-cards) rounded-xl text-sm transition-all focus:outline-none focus:border-(--primary-text) focus:bg-white placeholder-neutral-400 text-(--primary-text)"
                                    style={{backgroundColor: 'var(--primary-bg-cards)', borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)'}}
                                />
                                {/* Toggle Knop */}
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