import React from 'react';

export default function USignalCard({ issue, onClick }) {
    const {
        title,
        address,
        created_at,
        status
    } = issue || {};

    const getStatusDetails = (currentStatus) => {
        switch (currentStatus?.toLowerCase()) {
            case 'open':
            case 'nieuw':
                return { label: 'Nieuw', className: 'bg-primary-accent text-white' };
            case 'in_behandeling':
            case 'in behandeling':
                return { label: 'In behandeling', className: 'bg-amber-500 text-white' };
            case 'opgelost':
                return { label: 'Opgelost', className: 'bg-secondary-accent text-white' };
            case 'gesloten':
            case 'afgehandeld':
                return { label: 'Gesloten', className: 'bg-primary-border text-secondary-text' };
            default:
                return { label: currentStatus || 'Nieuw', className: 'bg-primary-accent text-white' };
        }
    };

    const statusDetails = getStatusDetails(status);

    const formattedDate = created_at
        ? new Date(created_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' })
        : '';

    return (
        <button
            onClick={onClick}
            className="w-full text-left flex items-center justify-between p-4 mb-3 last:mb-0 bg-primary-bg-cards border border-primary-border rounded-xl shadow-sm hover:border-primary-accent hover:shadow-md active:scale-[0.995] transition-all cursor-pointer antialiased focus:outline-none focus:ring-2 focus:ring-primary-accent/20"
        >
            {/* Info */}
            <div className="flex flex-col gap-1 min-w-0 pr-4">
                <h4 className="font-headline font-black text-[15px] text-primary-text tracking-tight leading-snug truncate">
                    {title}
                </h4>
                <div className="flex items-center gap-1.5 text-xs text-secondary-text font-label font-medium">
                    <span className="truncate">{address || 'Rotterdam'}</span>
                    <span>•</span>
                    <span className="flex-shrink-0">{formattedDate}</span>
                </div>
            </div>

            {/* Status Badge */}
            <div className="flex-shrink-0">
                <span className={`px-3 py-1 text-[10px] font-label font-black uppercase tracking-wider rounded-full shadow-sm ${statusDetails.className}`}>
                    {statusDetails.label}
                </span>
            </div>
        </button>
    );
}