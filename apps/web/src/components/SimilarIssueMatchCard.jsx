import React from 'react';
import AttachmentImage from './AttachmentImage';

const CONFIDENCE_LABELS = {
    high: 'Hoge overeenkomst',
    medium: 'Gemiddelde overeenkomst',
    low: 'Lage overeenkomst',
};

function getStatusDetails(status) {
    switch (status?.toLowerCase()) {
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
            return { label: status || 'Nieuw', className: 'bg-primary-accent text-white' };
    }
}

export default function SimilarIssueMatchCard({
    match,
    variant = 'linkable',
    onSelect,
    disabled = false,
    disabledReason,
    selected = false,
}) {
    const isOwn = variant === 'own';
    const isDisabled = isOwn || disabled;
    const statusDetails = getStatusDetails(match.status);
    const formattedDate = match.created_at
        ? new Date(match.created_at).toLocaleDateString('nl-NL', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : '';
    const confidenceLabel = CONFIDENCE_LABELS[match.confidence] || match.confidence;
    const followerLabel = `${match.participant_count ?? 0} volgers`;
    const thumbnail = match.attachments?.[0];

    const handleClick = () => {
        if (!isDisabled && onSelect) {
            onSelect(match.id);
        }
    };

    return (
        <button
            type="button"
            onClick={handleClick}
            disabled={isDisabled}
            className={`w-full min-h-[5.5rem] text-left p-4 rounded-xl border-2 transition-all ${
                isOwn
                    ? 'bg-primary-bg/50 border-primary-border/60 opacity-60 cursor-default'
                    : isDisabled
                      ? 'bg-primary-bg/50 border-primary-border/60 opacity-70 cursor-not-allowed'
                      : selected
                        ? 'bg-primary-bg border-primary-accent shadow-md ring-2 ring-primary-accent/30 cursor-pointer'
                        : 'bg-primary-bg border-primary-border hover:border-primary-accent hover:shadow-sm cursor-pointer'
            }`}
        >
            <div className="flex items-start gap-3">
                <div className="w-16 h-16 shrink-0 rounded-lg bg-primary-bg border border-primary-border overflow-hidden">
                    {thumbnail ? (
                        <AttachmentImage
                            attachment={thumbnail}
                            className="w-full h-full object-cover"
                        />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center px-1 text-center text-[9px] font-bold uppercase tracking-wide text-secondary-text">
                            Geen afbeelding
                        </div>
                    )}
                </div>

                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2 mb-1.5">
                        <div className="min-w-0 flex-1">
                            {isOwn ? (
                                <span className="inline-block mb-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-primary-border text-secondary-text">
                                    Jouw melding
                                </span>
                            ) : null}
                            <h4 className="font-black text-sm text-primary-text leading-snug line-clamp-2">
                                {match.title}
                            </h4>
                        </div>
                        <span
                            className={`shrink-0 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-full ${statusDetails.className}`}
                        >
                            {statusDetails.label}
                        </span>
                    </div>

                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-secondary-text">
                        <span className="font-bold text-primary-text">{followerLabel}</span>
                        {match.duplicate_count > 0 ? (
                            <span>{match.duplicate_count} gekoppeld</span>
                        ) : null}
                        <span>{confidenceLabel}</span>
                        {formattedDate ? <span>{formattedDate}</span> : null}
                    </div>

                    {disabledReason ? (
                        <p className="mt-1.5 text-xs font-bold text-secondary-text">{disabledReason}</p>
                    ) : null}
                </div>
            </div>
        </button>
    );
}
