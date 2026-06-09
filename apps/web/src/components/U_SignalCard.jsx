import React from 'react';

export default function USignalCard({title, location, date, status, onClick}) {

    const statusStyles = {
        'Nieuw': 'bg-red-50 text-red-700 border-red-200',
        'In behandeling': 'bg-amber-50 text-amber-700 border-amber-200',
        'Afgehandeld': 'bg-emerald-50 text-emerald-700 border-emerald-200'
    };

    const currentBadgeStyle = statusStyles[status] || 'bg-stone-500 text-white';

    return (
        <button
            onClick={onClick}
            style={{fontFamily: "'Open Sans', sans-serif"}}
            className="w-full text-left flex items-center justify-between p-4 mb-3 last:mb-0 bg-white rounded-xl border border-stone-200/80 shadow-sm hover:border-stone-300 hover:shadow-md active:scale-[0.995] transition-all cursor-pointer antialiased focus:outline-none focus:ring-2 focus:ring-stone-400/20"
        >
            {/* Info */}
            <div className="flex flex-col gap-1 min-w-0 pr-4">
                <h4 className="font-bold text-[15px] text-stone-900 tracking-tight leading-snug truncate">
                    {title}
                </h4>
                <div className="flex items-center gap-1.5 text-xs text-stone-400 font-medium">
                    <span className="truncate">{location}</span>
                    <span>•</span>
                    <span className="flex-shrink-0">{date}</span>
                </div>
            </div>

            {/* Status */}
            <div className="flex-shrink-0">
                <span
                    className={`px-3 py-1 text-[11px] font-bold rounded-full tracking-wide shadow-sm ${currentBadgeStyle}`}>
                    {status}
                </span>
            </div>
        </button>
    );
}