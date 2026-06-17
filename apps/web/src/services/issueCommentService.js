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

function normalizeNextPage(nextPageUrl) {
    if (!nextPageUrl) {
        return null;
    }

    if (nextPageUrl.startsWith('/issues/')) {
        return nextPageUrl;
    }

    try {
        const url = new URL(nextPageUrl);
        const path = url.pathname.replace(/^\/api/, '');
        return `${path}${url.search}`;
    } catch {
        const apiIndex = nextPageUrl.indexOf('/api/');
        if (apiIndex >= 0) {
            return nextPageUrl.slice(apiIndex + 4);
        }
        return nextPageUrl;
    }
}

export async function fetchIssueComments(issueId) {
    let allComments = [];
    let nextPage = `/issues/${issueId}/comments?per_page=100`;

    while (nextPage) {
        const response = await apiClient.get(nextPage);
        const data = response.data.data || response.data;
        allComments = [...allComments, ...(Array.isArray(data) ? data : [])];
        nextPage = normalizeNextPage(response.data.next_page_url);
    }

    return allComments;
}

export async function createIssueComment(issueId, { content, is_anonymous }) {
    const response = await apiClient.post(`/issues/${issueId}/comments`, {
        content,
        is_anonymous,
    });
    return response.data.data || response.data;
}

export async function updateIssueComment(issueId, commentId, content) {
    const response = await apiClient.patch(`/issues/${issueId}/comments/${commentId}`, {
        content,
    });
    return response.data.data || response.data;
}

export async function deleteIssueComment(issueId, commentId) {
    await apiClient.delete(`/issues/${issueId}/comments/${commentId}`);
}
