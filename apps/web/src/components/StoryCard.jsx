import React from 'react';

export default function StoryCard({title, location, date, imageUrl, onClick}) {
    return (
        <button
            onClick={onClick}
            style={{fontFamily: "'Open Sans', sans-serif"}}
            className="w-full max-w-sm text-left bg-white rounded-2xl border border-stone-200/80 shadow-sm hover:border-stone-300 hover:shadow-md active:scale-[0.995] transition-all cursor-pointer antialiased overflow-hidden flex flex-col focus:outline-none focus:ring-2 focus:ring-stone-400/20"
        >
            {/* Image */}
            <div className="relative w-full h-48 bg-stone-100 flex-shrink-0">
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

                {/* Solved Badge */}
                <div
                    className="absolute top-3 left-3 flex items-center gap-1 bg-[#40b85c] text-white px-2.5 py-1 rounded-full text-[11px] font-bold shadow-sm tracking-wide">
                    <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         strokeWidth="2.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Opgelost</span>
                </div>
            </div>

            {/* Info */}
            <div className="p-4 flex flex-col gap-1 w-full">
                <h4 className="font-bold text-[15px] text-stone-900 tracking-tight leading-snug truncate">
                    {title}
                </h4>

                <div className="flex items-center gap-1.5 text-xs text-stone-400 font-medium">
                    <span className="truncate">{location}</span>
                    <span>•</span>
                    <span className="flex-shrink-0">{date}</span>
                </div>
            </div>
        </button>
    );
}