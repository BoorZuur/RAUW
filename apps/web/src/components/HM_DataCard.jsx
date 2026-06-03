import React from 'react';

export default function DataCard({data}) {
    // Dummy data
    const defaultData = [
        {categorie: 'Overlast door jeugd', aantal: 42, percentage: 45, color: 'bg-red-500'},
        {categorie: 'Geluidsoverlast', aantal: 28, percentage: 30, color: 'bg-amber-500'},
        {categorie: 'Zwerfafval & Milieu', aantal: 15, percentage: 16, color: 'bg-emerald-500'},
        {categorie: 'Foutparkeren', aantal: 8, percentage: 9, color: 'bg-blue-500'},
    ];

    const showData = data && data.length > 0 ? data : defaultData;

    return (
        <div
            style={{fontFamily: "'Open Sans', sans-serif"}}
            className="bg-white rounded-2xl p-4 border border-stone-200/90 shadow-sm max-w-sm w-full antialiased text-stone-700"
        >
            <div className="mb-3.5">
                <h3 className="font-bold text-base text-stone-900 tracking-tight leading-tight">
                    Meldingen per Categorie
                </h3>
            </div>


            <div className="space-y-2.5">
                {showData.map((item, index) => (
                    <div key={index} className="space-y-1">

                        {/* Categorie + Aantal */}
                        <div className="flex justify-between items-center text-xs">
                            <span className="font-semibold text-stone-700 truncate pr-4">
                                {item.categorie}
                            </span>

                            <span
                                className="font-bold text-stone-900 bg-stone-100 px-2 py-0.5 rounded text-xs flex-shrink-0 tracking-wide">
                                {item.aantal}x ({item.percentage}%)
                            </span>
                        </div>

                        {/* Bar Tracker */}
                        <div className="w-full h-1.5 bg-stone-100 rounded-full overflow-hidden">
                            <div
                                className={`h-full rounded-full ${item.color || 'bg-stone-500'} transition-all duration-500`}
                                style={{width: `${item.percentage}%`}}
                            />
                        </div>

                    </div>
                ))}
            </div>
        </div>
    );
}