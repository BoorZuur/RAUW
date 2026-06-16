import React, { useEffect, useState, useRef, useCallback } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import { Filter, ChevronDown, Loader2 } from 'lucide-react';
import U_Nav from '../components/U_Nav';
import Footer from '../components/Footer.jsx';
import StoryDetailModal from '../modal/StoryDetailModal.jsx';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import { listIssuesForMap, getIssue } from '../services/issueService';
import { createIssueComment, fetchIssueComments } from '../services/issueCommentService';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { Accept: 'application/json' },
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

const DefaultIcon = L.icon({
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
});
L.Marker.prototype.options.icon = DefaultIcon;

const VIEW_OPTIONS = [
    { label: 'Alle meldingen', value: 'all' },
    { label: 'Mijn verhalen', value: 'mine', requiresAuth: true },
    { label: 'Nieuw', value: 'open' },
    { label: 'In behandeling', value: 'in_behandeling' },
    { label: 'Opgelost', value: 'opgelost' },
    { label: 'Afgehandeld', value: 'gesloten' },
    { label: 'Gevolgde verhalen', value: 'followed', requiresAuth: true },
];

function viewToApiParams(view) {
    if (view === 'all') return {};
    if (view === 'mine') return { mine: true };
    if (view === 'followed') return { followed: true };
    return { status: view };
}

