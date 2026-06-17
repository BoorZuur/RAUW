import axios from 'axios';

const apiClient = axios.create({
    baseURL: `${import.meta.env.VITE_API_BASE_URL}/api`,
    headers: { Accept: 'application/json' },
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

function unwrapList(response) {
    const data = response.data.data ?? response.data;
    return Array.isArray(data) ? data : [];
}

export async function fetchParticipants(issueId) {
    const response = await apiClient.get(`/issues/${issueId}/participants`);
    return unwrapList(response);
}
