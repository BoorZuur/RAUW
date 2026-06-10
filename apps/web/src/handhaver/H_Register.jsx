import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import { Eye, EyeClosed, Lock, Mail, User, IdCard } from "lucide-react";
import RauwLogoImg from '../assets/LogoRAUW.png';
import BOAachtergrond from '../assets/BOAachtergrond.jpg';

export default function Register() {
    const [username, setUsername] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [badgeNumber, setBadgeNumber] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (password !== confirmPassword) { setError('Wachtwoorden komen niet overeen.'); return; }
        try {
            const response = await axios.post('http://localhost:8001/api/auth/register/user', { username, email, password, confirm_password: confirmPassword });
            if (response.data.access_token) {
                localStorage.setItem('auth_token', response.data.access_token);
            }
            localStorage.setItem('user_type', 'officer');
            navigate('/feed');
        } catch (err) { setError('Registratie mislukt.'); }
    };

    const fields = [
        { id: 'username', label: 'Gebruikersnaam', icon: User, type: 'text', placeholder: 'Gebruikersnaam', state: username, setter: setUsername },
        { id: 'email', label: 'E-mailadres', icon: Mail, type: 'email', placeholder: 'jij@voorbeeld.nl', state: email, setter: setEmail },
        { id: 'badgeNumber', label: 'Badgenummer / Dienstnummer', icon: IdCard, type: 'text', state: badgeNumber, setter: setBadgeNumber, placeholder: 'R-T-00000' },
        { id: 'password', label: 'Wachtwoord', icon: Lock, type: showPassword ? 'text' : 'password', placeholder: 'Jouw wachtwoord', state: password, setter: setPassword },
        { id: 'confirmPassword', label: 'Wachtwoord herhalen', icon: Lock, type: showPassword ? 'text' : 'password', placeholder: 'Herhaal wachtwoord', state: confirmPassword, setter: setConfirmPassword }
    ];

    return (
        <div className="min-h-screen w-full flex flex-col md:flex-row bg-primary-bg text-primary-text antialiased">

            <div className="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-center items-center p-12 bg-no-repeat bg-cover bg-center" style={{ backgroundImage: `linear-gradient(to bottom, rgba(18, 24, 32, 0.85), rgba(18, 24, 32, 0.95)), url('${BOAachtergrond}')` }}>
                <img src={RauwLogoImg} alt="RAUW Rotterdam" className="absolute top-12 left-12 w-20 h-20 object-contain" />

                <div className="max-w-md text-center text-white">
                    <h1 className="text-4xl lg:text-5xl font-headline tracking-tight leading-[1.15] mb-6">
                        Houd de stad<br/>veilig en leefbaar.
                    </h1>
                    <p className="text-sm lg:text-base font-body text-gray-300">
                        Registreer je als handhaver om toezicht te houden, meldingen direct op te volgen en de veiligheid in jouw Rotterdamse wijk te waarborgen.
                    </p>
                </div>
            </div>

            <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-36 bg-primary-bg overflow-y-auto">
                <div className="w-full max-w-sm mx-auto">

                    <div className="mb-8 text-center md:text-left">
                        <h2 className="text-3xl font-black tracking-tight mb-2">
                            Account aanmaken
                        </h2>
                        <p className="text-sm">
                            Word actief lid van je Rotterdamse buurt.
                        </p>
                    </div>

                    {error && <div role="alert" className="mb-4 p-3 bg-red-500/10 border border-red-500 rounded-xl text-red-600 text-xs font-medium text-center">{error}</div>}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {fields.map((field) => (
                            <div
                                key={field.id}
                                className="text-left">
                                <label
                                    htmlFor={field.id}
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
                                        <button type="button"
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
                            Registreren
                        </button>
                    </form>

                    <p className="mt-6 p- text-sm text-center text-secondary-text">
                        Al een account?
                        <button
                            onClick={() => navigate('/loginhandhaver')}
                            className="text-primary-text font-bold hover:underline">
                            Inloggen
                        </button>
                    </p>
                </div>
            </div>
        </div>
    );
}