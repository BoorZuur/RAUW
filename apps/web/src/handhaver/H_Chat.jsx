import React, { useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import axios from 'axios';
import { Send, Lock, Unlock, Search, MessageCircle } from 'lucide-react';
import HM_Nav from '../components/HM_Nav.jsx';
import { listIssuesForMap } from '../services/issueService';
import { fetchChats, openChat, closeChat, sendMessage, markMessagesRead } from '../services/issueChatService';
import useIssueChatPolling from '../hooks/useIssueChatPolling';

export default function H_ChatPage() {
    const location = useLocation();
    const [issues, setIssues] = useState([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedIssue, setSelectedIssue] = useState(null);
    const [officer, setOfficer] = useState(null);
    
    const [activeChat, setActiveChat] = useState(null);
    
    const [inputValue, setInputValue] = useState('');

    const { messages, isLoading, refresh } = useIssueChatPolling(
        selectedIssue?.id, 
        activeChat?.id
    );

    useEffect(() => {
        async function loadIssues() {
            try {
                const token = localStorage.getItem('auth_token');
                const meRes = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const officerProfile = meRes.data?.profile || meRes.data;
                setOfficer(officerProfile);

                const data = await listIssuesForMap();
                const assignedIssues = data.filter(issue => issue.assigned_officer_id === officerProfile.id);
                setIssues(assignedIssues);
                
                if (location.state?.selectedIssueId) {
                    const preselected = assignedIssues.find(i => i.id === location.state.selectedIssueId);
                    if (preselected) setSelectedIssue(preselected);
                }
            } catch (err) {
                console.error("Failed to load issues", err);
            }
        }
        loadIssues();
    }, [location.state]);

    useEffect(() => {
        async function loadChatDetails() {
            if (!selectedIssue) {
                setActiveChat(null);
                return;
            }
            try {
                const chats = await fetchChats(selectedIssue.id);
                if (chats.length > 0) {
                    setActiveChat(chats[0]);
                } else {
                    setActiveChat(null);
                }
            } catch (err) {
                console.error("Failed to fetch chat details", err);
                setActiveChat(null);
            }
        }
        loadChatDetails();
    }, [selectedIssue]);

    useEffect(() => {
        if (selectedIssue && activeChat && messages.length > 0) {
            const hasUnread = messages.some(m => !m.read_at && m.sender_type === 'user');
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

    const handleToggleChat = async () => {
        if (!selectedIssue || !activeChat) return;
        try {
            if (activeChat.status === 'open') {
                const closed = await closeChat(selectedIssue.id, activeChat.id);
                setActiveChat(closed);
            } else {
                // Reopen the chat
                const payload = activeChat.user_id 
                    ? { user_id: activeChat.user_id } 
                    : { chat_id: activeChat.id };
                const reopened = await openChat(selectedIssue.id, payload);
                setActiveChat(reopened);
            }
            refresh();
        } catch (err) {
            console.error("Failed to toggle chat state", err);
        }
    };

    const filteredIssues = issues.filter(i => 
        i.id.toString().includes(searchQuery) || 
        (i.category?.name || '').toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <div className="min-h-screen flex bg-primary-bg text-primary-text transition-colors duration-200">
            <div className="shrink-0 w-64 border-r border-primary-border">
                <HM_Nav />
            </div>

            <main className="flex-1 p-8 flex flex-col min-h-screen">
                <header className="flex justify-between items-center mb-8 shrink-0">
                    <div>
                        <h1 className="text-4xl font-bold">Communicatie</h1>
                        <p className="text-secondary-text text-sm mt-1">Beheer hier alle lopende gesprekken met melders</p>
                    </div>
                    {activeChat && (
                        <button 
                            onClick={handleToggleChat}
                            className="flex items-center gap-2 bg-primary-bg-cards border border-primary-border hover:border-primary-accent transition-all px-5 py-2.5 rounded-xl text-sm font-semibold"
                        >
                            {activeChat.status === 'open' ? <Lock size={16}/> : <Unlock size={16}/>}
                            {activeChat.status === 'open' ? 'Gesprek Sluiten' : 'Gesprek Heropenen'}
                        </button>
                    )}
                </header>

                <div className="flex-1 bg-primary-bg-cards rounded-4xl flex border border-primary-border shadow-lg overflow-hidden h-[calc(100vh-160px)]">
                    {/* Zijbalk met Meldingen */}
                    <aside className="w-80 border-r border-primary-border flex flex-col shrink-0">
                        <div className="p-5 border-b border-primary-border">
                            <div className="relative">
                                <Search className="absolute left-3 top-3 text-secondary-text" size={16} />
                                <input 
                                    value={searchQuery}
                                    onChange={e => setSearchQuery(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2.5 bg-primary-bg border border-primary-border rounded-xl text-sm focus:outline-none focus:border-primary-accent" 
                                    placeholder="Zoek meldingen..." 
                                />
                            </div>
                        </div>
                        <div className="flex-1 overflow-y-auto">
                            {filteredIssues.map(issue => (
                                <div 
                                    key={issue.id} 
                                    onClick={() => setSelectedIssue(issue)}
                                    className={`p-5 cursor-pointer hover:bg-primary-border border-b border-primary-border transition-all ${selectedIssue?.id === issue.id ? 'bg-primary-border/50' : ''}`}
                                >
                                    <div className="flex justify-between items-start mb-1">
                                        <p className="font-bold">Melding #{issue.id}</p>
                                    </div>
                                    <p className="text-xs text-secondary-text truncate">{issue.category?.name || 'Onbekende categorie'}</p>
                                </div>
                            ))}
                            {filteredIssues.length === 0 && (
                                <div className="p-5 text-sm text-secondary-text text-center">
                                    Geen meldingen gevonden.
                                </div>
                            )}
                        </div>
                    </aside>

                    {/* Chatvenster of Deelnemerslijst */}
                    <section className="flex-1 flex flex-col h-full">
                        {selectedIssue ? (
                            activeChat ? (
                                <>
                                    <div className="p-4 border-b border-primary-border bg-primary-bg-cards flex justify-between items-center shrink-0">
                                        <div>
                                            <p className="font-bold">Gesprek met melder</p>
                                            <p className="text-xs text-secondary-text">Melding #{selectedIssue.id}</p>
                                        </div>
                                        <div>
                                            <span className={`text-[10px] font-bold text-white px-2 py-0.5 rounded-full uppercase ${activeChat.status === 'open' ? 'bg-secondary-accent' : 'bg-red-500'}`}>
                                                {activeChat.status === 'open' ? 'Open' : 'Gesloten'}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex-1 p-8 overflow-y-auto space-y-6">
                                        {isLoading && messages.length === 0 && (
                                            <div className="text-center text-sm text-secondary-text">Laden...</div>
                                        )}
                                        {messages.length === 0 && !isLoading && (
                                            <div className="text-center text-sm text-secondary-text mt-10">Geen berichten. Verstuur het eerste bericht!</div>
                                        )}
                                        {messages.map(m => (
                                            <div key={m.id} className={`flex ${m.sender_type === 'officer' ? 'justify-end' : 'justify-start'}`}>
                                                <div className="flex flex-col items-end">
                                                    <div className={`max-w-[85%] p-4 rounded-2xl ${m.sender_type === 'officer' ? 'bg-primary-accent text-white rounded-br-none' : 'bg-primary-bg border border-primary-border rounded-bl-none'}`}>
                                                        <p className="text-sm">{m.content}</p>
                                                    </div>
                                                    <span className="text-[10px] text-secondary-text mt-1">{new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {activeChat.status === 'open' ? (
                                        <div className="p-6 border-t border-primary-border bg-primary-bg shrink-0">
                                            <div className="flex gap-4 items-center">
                                                <input 
                                                    value={inputValue}
                                                    onChange={e => setInputValue(e.target.value)}
                                                    onKeyDown={e => e.key === 'Enter' && handleSendMessage()}
                                                    className="flex-1 bg-primary-bg-cards border border-primary-border focus:border-primary-accent rounded-full px-6 py-3.5 focus:outline-none" 
                                                    placeholder="Typ een antwoord..." 
                                                />
                                                <button onClick={handleSendMessage} className="bg-primary-accent text-white p-4 rounded-full hover:opacity-90 transition-all">
                                                    <Send size={18}/>
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="p-6 border-t border-primary-border bg-primary-bg text-center text-sm text-secondary-text shrink-0">
                                            Dit gesprek is gesloten.
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div className="flex-1 flex flex-col items-center justify-center p-8 text-center h-full overflow-y-auto">
                                    <div className="mb-6 bg-primary-border w-16 h-16 rounded-full flex items-center justify-center text-secondary-text">
                                        <MessageCircle size={32} />
                                    </div>
                                    <h2 className="text-xl font-bold mb-2">Geen actief gesprek</h2>
                                    <p className="text-secondary-text text-sm mb-8 max-w-md">
                                        Er is nog geen gesprek gestart voor melding #{selectedIssue.id}. Open het CommandCenter om een gesprek te starten met een deelnemer.
                                    </p>
                                </div>
                            )
                        ) : (
                            <div className="flex-1 flex items-center justify-center text-secondary-text h-full">
                                Selecteer een toegewezen melding aan de linkerkant om de communicatie te beheren.
                            </div>
                        )}
                    </section>
                </div>
            </main>
        </div>
    );
}