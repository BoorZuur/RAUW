import React, { useState, useEffect } from 'react';
import { Trash2, Edit2, X, Check, AlertCircle } from 'lucide-react';
import AttachmentImage from '../components/AttachmentImage';
import axios from 'axios';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { 'Accept': 'application/json' }
});

export default function StoryDetailModal({ issue, onClose, onAddComment, onDeleteIssue, onUpdateIssue }) {
    const [commentText, setCommentText] = useState('');
    const [localComments, setLocalComments] = useState(issue?.comments || []);
    const [isEditing, setIsEditing] = useState(false);
    const [editForm, setEditForm] = useState({ title: issue?.title || '', content: issue?.content || '' });
    const [error, setError] = useState(null);
    const [confirmAction, setConfirmAction] = useState(null);

    useEffect(() => {
        if (!issue?.id) return;
        const fetchAllComments = async () => {
            let allComments = [];
            let nextPage = `issues/${issue.id}/comments?per_page=100`;
            try {
                while (nextPage) {
                    const response = await apiClient.get(nextPage);
                    const data = response.data.data || response.data;
                    allComments = [...allComments, ...data];
                    nextPage = response.data.next_page_url ? response.data.next_page_url.split('/api/')[1] : null;
                }
                setLocalComments(allComments);
            } catch (err) {
                console.error("Kon comments niet ophalen:", err);
            }
        };
        fetchAllComments();
    }, [issue?.id]);

    if (!issue) return null;

    const { id, title, content, address, created_at, attachments = [], followers, participant_count, status, category } = issue;
    const totalFollowers = typeof participant_count === 'number' ? participant_count : (Array.isArray(followers) ? followers.length : 0);
    const formattedDate = created_at ? new Date(created_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

    const getStatusDetails = (currentStatus) => {
        switch (currentStatus?.toLowerCase()) {
            case 'open': case 'nieuw': return { label: 'Nieuw', className: 'bg-primary-accent text-white' };
            case 'in_behandeling': case 'in behandeling': return { label: 'In behandeling', className: 'bg-amber-500 text-white' };
            case 'opgelost': return { label: 'Opgelost', className: 'bg-secondary-accent text-white' };
            case 'gesloten': case 'afgehandeld': return { label: 'Gesloten', className: 'bg-primary-border text-secondary-text' };
            default: return { label: currentStatus || 'Open', className: 'bg-primary-accent text-white' };
        }
    };
    const statusDetails = getStatusDetails(status);

    const handleSaveEdit = async () => {
        setError(null);
        try {
            await onUpdateIssue(id, editForm);
            setIsEditing(false);
        } catch (err) {
            setError("Het opslaan is mislukt.");
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            {confirmAction && (
                <div className="absolute inset-0 z-60 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-stone-900 border border-primary-border p-6 rounded-2xl shadow-xl max-w-sm w-full">
                        <h3 className="text-white font-black text-lg mb-2">Weet je het zeker?</h3>
                        <p className="text-stone-400 text-sm mb-6">{confirmAction === 'delete' ? 'Deze issue wordt definitief verwijderd.' : 'Wijzigingen opslaan?'}</p>
                        <div className="flex gap-3">
                            <button onClick={() => setConfirmAction(null)} className="flex-1 py-2 rounded-xl bg-stone-800 text-white font-bold">Annuleren</button>
                            <button onClick={() => { confirmAction === 'delete' ? onDeleteIssue(id) : handleSaveEdit(); setConfirmAction(null); }} className={`flex-1 py-2 rounded-xl font-bold ${confirmAction === 'delete' ? 'bg-red-600' : 'bg-primary-accent'}`}>
                                Bevestigen
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <button onClick={onClose} className="absolute top-6 right-6 text-white hover:text-primary-accent z-50"><X className="w-8 h-8" /></button>

            <div className="absolute top-6 left-6 flex gap-3 z-50">
                {!isEditing ? (
                    <>
                        <button onClick={() => setIsEditing(true)} className="p-3 bg-primary-bg-cards border border-primary-border text-primary-text rounded-xl hover:border-primary-accent"><Edit2 className="w-5 h-5" /></button>
                        <button onClick={() => setConfirmAction('delete')} className="p-3 bg-red-500/10 border border-red-500/20 text-red-500 rounded-xl hover:bg-red-500/20"><Trash2 className="w-5 h-5" /></button>
                    </>
                ) : (
                    <button onClick={() => setConfirmAction('save')} className="p-3 bg-green-500/10 border border-green-500/20 text-green-500 rounded-xl hover:bg-green-500/20"><Check className="w-5 h-5" /></button>
                )}
            </div>

            <div className="w-full max-w-5xl h-[85vh] bg-primary-bg-cards border border-primary-border rounded-3xl overflow-hidden shadow-2xl flex flex-col md:flex-row">
                <div className="flex-1 bg-primary-bg flex flex-col h-1/2 md:h-full border-b md:border-b-0 md:border-r border-primary-border">
                    {/* Attachment sectie teruggeplaatst */}
                    <div className="w-full h-48 md:h-64 bg-stone-950 flex items-center justify-center relative shrink-0 overflow-hidden border-b border-primary-border">
                        {attachments && attachments.length > 0 ? (
                            <AttachmentImage attachment={attachments[0]} className="w-full h-full object-cover" alt={title} />
                        ) : (
                            <div className="text-stone-400 font-label text-xs uppercase font-black">Geen afbeelding</div>
                        )}
                    </div>

                    <div className="flex-1 p-6 sm:p-8 bg-stone-950 overflow-y-auto custom-scrollbar">
                        {error && <div className="mb-4 p-3 bg-red-500/20 text-red-500 rounded-xl flex items-center gap-2 text-sm font-bold">
                            <AlertCircle className="w-4 h-4"/>
                            {error}
                        </div>}
                        {isEditing ? (
                            <div className="flex flex-col gap-4">
                                <input className="text-3xl font-black bg-transparent border-b border-primary-border text-white focus:outline-none"
                                       value={editForm.title} onChange={(e) => setEditForm({...editForm, title: e.target.value})} />
                                <textarea className="w-full h-48 bg-primary-bg p-4 rounded-xl border border-primary-border text-stone-200"
                                          value={editForm.content} onChange={(e) => setEditForm({...editForm, content: e.target.value})} />
                            </div>
                        ) : (
                            <>
                                {/* Status badges teruggeplaatst */}
                                <div className="flex flex-wrap items-center gap-2 mb-4">
                                    <span className="text-[10px] font-label font-black uppercase tracking-widest text-primary-accent bg-primary-accent/10 border border-primary-accent/30 px-2.5 py-1 rounded-md">{address || "Rotterdam"}</span>
                                    <span className={`px-2.5 py-1 rounded-md text-[10px] font-label font-black uppercase ${statusDetails.className}`}>{statusDetails.label}</span>
                                    {category && <span className="bg-stone-800 text-stone-200 border border-stone-700 px-2.5 py-1 rounded-md text-[10px] font-label font-bold uppercase">{category.name || category}</span>}
                                </div>
                                <h2 className="font-headline font-black text-xl sm:text-3xl text-white mb-4">{title}</h2>
                                <p className="font-label text-sm text-stone-200/95 leading-relaxed">{content}</p>
                            </>
                        )}
                    </div>
                </div>

                <div className="w-full md:w-100 flex flex-col h-1/2 md:h-full bg-primary-bg-cards shrink-0">
                    <div className="p-4 border-b border-primary-border font-black text-sm text-primary-text">{totalFollowers} volgers</div>
                    <div className="flex-1 overflow-y-auto p-6 space-y-4">
                        {localComments.map((comment) => (
                            <div key={comment.id} className="flex gap-3">
                                <div className="w-8 h-8 rounded-full bg-stone-800 flex items-center justify-center shrink-0">
                                    <span className="text-[10px] font-black text-primary-accent">{(comment.user?.name || 'U').charAt(0).toUpperCase()}</span>
                                </div>
                                <div className="flex-1 bg-primary-bg p-3 rounded-2xl rounded-tl-none border border-primary-border">
                                    <p className="text-[13px] text-stone-200">{comment.content || comment.body}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                    <form
                        onSubmit={(e) => { e.preventDefault();
                            onAddComment?.(id, commentText); setCommentText(''); }}
                        className="p-4 border-t border-primary-border">
                        <input
                            type="text"
                            value={commentText} onChange={(e) => setCommentText(e.target.value)}
                            placeholder="Reactie..."
                            className="w-full bg-primary-bg p-2 rounded-xl border border-primary-border text-white" />
                    </form>
                </div>
            </div>
        </div>
    );
}