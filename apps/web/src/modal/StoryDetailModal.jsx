import React, { useState, useEffect, useRef, useCallback } from 'react';
import { AlertCircle, Trash2, Pencil } from 'lucide-react';
import AttachmentImage from '../components/AttachmentImage';
import { getCommentAuthorDisplayName } from '../utils/authorDisplay';
import { COMMENT_MAX_LENGTH, COMMENT_WARN_AT } from '../constants/commentLimits';
import { getCommentErrorMessage } from '../utils/commentErrors';
import { fetchIssueComments, updateIssueComment, deleteIssueComment } from '../services/issueCommentService';
import { useIssueCommentPolling } from '../hooks/useIssueCommentPolling';

function resolveDefaultAnonymous(issue) {
    if (typeof issue?.default_comment_is_anonymous === 'boolean') {
        return issue.default_comment_is_anonymous;
    }
    return Boolean(issue?.is_anonymous && issue?.author?.is_anonymous);
}

function getLengthClass(length) {
    if (length >= COMMENT_MAX_LENGTH) return 'text-red-500';
    if (length >= COMMENT_WARN_AT) return 'text-amber-500';
    return 'text-secondary-text';
}

export default function StoryDetailModal({ issue, onClose, onAddComment }) {
    const [commentText, setCommentText] = useState('');
    const [isAnonymousComment, setIsAnonymousComment] = useState(() => resolveDefaultAnonymous(issue));
    const [localComments, setLocalComments] = useState(issue.comments || []);
    const [commentError, setCommentError] = useState(null);
    const [editingCommentId, setEditingCommentId] = useState(null);
    const [editDraft, setEditDraft] = useState('');
    const [deleteCommentTarget, setDeleteCommentTarget] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const commentsContainerRef = useRef(null);
    const wasNearBottomRef = useRef(true);
    const prevCountRef = useRef((issue.comments || []).length);

    useEffect(() => {
        setIsAnonymousComment(resolveDefaultAnonymous(issue));
    }, [issue?.id, issue?.default_comment_is_anonymous, issue?.is_anonymous, issue?.author?.is_anonymous]);

    useEffect(() => {
        if (!issue?.id) return;

        let cancelled = false;

        fetchIssueComments(issue.id)
            .then((comments) => {
                if (!cancelled) {
                    setLocalComments(comments);
                    prevCountRef.current = comments.length;
                }
            })
            .catch((err) => {
                console.error('Kon niet alle comments ophalen:', err);
            });

        return () => {
            cancelled = true;
        };
    }, [issue?.id]);

    const handlePollComments = useCallback((serverComments) => {
        const prevCount = prevCountRef.current;
        const shouldScroll = serverComments.length > prevCount && wasNearBottomRef.current;

        setLocalComments(serverComments);
        prevCountRef.current = serverComments.length;

        if (shouldScroll) {
            requestAnimationFrame(() => {
                const el = commentsContainerRef.current;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        }
    }, []);

    useIssueCommentPolling({
        issueId: issue?.id,
        enabled: Boolean(issue?.id),
        paused: editingCommentId !== null,
        onComments: handlePollComments,
    });

    const handleCommentsScroll = () => {
        const el = commentsContainerRef.current;
        if (!el) return;
        wasNearBottomRef.current = el.scrollHeight - el.scrollTop - el.clientHeight <= 80;
    };

    if (!issue) return null;

    const {
        id,
        title,
        content,
        address,
        created_at,
        attachments = [],
        followers,
        participant_count,
        status,
        category,
    } = issue;

    const totalFollowers = typeof participant_count === 'number'
        ? participant_count
        : Array.isArray(followers) ? followers.length : 0;

    const formattedDate = created_at
        ? new Date(created_at).toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' })
        : '';

    const getStatusDetails = (currentStatus) => {
        switch (currentStatus?.toLowerCase()) {
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
                return { label: currentStatus || 'Open', className: 'bg-primary-accent text-white' };
        }
    };

    const statusDetails = getStatusDetails(status);
    const showIdentifiedWarning = !isAnonymousComment && (issue.is_anonymous || issue.default_comment_is_anonymous);
    const commentLength = commentText.length;
    const editLength = editDraft.length;

    const handleCommentSubmit = async (e) => {
        e.preventDefault();
        const text = commentText.trim();
        if (!text || commentLength > COMMENT_MAX_LENGTH || isSubmitting) return;

        setCommentError(null);
        const submittedText = commentText;
        setCommentText('');
        setIsSubmitting(true);

        try {
            const newComment = await onAddComment?.(id, text, isAnonymousComment);
            if (newComment) {
                setLocalComments((prev) => {
                    const exists = prev.some((c) => c.id === newComment.id);
                    if (exists) {
                        return prev.map((c) => (c.id === newComment.id ? newComment : c));
                    }
                    return [...prev, newComment];
                });
                prevCountRef.current += 1;
                requestAnimationFrame(() => {
                    const el = commentsContainerRef.current;
                    if (el) {
                        el.scrollTop = el.scrollHeight;
                        wasNearBottomRef.current = true;
                    }
                });
            }
        } catch (err) {
            setCommentText(submittedText);
            setCommentError(getCommentErrorMessage(err));
        } finally {
            setIsSubmitting(false);
        }
    };

    const startEdit = (comment) => {
        setEditingCommentId(comment.id);
        setEditDraft(comment.content || comment.body || '');
        setCommentError(null);
    };

    const cancelEdit = () => {
        setEditingCommentId(null);
        setEditDraft('');
    };

    const saveEdit = async (commentId) => {
        const text = editDraft.trim();
        if (!text || editLength > COMMENT_MAX_LENGTH) return;

        setCommentError(null);

        try {
            const updated = await updateIssueComment(id, commentId, text);
            setLocalComments((prev) => prev.map((c) => (c.id === commentId ? updated : c)));
            setEditingCommentId(null);
            setEditDraft('');
        } catch (err) {
            setCommentError(getCommentErrorMessage(err));
        }
    };

    const confirmDeleteComment = async () => {
        if (!deleteCommentTarget) return;

        const commentId = deleteCommentTarget.id;
        setCommentError(null);

        try {
            await deleteIssueComment(id, commentId);
            setLocalComments((prev) => prev.filter((c) => c.id !== commentId));
            prevCountRef.current = Math.max(0, prevCountRef.current - 1);
            setDeleteCommentTarget(null);
        } catch (err) {
            setDeleteCommentTarget(null);
            setCommentError(getCommentErrorMessage(err));
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
            {deleteCommentTarget ? (
                <div className="absolute inset-0 z-60 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-stone-900 border border-primary-border p-6 rounded-2xl shadow-xl max-w-sm w-full">
                        <h3 className="text-white font-black text-lg mb-2">Weet je het zeker?</h3>
                        <p className="text-stone-400 text-sm mb-6">Deze reactie wordt definitief verwijderd.</p>
                        <div className="flex gap-3">
                            <button
                                type="button"
                                onClick={() => setDeleteCommentTarget(null)}
                                className="flex-1 py-2 rounded-xl bg-stone-800 text-white font-bold"
                            >
                                Annuleren
                            </button>
                            <button
                                type="button"
                                onClick={confirmDeleteComment}
                                className="flex-1 py-2 rounded-xl font-bold bg-red-600 text-white"
                            >
                                Verwijderen
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}

            <button
                onClick={onClose}
                className="absolute top-6 right-6 text-white hover:text-primary-accent transition-colors cursor-pointer p-2 z-50 focus:outline-none"
                aria-label="Sluiten"
            >
                <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div className="w-full max-w-5xl h-[85vh] bg-primary-bg-cards border border-primary-border rounded-3xl overflow-hidden shadow-2xl flex flex-col md:flex-row">
                <div className="flex-1 bg-primary-bg flex flex-col h-1/2 md:h-full border-b md:border-b-0 md:border-r border-primary-border">
                    <div className="w-full h-48 md:h-64 bg-stone-950 flex items-center justify-center relative shrink-0 overflow-hidden border-b border-primary-border">
                        {attachments && attachments.length > 0 ? (
                            <AttachmentImage attachment={attachments[0]} className="w-full h-full object-cover" alt={title} />
                        ) : (
                            <div className="flex flex-col items-center gap-2 text-stone-400">
                                <svg className="w-8 h-8 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <span className="font-label text-xs uppercase font-black tracking-widest opacity-60">Geen afbeelding</span>
                            </div>
                        )}
                    </div>

                    <div className="flex-1 p-6 sm:p-8 bg-stone-950 overflow-y-auto custom-scrollbar flex flex-col justify-start">
                        <div className="flex flex-wrap items-center gap-2 mb-4">
                            <span className="text-[10px] sm:text-[11px] font-label font-black uppercase tracking-widest text-primary-accent bg-primary-accent/10 border border-primary-accent/30 px-2.5 py-1 rounded-md">
                                {address || 'Rotterdam'}
                            </span>
                            <span className={`px-2.5 py-1 rounded-md text-[10px] sm:text-[11px] font-label font-black uppercase tracking-wider shadow-xs ${statusDetails.className}`}>
                                {statusDetails.label}
                            </span>
                            {category ? (
                                <span className="bg-stone-800 text-stone-200 border border-stone-700 px-2.5 py-1 rounded-md text-[10px] sm:text-[11px] font-label font-bold uppercase tracking-wider">
                                    {category.name || category}
                                </span>
                            ) : null}
                        </div>
                        <h2 className="font-headline font-black text-xl sm:text-3xl tracking-tight leading-tight text-white mb-4">
                            {title}
                        </h2>
                        <p className="font-label text-sm text-stone-200/95 leading-relaxed max-w-2xl antialiased">
                            {content}
                        </p>
                    </div>
                </div>

                <div className="w-full md:w-100 flex flex-col h-1/2 md:h-full bg-primary-bg-cards shrink-0">
                    <div className="p-4 border-b border-primary-border flex items-center justify-between bg-primary-bg/40">
                        <div className="flex items-center gap-1.5 text-sm font-label text-primary-text">
                            <svg className="w-4 h-4 text-secondary-text" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span className="font-black">{totalFollowers}</span>
                            <span className="text-secondary-text">volgers</span>
                        </div>
                        {formattedDate ? (
                            <span className="text-[11px] text-secondary-text font-label uppercase font-bold tracking-wider">
                                {formattedDate}
                            </span>
                        ) : null}
                    </div>

                    <div
                        ref={commentsContainerRef}
                        onScroll={handleCommentsScroll}
                        className="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar bg-primary-bg-cards"
                    >
                        {localComments.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-center p-6 opacity-60">
                                <div className="w-16 h-16 rounded-full bg-primary-bg border border-primary-border flex items-center justify-center mb-4 text-2xl">💬</div>
                                <p className="text-sm font-label font-bold text-primary-text">Nog geen reacties</p>
                            </div>
                        ) : (
                            localComments.map((comment) => {
                                const authorName = getCommentAuthorDisplayName(comment);
                                const isEditing = editingCommentId === comment.id;

                                return (
                                    <div key={comment.id} className="flex gap-3 animate-fade-in group">
                                        <div className="w-8 h-8 rounded-full bg-stone-800 flex items-center justify-center shrink-0 mt-1 border border-primary-border">
                                            <span className="text-[10px] font-black text-primary-accent">
                                                {authorName.charAt(0).toUpperCase()}
                                            </span>
                                        </div>

                                        <div className="flex-1 bg-primary-bg p-3 rounded-2xl rounded-tl-none border border-primary-border shadow-sm w-fit max-w-[90%]">
                                            <div className="flex items-start justify-between gap-2">
                                                <span className="font-bold text-[13px] text-white shrink-0">
                                                    {authorName}
                                                </span>
                                                {(comment.can_update || comment.can_delete) && !isEditing ? (
                                                    <div className="flex items-center gap-1 shrink-0">
                                                        {comment.can_update ? (
                                                            <button
                                                                type="button"
                                                                onClick={() => startEdit(comment)}
                                                                className="p-1 text-secondary-text hover:text-primary-accent transition-colors"
                                                                aria-label="Bewerken"
                                                            >
                                                                <Pencil className="w-3.5 h-3.5" />
                                                            </button>
                                                        ) : null}
                                                        {comment.can_delete ? (
                                                            <button
                                                                type="button"
                                                                onClick={() => setDeleteCommentTarget(comment)}
                                                                className="p-1 text-secondary-text hover:text-red-500 transition-colors"
                                                                aria-label="Verwijderen"
                                                            >
                                                                <Trash2 className="w-3.5 h-3.5" />
                                                            </button>
                                                        ) : null}
                                                    </div>
                                                ) : null}
                                            </div>

                                            {isEditing ? (
                                                <div className="mt-2 flex flex-col gap-2">
                                                    <textarea
                                                        value={editDraft}
                                                        onChange={(e) => {
                                                            setEditDraft(e.target.value);
                                                            if (commentError) setCommentError(null);
                                                        }}
                                                        maxLength={COMMENT_MAX_LENGTH}
                                                        rows={3}
                                                        className="w-full bg-stone-900 border border-primary-border rounded-xl p-2 text-[13px] text-stone-200 focus:outline-none focus:border-primary-accent resize-none"
                                                    />
                                                    <p className={`text-[10px] font-bold text-right ${getLengthClass(editLength)}`}>
                                                        {editLength}/{COMMENT_MAX_LENGTH}
                                                    </p>
                                                    <div className="flex gap-2 justify-end">
                                                        <button
                                                            type="button"
                                                            onClick={cancelEdit}
                                                            className="text-[11px] font-label font-bold uppercase text-secondary-text hover:text-white"
                                                        >
                                                            Annuleren
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => saveEdit(comment.id)}
                                                            disabled={!editDraft.trim() || editLength > COMMENT_MAX_LENGTH}
                                                            className="text-[11px] font-label font-black uppercase text-primary-accent disabled:opacity-30"
                                                        >
                                                            Opslaan
                                                        </button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <p className="text-[13px] text-stone-200 leading-relaxed whitespace-pre-wrap break-words mt-1">
                                                    {comment.content || comment.body || comment.text || 'Geen inhoud'}
                                                </p>
                                            )}

                                            <div className="text-[9px] text-secondary-text/60 font-bold mt-1.5">
                                                {comment.created_at
                                                    ? new Date(comment.created_at).toLocaleDateString('nl-NL', { day: '2-digit', month: 'short' })
                                                    : 'Zojuist'}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>

                    <form onSubmit={handleCommentSubmit} className="p-4 border-t border-primary-border bg-primary-bg/20">
                        {commentError ? (
                            <div className="mb-3 p-3 bg-red-500/20 text-red-500 rounded-xl flex items-center gap-2 text-sm font-bold">
                                <AlertCircle className="w-4 h-4 shrink-0" />
                                {commentError}
                            </div>
                        ) : null}

                        <div className="relative flex items-center justify-between p-3 mb-2 bg-primary-bg-cards border-2 border-primary-border rounded-2xl transition-all duration-200 hover:border-primary-accent/50 focus-within:ring-2 focus-within:ring-primary-accent/30">
                            <label htmlFor="comment_is_anonymous" className="text-xs font-black uppercase tracking-widest text-primary-text cursor-pointer select-none pr-4">
                                Anoniem reageren
                            </label>
                            <div className="relative flex items-center">
                                <input
                                    type="checkbox"
                                    id="comment_is_anonymous"
                                    checked={isAnonymousComment}
                                    onChange={(e) => setIsAnonymousComment(e.target.checked)}
                                    className="peer appearance-none w-6 h-6 rounded-lg border-2 border-primary-border bg-primary-bg checked:bg-primary-text checked:border-primary-text transition-all duration-150 cursor-pointer focus:ring-0 focus:outline-none"
                                />
                                <svg
                                    className="absolute left-1.5 top-1.5 w-3 h-3 text-primary-bg pointer-events-none opacity-0 scale-50 peer-checked:opacity-100 peer-checked:scale-100 transition-all duration-150"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={4}
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>

                        {isAnonymousComment ? (
                            <p className="text-[11px] text-secondary-text mb-2">Je reactie is zichtbaar onder een alias.</p>
                        ) : showIdentifiedWarning ? (
                            <p className="text-[11px] text-amber-500/90 mb-2">Je gebruikersnaam wordt zichtbaar.</p>
                        ) : null}

                        <div className="flex flex-col gap-1 bg-primary-bg border border-primary-border rounded-xl px-3 py-1 focus-within:border-primary-accent transition-colors">
                            <div className="flex items-end gap-2">
                                <textarea
                                    value={commentText}
                                    onChange={(e) => {
                                        setCommentText(e.target.value);
                                        if (commentError) setCommentError(null);
                                    }}
                                    maxLength={COMMENT_MAX_LENGTH}
                                    rows={2}
                                    placeholder="Een reactie toevoegen..."
                                    className="w-full bg-transparent text-sm py-2.5 text-primary-text placeholder-secondary-text/60 font-label focus:outline-none resize-none"
                                />
                                <button
                                    type="submit"
                                    disabled={!commentText.trim() || commentLength > COMMENT_MAX_LENGTH || isSubmitting}
                                    className="text-xs font-label font-black uppercase tracking-wider text-primary-accent disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer hover:brightness-110 transition-all pb-2.5 shrink-0"
                                >
                                    Plaatsen
                                </button>
                            </div>
                            <p className={`text-[10px] font-bold text-right pb-1 ${getLengthClass(commentLength)}`}>
                                {commentLength}/{COMMENT_MAX_LENGTH}
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
