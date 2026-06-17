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

export async function getMyIssues() {
    const response = await apiClient.get('/issues', { params: { mine: 1 } });
    return unwrapList(response);
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

/**
 * Fetches all issues for the map, applying server-side filters and paginating
 * through every page (per_page=100, max allowed by IndexIssueRequest).
 */
export async function listIssuesForMap({
    districtIds = [],
    statuses = [],
    mine,
    followed,
    excludeMine,
} = {}) {
    const params = { per_page: 100 };

    if (districtIds.length > 0) {
        params.district_id = districtIds.length === 1 ? districtIds[0] : districtIds;
    }
    if (statuses.length > 0) {
        params.status = statuses.length === 1 ? statuses[0] : statuses;
    }
    if (mine) {
        params.mine = 1;
    }
    if (followed) {
        params.followed = 1;
    }
    if (excludeMine) {
        params.exclude_mine = 1;
    }

    let allIssues = [];
    let page = 1;
    let lastPage = 1;

    do {
        const response = await apiClient.get('/issues', { params: { ...params, page } });
        const data = response.data.data ?? response.data;
        const items = Array.isArray(data) ? data : [];
        allIssues = allIssues.concat(items);

        const meta = response.data.meta;
        if (meta?.last_page != null) {
            lastPage = meta.last_page;
        } else {
            lastPage = items.length < params.per_page ? page : page + 1;
        }
        page += 1;
    } while (page <= lastPage);

    return allIssues;
}

const SCOPE_PARAMS = {
    mine: { mine: true },
    followed: { followed: true },
    exclude_mine: { excludeMine: true },
};

/**
 * Fetches map issues with multi-select district/status filters and optional
 * scope keys. Multiple scopes trigger parallel fetches merged by issue id (OR).
 */
export async function listIssuesForMapWithFilters({
    districtIds = [],
    statuses = [],
    scopes = [],
} = {}) {
    const base = { districtIds, statuses };

    if (scopes.length === 0) {
        return listIssuesForMap(base);
    }

    if (scopes.length === 1) {
        return listIssuesForMap({
            ...base,
            ...SCOPE_PARAMS[scopes[0]],
        });
    }

    const results = await Promise.all(
        scopes.map((scope) =>
            listIssuesForMap({
                ...base,
                ...SCOPE_PARAMS[scope],
            }),
        ),
    );

    const byId = new Map();
    for (const issues of results) {
        for (const issue of issues) {
            byId.set(issue.id, issue);
        }
    }

    return Array.from(byId.values());
}

export async function deleteIssue(issueId, { leaveParticipation } = {}) {
    const config = {};

    if (leaveParticipation != null) {
        config.params = { leave_participation: leaveParticipation ? 1 : 0 };
    }

    await apiClient.delete(`/issues/${issueId}`, config);
}
