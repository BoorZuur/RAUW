import React, { useState } from 'react';
import { Loader2 } from 'lucide-react';
import SimilarIssueMatchCard from '../components/SimilarIssueMatchCard.jsx';

function isMatchDisabled(match) {
    return match.linkable === false || match.is_participant === true;
}

function getDisabledReason(match) {
    if (match.is_participant === true || match.linkable === false) {
        return 'Je volgt dit al';
    }
    return undefined;
}

export default function DuplicateSuggestionModal({
    matches = [],
    ownMatches = [],
    onSelectMatch,
    onSubmitStandalone,
    onCancel,
    onAbortReport,
    isSubmitting = false,
    error = null,
}) {
    const [selectedId, setSelectedId] = useState(null);

    const selectableMatches = matches.filter((match) => !isMatchDisabled(match));
    const hasSelectableMatch = selectableMatches.length > 0;
    const canLink = hasSelectableMatch && selectedId != null;

    const handleSelect = (id) => {
        setSelectedId((prev) => (prev === id ? null : id));
    };

    const handleLink = () => {
        if (canLink && onSelectMatch) {
            onSelectMatch(selectedId);
        }
    };

    return (
        <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div className="bg-primary-bg-cards p-6 sm:p-8 rounded-3xl border-2 border-primary-border shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col">
                <h3 className="text-xl font-black uppercase mb-2">Lijkt op een bestaande melding</h3>
                <p className="text-sm text-secondary-text mb-6">
                    Er zijn vergelijkbare meldingen in de buurt. Koppel je melding aan een bestaand
                    verhaal om updates te volgen, of meld toch een nieuwe melding.
                </p>

                <div className="flex-1 overflow-y-auto space-y-3 mb-6 pr-1 custom-scrollbar">
                    {ownMatches.map((match) => (
                        <SimilarIssueMatchCard key={`own-${match.id}`} match={match} variant="own" />
                    ))}
                    {matches.map((match) => {
                        const disabled = isMatchDisabled(match);
                        return (
                            <SimilarIssueMatchCard
                                key={match.id}
                                match={match}
                                variant="linkable"
                                selected={selectedId === match.id}
                                disabled={disabled}
                                disabledReason={getDisabledReason(match)}
                                onSelect={disabled ? undefined : handleSelect}
                            />
                        );
                    })}
                </div>

                {error ? (
                    <p className="text-red-600 font-bold mb-4 p-3 bg-red-100 rounded-lg text-sm">
                        {error}
                    </p>
                ) : null}

                <div className="space-y-3">
                    <button
                        type="button"
                        onClick={handleLink}
                        disabled={!canLink || isSubmitting}
                        className="w-full p-3 bg-secondary-accent text-white rounded-xl font-black uppercase tracking-widest hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer flex items-center justify-center gap-2"
                    >
                        {isSubmitting ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin" />
                                Bezig...
                            </>
                        ) : (
                            'Koppel aan dit verhaal'
                        )}
                    </button>

                    <button
                        type="button"
                        onClick={onSubmitStandalone}
                        disabled={isSubmitting}
                        className="w-full p-3 border-2 border-primary-border rounded-xl font-bold uppercase tracking-wider hover:border-primary-accent transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        Nee, toch melden
                    </button>

                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={isSubmitting}
                        className="w-full p-3 text-sm font-bold text-secondary-text hover:text-primary-text transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        Annuleren
                    </button>

                    <button
                        type="button"
                        onClick={onAbortReport}
                        disabled={isSubmitting}
                        className="w-full p-2 text-xs font-bold text-red-600/80 hover:text-red-600 transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        Melding annuleren
                    </button>
                </div>
            </div>
        </div>
    );
}