export default function MapOverview() {
    const mapRef = useRef(null);
    const leafletMap = useRef(null);
    const markersLayer = useRef([]);
    const handleSelectIssueRef = useRef(null);

    const [reports, setReports] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [filters, setFilters] = useState({ district: 'all', view: 'all' });
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [selectedIssue, setSelectedIssue] = useState(null);
    const [currentUserId, setCurrentUserId] = useState(null);
    const [isLoggedIn, setIsLoggedIn] = useState(false);

    const patchIssueInList = useCallback((updatedIssue) => {
        const patch = {
            participant_count: updatedIssue.participant_count,
            is_participant: updatedIssue.is_participant,
            default_comment_is_anonymous: updatedIssue.default_comment_is_anonymous,
        };
        setReports((prev) =>
            prev.map((report) => (report.id === updatedIssue.id ? { ...report, ...patch } : report)),
        );
    }, []);

    const handleParticipationChange = useCallback(
        (updatedIssue) => {
            setSelectedIssue((prev) =>
                prev ? { ...prev, ...updatedIssue, comments: prev.comments } : updatedIssue,
            );
            patchIssueInList(updatedIssue);
        },
        [patchIssueInList],
    );

    const handleSelectIssue = useCallback(async (issue) => {
        try {
            const fullIssueData = await getIssue(issue.id);
            let allComments = [];
            try {
                allComments = await fetchIssueComments(issue.id);
            } catch (commentErr) {
                console.warn('Kon (sommige) comments niet ophalen:', commentErr);
            }
            setSelectedIssue({
                ...fullIssueData,
                comments: allComments,
            });
        } catch (err) {
            console.error('Kon issue details niet ophalen:', err);
            setSelectedIssue(issue);
        }
    }, []);

    handleSelectIssueRef.current = handleSelectIssue;

    const handleAddComment = useCallback(async (issueId, commentText, isAnonymous = false) => {
        try {
            const newComment = await createIssueComment(issueId, {
                content: commentText,
                is_anonymous: isAnonymous,
            });
            setSelectedIssue((prev) => ({
                ...prev,
                comments: [...(prev.comments || []), newComment],
            }));
            return newComment;
        } catch (err) {
            console.error('Kon reactie niet plaatsen:', err.response?.data || err);
            throw err;
        }
    }, []);

    useEffect(() => {
        if (!leafletMap.current) {
            const regionBounds = L.latLngBounds([51.75, 4.05], [52.0, 4.65]);
            leafletMap.current = L.map(mapRef.current, {
                zoomControl: false,
                maxBounds: regionBounds,
                maxBoundsViscosity: 1.0,
                minZoom: 10,
            }).setView([51.9225, 4.47917], 13);

            L.control.zoom({ position: 'bottomright' }).addTo(leafletMap.current);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            }).addTo(leafletMap.current);
        }
    }, []);

    useEffect(() => {
        const loadDistricts = async () => {
            const token = localStorage.getItem('auth_token');
            setIsLoggedIn(Boolean(token));

            try {
                if (token) {
                    const meRes = await apiClient.get('/auth/me');
                    const profile = meRes.data?.profile || meRes.data;
                    setCurrentUserId(profile?.id ?? null);
                    const profileDistricts = profile?.districts || [];
                    if (profileDistricts.length > 0) {
                        setDistricts(profileDistricts);
                        return;
                    }
                }

                const distRes = await apiClient.get('/districts');
                setDistricts(distRes.data.data || distRes.data);
            } catch (err) {
                console.error('Kon wijken niet laden:', err);
            }
        };

        loadDistricts();
    }, []);

    useEffect(() => {
        const loadReports = async () => {
            setIsLoading(true);
            try {
                const issues = await listIssuesForMap({
                    districtId: filters.district,
                    ...viewToApiParams(filters.view),
                });
                setReports(issues);
            } catch (err) {
                console.error('Kon meldingen niet laden:', err);
                setReports([]);
            } finally {
                setIsLoading(false);
            }
        };

        loadReports();
    }, [filters]);

    useEffect(() => {
        if (!leafletMap.current) return;

        markersLayer.current.forEach((m) => leafletMap.current.removeLayer(m));
        markersLayer.current = [];

        reports.forEach((report) => {
            if (report.latitude && report.longitude) {
                const marker = L.marker([report.latitude, report.longitude]).addTo(leafletMap.current);

                const popupContent = `
                    <div class="font-label text-primary-text p-1 m-0">
                        <h3 class="font-headline font-black text-base mb-1">
                            ${report.title || 'Geen titel'}
                        </h3>
                        <p class="text-xs text-secondary-text mb-3">
                            ${report.address || 'Geen adres'}
                        </p>
                        <button id="btn-${report.id}" class="w-full py-2 px-3 bg-primary-accent text-white text-[10px] font-black uppercase tracking-widest rounded-lg cursor-pointer hover:brightness-110 transition-all">
                            Bekijk details
                        </button>
                    </div>
                `;

                marker.bindPopup(popupContent, {
                    className: 'custom-leaflet-popup',
                });

                marker.on('popupopen', () => {
                    const btn = document.getElementById(`btn-${report.id}`);
                    if (btn) {
                        btn.onclick = () => handleSelectIssueRef.current?.(report);
                    }
                });
                markersLayer.current.push(marker);
            }
        });
    }, [reports]);

    const availableViewOptions = VIEW_OPTIONS.filter(
        (opt) => !opt.requiresAuth || isLoggedIn,
    );

    const selectedDistrictLabel =
        filters.district === 'all'
            ? 'Alle wijken'
            : districts.find((d) => d.id.toString() === filters.district)?.name || 'Wijk';

    const selectedViewLabel =
        VIEW_OPTIONS.find((opt) => opt.value === filters.view)?.label || 'Alle meldingen';

    const activeFilterCount =
        (filters.district !== 'all' ? 1 : 0) + (filters.view !== 'all' ? 1 : 0);

    return (
        <div className="min-h-screen w-full flex flex-col bg-primary-bg text-primary-text overflow-hidden transition-colors duration-300">
            <header className="fixed top-0 w-full z-50">
                <U_Nav />
            </header>

            <div className="grow flex w-full pt-26">
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300"></div>

                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg
                                className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                strokeWidth="1.2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <circle cx="12" cy="11" r="2" className="opacity-60" />
                            </svg>
                        </div>

                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg
                                className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                strokeWidth="1.2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>

                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg
                                className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                strokeWidth="1.2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <polyline points="22 11.08 12 19 9 16" />
                                <path d="M22 4L12 14.01l-3-3" className="opacity-40" />
                                <circle cx="12" cy="12" r="10" strokeDasharray="3 3" className="opacity-50" />
                            </svg>
                        </div>
                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300"></div>
                </div>

                <main className="z-10 grow w-full max-w-6xl mx-auto px-6 mt-8 pb-12 md:p-8 flex flex-col overflow-hidden">
                    <section className="flex-1 min-h-[50vh] md:min-h-[calc(100vh-14rem)] bg-primary-bg-cards border-2 border-primary-border rounded-3xl overflow-hidden relative shadow-md">
                        <div ref={mapRef} className="absolute inset-0 z-0" />

                        <div className="absolute top-4 left-4 z-20">
                            <button
                                type="button"
                                onClick={() => setIsFilterOpen(!isFilterOpen)}
                                className="flex items-center gap-2 px-4 py-3 bg-primary-bg-cards border border-primary-border rounded-xl text-sm font-bold hover:border-primary-accent transition-all shadow-lg"
                            >
                                <Filter size={16} className="text-primary-accent" />
                                <span>Filters</span>
                                {activeFilterCount > 0 ? (
                                    <span className="ml-1 px-1.5 py-0.5 text-[10px] font-black bg-primary-accent text-white rounded-full">
                                        {activeFilterCount}
                                    </span>
                                ) : null}
                                <ChevronDown
                                    size={16}
                                    className={`transition-transform ${isFilterOpen ? 'rotate-180' : ''}`}
                                />
                            </button>

                            {isFilterOpen ? (
                                <div className="mt-2 w-72 p-4 bg-primary-bg-cards border border-primary-border rounded-xl shadow-2xl space-y-4">
                                    <div>
                                        <label
                                            htmlFor="map-district-filter"
                                            className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text"
                                        >
                                            Wijk
                                        </label>
                                        <select
                                            id="map-district-filter"
                                            value={filters.district}
                                            onChange={(e) =>
                                                setFilters((prev) => ({
                                                    ...prev,
                                                    district: e.target.value,
                                                }))
                                            }
                                            className="w-full p-3 bg-primary-bg border-2 border-primary-border rounded-xl text-sm font-medium text-primary-text focus:border-primary-accent outline-none"
                                        >
                                            <option value="all">Alle wijken</option>
                                            {districts.map((d) => (
                                                <option key={d.id} value={d.id.toString()}>
                                                    {d.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="map-view-filter"
                                            className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text"
                                        >
                                            Weergave
                                        </label>
                                        <select
                                            id="map-view-filter"
                                            value={filters.view}
                                            onChange={(e) =>
                                                setFilters((prev) => ({
                                                    ...prev,
                                                    view: e.target.value,
                                                }))
                                            }
                                            className="w-full p-3 bg-primary-bg border-2 border-primary-border rounded-xl text-sm font-medium text-primary-text focus:border-primary-accent outline-none"
                                        >
                                            {availableViewOptions.map((opt) => (
                                                <option key={opt.value} value={opt.value}>
                                                    {opt.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <p className="text-[10px] text-secondary-text font-label">
                                        {selectedDistrictLabel} · {selectedViewLabel}
                                    </p>
                                </div>
                            ) : null}
                        </div>

                        {isLoading ? (
                            <div className="absolute top-4 right-4 z-20 flex items-center gap-2 px-3 py-2 bg-primary-bg-cards border border-primary-border rounded-xl shadow-lg">
                                <Loader2 size={16} className="animate-spin text-primary-accent" />
                                <span className="text-xs font-bold text-secondary-text">Laden...</span>
                            </div>
                        ) : null}
                    </section>
                </main>

                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300 self-end"></div>

                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg
                                className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                strokeWidth="1.2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M4 20 L15 4 L18 5 L10 20" />
                                <line x1="2" y1="20" x2="22" y2="20" />
                                <line x1="14" y1="6" x2="20" y2="20" className="opacity-40" />
                                <line x1="13" y1="9" x2="17" y2="20" className="opacity-40" />
                            </svg>
                        </div>

                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg
                                className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                strokeWidth="1.2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <rect x="2" y="10" width="6" height="11" />
                                <rect x="9" y="3" width="6" height="18" />
                                <rect x="16" y="8" width="6" height="13" />
                            </svg>
                        </div>

                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg
                                className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                strokeWidth="1.2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="8" y1="9" x2="16" y2="9" />
                                <path d="M5 12a7 7 0 0 0 14 0" />
                                <circle cx="12" cy="4" r="1" />
                            </svg>
                        </div>
                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300 self-end"></div>
                </div>
            </div>

            <div className="z-10 bg-primary-bg">
                <Footer />
            </div>

            {selectedIssue ? (
                <StoryDetailModal
                    issue={selectedIssue}
                    onClose={() => setSelectedIssue(null)}
                    onAddComment={handleAddComment}
                    currentUserId={currentUserId}
                    onParticipationChange={handleParticipationChange}
                />
            ) : null}
        </div>
    );
}
