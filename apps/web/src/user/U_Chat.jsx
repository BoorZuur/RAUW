import React, { useState, useEffect } from 'react';
import { Send, User, ShieldCheck, MessageCircle, MoreVertical } from 'lucide-react';
import Nav from '../components/U_Nav.jsx';
import Footer from "../components/Footer.jsx";
import { getMyIssues, getParticipatingIssues } from '../services/issueService';
import { fetchChats, sendMessage, markMessagesRead } from '../services/issueChatService';
import useIssueChatPolling from '../hooks/useIssueChatPolling';

export default function UserChatPage() {
    const [issues, setIssues] = useState([]);
    const [selectedIssue, setSelectedIssue] = useState(null);
    const [activeChat, setActiveChat] = useState(null);
    const [inputValue, setInputValue] = useState('');

    const { messages, isLoading, refresh } = useIssueChatPolling(
        selectedIssue?.id, 
        activeChat?.id
    );

    useEffect(() => {
        async function loadIssues() {
            try {
                // Fetch issues where user is author or participant
                const [my, participating] = await Promise.all([
                    getMyIssues(),
                    getParticipatingIssues()
                ]);
                const combined = [...my, ...participating];
                const uniqueIds = new Set();
                const uniqueIssues = [];
                for (const issue of combined) {
                    if (!uniqueIds.has(issue.id)) {
                        uniqueIds.add(issue.id);
                        uniqueIssues.push(issue);
                    }
                }
                setIssues(uniqueIssues);
            } catch (err) {
                console.error("Failed to fetch issues", err);
            }
        }
        loadIssues();
    }, []);

    useEffect(() => {
        async function loadChat() {
            if (!selectedIssue) {
                setActiveChat(null);
                return;
            }
            try {
                const chats = await fetchChats(selectedIssue.id);
                // The user only sees their own chat with an officer. 
                // There's at most 1 chat for them per issue in this design.
                setActiveChat(chats.length > 0 ? chats[0] : null);
            } catch (err) {
                console.error("Failed to fetch chat", err);
                setActiveChat(null);
            }
        }
        loadChat();
    }, [selectedIssue]);

    useEffect(() => {
        // Mark messages as read when they load
        if (selectedIssue && activeChat && messages.length > 0) {
            const hasUnread = messages.some(m => !m.read_at && m.sender_type === 'officer');
            if (hasUnread) {
                markMessagesRead(selectedIssue.id, activeChat.id).catch(console.error);
            }
        }
    }, [messages, selectedIssue, activeChat]);

    const handleSendMessage = async () => {
        if (!inputValue.trim() || !selectedIssue || !activeChat) return;
        try {
            await sendMessage(selectedIssue.id, activeChat.id, inputValue);
            setInputValue('');
            refresh();
        } catch (err) {
            console.error("Failed to send message", err);
        }
    };

    return (
        <div className="min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300 overflow-x-hidden">
            <div className="z-50"><Nav/></div>
            <div className="grow flex w-full pt-16">
                {/* Linker paneel */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300"></div>
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">
                        {/* Icons */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><circle cx="12" cy="11" r="2" className="opacity-60" /></svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><polyline points="22 11.08 12 19 9 16" /><path d="M22 4L12 14.01l-3-3" className="opacity-40" /><circle cx="12" cy="12" r="10" strokeDasharray="3 3" className="opacity-50" /></svg>
                        </div>
                    </div>
                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300"></div>
                </div>

                <main className="w-full max-w-5xl mx-auto pt-26 px-6 pb-12 flex h-[calc(100vh-64px)] gap-6">
                    {/* ZIJBALK: Chatlijst */}
                    <aside className="w-80 shrink-0 bg-primary-bg-cards border border-primary-border rounded-3xl flex flex-col overflow-hidden h-full">
                        <div className="p-6 border-b border-primary-border">
                            <h2 className="font-headline font-bold text-lg">Mijn gesprekken</h2>
                        </div>
                        <div className="flex-1 overflow-y-auto">
                            {issues.length === 0 ? (
                                <div className="p-4 text-sm text-secondary-text text-center">Geen meldingen gevonden.</div>
                            ) : (
                                issues.map((issue) => (
                                    <div 
                                        key={issue.id} 
                                        onClick={() => setSelectedIssue(issue)}
                                        className={`p-4 cursor-pointer border-b border-primary-border hover:bg-primary-bg transition-colors ${selectedIssue?.id === issue.id ? 'bg-primary-bg' : ''}`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-primary-border flex items-center justify-center">
                                                <MessageCircle size={18} />
                                            </div>
                                            <div className="flex-1 overflow-hidden">
                                                <p className="font-bold text-sm truncate">Melding #{issue.id}</p>
                                                <p className="text-xs text-secondary-text truncate">{issue.category?.name || 'Onbekende categorie'}</p>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </aside>

                    {/* HOOFD CHAT VENSTER */}
                    <section className="flex-1 bg-primary-bg-cards border border-primary-border rounded-3xl shadow-sm flex flex-col overflow-hidden h-full">
                        {selectedIssue ? (
                            <>
                                <header className="p-6 border-b border-primary-border flex items-center justify-between shrink-0">
                                    <div className="flex items-center gap-3">
                                        <div className="bg-primary-accent/10 p-2 rounded-full text-primary-accent">
                                            <ShieldCheck size={20} />
                                        </div>
                                        <div>
                                            <h1 className="font-headline font-bold">Handhaving</h1>
                                            <p className="text-xs text-secondary-text">Gesprek over melding #{selectedIssue.id}</p>
                                        </div>
                                    </div>
                                    <MoreVertical size={20} className="text-secondary-text cursor-pointer" />
                                </header>

                                {/* Berichtenlijst */}
                                <div className="flex-1 overflow-y-auto p-6 space-y-6">
                                    {!activeChat && (
                                        <div className="text-center text-sm text-secondary-text my-10">
                                            Er is nog geen gesprek gestart door een handhaver voor deze melding.
                                        </div>
                                    )}
                                    {isLoading && messages.length === 0 && (
                                        <div className="text-center text-sm text-secondary-text">Laden...</div>
                                    )}
                                    {messages.map((msg) => {
                                        const isOfficer = msg.sender_type === 'officer';
                                        return isOfficer ? (
                                            <div key={msg.id} className="flex items-end gap-3">
                                                <div className="w-8 h-8 rounded-full bg-primary-border flex items-center justify-center shrink-0">
                                                    <ShieldCheck size={16} className="text-primary-accent" />
                                                </div>
                                                <div className="flex flex-col items-start">
                                                    <div className="bg-primary-border px-5 py-3 rounded-2xl rounded-bl-none text-primary-text text-sm max-w-[85%] shadow-sm">
                                                        {msg.content}
                                                    </div>
                                                    <span className="text-[10px] text-secondary-text mt-1 ml-2">{new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                                </div>
                                            </div>
                                        ) : (
                                            <div key={msg.id} className="flex justify-end items-end gap-3">
                                                <div className="flex flex-col items-end">
                                                    <div className="bg-primary-accent px-5 py-3 rounded-2xl rounded-br-none text-white text-sm max-w-[85%] shadow-sm">
                                                        {msg.content}
                                                    </div>
                                                    <span className="text-[10px] text-secondary-text mt-1 mr-2">{new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                {/* Input field */}
                                {activeChat && activeChat.is_open && (
                                    <div className="p-4 border-t border-primary-border bg-primary-bg-cards shrink-0">
                                        <div className="flex gap-3 bg-primary-bg border border-primary-border rounded-full px-5 py-2 items-center">
                                            <input
                                                value={inputValue}
                                                onChange={(e) => setInputValue(e.target.value)}
                                                onKeyDown={(e) => e.key === 'Enter' && handleSendMessage()}
                                                className="flex-1 bg-transparent py-2 text-sm focus:outline-none placeholder:text-secondary-text"
                                                placeholder="Typ uw bericht..."
                                            />
                                            <button onClick={handleSendMessage} className="text-primary-accent hover:opacity-80 transition-opacity">
                                                <Send size={20} />
                                            </button>
                                        </div>
                                    </div>
                                )}
                                {activeChat && !activeChat.is_open && (
                                    <div className="p-4 border-t border-primary-border bg-primary-bg-cards shrink-0 text-center text-sm text-secondary-text">
                                        Dit gesprek is gesloten.
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="flex items-center justify-center h-full text-secondary-text">
                                Selecteer een melding om het gesprek te openen.
                            </div>
                        )}
                    </section>
                </main>

                {/* Rechter paneel */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300 self-end"></div>
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">
                        {/* Icons */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><path d="M4 20 L15 4 L18 5 L10 20" /><line x1="2" y1="20" x2="22" y2="20" /><line x1="14" y1="6" x2="20" y2="20" className="opacity-40" /><line x1="13" y1="9" x2="17" y2="20" className="opacity-40" /></svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="10" width="6" height="11" /><rect x="9" y="3" width="6" height="18" /><rect x="16" y="8" width="6" height="13" /></svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="5" x2="12" y2="19" /><line x1="8" y1="9" x2="16" y2="9" /><path d="M5 12a7 7 0 0 0 14 0" /><circle cx="12" cy="4" r="1" /></svg>
                        </div>
                    </div>
                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300 self-end"></div>
                </div>
            </div>

            <div className="z-10 bg-primary-bg"><Footer /></div>
        </div>
    );
}