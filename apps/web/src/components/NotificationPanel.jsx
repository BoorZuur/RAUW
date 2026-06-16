import React from 'react';
import { Check, ChevronDown, Loader2, X } from 'lucide-react';
import { getNotificationExpandedDetail } from '../utils/getNotificationExpandedDetail';
import { formatRelativeTime } from '../utils/formatRelativeTime';
import { getNotificationIcon } from '../utils/notificationIcons';

const tabs = [
    { id: 'unread', label: 'Ongelezen' },
    { id: 'all', label: 'Alles' },
];

function resolveNotificationId(notification) {
    return notification?.id ?? notification?.notification_id;
}

export default function NotificationPanel({
    isOpen,
    onClose,
    notifications,
    loading,
    error,
    activeTab,
    onTabChange,
    expandedId,
    onRowClick,
    selectMode,
    selectedIds,
    onToggleSelect,
    onEnterSelectMode,
    onExitSelectMode,
    onBulkMarkRead,
    bulkLoading,
    onLoadMore,
    hasMore,
}) {
    if (!isOpen) return null;

    const hasNotifications = (notifications?.length || 0) > 0;
    const selectedCount = selectedIds?.length || 0;
    const emptyText = activeTab === 'unread' ? 'Geen ongelezen notificaties' : 'Geen notificaties';

    return (
        <div
            className="absolute right-0 top-full mt-2 z-60 w-96 max-h-[min(70vh,480px)] overflow-hidden rounded-2xl border border-primary-border bg-primary-bg-cards shadow-2xl"
            role="dialog"
            aria-label="Notificatiepaneel"
        >
            <div className="border-b border-primary-border p-4">
                <div className="mb-3 flex items-center justify-between">
                    <h3 className="text-sm font-black text-primary-text">Notificaties</h3>
                    <div className="flex items-center gap-2">
                        {selectMode ? (
                            <button
                                type="button"
                                onClick={onExitSelectMode}
                                className="rounded-xl border border-primary-border px-3 py-1.5 text-[12px] font-bold text-primary-text hover:border-primary-accent"
                            >
                                Annuleren
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={onEnterSelectMode}
                                className="rounded-xl border border-primary-border px-3 py-1.5 text-[12px] font-bold text-primary-text hover:border-primary-accent"
                            >
                                Selecteren
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-xl p-1.5 text-secondary-text hover:bg-black/5 hover:text-primary-text dark:hover:bg-white/10"
                            aria-label="Sluit notificaties"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div className="inline-flex rounded-2xl border border-primary-border bg-primary-bg p-1">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            type="button"
                            onClick={() => onTabChange(tab.id)}
                            className={`rounded-xl px-3 py-1.5 text-[12px] font-bold transition-colors ${
                                activeTab === tab.id
                                    ? 'bg-primary-text text-primary-bg'
                                    : 'text-secondary-text hover:text-primary-text'
                            }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>
            </div>

            <div className="max-h-[min(70vh,400px)] overflow-y-auto">
                {loading ? (
                    <div className="flex items-center justify-center gap-2 p-8 text-secondary-text">
                        <Loader2 className="h-4 w-4 animate-spin text-primary-accent" />
                        <span className="text-sm font-medium">Notificaties laden...</span>
                    </div>
                ) : null}

                {!loading && error ? (
                    <div className="space-y-3 p-6 text-center">
                        <p className="text-sm text-red-500">{error}</p>
                        <button
                            type="button"
                            onClick={() => onTabChange(activeTab)}
                            className="rounded-xl border border-primary-border px-3 py-2 text-[12px] font-bold text-primary-text hover:border-primary-accent"
                        >
                            Opnieuw proberen
                        </button>
                    </div>
                ) : null}

                {!loading && !error && !hasNotifications ? (
                    <div className="p-8 text-center text-sm text-secondary-text">{emptyText}</div>
                ) : null}

                {!loading && !error && hasNotifications ? (
                    <ul className="divide-y divide-primary-border">
                        {notifications.map((notification) => {
                            const notificationId = resolveNotificationId(notification);
                            const isExpanded = expandedId === notificationId;
                            const isSelected = selectedIds?.includes(notificationId);
                            const Icon = getNotificationIcon(notification.type);

                            return (
                                <li key={notificationId} className="bg-transparent">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (selectMode) {
                                                onToggleSelect(notificationId);
                                                return;
                                            }
                                            onRowClick(notificationId);
                                        }}
                                        className="w-full px-4 py-3 text-left hover:bg-black/5 dark:hover:bg-white/5"
                                    >
                                        <div className="flex items-start gap-3">
                                            {selectMode ? (
                                                <span
                                                    onClick={(event) => {
                                                        event.stopPropagation();
                                                        onToggleSelect(notificationId);
                                                    }}
                                                    className={`mt-0.5 flex h-5 w-5 items-center justify-center rounded-md border ${
                                                        isSelected
                                                            ? 'border-primary-accent bg-primary-accent text-white'
                                                            : 'border-primary-border bg-primary-bg'
                                                    }`}
                                                    aria-hidden
                                                >
                                                    {isSelected ? <Check className="h-3.5 w-3.5" /> : null}
                                                </span>
                                            ) : (
                                                <span className="mt-0.5 rounded-xl bg-primary-bg p-2">
                                                    <Icon className="h-4 w-4 text-primary-accent" />
                                                </span>
                                            )}

                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-start justify-between gap-2">
                                                    <p className="truncate text-[13px] font-bold text-primary-text">
                                                        {notification.title || 'Notificatie'}
                                                    </p>
                                                    <div className="flex items-center gap-2">
                                                        {!notification.is_read ? (
                                                            <span className="h-2 w-2 rounded-full bg-primary-accent" />
                                                        ) : null}
                                                        {!selectMode ? (
                                                            <ChevronDown
                                                                className={`h-4 w-4 text-secondary-text transition-transform ${
                                                                    isExpanded ? 'rotate-180' : ''
                                                                }`}
                                                            />
                                                        ) : null}
                                                    </div>
                                                </div>
                                                <p className="mt-0.5 line-clamp-1 text-[12px] text-secondary-text">
                                                    {notification.body}
                                                </p>
                                                <p className="mt-1 text-[11px] text-secondary-text">
                                                    {formatRelativeTime(notification.created_at)}
                                                </p>

                                                {!selectMode && isExpanded ? (
                                                    <div className="mt-2 space-y-1 rounded-xl border border-primary-border bg-primary-bg p-2.5">
                                                        {getNotificationExpandedDetail(notification).map((line) => (
                                                            <p
                                                                key={`${notificationId}-${line}`}
                                                                className="text-[12px] text-secondary-text"
                                                            >
                                                                {line}
                                                            </p>
                                                        ))}
                                                    </div>
                                                ) : null}
                                            </div>
                                        </div>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                ) : null}
            </div>

            {hasMore && !loading && !error ? (
                <div className="border-t border-primary-border p-3">
                    <button
                        type="button"
                        onClick={onLoadMore}
                        className="w-full rounded-xl border border-primary-border px-3 py-2 text-[12px] font-bold text-primary-text hover:border-primary-accent"
                    >
                        Meer laden
                    </button>
                </div>
            ) : null}

            {selectMode && selectedCount > 0 ? (
                <div className="sticky bottom-0 flex items-center justify-between gap-2 border-t border-primary-border bg-primary-bg/95 p-3 backdrop-blur">
                    <p className="text-[12px] text-secondary-text">
                        {selectedCount} geselecteerd
                    </p>
                    <button
                        type="button"
                        onClick={onBulkMarkRead}
                        disabled={bulkLoading}
                        className="inline-flex items-center gap-2 rounded-xl bg-primary-accent px-3 py-2 text-[12px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {bulkLoading ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : null}
                        Markeer als gelezen
                    </button>
                </div>
            ) : null}
        </div>
    );
}
