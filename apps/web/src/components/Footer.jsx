import React from 'react';
import RauwLogoImg from "../assets/LogoRAUW.png";

export default function Footer() {
    return (
        <footer className="w-full bg-primary-bg-cards border-t-2 border-primary-border py-12 px-6 mt-auto">
            <div className="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">

                <div className="flex flex-col items-center text-center space-y-4">
                    <img
                        src={RauwLogoImg}
                        alt="RAUW Logo"
                        translate="no"
                        className="h-30 w-auto object-contain"
                    />
                </div>

                <div className="space-y-4">
                    <h4 className="font-black text-xs uppercase tracking-widest text-secondary-text">Menu</h4>
                    <ul className="space-y-2 text-sm font-medium">
                        <li><a href="/feed" className="hover:text-primary-accent transition-colors">Home</a></li>
                        <li><a href="/meld" className="hover:text-primary-accent transition-colors">Melding doen</a></li>
                    </ul>
                </div>

                <div className="space-y-4">
                    <h4 className="font-black text-xs uppercase tracking-widest text-secondary-text">Contact</h4>
                    <p className="text-sm">
                        Vragen over handhaving?<br />
                        Neem contact op met de gemeente.
                    </p>
                </div>
            </div>

            <div className="max-w-6xl mx-auto mt-12 pt-8 border-t border-primary-border text-center text-[10px] uppercase tracking-widest text-secondary-text">
                &copy; {new Date().getFullYear()} Rotterdamse Actie Uit de Wijken. Alle rechten voorbehouden.
            </div>
        </footer>
    );
}