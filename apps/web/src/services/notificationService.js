import axios from 'axios';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { Accept: 'application/json' },
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

export async function getUnreadCount() {
    const response = await apiClient.get('/notifications/unread-count');
    return response.data;
}

export async function listNotifications({ page, perPage, isRead, since } = {}) {
    const params = {};
    if (page != null) params.page = page;
    if (perPage != null) params.per_page = perPage;
    // Laravel boolean validation accepts 0/1 in query strings, not "false"/"true"
    if (isRead != null) params.is_read = isRead ? 1 : 0;
    if (since != null) params.since = since;

    const response = await apiClient.get('/notifications', { params });
    return response.data;
}

export async function markNotificationRead(id) {
    const response = await apiClient.patch(`/notifications/${id}`, { is_read: true });
    return response.data;
}

export async function markNotificationsReadBulk(ids) {
    const response = await apiClient.patch('/notifications/bulk-read', { ids });
    return response.data;
}
