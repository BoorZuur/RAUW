import React from 'react';

export default function StoryDetailModal({ issue, onClose }) {
    if (!issue) return null;

    const isResolved = issue.status === 'gesloten' || issue.resolved_at !== null;

    return (
        <div className="fixed inset-0 z-100 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div className="bg-primary-bg border-2 border-primary-border rounded-3xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-8 relative shadow-2xl">
                <button
                    onClick={onClose}
                    className="absolute top-6 right-6 font-black uppercase text-[10px] tracking-widest text-secondary-text hover:text-primary-text"
                >
                    Sluiten
                </button>

                <div className="mb-6">
                    {isResolved ? (
                        <span className="bg-secondary-accent/10 text-secondary-accent px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
                            Opgelost
                        </span>
                    ) : (
                        <span className="bg-primary-border/20 text-secondary-text px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
                            In behandeling
                        </span>
                    )}
                </div>

                <h2 className="font-headline text-3xl font-black text-primary-text mb-2">{issue.title}</h2>
                <div className="text-xs font-label text-secondary-text mb-8 uppercase tracking-widest">
                    {issue.address}
                    {issue.district?.name && ` • ${issue.district.name}`}
                    • {new Date(issue.created_at).toLocaleDateString()}
                </div>

                <p className="font-body text-primary-text leading-relaxed mb-8 bg-primary-bg-cards p-6 rounded-2xl border border-primary-border">
                    {issue.content}
                </p>

                {issue.images?.length > 0 && (
                    <div className="mb-8">
                        <h3 className="text-[10px] font-black text-secondary-text mb-4 uppercase tracking-widest">Bijgevoegde foto's</h3>
                        <div className="grid grid-cols-2 gap-4">
                            {issue.images.map((img, idx) => (
                                <img key={idx} src={img.path} alt="Melding" className="rounded-2xl w-full h-48 object-cover border-2 border-primary-border" />
                            ))}
                        </div>
                    </div>
                )}

                <div className="border-t border-primary-border pt-8">
                    <h3 className="font-black text-sm text-primary-text mb-4 uppercase tracking-widest">Reacties ({issue.comments?.length || 0})</h3>
                    {issue.comments?.length > 0 ? (
                        <div className="space-y-4">
                            {issue.comments.map(c => (
                                <div key={c.id} className="bg-primary-bg-cards p-4 rounded-xl border border-primary-border">
                                    <div className="flex justify-between items-center mb-2">
                                        <span className="font-black text-xs text-primary-text">{c.user?.name || 'Anoniem'}</span>
                                        <span className="text-[9px] text-secondary-text">{new Date(c.created_at).toLocaleDateString()}</span>
                                    </div>
                                    <p className="text-sm font-body text-secondary-text">{c.body}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-xs font-label text-secondary-text italic">Nog geen reacties geplaatst.</p>
                    )}
                </div>
            </div>
        </div>
    );
}