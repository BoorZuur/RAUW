import React from 'react';

export default function MainStoryCard({ issue, onClick }) {
    const {
        title,
        content,
        address,
        created_at,
        image_url,
        participant_count,
        followers,
        status,
        category
    } = issue || {};

    // Bepaal het aantal volgers dynamisch op basis van wat de database teruggeeft
    const totalFollowers = typeof participant_count=== 'number'
        ? participant_count
        : Array.isArray(followers)
            ? followers.length
            : 0;

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
            className="w-full text-left bg-primary-bg-cards border border-primary-border rounded-2xl shadow-sm hover:shadow-md hover:border-primary-accent active:scale-[0.998] transition-all cursor-pointer antialiased overflow-hidden flex flex-col sm:flex-row focus:outline-none focus:ring-2 focus:ring-primary-accent/20"
        >
            {/* Image container */}
            <div className="relative w-full sm:w-[35%] h-48 sm:h-auto min-h-[180px] bg-primary-bg border-b sm:border-b-0 sm:border-r border-primary-border flex-shrink-0">
                {image_url ? (
                    <img
                        src={image_url}
                        alt={title}
                        className="w-full h-full object-cover"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-secondary-text text-xs font-label">
                        Geen afbeelding beschikbaar
                    </div>
                )}
            </div>

            {/* Content container */}
            <div className="p-6 flex flex-col justify-between flex-1 min-w-0 bg-primary-bg-cards">
                <div>
                    <div className="flex flex-col gap-2 mb-3">
                        <div className="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-label font-black tracking-widest uppercase text-secondary-text truncate">
                            {address || 'Rotterdam'}
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`px-3 py-1 rounded-full text-[11px] font-label font-black uppercase tracking-wider ${statusDetails.className}`}>
                                {statusDetails.label}
                            </span>

                            {category && (
                                <span className="inline-flex items-center gap-1 bg-primary-bg text-primary-text border border-primary-border px-2.5 py-1 rounded-full text-[11px] font-label font-bold">
                                    <svg className="w-3 h-3 text-primary-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    {category.name || category}
                                </span>
                            )}
                        </div>
                    </div>

                    <h3 className="font-headline font-extrabold text-xl sm:text-2xl text-primary-text tracking-tight leading-snug mb-2 line-clamp-2">
                        {title}
                    </h3>

                    <p className="font-label text-secondary-text text-sm sm:text-[15px] leading-relaxed line-clamp-2 mb-6">
                        {content}
                    </p>
                </div>

                <div className="flex items-center justify-between border-t border-primary-border pt-4 text-xs sm:text-sm text-secondary-text font-label font-medium mt-auto">
                    <div className="flex items-center gap-4">
                        <span className="flex items-center gap-1">
                            <svg className="w-4 h-4 text-secondary-text/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            {/* Hier gebruiken we nu de dynamische variabele */}
                            <span className="font-bold text-primary-text">{totalFollowers}</span> volgers
                        </span>
                        {formattedDate && <span className="text-secondary-text/60 font-normal">{formattedDate}</span>}
                    </div>

                    <span className="text-primary-text font-black text-xs uppercase tracking-wider inline-flex items-center gap-1 hover:text-primary-accent transition-colors">
                        Lees meer
                        <svg className="w-3.5 h-3.5 text-primary-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="3">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </div>
        </button>
    );
}