import React from 'react';

export default function ReportCard({
                                       title,
                                       description,
                                       status,
                                       location,
                                       time,
                                       reporter,
                                       tags = [],
                                       priority = 'red',
                                       onClick
                                   }) {

    const priorityClasses = {
        red: 'bg-red-500',
        orange: 'bg-amber-500',
        green: 'bg-emerald-500'
    };

    const statusClasses = {
        'Nieuw': 'bg-primary-bg text-red-600 border-primary-border',
        'In behandeling': 'bg-primary-bg text-amber-600 border-primary-border',
        'Afgehandeld': 'bg-primary-bg text-emerald-600 border-primary-border'
    };

    const leftDotStyle = priorityClasses[priority] || priorityClasses.red;
    const rightBadgeStyle = statusClasses[status] || 'bg-primary-bg text-secondary-text border-primary-border';

    return (
        <div
            className="bg-primary-bg-cards rounded-2xl p-4 border border-primary-border shadow-sm max-w-md w-full antialiased text-primary-text font-label tracking-normal transition-all cursor-pointer hover:shadow-md"
            onClick={onClick}
        >
            {/* Titel & Status Badge */}
            <div className="flex items-start justify-between gap-4 mb-2.5">
                <div className="flex items-center gap-2.5 min-w-0">
                    <span className={`w-2.5 h-5 rounded-full shrink-0 ${leftDotStyle}`}/>
                    <h3 className="font-bold text-base text-primary-text truncate tracking-tight leading-snug py-0.5">
                        {title}
                    </h3>
                </div>
                <span className={`px-2.5 py-0.5 text-[11px] font-semibold rounded-full border tracking-wide whitespace-nowrap ${rightBadgeStyle}`}>
                    {status}
                </span>
            </div>

            {/* Beschrijving */}
            <div className="mb-4">
                <p className="text-secondary-text text-sm leading-relaxed font-normal whitespace-pre-line">
                    {description}
                </p>
            </div>

            {/* metadata & tags */}
            <div className="pt-3 border-t border-primary-border flex flex-col gap-2">
                <div className="flex items-center gap-1 text-[11px] text-secondary-text font-semibold min-w-0">
                    <span className="text-primary-border">📍</span>
                    <span className="truncate">{location}</span>
                </div>

                <div className="flex items-end justify-between gap-4 text-[11px]">
                    <div className="flex flex-wrap gap-1 min-w-0">
                        {tags.length > 0 ? (
                            tags.map((tag, index) => (
                                <span key={index} className="px-2 py-0.5 bg-primary-bg text-secondary-text rounded-md text-[10px] font-semibold border border-primary-border whitespace-nowrap">
                                    {tag}
                                </span>
                            ))
                        ) : (
                            <span className="text-[10px] text-secondary-text italic font-normal">Geen tags</span>
                        )}
                    </div>

                    <div className="flex items-center gap-2.5 text-secondary-text font-medium shrink-0">
                        <div className="flex items-center gap-1">
                            <span>👤</span>
                            <span className="whitespace-nowrap">{reporter}</span>
                        </div>
                        <span className="text-primary-border">•</span>
                        <div className="flex items-center gap-1 bg-primary-bg px-1.5 py-0.5 rounded-md font-bold text-primary-text">
                            <span>🕒</span>
                            <span>{time}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}