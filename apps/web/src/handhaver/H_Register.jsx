import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Eye, EyeClosed, Lock, Mail, User, IdCard, MapPin, Loader2 } from "lucide-react";
import RauwLogoImg from '../assets/LogoRAUW.png';
import BOAachtergrond from '../assets/BOAachtergrond.jpg';

export default function Register() {
    const [username, setUsername] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [badgeNumber, setBadgeNumber] = useState('');
    const [departmentId, setDepartmentId] = useState('');
    const [departments, setDepartments] = useState([]);
    const [loadingDepartments, setLoadingDepartments] = useState(true);
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState('');

    // Locatie states (Standaard ingesteld op Rotterdam Centrum als fallback)
    const [latitude, setLatitude] = useState(51.9244);
    const [longitude, setLongitude] = useState(4.4777);

    // 1. Haal afdelingen op & 2. Vraag GPS-locatie op bij laden van de pagina
    useEffect(() => {
        const token = localStorage.getItem('auth_token');

        // Afdelingen ophalen
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/departments`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
            .then(response => {
                const data = Array.isArray(response.data) ? response.data : (response.data.data || []);
                setDepartments(data);
                setLoadingDepartments(false);
            })
            .catch(err => {
                console.error('Kon afdelingen niet ophalen:', err);
                setLoadingDepartments(false);
            });

        // GPS coördinaten opvragen via de browser
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setLatitude(position.coords.latitude);
                    setLongitude(position.coords.longitude);
                },
                (error) => {
                    console.warn("GPS toegang geweigerd of mislukt, fallback naar Rotterdam Centrum gebruikt.", error);
                }
            );
        }
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');

        if (password !== confirmPassword) {
            setError('Wachtwoorden komen niet overeen.');
            return;
        }

        if (!departmentId) {
            setError('Selecteer aub een afdeling.');
            return;
        }

        try {
            // Nu sturen we exact mee wat de RegisterOfficerRequest klasse in PHP verwacht!
            const response = await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/auth/register/officer`, {
                username,
                email,
                password,
                confirm_password: confirmPassword,
                badge_number: badgeNumber,
                department_ids: [Number(departmentId)], // Moet een array van integers zijn
                latitude: Number(latitude),             // Nu verplicht meegestuurd
                longitude: Number(longitude)            // Nu verplicht meegestuurd
            });

            if (response.data.access_token) {
                localStorage.setItem('auth_token', response.data.access_token);
            }
            localStorage.setItem('user_type', 'officer');
            navigate('/feed');

        } catch (err) {
            if (err.response && err.response.status === 422) {
                const validationErrors = err.response.data.errors;
                if (validationErrors) {
                    const firstErrorKey = Object.keys(validationErrors)[0];
                    setError(validationErrors[firstErrorKey][0]);
                } else {
                    setError(err.response.data.message || 'Ingevulde gegevens zijn ongeldig.');
                }
            } else {
                setError('Registratie mislukt. Server is onbereikbaar.');
            }
        }
    };

    const fields = [
        {
            id: 'username',
            label: 'Gebruikersnaam',
            icon: User,
            type: 'text',
            placeholder: 'Gebruikersnaam',
            state: username,
            setter: setUsername
        },
        {
            id: 'email',
            label: 'E-mailadres',
            icon: Mail,
            type: 'email',
            placeholder: 'jij@voorbeeld.nl',
            state: email,
            setter: setEmail
        },
        {
            id: 'badgeNumber',
            label: 'Badgenummer / Dienstnummer',
            icon: IdCard,
            type: 'text',
            state: badgeNumber,
            setter: setBadgeNumber,
            placeholder: 'R-T-00000'
        },
        {
            id: 'password',
            label: 'Wachtwoord',
            icon: Lock,
            type: showPassword ? 'text' : 'password',
            placeholder: 'Jouw wachtwoord',
            state: password,
            setter: setPassword
        },
        {
            id: 'confirmPassword',
            label: 'Wachtwoord herhalen',
            icon: Lock,
            type: showPassword ? 'text' : 'password',
            placeholder: 'Herhaal wachtwoord',
            state: confirmPassword,
            setter: setConfirmPassword
        }
    ];

    return (
        <div className="min-h-screen w-full flex flex-col md:flex-row bg-primary-bg text-primary-text antialiased">

            {/* Linker visuele paneel (Verborgen op mobiel) */}
            <div
                className="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-center items-center p-12 bg-no-repeat bg-cover bg-center"
                style={{backgroundImage: `linear-gradient(to bottom, rgba(18, 24, 32, 0.85), rgba(18, 24, 32, 0.95)), url('${BOAachtergrond}')`}}>
                <img src={RauwLogoImg} alt="RAUW Rotterdam"
                     className="absolute top-12 left-12 w-20 h-20 object-contain"/>

                <div className="max-w-md text-center text-white">
                    <h1 className="text-4xl lg:text-5xl font-headline tracking-tight leading-[1.15] mb-6">
                        Houd de stad
                        <br/>
                        veilig en leefbaar.
                    </h1>
                    <p className="text-sm lg:text-base font-body text-gray-300">
                        Registreer je als handhaver om toezicht te houden, meldingen direct op te volgen en de
                        veiligheid in jouw Rotterdamse wijk te waarborgen.
                    </p>
                </div>
            </div>

            {/* Rechter formulier paneel / Mobiele Container */}
            <div
                className="flex-1 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-24 xl:px-36 bg-primary-bg overflow-y-auto">
                <div className="w-full max-w-sm mx-auto">

                    {/* Mobiel Logo: Alleen zichtbaar op schermen kleiner dan md */}
                    <div className="flex md:hidden justify-center mb-6">
                        <img src={RauwLogoImg} alt="RAUW Rotterdam" className="w-20 h-20 object-contain"/>
                    </div>

                    <div className="mb-8 text-center md:text-left">
                        <h2 className="text-3xl font-black tracking-tight mb-2">
                            Account aanmaken
                        </h2>
                        <p className="text-sm text-secondary-text">
                            Meld je aan voor het handhavingsplatform.
                        </p>
                    </div>

                    {error && <div role="alert"
                                   className="mb-4 p-3 bg-red-500/10 border border-red-500 rounded-xl text-red-600 text-xs font-medium text-center">{error}</div>}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {fields.map((field) => (
                            <div key={field.id} className="text-left">
                                <label htmlFor={field.id}
                                       className="block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text">
                                    {field.label}
                                </label>
                                <div className="relative">
                                    <field.icon className="absolute left-4 top-3.5 w-5 h-5 text-secondary-text"/>
                                    <input
                                        id={field.id}
                                        type={field.type}
                                        required
                                        value={field.state}
                                        onChange={(e) => field.setter(e.target.value)}
                                        placeholder={field.placeholder}
                                        className="w-full h-12 pl-11 pr-10 bg-primary-bg-cards border border-primary-border rounded-xl text-sm focus:border-primary-accent focus:ring-0 outline-none"
                                    />

                                    {field.id.includes('password') &&
                                        <button type="button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                className="absolute right-4 top-3.5 text-secondary-text hover:text-primary-text">
                                            {showPassword ? <Eye className="w-5 h-5"/> :
                                                <EyeClosed className="w-5 h-5"/>}
                                        </button>}
                                </div>
                            </div>
                        ))}

                        {/* Dropdown voor Afdeling */}
                        <div className="text-left">
                            <label htmlFor="department"
                                   className="block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text">
                                Afdeling / Team
                            </label>
                            <div className="relative">
                                {loadingDepartments ? (
                                    <Loader2
                                        className="absolute left-4 top-3.5 w-5 h-5 text-secondary-text animate-spin"/>
                                ) : (
                                    <MapPin className="absolute left-4 top-3.5 w-5 h-5 text-secondary-text"/>
                                )}
                                <select
                                    id="department"
                                    required
                                    value={departmentId}
                                    onChange={(e) => setDepartmentId(e.target.value)}
                                    disabled={loadingDepartments}
                                    className="w-full h-12 pl-11 pr-10 bg-primary-bg-cards border border-primary-border rounded-xl text-sm focus:border-primary-accent focus:ring-0 outline-none appearance-none cursor-pointer text-primary-text invalid:text-secondary-text/60"
                                >
                                    <option value="" disabled hidden>Kies uw afdeling...</option>
                                    {departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}
                                                className="bg-primary-bg-cards text-primary-text">
                                            {dept.name}
                                        </option>
                                    ))}
                                </select>
                                <div
                                    className="pointer-events-none absolute inset-y-0 right-4 flex items-center text-secondary-text">
                                    <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                         viewBox="0 0 20 20">
                                        <path
                                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            className="w-full h-12 mt-4 bg-primary-text text-primary-bg hover:bg-primary-accent font-black uppercase tracking-widest rounded-xl transition-all shadow-md active:scale-[0.98] cursor-pointer"
                        >
                            Registreren
                        </button>
                    </form>

                    <p className="mt-6 text-sm text-center text-secondary-text">
                        Al een account?{' '}
                        <button
                            onClick={() => {
                                window.location.href = '/loginhandhaver';
                            }}
                            className="text-primary-text font-bold hover:underline cursor-pointer"
                        >
                            Inloggen
                        </button>
                    </p>
                </div>
            </div>
        </div>
    );
}