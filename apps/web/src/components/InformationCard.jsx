import React from 'react';

export default function InformationCard({ titel, aantal, kleur = 'text-primary-text', icon }) {
    return (
        <div className="bg-primary-bg-cards rounded-2xl p-4 border border-primary-border shadow-sm w-full sm:max-w-60 flex-1 font-label antialiased transition-all select-none flex flex-col justify-between min-h-[102px]">
            <div className="flex justify-between items-start gap-2 mb-2">
                <span className="text-[10px] font-bold text-secondary-text uppercase tracking-wider truncate min-w-0" title={titel}>
                    {titel}
                </span>
                {icon && (
                    <div className="text-secondary-text bg-primary-bg p-1 rounded-lg shrink-0 border border-primary-border/40">
                        {icon}
                    </div>
                )}
            </div>

            <div className={`text-2xl sm:text-3xl font-bold tracking-tight truncate ${kleur}`} title={aantal}>
                {aantal}
            </div>
        </div>
    );
}