import React, { useState } from 'react';
import { Send, Lock, Unlock, Search } from 'lucide-react';
import HM_Nav from '../components/HM_Nav.jsx';

export default function H_ChatPage() {
    const [activeChat, setActiveChat] = useState({ id: 1, is_open: true });
    const [messages] = useState([
        { id: 1, content: "Hallo, kunt u de situatie toelichten?", sender_type: 'officer' },
        { id: 2, content: "Er staan hier een groep jongeren die de doorgang blokkeren.", sender_type: 'user' }
    ]);

    return (
        <div className="min-h-screen flex bg-primary-bg text-primary-text transition-colors duration-200">
            <div className="shrink-0 w-64 border-r border-primary-border">
                <HM_Nav />
            </div>

            <main className="flex-1 p-8 flex flex-col min-h-screen">
                <header className="flex justify-between items-center mb-8">
                    <div>
                        <h1 className="text-4xl font-bold">Communicatie</h1>
                        <p className="text-secondary-text text-sm mt-1">Beheer hier alle lopende gesprekken</p>
                    </div>
                    <button className="flex items-center gap-2 bg-primary-bg-cards border border-primary-border hover:border-primary-accent transition-all px-5 py-2.5 rounded-xl text-sm font-semibold">
                        {activeChat.is_open ? <Lock size={16}/> : <Unlock size={16}/>}
                        {activeChat.is_open ? 'Gesprek Sluiten' : 'Gesprek Openen'}
                    </button>
                </header>

                <div className="flex-1 bg-primary-bg-cards rounded-4xl flex border border-primary-border shadow-lg overflow-hidden mb-8">
                    {/* Zijbalk */}
                    <aside className="w-80 border-r border-primary-border flex flex-col">
                        <div className="p-5 border-b border-primary-border">
                            <div className="relative">
                                <Search className="absolute left-3 top-3 text-secondary-text" size={16} />
                                <input className="w-full pl-10 pr-4 py-2.5 bg-primary-bg border border-primary-border rounded-xl text-sm focus:outline-none" placeholder="Zoek gesprekken..." />
                            </div>
                        </div>
                        <div className="flex-1 overflow-y-auto">
                            {[1, 2, 3].map(i => (
                                <div key={i} className={`p-5 cursor-pointer hover:bg-primary-border border-b border-primary-border transition-all ${i === 1 ? 'bg-primary-border/50' : ''}`}>
                                    <div className="flex justify-between items-start mb-1">
                                        <p className="font-bold">Gesprek #{100 + i}</p>
                                        <span className="text-[10px] font-bold bg-secondary-accent text-white px-2 py-0.5 rounded-full uppercase">Open</span>
                                    </div>
                                    <p className="text-xs text-secondary-text truncate">Laatste bericht: Groep jongeren op plein...</p>
                                </div>
                            ))}
                        </div>
                    </aside>

                    {/* Chatvenster */}
                    <section className="flex-1 flex flex-col">
                        <div className="flex-1 p-8 overflow-y-auto space-y-6">
                            {messages.map(m => (
                                <div key={m.id} className={`flex ${m.sender_type === 'officer' ? 'justify-end' : 'justify-start'}`}>
                                    <div className={`max-w-[70%] p-4 rounded-2xl ${m.sender_type === 'officer' ? 'bg-primary-accent text-white rounded-br-none' : 'bg-primary-bg border border-primary-border rounded-bl-none'}`}>
                                        <p className="text-sm">{m.content}</p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="p-6 border-t border-primary-border bg-primary-bg">
                            <div className="flex gap-4 items-center">
                                <input className="flex-1 bg-primary-bg-cards border border-primary-border rounded-full px-6 py-3.5 focus:outline-none" placeholder="Typ een antwoord..." />
                                <button className="bg-primary-accent text-white p-4 rounded-full hover:opacity-90 transition-all"><Send size={18}/></button>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    );
}