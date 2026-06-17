import { useState, useEffect, useCallback, useRef } from 'react';
import { fetchChatMessages } from '../services/issueChatService';

export default function useIssueChatPolling(issueId, chatId, intervalMs = 5000) {
    const [messages, setMessages] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);
    const pollingRef = useRef(null);

    const loadMessages = useCallback(async (isInitial = false) => {
        if (!issueId || !chatId) return;

        if (isInitial) {
            setIsLoading(true);
        }

        try {
            const data = await fetchChatMessages(issueId, chatId);
            setMessages(data);
            setError(null);
        } catch (err) {
            console.error('Failed to fetch chat messages:', err);
            setError(err);
        } finally {
            if (isInitial) {
                setIsLoading(false);
            }
        }
    }, [issueId, chatId]);

    const startPolling = useCallback(() => {
        if (pollingRef.current) clearInterval(pollingRef.current);
        pollingRef.current = setInterval(() => loadMessages(false), intervalMs);
    }, [loadMessages, intervalMs]);

    const stopPolling = useCallback(() => {
        if (pollingRef.current) {
            clearInterval(pollingRef.current);
            pollingRef.current = null;
        }
    }, []);

    useEffect(() => {
        setMessages([]);
        if (issueId && chatId) {
            loadMessages(true).then(() => {
                startPolling();
            });
        }
        return stopPolling;
    }, [issueId, chatId, loadMessages, startPolling, stopPolling]);

    // Force a manual refresh (e.g., after sending a message)
    const refresh = useCallback(async () => {
        await loadMessages(false);
    }, [loadMessages]);

    return { messages, setMessages, isLoading, error, refresh };
}
