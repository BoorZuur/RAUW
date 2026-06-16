import React from 'react';

export default function DataCard({ data }) {
    const defaultData = [
        { categorie: 'Overlast door jeugd', aantal: 42, percentage: 45, type: 'primary' },
        { categorie: 'Geluidsoverlast', aantal: 28, percentage: 30, type: 'accent' },
        { categorie: 'Zwerfafval & Milieu', aantal: 15, percentage: 16, type: 'secondary' },
        { categorie: 'Foutparkeren', aantal: 8, percentage: 9, type: 'other' },
    ];

    const getColorClass = (type) => {
        switch (type) {
            case 'primary': return 'bg-primary-accent';
            case 'accent': return 'bg-secondary-accent';
            case 'secondary': return 'bg-blue-500';
            default: return 'bg-primary-border';
        }
    };

    const showData = data && data.length > 0 ? data : defaultData;

    return (
        <div className="bg-primary-bg-cards rounded-2xl p-4 border border-primary-border shadow-sm max-w-sm w-full antialiased text-primary-text font-label">
            <div className="mb-3.5">
                <h3 className="font-bold text-base text-primary-text tracking-tight leading-tight">
                    Meldingen per Categorie
                </h3>
            </div>

            <div className="space-y-2.5">
                {showData.map((item, index) => (
                    <div key={index} className="space-y-1">
                        {/* Categorie + Aantal */}
                        <div className="flex justify-between items-center text-xs">
                            <span className="font-semibold text-primary-text truncate pr-4">
                                {item.categorie}
                            </span>

                            <span className="font-bold text-primary-text bg-primary-bg px-2 py-0.5 rounded text-xs flex-shrink-0 tracking-wide border border-primary-border">
                                {item.aantal}x ({item.percentage}%)
                            </span>
                        </div>

                        {/* Bar Tracker */}
                        <div className="w-full h-1.5 bg-primary-bg rounded-full overflow-hidden border border-primary-border/20">
                            <div
                                className={`h-full rounded-full ${getColorClass(item.type)} transition-all duration-500`}
                                style={{ width: `${item.percentage}%` }}
                            />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}