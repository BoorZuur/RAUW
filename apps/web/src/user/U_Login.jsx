import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import { Eye, EyeClosed, Lock, Mail } from "lucide-react";
import RauwLogoImg from '../assets/LogoRAUW.png';
import BgRotterdam2 from '../assets/AchtergrondRotterdam4.webp';

// We definiëren de client voor algemeen gebruik
const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { 'Accept': 'application/json' }
});

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const response = await apiClient.post('/auth/login', { email, password });

            if (response.data.access_token) {
                localStorage.setItem('auth_token', response.data.access_token);
                localStorage.setItem('user_type', 'user');
                navigate('/feed');
            }
        } catch (err) {
            setError('Inloggen mislukt.');
        }
    };

    const fields = [
        { id: 'email', label: 'E-mailadres', icon: Mail, type: 'email', placeholder: 'jij@voorbeeld.nl', state: email, setter: setEmail },
        { id: 'password', label: 'Wachtwoord', icon: Lock, type: showPassword ? 'text' : 'password', placeholder: 'Jouw wachtwoord', state: password, setter: setPassword }
    ];

    return (
        <div className="min-h-screen w-full flex flex-col md:flex-row bg-primary-bg text-primary-text antialiased">

            <div className="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-center items-center p-12 bg-no-repeat bg-cover bg-center" style={{ backgroundImage: `linear-gradient(to bottom, rgba(18, 24, 32, 0.85), rgba(18, 24, 32, 0.95)), url('${BgRotterdam2}')` }}>
                <img src={RauwLogoImg} alt="RAUW Rotterdam" className="absolute top-12 left-12 w-20 h-20 object-contain" />
                <div className="max-w-md text-center text-white">
                    <h1 className="text-4xl lg:text-5xl font-headline tracking-tight leading-[1.15] mb-6">
                        Welkom terug op<br/>de straat.
                    </h1>
                    <p className="text-sm lg:text-base font-body text-gray-300">
                        Log in om weer direct betrokken te zijn bij je buurt en meldingen in jouw wijk te volgen.
                    </p>
                </div>
            </div>

            <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-36 bg-primary-bg overflow-y-auto">
                <div className="w-full max-w-sm mx-auto">

                    <div className="mb-8 text-center md:text-left">
                        <h2 className="text-3xl tracking-tight mb-2">
                            Welkom terug
                        </h2>
                        <p className="text-sm text-secondary-text">
                            Laat je stem horen in je buurt.
                        </p>
                    </div>

                    {error && <div role="alert" className="mb-4 p-3 bg-red-500/10 border border-red-500 rounded-xl text-red-600 text-xs font-medium text-center">{error}</div>}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {fields.map((field) => (
                            <div key={field.id} className="text-left">
                                <label htmlFor={field.id}
                                       className="block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text">
                                       {field.label}
                                </label>
                                <div className="relative">
                                    <field.icon className="absolute left-4 top-3.5 w-5 h-5 text-secondary-text" />
                                    <input
                                        id={field.id}
                                        type={field.type}
                                        required value={field.state}
                                        onChange={(e) => field.setter(e.target.value)}
                                        placeholder={field.placeholder}
                                        className="w-full h-12 pl-11 pr-10 bg-primary-bg-cards border border-primary-border rounded-xl text-sm focus:border-primary-accent focus:ring-0 outline-none" />

                                    {field.id.includes('password') &&
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute right-4 top-3.5 text-secondary-text hover:text-primary-text">
                                            {showPassword ? <Eye className="w-5 h-5" /> : <EyeClosed className="w-5 h-5" />}
                                        </button>}
                                </div>
                            </div>
                        ))}
                        <button
                            type="submit"
                            className="w-full h-12 mt-4 bg-primary-text text-primary-bg hover:bg-primary-accent font-black uppercase tracking-widest rounded-xl transition-all shadow-md active:scale-[0.98]"
                        >
                            Inloggen
                        </button>
                    </form>

                    <p className="mt-6 p-4 text-sm text-center text-secondary-text">
                        Nog geen account?
                        <button onClick={() => navigate('/registreer')}
                                className="text-primary-text font-bold hover:underline">
                            Registreren
                        </button>
                    </p>
                </div>
            </div>
        </div>
    );
}