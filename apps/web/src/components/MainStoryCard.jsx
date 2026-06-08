import React from 'react';

export default function MainStoryCard({title, description, location, district, date, imageUrl, followers, onClick}) {
    return (
        <button
            onClick={onClick}
            style={{fontFamily: "'Open Sans', sans-serif"}}
            className="w-full max-w-[900px] text-left bg-[#fcfbfa] rounded-2xl border border-stone-200/60 shadow-sm hover:shadow-md active:scale-[0.998] transition-all cursor-pointer antialiased overflow-hidden flex flex-col sm:flex-row focus:outline-none focus:ring-2 focus:ring-stone-400/20"
        >
            {/* Linkerzijde: Afbeelding */}
            <div className="relative w-full sm:w-[35%] h-48 sm:h-auto min-h-[180px] bg-stone-100 flex-shrink-0">
                {imageUrl ? (
                    <img
                        src={imageUrl}
                        alt={title}
                        className="w-full h-full object-cover"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-stone-400 text-xs">
                        Geen afbeelding beschikbaar
                    </div>
                )}
            </div>

            {/* Rechterzijde: Content */}
            <div className="p-6 flex flex-col justify-between flex-1 min-w-0 bg-white">
                <div>
                    {/* Bovenste info & Badges */}
                    <div className="flex flex-col gap-2 mb-3">
                        <div
                            className="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-bold tracking-wider text-stone-400 uppercase">
                            <span>{location}</span>
                            <span>—</span>
                            <span className="text-stone-500">{district}</span>
                        </div>

                        <div className="flex items-center gap-2">
                            {/* In behandeling Badge */}
                            <span
                                className="bg-[#ef7d14] text-white px-3 py-1 rounded-full text-[11px] font-semibold tracking-wide">
                                In behandeling
                            </span>
                            {/* Overlast Badge */}
                            <span
                                className="inline-flex items-center gap-1 bg-stone-50 text-stone-700 border border-stone-200 px-2.5 py-1 rounded-full text-[11px] font-medium">
                                <svg className="w-3 h-3 text-stone-500" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round"
                                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Overlast
                            </span>
                        </div>
                    </div>

                    {/* Titel */}
                    <h3 className="font-bold text-xl sm:text-2xl text-stone-900 tracking-tight leading-snug mb-2 line-clamp-2">
                        {title}
                    </h3>

                    {/* Beschrijving */}
                    <p className="text-stone-500 text-sm sm:text-[15px] leading-relaxed line-clamp-2 mb-6">
                        {description}
                    </p>
                </div>

                {/* Onderste balk met statistieken */}
                <div
                    className="flex items-center justify-between border-t border-stone-100 pt-4 text-xs sm:text-sm text-stone-500 font-medium mt-auto">
                    <div className="flex items-center gap-4">
                        {/* Volgers */}
                        <span className="flex items-center gap-1">
                            <svg className="w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" strokeWidth="2">
                                <path strokeLinecap="round" strokeLinejoin="round"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            {followers} volgers
                        </span>
                        {/* Datum */}
                        <span className="text-stone-400 font-normal">{date}</span>
                    </div>

                    {/* Lees Meer Actie */}
                    <span
                        className="text-stone-900 font-bold inline-flex items-center gap-1 hover:text-stone-600 transition-colors">
                        Lees meer
                        <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             strokeWidth="3">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </div>
        </button>
    );
}