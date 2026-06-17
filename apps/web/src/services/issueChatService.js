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

function unwrapData(response) {
    return response.data.data ?? response.data;
}

function unwrapList(response) {
    const data = response.data.data ?? response.data;
    return Array.isArray(data) ? data : [];
}

export async function fetchChats(issueId) {
    const response = await apiClient.get(`/issues/${issueId}/chats`);
    return unwrapList(response);
}

export async function openChat(issueId, payload) {
    const data = typeof payload === 'object' ? payload : { user_id: payload };
    const response = await apiClient.patch(`/issues/${issueId}/chats/open`, data);
    return unwrapData(response);
}

export async function closeChat(issueId, chatId) {
    const response = await apiClient.patch(`/issues/${issueId}/chats/${chatId}/close`);
    return unwrapData(response);
}

export async function fetchChatMessages(issueId, chatId) {
    const response = await apiClient.get(`/issues/${issueId}/chats/${chatId}/messages`);
    return unwrapList(response);
}

export async function sendMessage(issueId, chatId, content, files = []) {
    if (!files || files.length === 0) {
        const response = await apiClient.post(`/issues/${issueId}/chats/${chatId}/messages`, { content });
        return unwrapData(response);
    }

    const formData = new FormData();
    if (content) {
        formData.append('content', content);
    }
    Array.from(files).forEach((file) => {
        formData.append('files[]', file);
    });

    const response = await apiClient.post(`/issues/${issueId}/chats/${chatId}/messages`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
    return unwrapData(response);
}

export async function downloadProtectedFile(url) {
    const response = await apiClient.get(url, {
        responseType: 'blob',
    });
    return URL.createObjectURL(response.data);
}

export async function markMessagesRead(issueId, chatId) {
    const response = await apiClient.post(`/issues/${issueId}/chats/${chatId}/messages/mark-read`);
    return unwrapData(response);
}
