import React from 'react';
import AttachmentImage from './AttachmentImage';
import { getIssueDisplayTitle } from '../utils/issueParticipation';

export default function StoryCard({ issue, onClick }) {
    const { content, address, created_at, attachments = [], participant_count, followers, status, category, district, duplicate_count } = issue || {};
    const displayTitle = getIssueDisplayTitle(issue);
    const totalFollowers = typeof participant_count === 'number' ? participant_count : (Array.isArray(followers) ? followers.length : 0);
    const statusDetails = { label: status || 'Open', className: 'bg-primary-accent text-white' };

    return (
        <button onClick={onClick} className="w-full max-w-sm text-left bg-primary-bg-cards border border-primary-border rounded-2xl shadow-sm hover:border-primary-accent transition-all flex flex-col overflow-hidden">
            <div className="relative w-full h-44 bg-primary-bg">
                {attachments.length > 0 ? (
                    <AttachmentImage attachment={attachments[0]} className="w-full h-full object-cover" />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-xs uppercase font-label">Geen beeld</div>
                )}
            </div>
            <div className="p-5 flex flex-col gap-3">
                <div className="text-[10px] font-bold text-primary-accent uppercase tracking-wider">{category?.name || district?.name || 'Melding'}</div>
                <div className="flex flex-wrap items-center gap-2">
                    <h4 className="font-extrabold text-primary-text text-base line-clamp-1">{displayTitle}</h4>
                    {duplicate_count > 0 ? (
                        <span className="shrink-0 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-primary-border text-secondary-text">
                            {duplicate_count} gekoppeld
                        </span>
                    ) : null}
                </div>
                <p className="text-secondary-text text-xs line-clamp-2 font-label">{content}</p>
                <div className="border-t pt-3 mt-auto font-bold text-[11px] text-secondary-text font-label">
                    {totalFollowers} volgers
                </div>
            </div>
        </button>
    );
}