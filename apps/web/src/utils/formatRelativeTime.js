const relativeTimeFormatter = new Intl.RelativeTimeFormat('nl-NL', { numeric: 'auto' });

export function formatRelativeTime(input) {
    if (!input) return '';

    const date = input instanceof Date ? input : new Date(input);
    if (Number.isNaN(date.getTime())) return '';

    const diffMs = date.getTime() - Date.now();
    const absSeconds = Math.abs(Math.round(diffMs / 1000));

    if (absSeconds < 60) return 'zojuist';

    const units = [
        { unit: 'year', seconds: 31536000 },
        { unit: 'month', seconds: 2592000 },
        { unit: 'week', seconds: 604800 },
        { unit: 'day', seconds: 86400 },
        { unit: 'hour', seconds: 3600 },
        { unit: 'minute', seconds: 60 },
    ];

    for (const { unit, seconds } of units) {
        if (absSeconds >= seconds) {
            const value = Math.round(diffMs / 1000 / seconds);
            return relativeTimeFormatter.format(value, unit);
        }
    }

    return 'zojuist';
}
