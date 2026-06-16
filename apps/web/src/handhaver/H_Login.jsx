import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import { Eye, EyeClosed, Lock, Mail } from "lucide-react";
import RauwLogoImg from '../assets/LogoRAUW.png';
import BOAachtergrond from '../assets/BOAachtergrond.jpg';

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    // Locatie states met Rotterdam Centrum als standaard fallback
    const [latitude, setLatitude] = useState(51.9244);
    const [longitude, setLongitude] = useState(4.4777);

    // Vraag direct bij het laden van het inlogscherm de GPS-locatie op
    useEffect(() => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setLatitude(position.coords.latitude);
                    setLongitude(position.coords.longitude);
                },
                (error) => {
                    console.warn("GPS toegang geweigerd voor login, fallback naar Rotterdam Centrum actief.", error);
                }
            );
        }
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');

        // Dubbele check: als de waarden om wat voor reden dan ook NaN of leeg zijn, harde fallback dwingen
        const finalLat = latitude && !isNaN(latitude) ? Number(latitude) : 51.9244;
        const finalLng = longitude && !isNaN(longitude) ? Number(longitude) : 4.4777;

        try {
            // We sturen nu verplicht latitude en longitude mee zoals de backend eist
            const response = await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/auth/login`, {
                email,
                password,
                latitude: finalLat,
                longitude: finalLng
            });

            if (response.data.access_token) {
                localStorage.setItem('auth_token', response.data.access_token);
            }
            localStorage.setItem('user_type', 'officer');
            navigate('/meldingen');

        } catch (err) {
            console.error("Inlogfout details:", err.response);

            if (err.response && err.response.data) {
                setError(err.response.data.message || 'Inloggen mislukt. Controleer uw gegevens.');
            } else {
                setError('Inloggen mislukt. De server reageert niet.');
            }
        }
    };

    const fields = [
        { id: 'email', label: 'E-mailadres', icon: Mail, type: 'email', placeholder: 'naam@rotterdam.nl', state: email, setter: setEmail },
        { id: 'password', label: 'Wachtwoord', icon: Lock, type: showPassword ? 'text' : 'password', placeholder: 'Jouw wachtwoord', state: password, setter: setPassword }
    ];

    return (
        <div className="min-h-screen w-full flex flex-col md:flex-row bg-primary-bg text-primary-text antialiased">

            <div className="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-center items-center p-12 bg-no-repeat bg-cover bg-center" style={{ backgroundImage: `linear-gradient(to bottom, rgba(18, 24, 32, 0.85), rgba(18, 24, 32, 0.95)), url('${BOAachtergrond}')` }}>
                <img src={RauwLogoImg} alt="RAUW Rotterdam" className="absolute top-12 left-12 w-20 h-20 object-contain" />
                <div className="max-w-md text-center text-white">
                    <h1 className="text-4xl lg:text-5xl font-headline tracking-tight leading-[1.15] mb-6">
                        Toezicht in<br/>jouw wijk.
                    </h1>
                    <p className="text-sm lg:text-base font-body text-gray-300">
                        Log in als handhaver om actuele meldingen in de stad te monitoren, locaties direct op te volgen en bij te dragen aan de veiligheid in Rotterdam.
                    </p>
                </div>
            </div>

            <div className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-36 bg-primary-bg overflow-y-auto">
                <div className="w-full max-w-sm mx-auto">

                    <div className="mb-8 text-center md:text-left">
                        <h2 className="text-3xl font-black tracking-tight mb-2">
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
                        Nog geen account?{' '}
                        <button onClick={() => navigate('/registreerhandhaver')}
                                className="text-primary-text font-bold hover:underline cursor-pointer">
                            Registreren
                        </button>
                    </p>
                </div>
            </div>
        </div>
    );
}