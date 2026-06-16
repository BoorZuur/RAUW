import React from 'react';
import { Send, User, ShieldCheck, MessageCircle, MoreVertical } from 'lucide-react';
import Nav from '../components/U_Nav.jsx';
import Footer from "../components/Footer.jsx";
import MainStoryCard from "../components/MainStoryCard.jsx";
import StoryDetailModal from "../modal/StoryDetailModal.jsx";

export default function UserChatPage() {
    return (
        <div className="min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300 overflow-x-hidden">

            <div className="z-50">
                <Nav/>
            </div>

            <div className="grow flex w-full pt-16">

                {/* ==================== LINKER PANEL: HANDHAVING & TOEZICHT KETEN ==================== */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">

                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300"></div>

                    {/* Verticale stapeling van Toezicht iconen */}
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">

                        {/* Icoon 1: Schild (Veiligheid) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <circle cx="12" cy="11" r="2" className="opacity-60" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 2: Handdruk (Samenwerking in de wijk) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 3: Checkmark (Opgeloste meldingen / Resultaat) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="22 11.08 12 19 9 16" />
                                <path d="M22 4L12 14.01l-3-3" className="opacity-40" />
                                <circle cx="12" cy="12" r="10" strokeDasharray="3 3" className="opacity-50" />
                            </svg>
                        </div>

                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300"></div>
                </div>

                {/* ==================== MIDDEN ==================== */}
                <main className="w-full max-w-5xl mx-auto pt-26 px-6 pb-12 flex h-screen gap-6">

                    {/* ZIJBALK: Chatlijst (Schakelen tussen chats) */}
                    <aside className="w-80 shrink-0 bg-primary-bg-cards border border-primary-border rounded-3xl flex flex-col overflow-hidden">
                        <div className="p-6 border-b border-primary-border">
                            <h2 className="font-headline font-bold text-lg">Mijn gesprekken</h2>
                        </div>
                        <div className="flex-1 overflow-y-auto">
                            {[1, 2, 3].map((i) => (
                                <div key={i} className={`p-4 cursor-pointer border-b border-primary-border hover:bg-primary-bg transition-colors ${i === 1 ? 'bg-primary-bg' : ''}`}>
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-full bg-primary-border flex items-center justify-center">
                                            <MessageCircle size={18} />
                                        </div>
                                        <div className="flex-1 overflow-hidden">
                                            <p className="font-bold text-sm truncate">Melding #{100 + i}</p>
                                            <p className="text-xs text-secondary-text truncate">Laatste bericht in gesprek...</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </aside>

                    {/* HOOFD CHAT VENSTER */}
                    <section className="flex-1 bg-primary-bg-cards border border-primary-border rounded-3xl shadow-sm flex flex-col overflow-hidden">
                        <header className="p-6 border-b border-primary-border flex items-center justify-between shrink-0">
                            <div className="flex items-center gap-3">
                                <div className="bg-primary-accent/10 p-2 rounded-full text-primary-accent">
                                    <ShieldCheck size={20} />
                                </div>
                                <div>
                                    <h1 className="font-headline font-bold">Handhaving</h1>
                                    <p className="text-xs text-secondary-text">Gesprek over melding #101</p>
                                </div>
                            </div>
                            <MoreVertical size={20} className="text-secondary-text cursor-pointer" />
                        </header>

                        {/* Berichtenlijst */}
                        <div className="flex-1 overflow-y-auto p-6 space-y-6">
                            <div className="flex items-end gap-3">
                                <div className="w-8 h-8 rounded-full bg-primary-border flex items-center justify-center shrink-0">
                                    <User size={16} className="text-secondary-text" />
                                </div>
                                <div className="flex flex-col items-start">
                                    <div className="bg-primary-border px-5 py-3 rounded-2xl rounded-bl-none text-primary-text text-sm max-w-[85%] shadow-sm">
                                        Welkom. Ik heb uw melding ontvangen en ben onderweg.
                                    </div>
                                    <span className="text-[10px] text-secondary-text mt-1 ml-2">14:20</span>
                                </div>
                            </div>

                            <div className="flex justify-end items-end gap-3">
                                <div className="flex flex-col items-end">
                                    <div className="bg-primary-accent px-5 py-3 rounded-2xl rounded-br-none text-white text-sm max-w-[85%] shadow-sm">
                                        Bedankt voor de snelle reactie!
                                    </div>
                                    <span className="text-[10px] text-secondary-text mt-1 mr-2">14:22</span>
                                </div>
                            </div>
                        </div>

                        <div className="p-4 border-t border-primary-border bg-primary-bg-cards shrink-0">
                            <div className="flex gap-3 bg-primary-bg border border-primary-border rounded-full px-5 py-2 items-center">
                                <input
                                    className="flex-1 bg-transparent py-2 text-sm focus:outline-none placeholder:text-secondary-text"
                                    placeholder="Typ uw bericht..."
                                />
                                <button className="text-primary-accent hover:opacity-80 transition-opacity">
                                    <Send size={20} />
                                </button>
                            </div>
                        </div>
                    </section>
                </main>

                {/* ==================== RECHTER PANEL: ROTTERDAM IDENTITEIT ==================== */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">

                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300 self-end"></div>

                    {/* Verticale stapeling van Rotterdamse iconen */}
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">

                        {/* Icoon 1: Erasmusbrug */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M4 20 L15 4 L18 5 L10 20" />
                                <line x1="2" y1="20" x2="22" y2="20" />
                                <line x1="14" y1="6" x2="20" y2="20" className="opacity-40" />
                                <line x1="13" y1="9" x2="17" y2="20" className="opacity-40" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 2: Stad / Gebouwen (De Wijken) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <rect x="2" y="10" width="6" height="11" />
                                <rect x="9" y="3" width="6" height="18" />
                                <rect x="16" y="8" width="6" height="13" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 3: Maritiem / Haven (Anker en Water) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="8" y1="9" x2="16" y2="9" />
                                <path d="M5 12a7 7 0 0 0 14 0" />
                                <circle cx="12" cy="4" r="1" />
                            </svg>
                        </div>

                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300 self-end"></div>
                </div>

            </div>

            <div className="z-10 bg-primary-bg">
                <Footer />
            </div>
        </div>
    );
}