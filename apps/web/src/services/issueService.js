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

export async function checkSimilarIssues({
    district_id,
    category_id,
    postal_code,
    address,
    latitude,
    longitude,
}) {
    const payload = { district_id, category_id };

    if (postal_code != null) payload.postal_code = postal_code;
    if (address != null) payload.address = address;
    if (latitude != null) payload.latitude = latitude;
    if (longitude != null) payload.longitude = longitude;

    const response = await apiClient.post('/issues/similar-check', payload);
    const body = response.data.data ?? response.data;

    return {
        own_matches: body.own_matches ?? [],
        matches: body.matches ?? [],
    };
}

export async function createIssue(payload) {
    const response = await apiClient.post('/issues', payload);
    return unwrapData(response);
}

export async function uploadIssueAttachments(issueId, files) {
    const formData = new FormData();

    for (const file of files) {
        formData.append('files[]', file);
    }

    const response = await apiClient.post(`/issues/${issueId}/attachments`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });

    return unwrapData(response);
}

export async function joinIssue(issueId, { isAnonymous } = {}) {
    const body = isAnonymous ? { is_anonymous: true } : {};
    const response = await apiClient.post(`/issues/${issueId}/join`, body);
    return unwrapData(response);
}

export async function leaveIssue(issueId) {
    await apiClient.delete(`/issues/${issueId}/leave`);
}

export async function getFollowedIssues() {
    const response = await apiClient.get('/issues', { params: { followed: 1 } });
    return unwrapList(response);
}

export async function getParticipatingIssues() {
    const response = await apiClient.get('/issues', { params: { participating: 1 } });
    return unwrapList(response);
}

export async function getIssue(issueId) {
    const response = await apiClient.get(`/issues/${issueId}`);
    return unwrapData(response);
}

export async function deleteIssue(issueId, { leaveParticipation } = {}) {
    const config = {};

    if (leaveParticipation != null) {
        config.params = { leave_participation: leaveParticipation ? 1 : 0 };
    }

    await apiClient.delete(`/issues/${issueId}`, config);
}
