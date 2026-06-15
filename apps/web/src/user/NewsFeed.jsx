import React from 'react';
import { Bell, Shield } from 'lucide-react';
import Nav from '../components/U_Nav.jsx';
import Footer from '../components/Footer.jsx';

export default function NewsFeed() {
    const newsItems = [
        {
            id: 1,
            title: 'Nieuwe buurt-app gelanceerd!',
            content: 'Welkom bij de nieuwe buurt-app. Via deze feed houden we je op de hoogte van belangrijke mededelingen van de wijkbeheerders.',
            officer_name: 'Wijkbeheerder',
            created_at: '2026-06-15'
        }
    ];

    return (
        <div className="min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300">
            <div className="z-50"><Nav /></div>

            <div className="grow flex w-full pt-26">
                {/* Linkerzijpaneel (Identiek aan Feed.jsx) */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 opacity-35">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40"></div>
                </div>

                {/* Pagina inhoud */}
                <main className="grow w-full max-w-3xl mx-auto px-6 mt-8 pb-12">
                    <header className="flex items-center gap-4 mb-12">
                        <div className="p-4 bg-primary-accent/10 rounded-2xl">
                            <Bell className="w-8 h-8 text-primary-accent" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-headline font-black text-white">Nieuws & Updates</h1>
                            <p className="text-secondary-text font-label text-sm mt-1">Officiële berichten vanuit de wijk</p>
                        </div>
                    </header>

                    <div className="space-y-8">
                        {newsItems.map((item) => (
                            <article key={item.id} className="bg-primary-bg-cards border border-primary-border p-8 rounded-3xl shadow-sm hover:border-primary-accent transition-all duration-300">
                                <div className="flex items-center gap-2 mb-4">
                                    <Shield className="w-4 h-4 text-primary-accent" />
                                    <span className="text-[10px] font-label font-black uppercase tracking-widest text-primary-accent">
                                        {item.officer_name}
                                    </span>
                                </div>
                                <h2 className="font-headline text-2xl font-bold text-white mb-3">{item.title}</h2>
                                <p className="font-body text-stone-300 text-sm leading-relaxed mb-6">
                                    {item.content}
                                </p>
                                <time className="text-[10px] font-label font-bold text-secondary-text uppercase tracking-widest opacity-60">
                                    {new Date(item.created_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' })}
                                </time>
                            </article>
                        ))}
                    </div>
                </main>

                {/* Rechterzijpaneel (Identiek aan Feed.jsx) */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 opacity-35 self-end">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 self-end"></div>
                </div>
            </div>

            <div className="z-10 bg-primary-bg"><Footer /></div>
        </div>
    );
}