import React from 'react';

export default function ShiftStatCard({
                                          titel,
                                          aantal,
                                          kleur = 'text-primary-text',
                                          icon,
                                          children,
                                          iconBgColor = 'bg-primary-bg'
                                      }) {
    const renderIcon = children || icon;

    return (

        <div className="bg-primary-bg-cards rounded-2xl p-4 sm:p-5 border border-primary-border shadow-sm w-full sm:max-w-[320px] flex-1 font-label antialiased transition-all select-none flex flex-col justify-between min-h-27.5">

            <div className="flex justify-between items-start gap-2 mb-2">
                <span className="text-[11px] font-bold text-secondary-text uppercase tracking-widest truncate min-w-0" title={titel}>
                    {titel}
                </span>
                {renderIcon && (
                    <div className={`p-1.5 rounded-xl shrink-0 flex items-center justify-center border border-primary-border/40 ${iconBgColor}`}>
                        {renderIcon}
                    </div>
                )}
            </div>

            <div className={`text-3xl sm:text-4xl font-bold tracking-tight truncate ${kleur}`} title={aantal}>
                {aantal}
            </div>

        </div>
    );
}