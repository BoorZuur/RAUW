import React from 'react';

export default function ShiftStatCard({
                                          titel,
                                          aantal,
                                          kleur = 'text-stone-900',
                                          icon,
                                          iconBgColor = 'bg-stone-50'
                                      }) {
    return (
        <div
            style={{fontFamily: "'Open Sans', sans-serif"}}
            className="bg-white rounded-2xl p-5 border border-stone-200/90 shadow-sm min-w-[200px] max-w-[320px] flex-1 antialiased transition-all select-none"
        >
            <div className="flex justify-between items-start mb-2">
                <span className="text-[11px] font-bold text-stone-400 uppercase tracking-widest truncate pr-2">
                    {titel}
                </span>
                {icon && (
                    <div className={`p-1.5 rounded-xl flex-shrink-0 flex items-center justify-center ${iconBgColor}`}>
                        {icon}
                    </div>
                )}
            </div>

            <div className={`text-4xl font-bold tracking-tight mt-1 ${kleur}`}>
                {aantal}
            </div>
        </div>
    );
}