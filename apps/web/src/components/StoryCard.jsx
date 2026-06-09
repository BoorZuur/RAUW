import React from 'react';

export default function StoryCard({ issue, onClick }) {
    const imageUrl = issue.images?.[0]?.path;
    const previewText = issue.content.length > 80 ? issue.content.substring(0, 80) + '...' : issue.content;

    return (
        <button
            onClick={onClick}
            className="w-full max-w-sm text-left bg-primary-bg-cards border-2 border-primary-border rounded-2xl shadow-sm hover:border-primary-accent transition-all cursor-pointer overflow-hidden flex flex-col focus:outline-none focus:ring-2 focus:ring-primary-accent"
        >
            <div className="relative w-full h-48 bg-primary-border flex-shrink-0">
                {imageUrl ? (
                    <img src={imageUrl} alt={issue.title} className="w-full h-full object-cover" />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-secondary-text text-[10px] uppercase font-black tracking-widest">Geen beeld</div>
                )}

                {issue.status === 'opgelost' && (
                    <div className="absolute top-3 left-3 flex items-center gap-1 bg-secondary-accent text-white px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest shadow-sm">
                        Opgelost
                    </div>
                )}
            </div>

            <div className="p-5 flex flex-col gap-2 w-full">
                <h4 className="font-headline font-black text-primary-text text-[16px] truncate">{issue.title}</h4>
                <p className="font-body text-secondary-text text-sm line-clamp-2">{previewText}</p>
                <div className="flex items-center gap-2 text-[9px] font-black text-secondary-text uppercase tracking-widest mt-2 border-t border-primary-border pt-3">
                    <span className="truncate">{issue.address || 'Locatie onbekend'}</span>
                    <span>•</span>
                    <span>{new Date(issue.created_at).toLocaleDateString()}</span>
                </div>
            </div>
        </button>
    );
}