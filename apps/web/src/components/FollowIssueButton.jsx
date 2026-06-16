import React, { useState } from 'react';
import { AlertCircle } from 'lucide-react';
import { joinIssue, leaveIssue, getIssue } from '../services/issueService';
import { getIssueErrorMessage } from '../utils/issueErrorMessages';
import { resolveJoinTargetId } from '../utils/issueParticipation';

function isIssueOwnedByUser(issue, currentUserId) {
    if (!issue || currentUserId == null) {
        return false;
    }

    const authorId = issue.author?.id ?? issue.user_id;
    if (authorId == null) {
        return false;
    }

    return String(authorId) === String(currentUserId);
}

function isIssueClosedForFollow(issue) {
    const status = issue?.status?.toLowerCase();
    return status === 'gesloten' || status === 'afgehandeld';
}

export function shouldShowFollowButton(issue, currentUserId) {
    if (!currentUserId || !issue) {
        return false;
    }

    if (isIssueOwnedByUser(issue, currentUserId)) {
        return false;
    }

    if (isIssueClosedForFollow(issue)) {
        return false;
    }

    return true;
}

export default function FollowIssueButton({ issue, currentUserId, onParticipationChange }) {
    const [isAnonymousFollow, setIsAnonymousFollow] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);
    const [showLeaveConfirm, setShowLeaveConfirm] = useState(false);

    if (!shouldShowFollowButton(issue, currentUserId)) {
        return null;
    }

    const joinTargetId = resolveJoinTargetId(issue);
    const isParticipant = Boolean(issue.is_participant);

    const mergeUpdatedIssue = (updated) => {
        onParticipationChange?.({
            ...issue,
            ...updated,
            id: issue.id,
            comments: issue.comments,
        });
    };

    const refetchIssue = async () => {
        const updated = await getIssue(issue.id);
        mergeUpdatedIssue(updated);
        return updated;
    };

    const handleJoin = async () => {
        setError(null);
        setIsLoading(true);

        try {
            // First join returns 201; repeat join is idempotent (200) — both are success.
            const updated = await joinIssue(joinTargetId, { isAnonymous: isAnonymousFollow });
            mergeUpdatedIssue({ ...updated, is_participant: true });
            setIsAnonymousFollow(false);
        } catch (err) {
            const code = err?.response?.data?.code;
            if (code === 'not_participant') {
                try {
                    await refetchIssue();
                } catch {
                    setError(getIssueErrorMessage(err));
                }
            } else {
                setError(getIssueErrorMessage(err));
            }
        } finally {
            setIsLoading(false);
        }
    };

    const handleLeave = async () => {
        setError(null);
        setIsLoading(true);

        try {
            await leaveIssue(joinTargetId);
            await refetchIssue();
            setShowLeaveConfirm(false);
        } catch (err) {
            const code = err?.response?.data?.code;
            if (code === 'not_participant') {
                try {
                    await refetchIssue();
                    setShowLeaveConfirm(false);
                } catch {
                    setError(getIssueErrorMessage(err));
                }
            } else {
                setError(getIssueErrorMessage(err));
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <>
            {showLeaveConfirm ? (
                <div className="fixed inset-0 z-70 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-stone-900 border border-primary-border p-6 rounded-2xl shadow-xl max-w-sm w-full">
                        <h3 className="text-white font-black text-lg mb-2">Ontvolgen?</h3>
                        <p className="text-stone-400 text-sm mb-6">
                            Je ontvangt geen updates meer over deze melding.
                        </p>
                        <div className="flex gap-3">
                            <button
                                type="button"
                                onClick={() => setShowLeaveConfirm(false)}
                                disabled={isLoading}
                                className="flex-1 py-2 rounded-xl bg-stone-800 text-white font-bold disabled:opacity-50"
                            >
                                Annuleren
                            </button>
                            <button
                                type="button"
                                onClick={handleLeave}
                                disabled={isLoading}
                                className="flex-1 py-2 rounded-xl font-bold bg-red-600 text-white disabled:opacity-50"
                            >
                                Ontvolgen
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}

            <div className="flex flex-col gap-2">
                {error ? (
                    <div className="flex items-center gap-1.5 text-[11px] text-red-500 font-bold">
                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                        <span>{error}</span>
                    </div>
                ) : null}

                <div className="flex flex-wrap items-center gap-3">
                    {!isParticipant ? (
                        <>
                            <button
                                type="button"
                                onClick={handleJoin}
                                disabled={isLoading}
                                className="px-4 py-2 rounded-xl text-xs font-label font-black uppercase tracking-wider bg-primary-accent text-white hover:brightness-110 transition-all disabled:opacity-50"
                            >
                                Volgen
                            </button>
                            <label className="flex items-center gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    checked={isAnonymousFollow}
                                    onChange={(e) => setIsAnonymousFollow(e.target.checked)}
                                    disabled={isLoading}
                                    className="w-4 h-4 rounded border-primary-border bg-primary-bg accent-primary-accent cursor-pointer"
                                />
                                <span className="text-xs font-label font-bold text-secondary-text">
                                    Anoniem volgen
                                </span>
                            </label>
                        </>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setShowLeaveConfirm(true)}
                            disabled={isLoading}
                            className="px-4 py-2 rounded-xl text-xs font-label font-black uppercase tracking-wider border border-primary-border text-primary-text hover:border-primary-accent transition-all disabled:opacity-50"
                        >
                            Ontvolgen
                        </button>
                    )}
                </div>
            </div>
        </>
    );
}
