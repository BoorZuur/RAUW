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
            className="fixed inset-0 h-screen w-screen flex flex-col justify-between transition-all duration-600 select-none overflow-hidden bg-no-repeat bg-cover bg-center"
            style={{
                /* AAA Contrast: Donkere overlay voor witte tekst */
                backgroundImage: `linear-gradient(to bottom, rgba(0, 0, 0, 0.85), rgba(18, 24, 32, 0.95)), url('${step.bgImage}')`,
                backgroundColor: step.bgColor
            }}
        >
            {/* Header */}
            <header className="w-full flex justify-between items-center z-30 max-w-7xl mx-auto px-6 h-20 sm:h-24 shrink-0">
                <div className="w-20 h-20 sm:w-24 sm:h-24 flex items-center justify-center hover:scale-105 transition-transform">
                    <img src={RauwLogoImg} alt="RAUW Rotterdam" className="w-full h-full object-contain" />
                </div>

                <button
                    onClick={() => navigate('/login')}
                    className="font-bold text-sm sm:text-base text-white hover:underline focus:ring-4 focus:ring-white/50 p-2 rounded-lg cursor-pointer transition-all"
                    aria-label="Inloggen"
                >
                    Inloggen
                </button>
            </header>

            {/* Main Content */}
            <main className="w-full max-w-3xl mx-auto flex flex-col items-center justify-center grow z-10 text-center px-6">
                <div className={`w-full flex flex-col items-center justify-center transition-all duration-600 ${isTransitioning ? 'opacity-0 blur-sm' : 'opacity-100 blur-none'}`}>
                    {/* Tag label - AAA contrast: witte tekst op zwarte/donkere achtergrond */}
                    <div className="inline-block bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full text-[11px] sm:text-xs font-bold tracking-widest text-white mb-6 border border-white/50 uppercase">
                        {step.tag}
                    </div>

                    <h1 className="text-3xl sm:text-5xl md:text-6xl tracking-tight leading-[1.1] mb-6 font-bold drop-shadow-sm">
                        {step.title}
                    </h1>

                    <p className="text-sm sm:text-base md:text-lg text-white leading-relaxed max-w-md md:max-w-lg mx-auto">
                        {step.description}
                    </p>
                </div>

                {/* Progress Indicators */}
                <nav className="my-8" aria-label="Onboarding stappen">
                    <div className="flex space-x-3">
                        {onboardingSteps.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => index !== currentStep && triggerStepChange(index)}
                                className={`h-1.5 rounded-full focus:ring-2 focus:ring-white transition-all ${
                                    index === currentStep ? 'w-10 bg-white' : 'w-5 bg-white/30 hover:bg-white/60'
                                }`}
                                aria-label={`Ga naar stap ${index + 1}`}
                            />
                        ))}
                    </div>
                </nav>

                {/* Navigation Buttons */}
                <div className="flex items-center gap-4">
                    {currentStep > 0 && (
                        <button
                            onClick={handleBack}
                            className="w-12 h-12 flex items-center justify-center rounded-full border border-white/30 bg-black/20 hover:bg-white/10 text-white focus:ring-4 focus:ring-white/50 transition-all"
                            aria-label="Vorige stap"
                        >
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}><path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        </button>
                    )}

                    <button
                        onClick={handleNext}
                        className="h-12 px-8 sm:h-14 sm:px-10 bg-white text-black font-bold rounded-full hover:bg-gray-100 focus:ring-4 focus:ring-white/50 transition-all flex items-center justify-center gap-2 text-sm sm:text-base"
                        aria-label={currentStep === onboardingSteps.length - 1 ? "Account aanmaken" : "Volgende stap"}
                    >
                        <span>{step.buttonText}</span>
                        {step.showArrow && (
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}><path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        )}
                    </button>
                </div>
            </main>

            {/* Footer */}
            <footer className="w-full max-w-7xl mx-auto px-6 py-6 border-t border-white/10 text-[10px] sm:text-[11px] font-bold text-white shrink-0">
                © {new Date().getFullYear()} RAUW Rotterdam
            </footer>
        </div>
    );
}