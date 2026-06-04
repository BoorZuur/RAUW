import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

import RauwLogoImg from '../assets/LogoRAUW.png';
import BgRotterdam1 from '../assets/AchtergrondRotterdam1.webp';
import BgRotterdam2 from '../assets/AchtergrondRotterdam2.jpg';
import BgRotterdam3 from '../assets/AchtergrondRotterdam3.jpg';

const onboardingSteps = [
    {
        id: 1,
        tag: "WELKOM BIJ RAUW",
        title: "Rotterdamse Actie\nUit de Wijken.",
        description: "RAUW is gemaakt voor Rotterdammers die hun wijk kennen. Zie je iets dat niet klopt? Meld het — direct, zonder gedoe.",
        buttonText: "Verder",
        bgImage: BgRotterdam1,
        bgColor: '#000000'
    },
    {
        id: 2,
        tag: "MELDEN IS MAKKELIJK",
        title: "Melden, beschrijven en klaar.",
        description: "Kies de plek op de kaart, omschrijf wat je zag en stuur het in. Anoniem kan ook.",
        buttonText: "Verder",
        bgImage: BgRotterdam2,
        bgColor: '#121820'
    },
    {
        id: 3,
        tag: "BOA'S REAGEREN DIRECT",
        title: "Je melding verdwijnt\nniet in een lade.",
        description: "Handhavers van de gemeente Rotterdam reageren op jouw signalen. Je volgt de status live — van nieuw tot opgelost.",
        buttonText: "Account aanmaken",
        showArrow: true,
        bgImage: BgRotterdam3,
        bgColor: '#121820'
    }
];

export default function Onboarding() {
    const [currentStep, setCurrentStep] = useState(0);
    const [isTransitioning, setIsTransitioning] = useState(false);
    const navigate = useNavigate();

    const triggerStepChange = (nextStepIndex) => {
        setIsTransitioning(true);
        setTimeout(() => {
            setCurrentStep(nextStepIndex);
            setIsTransitioning(false);
        }, 300);
    };

    const handleNext = () => {
        if (currentStep < onboardingSteps.length - 1) {
            triggerStepChange(currentStep + 1);
        } else {
            navigate('/registreer');
        }
    };

    const handleBack = () => {
        if (currentStep > 0) {
            triggerStepChange(currentStep - 1);
        }
    };

    const step = onboardingSteps[currentStep];

    return (
        <div
            className="fixed inset-0 h-screen w-screen m-0 p-4 sm:p-6 flex flex-col justify-between transition-all duration-600 ease-in-out select-none overflow-hidden bg-no-repeat bg-cover bg-center text-white"
            style={{
                backgroundImage: `linear-gradient(to bottom, rgba(0, 0, 0, 0.7), rgba(18, 24, 32, 0.9)), url('${step.bgImage}')`,
                backgroundColor: step.bgColor
            }}
        >
            <header className="w-full flex justify-between items-center z-30 max-w-7xl mx-auto pointer-events-none h-20 sm:h-24 shrink-0">
                <div className="w-20 h-20 sm:w-24 sm:h-24 overflow-hidden flex items-center justify-center transition-transform hover:scale-105 pointer-events-auto">
                    <img src={RauwLogoImg} alt="RAUW Rotterdam" className="w-full h-full object-contain" />
                </div>

                <div className="flex items-center ml-auto pointer-events-auto">
                    <button
                        onClick={() => navigate('/login')}
                        className="font-label text-sm sm:text-base tracking-wide text-gray-300 hover:text-white transition-colors bg-transparent border-none cursor-pointer"
                    >
                        Inloggen
                    </button>
                </div>
            </header>

            <main className="w-full max-w-xl md:max-w-2xl lg:max-w-3xl mx-auto flex flex-col items-center justify-center grow z-10 text-center relative">
                <div className="w-full h-64 sm:h-80 md:h-88 flex flex-col items-center justify-center relative">
                    <div
                        className={`w-full flex flex-col items-center justify-center transition-all duration-600 ease-in-out absolute inset-0 ${
                            isTransitioning ? 'opacity-0 scale-98 blur-sm' : 'opacity-100 scale-100 blur-none'
                        }`}
                    >
                        <div className="inline-block bg-white/10 backdrop-blur-md px-3.5 py-1.5 rounded-full text-[11px] sm:text-xs font-label tracking-widest text-gray-300 mb-4 sm:mb-6 border border-white/5 uppercase select-none">
                            {step.tag}
                        </div>

                        <h1 className="text-3xl sm:text-5xl md:text-6xl tracking-tight leading-[1.1] mb-4 sm:mb-6 whitespace-pre-line font-headline drop-shadow-sm">
                            {step.title}
                        </h1>

                        <p className="text-sm sm:text-base md:text-lg text-gray-300 leading-relaxed font-body max-w-md md:max-w-lg text-balance mx-auto">
                            {step.description}
                        </p>
                    </div>
                </div>

                {/* Progress Indicators */}
                <div className="w-full flex justify-center mb-6 sm:mb-8 mt-4">
                    <div className="flex space-x-2.5">
                        {onboardingSteps.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => index !== currentStep && triggerStepChange(index)}
                                className={`h-1 transition-all duration-500 rounded-full cursor-pointer ${
                                    index === currentStep ? 'w-10 bg-white' : 'w-5 bg-white/20 hover:bg-white/40'
                                }`}
                            />
                        ))}
                    </div>
                </div>

                {/* Navigation Buttons */}
                <div className="w-full h-14 flex items-center justify-center relative">
                    <div className="flex items-center justify-center space-x-4 absolute inset-0">
                        <div className="w-12 h-12 flex items-center justify-center shrink-0">
                            {currentStep > 0 && (
                                <button
                                    onClick={handleBack}
                                    className="flex items-center justify-center w-full h-full rounded-full border border-white/20 bg-black/10 backdrop-blur-sm hover:bg-white/10 text-white transition-all active:scale-95 cursor-pointer"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                    </svg>
                                </button>
                            )}
                        </div>

                        <button
                            onClick={handleNext}
                            className="w-48 sm:w-64 h-12 sm:h-14 bg-white hover:bg-neutral-100 active:scale-98 text-black font-label rounded-full transition-all flex items-center justify-center space-x-2.5 text-sm sm:text-base shadow-lg cursor-pointer"
                        >
                            <span>{step.buttonText}</span>
                            {step.showArrow && (
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2.5} stroke="currentColor" className="w-4 h-4">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            )}
                        </button>
                        <div className="w-12 h-12 shrink-0 hidden sm:block" />
                    </div>
                </div>
            </main>

            <footer className="w-full max-w-7xl mx-auto flex justify-between items-center text-[10px] sm:text-[11px] font-label text-gray-400 z-20 pt-2 sm:pt-4 border-t border-white/5 shrink-0">
                <span>© {new Date().getFullYear()} RAUW Rotterdam</span>
            </footer>
        </div>
    );
}