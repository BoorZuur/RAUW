import React from 'react';
import AttachmentImage from './AttachmentImage';
import { getIssueDisplayTitle } from '../utils/issueParticipation';

export default function MainStoryCard({ issue, onClick }) {
    const { address, created_at, participant_count, followers, status, category, duplicate_count } = issue || {};
    const attachments = Array.isArray(issue?.attachments) ? issue.attachments : [];
    const displayTitle = getIssueDisplayTitle(issue);

    const totalFollowers = typeof participant_count === 'number' ? participant_count : (Array.isArray(followers) ? followers.length : 0);
    const getStatusDetails = (s) => {
        switch (s?.toLowerCase()) {
            case 'open': case 'nieuw': return { label: 'Nieuw', className: 'bg-primary-accent text-white' };
            case 'in_behandeling': case 'in behandeling': return { label: 'In behandeling', className: 'bg-amber-500 text-white' };
            case 'opgelost': return { label: 'Opgelost', className: 'bg-secondary-accent text-white' };
            case 'gesloten': case 'afgehandeld': return { label: 'Gesloten', className: 'bg-primary-border text-secondary-text' };
            default: return { label: s || 'Open', className: 'bg-primary-accent text-white' };
        }
    };
    const statusDetails = getStatusDetails(status);
    const formattedDate = created_at ? new Date(created_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

    return (
        <button onClick={onClick} className="w-full text-left bg-primary-bg-cards border border-primary-border rounded-2xl shadow-sm hover:shadow-md transition-all flex flex-col sm:flex-row overflow-hidden focus:outline-none focus:ring-2 focus:ring-primary-accent/20">
            <div className="relative w-full sm:w-[35%] h-48 bg-primary-bg border-b sm:border-b-0 sm:border-r border-primary-border flex-shrink-0">
                {attachments.length > 0 ? (
                    <AttachmentImage attachment={attachments[0]} className="w-full h-full object-cover" />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-secondary-text text-xs uppercase font-label">Geen beeld</div>
                )}
            </div>
            <div className="p-6 flex flex-col justify-between flex-1 min-w-0">
                <div>
                    <div className="flex flex-col gap-2 mb-3">
                        <div className="text-[10px] font-black uppercase tracking-widest text-secondary-text truncate">{address || 'Rotterdam'}</div>
                        <span className={`px-3 py-1 rounded-full text-[11px] font-black uppercase w-fit ${statusDetails.className}`}>{statusDetails.label}</span>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-headline font-extrabold text-xl text-primary-text line-clamp-2">{displayTitle}</h3>
                        {duplicate_count > 0 ? (
                            <span className="shrink-0 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-primary-border text-secondary-text">
                                {duplicate_count} gekoppeld
                            </span>
                        ) : null}
                    </div>
                </div>
                <div className="flex items-center justify-between border-t border-primary-border pt-4 mt-4 text-xs font-label">
                    <span className="font-bold text-primary-text">{totalFollowers} volgers</span>
                    {formattedDate && <span className="text-secondary-text">{formattedDate}</span>}
                </div>
            </div>
        </button>
    );
}