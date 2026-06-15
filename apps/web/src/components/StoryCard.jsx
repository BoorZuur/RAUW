import React from 'react';
import AttachmentImage from './AttachmentImage';

export default function StoryCard({ issue, onClick }) {
    const { title, content, address, created_at, attachments = [], participant_count, followers, status, category, district } = issue || {};
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
                <h4 className="font-extrabold text-primary-text text-base line-clamp-1">{title}</h4>
                <p className="text-secondary-text text-xs line-clamp-2 font-label">{content}</p>
                <div className="border-t pt-3 mt-auto font-bold text-[11px] text-secondary-text font-label">
                    {totalFollowers} volgers
                </div>
            </div>
        </button>
    );
}