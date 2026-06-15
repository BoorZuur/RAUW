import React from 'react';
import AttachmentImage from './AttachmentImage';

export default function USignalCard({ issue, onClick }) {
    const { title, address, created_at, status, attachments = [] } = issue || {};
    const formattedDate = created_at ? new Date(created_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

    const getStatusDetails = (s) => {
        switch (s?.toLowerCase()) {
            case 'open': case 'nieuw': return { label: 'Nieuw', className: 'bg-primary-accent text-white' };
            case 'in_behandeling': case 'in behandeling': return { label: 'In behandeling', className: 'bg-amber-500 text-white' };
            case 'opgelost': return { label: 'Opgelost', className: 'bg-secondary-accent text-white' };
            case 'gesloten': case 'afgehandeld': return { label: 'Gesloten', className: 'bg-primary-border text-secondary-text' };
            default: return { label: s || 'Nieuw', className: 'bg-primary-accent text-white' };
        }
    };
    const statusDetails = getStatusDetails(status);

    return (
        <button onClick={onClick} className="w-full text-left flex items-center justify-between p-3 mb-3 last:mb-0 bg-primary-bg-cards border border-primary-border rounded-xl shadow-sm hover:border-primary-accent hover:shadow-md active:scale-[0.995] transition-all cursor-pointer antialiased focus:outline-none focus:ring-2 focus:ring-primary-accent/20">
            <div className="flex items-center gap-4 min-w-0">
                <div className="w-12 h-12 rounded-lg bg-primary-bg border border-primary-border overflow-hidden flex-shrink-0">
                    {attachments.length > 0 ? (
                        <AttachmentImage attachment={attachments[0]} className="w-full h-full object-cover" />
                    ) : <div className="w-full h-full flex items-center justify-center text-[8px] text-secondary-text">N/A</div>}
                </div>
                <div className="flex flex-col gap-1 min-w-0 pr-2">
                    <h4 className="font-headline font-black text-[15px] text-primary-text tracking-tight leading-snug truncate">{title}</h4>
                    <div className="flex items-center gap-1.5 text-xs text-secondary-text font-label font-medium">
                        <span className="truncate">{address || 'Rotterdam'}</span>
                        <span>•</span>
                        <span className="shrink-0">{formattedDate}</span>
                    </div>
                </div>
            </div>
            <div className="shrink-0 pl-2">
                <span className={`px-3 py-1 text-[10px] font-label font-black uppercase tracking-wider rounded-full shadow-sm ${statusDetails.className}`}>
                    {statusDetails.label}
                </span>
            </div>
        </button>
    );
}