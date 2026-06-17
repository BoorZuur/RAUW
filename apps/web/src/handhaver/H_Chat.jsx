import React, { useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import axios from 'axios';
import { Send, Lock, Unlock, Search, MessageCircle, Paperclip, Check, CheckCheck, X } from 'lucide-react';
import HM_Nav from '../components/HM_Nav.jsx';
import AuthAttachment from '../components/AuthAttachment.jsx';
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
    const [allChatsForIssue, setAllChatsForIssue] = useState([]);

    const [inputValue, setInputValue] = useState('');
    const [selectedFiles, setSelectedFiles] = useState(null);

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
        let isMounted = true;
        async function loadChatDetails() {
            if (!selectedIssue) {
                if (isMounted) {
                    setActiveChat(null);
                    setAllChatsForIssue([]);
                }
                return;
            }
            try {
                const chats = await fetchChats(selectedIssue.id);
                if (isMounted) {
                    setAllChatsForIssue(prev => JSON.stringify(prev) !== JSON.stringify(chats) ? chats : prev);

                    if (chats.length > 0) {
                        setActiveChat(prev => {
                            if (!prev) {
                                if (location.state?.selectedChatId) {
                                    const target = chats.find(c => c.id === location.state.selectedChatId);
                                    if (target) return target;
                                }
                                return chats[0];
                            } else {
                                const target = chats.find(c => c.id === prev.id);
                                if (target && JSON.stringify(target) !== JSON.stringify(prev)) {
                                    return target;
                                }
                                return prev;
                            }
                        });
                    } else {
                        setActiveChat(null);
                    }
                }
            } catch (err) {
                console.error("Failed to fetch chat details", err);
                if (isMounted) {
                    setActiveChat(null);
                    setAllChatsForIssue([]);
                }
            }
        }
        loadChatDetails();

        const interval = setInterval(loadChatDetails, 10000);
        return () => {
            isMounted = false;
            clearInterval(interval);
        };
    }, [selectedIssue, location.state]);

    useEffect(() => {
        if (selectedIssue && activeChat && messages.length > 0) {
            const hasUnread = messages.some(m => !m.read_at && m.sender_type === 'user');
            if (hasUnread) {
                markMessagesRead(selectedIssue.id, activeChat.id).catch(console.error);
            }
        }
    }, [messages, selectedIssue, activeChat]);

    const handleSendMessage = async () => {
        if ((!inputValue.trim() && (!selectedFiles || selectedFiles.length === 0)) || !selectedIssue || !activeChat) return;
        try {
            await sendMessage(selectedIssue.id, activeChat.id, inputValue, selectedFiles);
            setInputValue('');
            setSelectedFiles(null);
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
                            {activeChat.status === 'open' ? <Lock size={16} /> : <Unlock size={16} />}
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
                                        <p className="font-bold">Melding #{issue.id} - {issue.title}</p>
                                    </div>
                                    <p className="text-xs text-secondary-text truncate mb-1">{issue.category?.name || 'Onbekende categorie'}</p>
                                    {issue.description && <p className="text-xs text-secondary-text opacity-80 line-clamp-2">{issue.description}</p>}
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
                                        <div className="flex items-center gap-4">
                                            <div>
                                                <p className="font-bold">Gesprek met melder</p>
                                                <p className="text-xs text-secondary-text">Melding #{selectedIssue.id}</p>
                                            </div>
                                            {allChatsForIssue.length > 1 && (
                                                <select
                                                    className="ml-4 border border-primary-border rounded-lg text-sm bg-primary-bg text-primary-text px-3 py-1.5 focus:outline-none focus:border-primary-accent"
                                                    value={activeChat?.id || ''}
                                                    onChange={e => {
                                                        const target = allChatsForIssue.find(c => c.id === parseInt(e.target.value, 10));
                                                        if (target) setActiveChat(target);
                                                    }}
                                                >
                                                    {allChatsForIssue.map((chat, idx) => (
                                                        <option key={chat.id} value={chat.id}>
                                                            Deelnemer {chat.user?.username || chat.user?.display_name || `(Chat #${chat.id})`}
                                                        </option>
                                                    ))}
                                                </select>
                                            )}
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
                                        {messages.map(m => {
                                            const isOfficer = m.sender_type === 'officer';
                                            return (
                                                <div key={m.id} className={`flex ${isOfficer ? 'justify-end' : 'justify-start'}`}>
                                                    <div className={`flex flex-col ${isOfficer ? 'items-end' : 'items-start'}`}>
                                                        <span className="text-xs text-secondary-text mb-1 ml-1 mr-1">
                                                            {isOfficer ? (m.sender?.display_name || 'Jij') : (m.sender?.display_name || 'Melder')}
                                                        </span>
                                                        <div className={`max-w-[85%] p-4 rounded-2xl ${isOfficer ? 'bg-primary-accent text-white rounded-br-none' : 'bg-primary-bg border border-primary-border rounded-bl-none'}`}>
                                                            {m.content && <p className="text-sm whitespace-pre-wrap">{m.content}</p>}
                                                            {m.attachments && m.attachments.length > 0 && (
                                                                <div className="mt-2 flex flex-col gap-2">
                                                                    {m.attachments.map(att => (
                                                                        <AuthAttachment key={att.id} attachment={att} />
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center gap-1 mt-1">
                                                            <span className="text-[10px] text-secondary-text">{new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                                            {isOfficer && (
                                                                m.is_read ? <CheckCheck size={12} className="text-primary-accent" /> : <Check size={12} className="text-secondary-text" />
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {activeChat.status === 'open' ? (
                                        <div className="p-4 border-t border-primary-border bg-primary-bg flex flex-col gap-2 shrink-0">
                                            {selectedFiles && selectedFiles.length > 0 && (
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    {Array.from(selectedFiles).map((file, idx) => (
                                                        <div key={idx} className="bg-primary-border text-primary-text text-xs px-3 py-1.5 rounded-full flex items-center gap-2">
                                                            <span className="truncate max-w-[150px]">{file.name}</span>
                                                            <button onClick={() => {
                                                                const dt = new DataTransfer();
                                                                Array.from(selectedFiles).filter((_, i) => i !== idx).forEach(f => dt.items.add(f));
                                                                setSelectedFiles(dt.files.length > 0 ? dt.files : null);
                                                            }} className="hover:text-red-500"><X size={12} /></button>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                            <div className="flex gap-4 items-center">
                                                <label className="cursor-pointer p-3 bg-primary-bg-cards border border-primary-border hover:bg-primary-border transition-colors rounded-full text-secondary-text">
                                                    <Paperclip size={20} />
                                                    <input type="file" multiple className="hidden" onChange={e => setSelectedFiles(e.target.files)} />
                                                </label>
                                                <input
                                                    value={inputValue}
                                                    onChange={e => setInputValue(e.target.value)}
                                                    onKeyDown={e => e.key === 'Enter' && handleSendMessage()}
                                                    className="flex-1 bg-primary-bg-cards border border-primary-border focus:border-primary-accent rounded-full px-6 py-3.5 focus:outline-none"
                                                    placeholder="Typ een antwoord..."
                                                />
                                                <button onClick={handleSendMessage} className="bg-primary-accent text-white p-4 rounded-full hover:opacity-90 transition-all">
                                                    <Send size={18} />
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