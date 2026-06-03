import React from 'react';

export default function ReportCard({
                                       title,
                                       description,
                                       status,
                                       location,
                                       time,
                                       reporter,
                                       tags = [],
                                       priority = 'red'
                                   }) {

    const priorityStyles = {
        red: 'bg-red-600',
        orange: 'bg-amber-500',
        green: 'bg-emerald-600'
    };

    const statusStyles = {
        'Nieuw': 'bg-red-50 text-red-700 border-red-200',
        'In behandeling': 'bg-amber-50 text-amber-700 border-amber-200',
        'Afgehandeld': 'bg-emerald-50 text-emerald-700 border-emerald-200'
    };

    const leftDotStyle = priorityStyles[priority] || priorityStyles.red;
    const rightBadgeStyle = statusStyles[status] || 'bg-stone-50 text-stone-700 border-stone-200';

    return (

        <div
            style={{fontFamily: "'Open Sans', sans-serif"}}
            className="bg-white rounded-2xl p-4 border border-stone-200/90 shadow-sm max-w-md w-full antialiased text-stone-700 tracking-normal transition-all"
        >

            {/* Titel & Status Badge */}
            <div className="flex items-start justify-between gap-4 mb-2.5">
                <div className="flex items-center gap-2.5 min-w-0">
                    <span className={`w-2.5 h-5 rounded-full flex-shrink-0 ${leftDotStyle}`}/>
                    <h3 className="font-bold text-base text-stone-900 truncate tracking-tight leading-snug py-0.5">
                        {title}
                    </h3>
                </div>
                <span
                    className={`px-2.5 py-0.5 text-[11px] font-semibold rounded-full border tracking-wide whitespace-nowrap ${rightBadgeStyle}`}>
                    {status}
                </span>
            </div>

            {/* Beschrijving  */}
            <div className="mb-4">
                <p className="text-stone-600 text-sm leading-relaxed font-normal whitespace-pre-line">
                    {description}
                </p>
            </div>

            {/* metadata & tags */}
            <div className="pt-3 border-t border-stone-100 flex flex-col gap-2">

                {/* Locatie */}
                <div className="flex items-center gap-1 text-[11px] text-stone-500 font-semibold min-w-0">
                    <svg className="w-3.5 h-3.5 text-stone-400/80 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span className="truncate">{location}</span>
                </div>

                <div className="flex items-end justify-between gap-4 text-[11px]">

                    {/* Tags */}
                    <div className="flex flex-wrap gap-1 min-w-0">
                        {tags.length > 0 ? (
                            tags.map((tag, index) => (
                                <span
                                    key={index}
                                    className="px-2 py-0.5 bg-stone-50 text-stone-600 rounded-md text-[10px] font-semibold border border-stone-200/40 whitespace-nowrap"
                                >
                                    {tag}
                                </span>
                            ))
                        ) : (
                            <span className="text-[10px] text-stone-400 italic font-normal">Geen tags</span>
                        )}
                    </div>

                    <div className="flex items-center gap-2.5 text-stone-400 font-medium flex-shrink-0">
                        {/* Melder */}
                        <div className="flex items-center gap-1 text-stone-500">
                            <svg className="w-3.5 h-3.5 text-stone-400/80 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span className="whitespace-nowrap">{reporter}</span>
                        </div>

                        <span className="text-stone-300">•</span>

                        {/* Tijd */}
                        <div
                            className="flex items-center gap-1 bg-stone-100 px-1.5 py-0.5 rounded-md font-bold text-stone-600">
                            <svg className="w-3 h-3 text-stone-500" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{time}</span>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    );
}