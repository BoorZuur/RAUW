import React from 'react';

export default function InformationCard({titel, aantal, kleur = 'text-primary-text', icon}) {
    return (
        <div className="bg-primary-bg-cards rounded-2xl p-4 border border-primary-border shadow-sm min-w-37.5 max-w-60 flex-1 font-label antialiased transition-all">
            <div className="flex justify-between items-start mb-1.5">
                <span className="text-[10px] font-bold text-secondary-text uppercase tracking-wider truncate pr-2">
                    {titel}
                </span>
                {icon && (
                    <div className="text-secondary-text bg-primary-bg p-1 rounded-lg shrink-0">
                        {icon}
                    </div>
                )}
            </div>

            <div className={`text-3xl font-bold tracking-tight ${kleur}`}>
                {aantal}
            </div>
        </div>
    );
}