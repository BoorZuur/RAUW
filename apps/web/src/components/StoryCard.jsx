import React from 'react';

export default function StoryCard({ issue, onClick }) {
    const {
        title,
        content,
        address,
        created_at,
        images,
        participant_count,
        followers,
        status,
        category,
        district
    } = issue || {};

    const imageUrl = images?.[0]?.path || issue?.image_url;

    // Bepaal het aantal volgers op exact dezelfde manier als de MainStoryCard
    const totalFollowers = typeof participant_count === 'number'
        ? participant_count
        : Array.isArray(followers)
            ? followers.length
            : 0;

    // Dynamische status mapping (exact gelijk aan MainStoryCard)
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

    const formattedDate = created_at
        ? new Date(created_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' })
        : '';

    return (
        <button
            onClick={onClick}
            className="w-full max-w-sm text-left bg-primary-bg-cards border border-primary-border rounded-2xl shadow-sm hover:shadow-md hover:border-primary-accent active:scale-[0.995] transition-all cursor-pointer overflow-hidden flex flex-col focus:outline-none focus:ring-2 focus:ring-primary-accent/20"
        >
            {/* Image Container */}
            <div className="relative w-full h-44 bg-primary-bg shrink-0 border-b border-primary-border">
                {imageUrl ? (
                    <img src={imageUrl} alt={title} className="w-full h-full object-cover" />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-secondary-text text-[10px] uppercase font-label font-black tracking-widest">
                        Geen beeld
                    </div>
                )}

                {/* Dynamische Status Badge op de foto geplakt */}
                <div className="absolute top-3 left-3 z-10">
                    <span className={`px-2.5 py-1 rounded-md text-[9px] font-label font-black uppercase tracking-widest shadow-sm ${statusDetails.className}`}>
                        {statusDetails.label}
                    </span>
                </div>
            </div>

            {/* Content Container */}
            <div className="p-5 flex flex-col justify-between flex-1 w-full gap-3">
                <div className="space-y-1">
                    {/* Categorie of District Korte Tag */}
                    <div className="text-[10px] font-label font-bold tracking-wider text-primary-accent uppercase truncate">
                        {category?.name || category || district?.name || 'Melding'}
                    </div>

                    {/* Title */}
                    <h4 className="font-headline font-extrabold text-primary-text text-base leading-snug line-clamp-1">
                        {title}
                    </h4>

                    {/* Description */}
                    <p className="font-label text-secondary-text text-xs leading-relaxed line-clamp-2">
                        {content}
                    </p>
                </div>

                {/* Card Footer */}
                <div className="border-t border-primary-border pt-3 mt-auto">
                    {/* Rij 1: Adres en Datum */}
                    <div className="flex items-center justify-between text-[10px] font-label font-bold text-secondary-text uppercase tracking-wider mb-1.5">
                        <span className="truncate max-w-45">{address || 'Rotterdam'}</span>
                        <span className="text-secondary-text/60 shrink-0">{formattedDate}</span>
                    </div>

                    {/* Rij 2: Volgers Teller (Net zoals in MainStory) */}
                    <div className="flex items-center gap-1 text-[11px] font-label text-secondary-text">
                        <svg className="w-3.5 h-3.5 text-secondary-text/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span className="font-black text-primary-text">{totalFollowers}</span> volgers
                    </div>
                </div>
            </div>
        </button>
    );
}