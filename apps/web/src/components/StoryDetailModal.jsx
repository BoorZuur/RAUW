import React, { useState } from 'react';

export default function StoryDetailModal({ issue, onClose, onAddComment }) {
    const [commentText, setCommentText] = useState('');

    if (!issue) return null;

    const {
        title,
        content,
        address,
        created_at,
        image_url,
        followers,
        participant_count,
        status,
        category,
        comments = []
    } = issue;

    const totalFollowers = typeof participant_count === 'number'
        ? participant_count
        : Array.isArray(followers) ? followers.length : 0;

    const formattedDate = created_at
        ? new Date(created_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' })
        : '';

    // Status badge styling (matcht met je MainStoryCard)
    const getStatusDetails = (currentStatus) => {
        switch (currentStatus?.toLowerCase()) {
            case 'open':
            case 'nieuw':
                return { label: 'Nieuw', className: 'bg-primary-accent text-white' };
            case 'in_behandeling':
            case 'in behandeling':
                return { label: 'In behandeling', className: 'bg-amber-500 text-white' };
            case 'opgelost':
                return { label: 'Opgelost', className: 'bg-secondary-accent text-white' };
            case 'gesloten':
            case 'afgehandeld':
                return { label: 'Gesloten', className: 'bg-primary-border text-secondary-text' };
            default:
                return { label: currentStatus || 'Open', className: 'bg-primary-accent text-white' };
        }
    };

    const statusDetails = getStatusDetails(status);

    const handleCommentSubmit = (e) => {
        e.preventDefault();
        if (!commentText.trim()) return;
        onAddComment?.(issue.id, commentText);
        setCommentText('');
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-fade-in">
            {/* Sluitknop buiten de modal (rechtsboven) */}
            <button
                onClick={onClose}
                className="absolute top-6 right-6 text-white hover:text-primary-accent transition-colors cursor-pointer p-2 z-50 focus:outline-none"
            >
                <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {/* Main Instagram-Style Container */}
            <div className="w-full max-w-5xl h-[85vh] bg-primary-bg-cards border border-primary-border rounded-3xl overflow-hidden shadow-2xl flex flex-col md:flex-row">

                {/* LINKERKANT: Foto + Alle Tekst/Informatie */}
                <div className="flex-1 bg-primary-bg border-b md:border-b-0 md:border-r border-primary-border h-1/2 md:h-full flex flex-col justify-between relative overflow-hidden group">
                    {/* Foto / Fallback */}
                    <div className="absolute inset-0 bg-primary-border/10 flex items-center justify-center z-0">
                        {image_url ? (
                            <img
                                src={image_url}
                                alt={title}
                                className="w-full h-full object-cover"
                            />
                        ) : (
                            <span className="text-secondary-text font-label text-sm font-bold">Geen afbeelding beschikbaar</span>
                        )}
                    </div>

                    {/* Gradient Overlay + Informatie (Onderaan vastgezet) */}
                    <div className="absolute bottom-0 inset-x-0 bg-linear-to-t from-black/90 via-black/60 to-transparent p-6 sm:p-8 pt-24 text-white z-10 max-h-[70%] overflow-y-auto custom-scrollbar">
                        <div className="flex flex-wrap items-center gap-2 mb-3">
                            {/* Locatie */}
                            <span className="text-[10px] sm:text-[11px] font-label font-black uppercase tracking-widest text-primary-accent bg-primary-accent/10 border border-primary-accent/20 px-2 py-0.5 rounded">
                                {address || "Rotterdam"}
                            </span>
                            {/* Status Badge */}
                            <span className={`px-2.5 py-0.5 rounded text-[10px] sm:text-[11px] font-label font-black uppercase tracking-wider ${statusDetails.className}`}>
                                {statusDetails.label}
                            </span>
                            {/* Categorie Badge */}
                            {category && (
                                <span className="bg-white/10 text-white border border-white/20 px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-label font-bold">
                                    {category.name || category}
                                </span>
                            )}
                        </div>

                        {/* Titel */}
                        <h2 className="font-headline font-black text-xl sm:text-3xl tracking-tight leading-tight mb-3">
                            {title}
                        </h2>

                        {/* Omschrijving */}
                        <p className="font-label text-sm text-stone-200/90 leading-relaxed max-w-2xl">
                            {content}
                        </p>
                    </div>
                </div>

                {/* RECHTERKANT: Volgers & Reacties Feed */}
                <div className="w-full md:w-100 flex flex-col h-1/2 md:h-full bg-primary-bg-cards shrink-0">

                    {/* Header: Volgers overzicht en datum */}
                    <div className="p-4 border-b border-primary-border flex items-center justify-between bg-primary-bg/40">
                        <div className="flex items-center gap-1.5 text-sm font-label text-primary-text">
                            <svg className="w-4 h-4 text-secondary-text" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span className="font-black">{totalFollowers}</span>
                            <span className="text-secondary-text">volgers</span>
                        </div>

                        {formattedDate && (
                            <span className="text-[11px] text-secondary-text font-label uppercase font-bold tracking-wider">
                                {formattedDate}
                            </span>
                        )}
                    </div>

                    {/* Scrollbare Reactielijst */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-primary-bg-cards">
                        {comments.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-center p-4">
                                <div className="w-12 h-12 rounded-full border-2 border-dashed border-primary-border flex items-center justify-center mb-2 text-secondary-text/60">💬</div>
                                <p className="text-xs text-secondary-text font-label font-bold">Nog geen reacties</p>
                                <p className="text-[11px] text-secondary-text/60 font-label mt-0.5">Wees de eerste om een update te delen.</p>
                            </div>
                        ) : (
                            comments.map((comment) => (
                                <div key={comment.id} className="flex gap-3 items-start text-sm font-label animate-fade-in">
                                    <div className="w-8 h-8 rounded-full bg-primary-bg text-primary-text font-black text-xs flex items-center justify-center shrink-0 border border-primary-border shadow-sm">
                                        {(comment.user?.name || 'U').charAt(0).toUpperCase()}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-primary-text leading-tight mb-0.5">
                                            <span className="font-black mr-1.5">{comment.user?.name || 'Buurtbewoner'}</span>
                                            <span className="text-secondary-text font-medium text-[13px] sm:text-sm wrap-break-word">{comment.body || comment.text}</span>
                                        </p>
                                        <span className="block text-[10px] text-secondary-text/50 font-bold">
                                            {comment.created_at ? new Date(comment.created_at).toLocaleDateString('nl-NL') : 'Zojuist'}
                                        </span>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Invoerveld onderaan */}
                    <form onSubmit={handleCommentSubmit} className="p-4 border-t border-primary-border bg-primary-bg/20">
                        <div className="flex items-center gap-2 bg-primary-bg border border-primary-border rounded-xl px-3 py-1 focus-within:border-primary-accent transition-colors">
                            <input
                                type="text"
                                value={commentText}
                                onChange={(e) => setCommentText(e.target.value)}
                                placeholder="Een reactie toevoegen..."
                                className="w-full bg-transparent text-sm py-2.5 text-primary-text placeholder-secondary-text/60 font-label focus:outline-none"
                            />
                            <button
                                type="submit"
                                disabled={!commentText.trim()}
                                className="text-xs font-label font-black uppercase tracking-wider text-primary-accent disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer hover:brightness-110 transition-all pl-2 shrink-0"
                            >
                                Plaatsen
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    );
}