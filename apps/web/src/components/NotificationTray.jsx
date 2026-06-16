import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Bell } from 'lucide-react';
import Notification from './Notification';
import NotificationPanel from './NotificationPanel';
import { useNotificationPolling } from '../hooks/useNotificationPolling';
import {
    listNotifications,
    markNotificationRead,
    markNotificationsReadBulk,
} from '../services/notificationService';

const TOAST_STAGGER_MS = 400;

function resolveNotificationId(notification) {
    return notification?.id ?? notification?.notification_id;
}

export default function NotificationTray() {
    const toastRef = useRef(null);
    const trayRef = useRef(null);
    const toastTimeoutsRef = useRef([]);

    const [panelOpen, setPanelOpen] = useState(false);
    const [activeTab, setActiveTab] = useState('unread');
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [expandedId, setExpandedId] = useState(null);
    const [selectMode, setSelectMode] = useState(false);
    const [selectedIds, setSelectedIds] = useState([]);
    const [bulkLoading, setBulkLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(false);

    const handleNewNotifications = useCallback((items) => {
        items.forEach((item, index) => {
            const timeoutId = setTimeout(() => {
                toastRef.current?.show(
                    item.title || 'Notificatie',
                    item.body || '',
                    'info'
                );
            }, index * TOAST_STAGGER_MS);
            toastTimeoutsRef.current.push(timeoutId);
        });
    }, []);

    const { unreadCount, setUnreadCount } = useNotificationPolling({
        onNewNotifications: handleNewNotifications,
    });

    const fetchNotifications = useCallback(async (tab, nextPage = 1, append = false) => {
        setLoading(true);
        setError(null);

        try {
            const response = await listNotifications({
                page: nextPage,
                perPage: 20,
                isRead: tab === 'unread' ? false : undefined,
            });
            const items = response.data || [];
            const meta = response.meta;

            setNotifications((prev) => (append ? [...prev, ...items] : items));
            setHasMore(meta ? meta.current_page < meta.last_page : false);
            setPage(nextPage);
        } catch (err) {
            if (err?.response?.status === 401) {
                setError('Je bent uitgelogd.');
            } else {
                setError('Kon notificaties niet laden.');
            }
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        if (panelOpen) {
            fetchNotifications(activeTab, 1, false);
        }
    }, [panelOpen, activeTab, fetchNotifications]);

    useEffect(() => {
        return () => {
            toastTimeoutsRef.current.forEach(clearTimeout);
            toastTimeoutsRef.current = [];
        };
    }, []);

    useEffect(() => {
        if (!panelOpen) {
            return undefined;
        }

        const handleClickOutside = (event) => {
            if (trayRef.current && !trayRef.current.contains(event.target)) {
                setPanelOpen(false);
            }
        };

        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setPanelOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [panelOpen]);

    const togglePanel = () => {
        setPanelOpen((prev) => !prev);
    };

    const handleTabChange = (tab) => {
        setExpandedId(null);
        setSelectMode(false);
        setSelectedIds([]);

        if (tab !== activeTab) {
            setActiveTab(tab);
        } else if (panelOpen) {
            fetchNotifications(tab, 1, false);
        }
    };

    const handleRowClick = async (notificationId) => {
        if (expandedId === notificationId) {
            setExpandedId(null);
            return;
        }

        setExpandedId(notificationId);

        const notification = notifications.find(
            (item) => resolveNotificationId(item) === notificationId
        );

        if (!notification || notification.is_read) {
            return;
        }

        setNotifications((prev) =>
            prev.map((item) =>
                resolveNotificationId(item) === notificationId
                    ? { ...item, is_read: true }
                    : item
            )
        );
        setUnreadCount((prev) => Math.max(0, prev - 1));

        try {
            await markNotificationRead(notificationId);
        } catch (err) {
            console.error('Mark read failed:', err);
            setNotifications((prev) =>
                prev.map((item) =>
                    resolveNotificationId(item) === notificationId
                        ? { ...item, is_read: false }
                        : item
                )
            );
            setUnreadCount((prev) => prev + 1);
        }
    };

    const handleToggleSelect = (notificationId) => {
        setSelectedIds((prev) =>
            prev.includes(notificationId)
                ? prev.filter((id) => id !== notificationId)
                : [...prev, notificationId]
        );
    };

    const handleEnterSelectMode = () => {
        setExpandedId(null);
        setSelectMode(true);
    };

    const handleExitSelectMode = () => {
        setSelectMode(false);
        setSelectedIds([]);
    };

    const handleBulkMarkRead = async () => {
        if (selectedIds.length === 0) {
            return;
        }

        const ids = [...selectedIds];
        const unreadSelected = notifications.filter((item) => {
            const id = resolveNotificationId(item);
            return ids.includes(id) && !item.is_read;
        }).length;

        setBulkLoading(true);
        setNotifications((prev) =>
            prev.map((item) =>
                ids.includes(resolveNotificationId(item))
                    ? { ...item, is_read: true }
                    : item
            )
        );
        setUnreadCount((prev) => Math.max(0, prev - unreadSelected));

        try {
            await markNotificationsReadBulk(ids);
            setSelectMode(false);
            setSelectedIds([]);

            if (activeTab === 'unread') {
                await fetchNotifications('unread', 1, false);
            }
        } catch (err) {
            console.error('Bulk mark read failed:', err);
            await fetchNotifications(activeTab, 1, false);
        } finally {
            setBulkLoading(false);
        }
    };

    const handleLoadMore = () => {
        fetchNotifications(activeTab, page + 1, true);
    };

    const badgeText = unreadCount > 9 ? '9+' : String(unreadCount);
    const ariaLabel =
        unreadCount > 0 ? `Notificaties, ${unreadCount} ongelezen` : 'Notificaties';

    return (
        <div className="relative" ref={trayRef}>
            <button
                type="button"
                onClick={togglePanel}
                className="relative rounded-2xl p-3 transition-all hover:bg-black/10 dark:hover:bg-white/10"
                aria-label={ariaLabel}
                aria-expanded={panelOpen}
            >
                <Bell className="h-5 w-5" />
                {unreadCount > 0 ? (
                    <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full border border-primary-bg bg-primary-accent px-1 text-[10px] font-bold text-white">
                        {badgeText}
                    </span>
                ) : null}
            </button>

            <NotificationPanel
                isOpen={panelOpen}
                onClose={() => setPanelOpen(false)}
                notifications={notifications}
                loading={loading}
                error={error}
                activeTab={activeTab}
                onTabChange={handleTabChange}
                expandedId={expandedId}
                onRowClick={handleRowClick}
                selectMode={selectMode}
                selectedIds={selectedIds}
                onToggleSelect={handleToggleSelect}
                onEnterSelectMode={handleEnterSelectMode}
                onExitSelectMode={handleExitSelectMode}
                onBulkMarkRead={handleBulkMarkRead}
                bulkLoading={bulkLoading}
                onLoadMore={handleLoadMore}
                hasMore={hasMore}
            />

            <Notification ref={toastRef} />
        </div>
    );
}
