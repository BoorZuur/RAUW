import { useCallback, useEffect, useRef } from 'react';
import { fetchIssueComments } from '../services/issueCommentService';

const POLL_INTERVAL_MS = 5_000;

function isPollingEnabled() {
    const token = localStorage.getItem('auth_token');
    const userType = localStorage.getItem('user_type');
    return Boolean(token) && userType === 'user';
}

export function useIssueCommentPolling({ issueId, enabled = true, paused = false, onComments }) {
    const intervalRef = useRef(null);
    const stoppedRef = useRef(false);
    const onCommentsRef = useRef(onComments);

    useEffect(() => {
        onCommentsRef.current = onComments;
    }, [onComments]);

    const poll = useCallback(async () => {
        if (!enabled || paused || !issueId || !isPollingEnabled() || stoppedRef.current) {
            return;
        }

        try {
            const comments = await fetchIssueComments(issueId);
            onCommentsRef.current?.(comments);
        } catch (err) {
            if (err?.response?.status === 401) {
                stoppedRef.current = true;
                if (intervalRef.current) {
                    clearInterval(intervalRef.current);
                    intervalRef.current = null;
                }
            } else {
                console.error('Comment poll error:', err);
            }
        }
    }, [enabled, issueId, paused]);

    useEffect(() => {
        if (!enabled || !issueId || !isPollingEnabled()) {
            return undefined;
        }

        stoppedRef.current = false;

        const startInterval = () => {
            if (intervalRef.current || paused) {
                return;
            }
            intervalRef.current = setInterval(poll, POLL_INTERVAL_MS);
        };

        const stopInterval = () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
        };

        const handleVisibilityChange = () => {
            if (document.hidden) {
                stopInterval();
            } else if (!paused) {
                void poll();
                startInterval();
            }
        };

        if (!document.hidden && !paused) {
            void poll();
            startInterval();
        }

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            stopInterval();
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [enabled, issueId, paused, poll]);

    return { poll };
}
