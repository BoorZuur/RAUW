import React from 'react';

export default function InformationCard({titel, aantal, kleur = 'text-stone-900', icon}) {
    return (
        <div
            style={{fontFamily: "'Open Sans', sans-serif"}}
            className="bg-white rounded-2xl p-4 border border-stone-200/90 shadow-sm min-w-[150px] max-w-[240px] flex-1 antialiased transition-all"
        >
            <div className="flex justify-between items-start mb-1.5">
                <span className="text-[10px] font-bold text-stone-400 uppercase tracking-wider truncate pr-2">
                    {titel}
                </span>
                {icon && <div className="text-stone-400 bg-stone-50 p-1 rounded-lg flex-shrink-0">{icon}</div>}
            </div>

            <div className={`text-3xl font-bold tracking-tight ${kleur}`}>
                {aantal}
            </div>
        </div>
    );
}