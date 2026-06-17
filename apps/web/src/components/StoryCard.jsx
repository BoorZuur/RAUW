import React from 'react';
import AttachmentImage from './AttachmentImage';

export default function StoryCard({ issue, onClick }) {
    const { title, content, address, created_at, attachments = [], participant_count, followers, status, category, district } = issue || {};
    const totalFollowers = typeof participant_count === 'number' ? participant_count : (Array.isArray(followers) ? followers.length : 0);
    const statusDetails = { label: status || 'Open', className: 'bg-primary-accent text-white' };

    return (
        <div
            onClick={onClick}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => e.key === 'Enter' && onClick && onClick()}
            className="w-full max-w-sm text-left bg-primary-bg-cards border border-primary-border rounded-2xl shadow-sm hover:border-primary-accent transition-all flex flex-col overflow-hidden focus:outline-none focus:ring-2 focus:ring-primary-accent/20 cursor-pointer select-none"
        >
            <div className="relative w-full h-44 bg-primary-bg flex-shrink-0">
                {attachments.length > 0 ? (
                    <AttachmentImage attachment={attachments[0]} className="w-full h-full object-cover" />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-xs uppercase font-label text-secondary-text">
                        Geen beeld
                    </div>
                )}
            </div>

            <div className="p-4 sm:p-5 flex flex-col gap-2.5 flex-1 justify-between">
                <div className="space-y-1.5">
                    <div className="text-[10px] font-black text-primary-accent uppercase tracking-widest truncate">
                        {category?.name || district?.name || 'Melding'}
                    </div>

                    <span className="block font-headline font-extrabold text-primary-text text-base line-clamp-1 leading-snug">
                        {title}
                    </span>

                    <p className="text-secondary-text text-xs line-clamp-2 font-label leading-relaxed">
                        {content}
                    </p>
                </div>

                <div className="border-t border-primary-border/60 pt-3 mt-2 font-bold text-[11px] text-secondary-text font-label tracking-wide">
                    {totalFollowers} volgers
                </div>
            </div>
        </div>
    );
}