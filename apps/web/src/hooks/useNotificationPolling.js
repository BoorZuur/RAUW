import { useCallback, useEffect, useRef, useState } from 'react';
import { getUnreadCount, listNotifications } from '../services/notificationService';

const POLL_INTERVAL_MS = 30_000;

function isPollingEnabled() {
    const token = localStorage.getItem('auth_token');
    const userType = localStorage.getItem('user_type');
    return Boolean(token) && userType === 'user';
}

export function useNotificationPolling({ onNewNotifications } = {}) {
    const [unreadCount, setUnreadCount] = useState(0);
    const lastKnownCountRef = useRef(null);
    const lastPollIsoRef = useRef(null);
    const intervalRef = useRef(null);
    const stoppedRef = useRef(false);
    const onNewNotificationsRef = useRef(onNewNotifications);

    useEffect(() => {
        onNewNotificationsRef.current = onNewNotifications;
    }, [onNewNotifications]);

    const poll = useCallback(async () => {
        if (!isPollingEnabled() || stoppedRef.current) {
            return;
        }

        try {
            const { count } = await getUnreadCount();
            const newCount = count ?? 0;
            const lastKnown = lastKnownCountRef.current;

            if (lastKnown !== null && newCount > lastKnown && lastPollIsoRef.current) {
                const response = await listNotifications({
                    since: lastPollIsoRef.current,
                    isRead: false,
                    perPage: 20,
                });
                const items = response.data || [];
                const chronological = [...items].reverse();

                if (chronological.length > 0 && onNewNotificationsRef.current) {
                    onNewNotificationsRef.current(chronological);
                }
            }

            lastKnownCountRef.current = newCount;
            setUnreadCount(newCount);
            lastPollIsoRef.current = new Date().toISOString();
        } catch (err) {
            if (err?.response?.status === 401) {
                stoppedRef.current = true;
                setUnreadCount(0);
                if (intervalRef.current) {
                    clearInterval(intervalRef.current);
                    intervalRef.current = null;
                }
            } else {
                console.error('Notification poll error:', err);
            }
        }
    }, []);

    useEffect(() => {
        if (!isPollingEnabled()) {
            return undefined;
        }

        stoppedRef.current = false;

        const startInterval = () => {
            if (intervalRef.current) {
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
            } else {
                void poll();
                startInterval();
            }
        };

        if (!document.hidden) {
            void poll();
            startInterval();
        }

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            stopInterval();
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [poll]);

    return { unreadCount, setUnreadCount };
}
