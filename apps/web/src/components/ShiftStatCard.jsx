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
        <div className="bg-primary-bg-cards rounded-2xl p-5 border border-primary-border shadow-sm min-w-[200px] max-w-[320px] flex-1 font-label antialiased transition-all select-none">
            <div className="flex justify-between items-start mb-2">
                <span className="text-[11px] font-bold text-secondary-text uppercase tracking-widest truncate pr-2">
                    {titel}
                </span>
                {renderIcon && (
                    <div className={`p-1.5 rounded-xl flex-shrink-0 flex items-center justify-center ${iconBgColor}`}>
                        {renderIcon}
                    </div>
                )}
            </div>

            <div className={`text-4xl font-bold tracking-tight mt-1 ${kleur}`}>
                {aantal}
            </div>
        </div>
    );
}