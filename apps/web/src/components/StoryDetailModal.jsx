import React, { useState } from 'react';

export default function StoryDetailModal({ issue, onClose, onAddComment }) {
    const [commentText, setCommentText] = useState('');

    if (!issue) return null;

    const {
        id,
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
        onAddComment?.(id || issue.id, commentText);
        setCommentText('');
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            {/* Sluitknop */}
            <button
                onClick={onClose}
                className="absolute top-6 right-6 text-white hover:text-primary-accent transition-colors cursor-pointer p-2 z-50 focus:outline-none"
                aria-label="Sluiten"
            >
                <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {/* Main Layout Container */}
            <div className="w-full max-w-5xl h-[85vh] bg-primary-bg-cards border border-primary-border rounded-3xl overflow-hidden shadow-2xl flex flex-col md:flex-row">

                {/* LINKERKANT: Visueel gesplitst voor WCAG AAA Contrast */}
                <div className="flex-1 bg-primary-bg flex flex-col h-1/2 md:h-full border-b md:border-b-0 md:border-r border-primary-border">

                    {/* Top: Afbeelding of Fallback */}
                    <div className="w-full h-48 md:h-64 bg-stone-950 flex items-center justify-center relative shrink-0 overflow-hidden border-b border-primary-border">
                        {image_url ? (
                            <img src={image_url} alt={title} className="w-full h-full object-cover" />
                        ) : (
                            <div className="flex flex-col items-center gap-2 text-stone-400">
                                <svg className="w-8 h-8 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <span className="font-label text-xs uppercase font-black tracking-widest opacity-60">Geen afbeelding</span>
                            </div>
                        )}
                    </div>

                    {/* Bottom: Informatie Text Area met hoog contrast */}
                    <div className="flex-1 p-6 sm:p-8 bg-stone-950 overflow-y-auto custom-scrollbar flex flex-col justify-start">
                        <div className="flex flex-wrap items-center gap-2 mb-4">
                            {/* Locatie */}
                            <span className="text-[10px] sm:text-[11px] font-label font-black uppercase tracking-widest text-primary-accent bg-primary-accent/10 border border-primary-accent/30 px-2.5 py-1 rounded-md">
                                {address || "Rotterdam"}
                            </span>
                            {/* Status Badge */}
                            <span className={`px-2.5 py-1 rounded-md text-[10px] sm:text-[11px] font-label font-black uppercase tracking-wider shadow-xs ${statusDetails.className}`}>
                                {statusDetails.label}
                            </span>
                            {/* Categorie Badge */}
                            {category && (
                                <span className="bg-stone-800 text-stone-200 border border-stone-700 px-2.5 py-1 rounded-md text-[10px] sm:text-[11px] font-label font-bold uppercase tracking-wider">
                                    {category.name || category}
                                </span>
                            )}
                        </div>

                        {/* Titel */}
                        <h2 className="font-headline font-black text-xl sm:text-3xl tracking-tight leading-tight text-white mb-4">
                            {title}
                        </h2>

                        {/* Omschrijving */}
                        <p className="font-label text-sm text-stone-200/95 leading-relaxed max-w-2xl antialiased">
                            {content}
                        </p>
                    </div>
                </div>

                {/* RECHTERKANT: Volgers & Reacties Feed */}
                <div className="w-full md:w-100 flex flex-col h-1/2 md:h-full bg-primary-bg-cards shrink-0">
                    {/* Header */}
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
                                    <div className="w-8 h-8 rounded-full bg-primary-bg text-primary-text font-black text-xs flex items-center justify-center shrink-0 border border-primary-border shadow-xs">
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