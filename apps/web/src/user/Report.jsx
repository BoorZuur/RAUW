import React, { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import U_Nav from '../components/U_Nav';
import NativeLeafletMap from '../components/MapComponent.jsx';
import Footer from '../components/Footer.jsx';
import DuplicateSuggestionModal from '../modal/DuplicateSuggestionModal.jsx';
import { checkSimilarIssues, createIssue, uploadIssueAttachments } from '../services/issueService.js';
import { getIssueErrorMessage } from '../utils/issueErrorMessages.js';

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

const getDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371e3;
    const φ1 = (lat1 * Math.PI) / 180;
    const φ2 = (lat2 * Math.PI) / 180;
    const Δφ = ((lat2 - lat1) * Math.PI) / 180;
    const Δλ = ((lon2 - lon1) * Math.PI) / 180;
    const a =
        Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

function findClosestDistrict(activePosition, districts) {
    let closestDistrict = null;
    let minDistance = Infinity;

    districts.forEach((d) => {
        const dist = getDistance(
            activePosition.lat,
            activePosition.lng,
            d.center_lat,
            d.center_lng,
        );
        const searchRadius = d.radius_meters + 2000;

        if (dist < searchRadius && dist < minDistance) {
            minDistance = dist;
            closestDistrict = d;
        }
    });

    return closestDistrict;
}

async function geocodeAddress(address) {
    const response = await axios.get('https://nominatim.openstreetmap.org/search', {
        params: { q: `${address}, Rotterdam`, format: 'json', limit: 1 },
    });

    if (response.data && response.data.length > 0) {
        return {
            lat: parseFloat(response.data[0].lat),
            lng: parseFloat(response.data[0].lon),
        };
    }

    return null;
}

export default function ReportIssue() {
    const navigate = useNavigate();
    const [formData, setFormData] = useState({
        title: '',
        category_id: 1,
        address: '',
        content: '',
        is_anonymous: false,
    });

    const [images, setImages] = useState([]);
    const [position, setPosition] = useState(null);
    const [error, setError] = useState(null);
    const [categories, setCategories] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [successMode, setSuccessMode] = useState(null);
    const [showDuplicateModal, setShowDuplicateModal] = useState(false);
    const [duplicateMatches, setDuplicateMatches] = useState([]);
    const [ownMatches, setOwnMatches] = useState([]);
    const [resolvedPayload, setResolvedPayload] = useState(null);
    const [isCheckingDuplicates, setIsCheckingDuplicates] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState(null);
    const [earlySimilarBanner, setEarlySimilarBanner] = useState(false);
    const payloadRef = useRef(null);

    const [showMapError, setShowMapError] = useState(false);
    const [mapErrorMessage, setMapErrorMessage] = useState('');

    useEffect(() => {
        Promise.all([apiClient.get('/api/categories'), apiClient.get('/api/districts')])
            .then(([catRes, distRes]) => {
                const catData = Array.isArray(catRes.data)
                    ? catRes.data
                    : catRes.data.data || [];
                const distData = Array.isArray(distRes.data)
                    ? distRes.data
                    : distRes.data.data || [];
                setCategories(catData);
                setDistricts(distData);
            })
            .catch((err) => {
                console.error('API Error details:', err.response || err);
                setError('Kon gegevens niet laden.');
            });
    }, []);

    const buildIssuePayload = useCallback(async () => {
        let activePosition = position;

        if (!activePosition && formData.address) {
            try {
                activePosition = await geocodeAddress(formData.address);
                if (activePosition) {
                    setPosition(activePosition);
                }
            } catch {
                return { error: 'geocode_failed' };
            }
        }

        if (!activePosition) {
            return { error: 'no_position' };
        }

        const closestDistrict = findClosestDistrict(activePosition, districts);

        if (!closestDistrict) {
            return { error: 'no_district' };
        }

        return {
            payload: {
                title: formData.title,
                category_id: parseInt(formData.category_id, 10),
                address: formData.address,
                content: formData.content,
                is_anonymous: formData.is_anonymous ? 1 : 0,
                latitude: activePosition.lat,
                longitude: activePosition.lng,
                district_id: closestDistrict.id,
                neighborhood: closestDistrict.name,
            },
            position: activePosition,
        };
    }, [position, formData, districts]);

    const submitIssue = useCallback(
        async (duplicateOfId, payloadOverride) => {
            const payload = payloadOverride ?? payloadRef.current ?? resolvedPayload;
            if (!payload) return;

            setIsSubmitting(true);
            setSubmitError(null);

            const issuePayload = duplicateOfId
                ? { ...payload, duplicate_of_id: duplicateOfId }
                : payload;

            try {
                const created = await createIssue(issuePayload);
                const issueId = created?.id;

                if (!issueId) {
                    throw new Error('Issue succesvol aangemaakt, maar geen ID ontvangen.');
                }

                if (images.length > 0) {
                    await uploadIssueAttachments(issueId, images);
                }

                setShowDuplicateModal(false);
                setShowConfirm(false);
                setSuccessMode(duplicateOfId ? 'duplicate' : 'standalone');

                if (duplicateOfId) {
                    setTimeout(
                        () =>
                            navigate('/account', {
                                state: { tab: 'verhalen die u volgt' },
                            }),
                        2000,
                    );
                } else {
                    setTimeout(() => navigate('/feed'), 2000);
                }
            } catch (err) {
                console.error('Fout bij aanmaken:', err.response?.data || err);
                setSubmitError(getIssueErrorMessage(err));
                setShowConfirm(false);
            } finally {
                setIsSubmitting(false);
            }
        },
        [images, navigate, resolvedPayload],
    );

    useEffect(() => {
        if (!position || !formData.category_id || districts.length === 0) {
            setEarlySimilarBanner(false);
            return undefined;
        }

        const closestDistrict = findClosestDistrict(position, districts);
        if (!closestDistrict) {
            setEarlySimilarBanner(false);
            return undefined;
        }

        const timer = setTimeout(async () => {
            try {
                const { matches } = await checkSimilarIssues({
                    district_id: closestDistrict.id,
                    category_id: parseInt(formData.category_id, 10),
                    address: formData.address || undefined,
                    latitude: position.lat,
                    longitude: position.lng,
                });
                setEarlySimilarBanner(matches.length > 0);
            } catch {
                setEarlySimilarBanner(false);
            }
        }, 500);

        return () => clearTimeout(timer);
    }, [position, formData.category_id, formData.address, districts]);

    const hasContent = formData.content.trim().length > 0;

    const handleConfirmSubmit = async () => {
        if (!formData.content.trim()) {
            return;
        }

        setSubmitError(null);
        setIsCheckingDuplicates(true);

        const result = await buildIssuePayload();

        if (result.error === 'geocode_failed') {
            handleMapError(
                'Kon adres niet automatisch vinden, controleer het adres of dit adres valt buiten de gebieden van Rotterdam.',
            );
            setShowConfirm(false);
            setIsCheckingDuplicates(false);
            return;
        }

        if (result.error === 'no_position') {
            handleMapError('Selecteer een locatie op de kaart of vul een geldig adres in.');
            setShowConfirm(false);
            setIsCheckingDuplicates(false);
            return;
        }

        if (result.error === 'no_district') {
            handleMapError('De gekozen locatie valt buiten een bekend wijkgebied.');
            setShowConfirm(false);
            setIsCheckingDuplicates(false);
            return;
        }

        setResolvedPayload(result.payload);
        payloadRef.current = result.payload;

        try {
            const similarResult = await checkSimilarIssues({
                district_id: result.payload.district_id,
                category_id: result.payload.category_id,
                address: result.payload.address,
                latitude: result.payload.latitude,
                longitude: result.payload.longitude,
            });

            const { own_matches: own, matches } = similarResult;
            setOwnMatches(own);
            setDuplicateMatches(matches);

            if (matches.length > 0 || own.length > 0) {
                setShowConfirm(false);
                setShowDuplicateModal(true);
            } else {
                await submitIssue(null, result.payload);
            }
        } catch (err) {
            console.warn('Similar-check failed, falling through to standalone create', err);
            await submitIssue(null, result.payload);
        } finally {
            setIsCheckingDuplicates(false);
        }
    };

    const getAllCategoriesFlattened = () => {
        return categories.flatMap((c) => [
            { ...c, isChild: false },
            ...(c.children || []).map((child) => ({ ...child, isChild: true })),
        ]);
    };

    const getSelectedCategoryName = () => {
        const found = getAllCategoriesFlattened().find((c) => c.id === formData.category_id);
        return found ? found.name : 'Selecteer een categorie...';
    };

    const handleMapError = (message) => {
        setMapErrorMessage(message);
        setShowMapError(true);
    };

    const handleGeocode = async () => {
        if (!formData.address) return;
        setError(null);

        try {
            const coords = await geocodeAddress(formData.address);

            if (coords) {
                let insideKnownDistrict = false;
                if (districts && districts.length > 0) {
                    insideKnownDistrict = districts.some((d) => {
                        const dist = getDistance(coords.lat, coords.lng, d.center_lat, d.center_lng);
                        const searchRadius = d.radius_meters + 2000;
                        return dist < searchRadius;
                    });
                }

                if (!insideKnownDistrict) {
                    handleMapError(
                        'Het ingevulde adres valt buiten de bekende wijkgebieden binnen de database van de Gemeente Rotterdam.',
                    );
                    setPosition(null);
                    return;
                }

                setPosition(coords);
            } else {
                setError('Kon het adres niet vinden. Controleer de spelling.');
            }
        } catch {
            setError('Kon locatie niet automatisch ophalen.');
        }
    };

    const handleAbortReport = () => {
        setShowDuplicateModal(false);
        navigate('/feed');
    };

    const handleDuplicateCancel = () => {
        setShowDuplicateModal(false);
        setSubmitError(null);
    };

    return (
        <div className="min-h-screen bg-primary-bg text-primary-text flex flex-col transition-colors duration-300 overflow-x-hidden">
            {showMapError ? (
                <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-fade-in">
                    <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-primary-border shadow-2xl max-w-sm w-full text-center">
                        <div className="w-12 h-12 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center mx-auto mb-4 border border-red-500/20">
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                        </div>
                        <h3 className="text-xl font-black uppercase mb-2 text-primary-text">
                            Locatie ongeldig
                        </h3>
                        <p className="text-sm text-secondary-text mb-6">{mapErrorMessage}</p>
                        <button
                            type="button"
                            onClick={() => setShowMapError(false)}
                            className="w-full p-3 bg-primary-text text-primary-bg font-black uppercase tracking-widest rounded-xl hover:bg-primary-accent transition-colors cursor-pointer"
                        >
                            Begrepen
                        </button>
                    </div>
                </div>
            ) : null}

            {showConfirm ? (
                <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-primary-border shadow-2xl max-w-sm w-full">
                        <h3 className="text-xl font-black uppercase mb-4">Bevestig melding</h3>
                        <p className="text-sm opacity-70 mb-8">
                            Weet je zeker dat je deze melding wilt versturen?
                        </p>
                        <div className="flex gap-4">
                            <button
                                type="button"
                                onClick={() => setShowConfirm(false)}
                                disabled={isCheckingDuplicates}
                                className="flex-1 p-3 border-2 border-primary-border rounded-xl cursor-pointer disabled:opacity-50"
                            >
                                Annuleren
                            </button>
                            <button
                                type="button"
                                onClick={handleConfirmSubmit}
                                disabled={isCheckingDuplicates || !hasContent}
                                className="flex-1 p-3 bg-secondary-accent text-white rounded-xl font-black uppercase tracking-widest hover:brightness-110 cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                {isCheckingDuplicates ? (
                                    <>
                                        <Loader2 className="w-4 h-4 animate-spin" />
                                        Controleren...
                                    </>
                                ) : (
                                    'Verstuur'
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}

            {showDuplicateModal ? (
                <DuplicateSuggestionModal
                    matches={duplicateMatches}
                    ownMatches={ownMatches}
                    onSelectMatch={(id) => submitIssue(id)}
                    onSubmitStandalone={() => submitIssue(null)}
                    onCancel={handleDuplicateCancel}
                    onAbortReport={handleAbortReport}
                    isSubmitting={isSubmitting}
                    error={submitError}
                />
            ) : null}

            {successMode === 'standalone' ? (
                <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-primary-border shadow-2xl max-w-sm w-full text-center">
                        <h3 className="text-xl font-black uppercase mb-4">Verzonden!</h3>
                        <p className="text-sm opacity-70 mb-8">
                            Je melding is succesvol ontvangen. Je wordt omgeleid...
                        </p>
                    </div>
                </div>
            ) : null}

            {successMode === 'duplicate' ? (
                <div className="fixed inset-0 z-100 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="bg-primary-bg-cards p-8 rounded-3xl border-2 border-primary-border shadow-2xl max-w-sm w-full text-center">
                        <h3 className="text-xl font-black uppercase mb-4">Melding gekoppeld</h3>
                        <p className="text-sm opacity-70 mb-8">
                            Je volgt nu dit verhaal. Je ontvangt updates over de voortgang. Je wordt
                            omgeleid...
                        </p>
                    </div>
                </div>
            ) : null}

            <div className="fixed top-0 w-full z-50">
                <U_Nav />
            </div>

            <div className="grow flex w-full pt-26">
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300"></div>

                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <circle cx="12" cy="11" r="2" className="opacity-60" />
                            </svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="22 11.08 12 19 9 16" />
                                <path d="M22 4L12 14.01l-3-3" className="opacity-40" />
                                <circle cx="12" cy="12" r="10" strokeDasharray="3 3" className="opacity-50" />
                            </svg>
                        </div>
                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300"></div>
                </div>

                <main className="z-10 grow w-full max-w-6xl mx-auto px-6 mt-8 pb-12 grid grid-cols-1 md:grid-cols-2 gap-10">
                    <section className="h-137.5 bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-3 shadow-sm">
                        <div className="w-full h-full rounded-2xl overflow-hidden border border-primary-border/50">
                            <NativeLeafletMap
                                position={position}
                                setPosition={setPosition}
                                setFormData={setFormData}
                                districts={districts}
                                onLocationError={handleMapError}
                            />
                        </div>
                    </section>

                    <section className="bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-6 shadow-sm">
                        <h2 className="text-xl font-black mb-6 uppercase tracking-widest text-primary-text">
                            Melding melden
                        </h2>
                        {error ? (
                            <p className="text-red-600 font-bold mb-4 p-3 bg-red-100 rounded-lg text-sm">
                                {error}
                            </p>
                        ) : null}
                        {earlySimilarBanner ? (
                            <p className="text-primary-accent font-bold mb-4 p-3 bg-primary-accent/10 border border-primary-accent/30 rounded-lg text-sm">
                                Er zijn vergelijkbare meldingen in de buurt
                            </p>
                        ) : null}

                        <div className="space-y-6 p-2">
                            <div>
                                <label
                                    htmlFor="title"
                                    className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text"
                                >
                                    Titel
                                </label>
                                <input
                                    id="title"
                                    className="w-full p-3 bg-primary-bg border-2 border-primary-border rounded-lg text-sm text-primary-text focus:outline-none focus:border-primary-accent"
                                    value={formData.title}
                                    onChange={(e) =>
                                        setFormData({ ...formData, title: e.target.value })
                                    }
                                />
                            </div>

                            <div className="relative">
                                <label
                                    id="category-label"
                                    className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text"
                                >
                                    Categorie
                                </label>
                                <button
                                    type="button"
                                    aria-haspopup="listbox"
                                    aria-expanded={isDropdownOpen}
                                    aria-labelledby="category-label"
                                    onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                                    className="w-full p-3 bg-primary-bg border-2 border-primary-border rounded-lg cursor-pointer flex justify-between items-center text-sm text-primary-text focus:outline-none focus:border-primary-accent"
                                >
                                    <span>{getSelectedCategoryName()}</span>
                                    <svg
                                        className={`w-4 h-4 transition-transform ${isDropdownOpen ? 'rotate-180' : ''}`}
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </button>
                                {isDropdownOpen ? (
                                    <div
                                        role="listbox"
                                        className="absolute z-50 w-full mt-1 max-h-56 overflow-y-auto bg-primary-bg-cards border-2 border-primary-border rounded-lg p-1 shadow-xl text-sm custom-scrollbar"
                                    >
                                        {getAllCategoriesFlattened().map((cat) => (
                                            <div
                                                key={cat.id}
                                                role="option"
                                                aria-selected={formData.category_id === cat.id}
                                                onClick={() => {
                                                    setFormData({
                                                        ...formData,
                                                        category_id: cat.id,
                                                    });
                                                    setIsDropdownOpen(false);
                                                }}
                                                className={`p-2.5 cursor-pointer rounded transition-colors hover:bg-primary-border/40 ${cat.isChild ? 'pl-6 text-secondary-text text-xs' : 'font-bold uppercase text-primary-text'}`}
                                            >
                                                {cat.name}
                                            </div>
                                        ))}
                                    </div>
                                ) : null}
                            </div>

                            <div>
                                <label
                                    htmlFor="address"
                                    className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text"
                                >
                                    Adres
                                </label>
                                <input
                                    id="address"
                                    className="w-full p-3 bg-primary-bg border-2 border-primary-border rounded-lg text-sm text-primary-text focus:outline-none focus:border-primary-accent"
                                    value={formData.address}
                                    onChange={(e) =>
                                        setFormData({ ...formData, address: e.target.value })
                                    }
                                    onBlur={handleGeocode}
                                />
                            </div>

                            <div>
                                <label
                                    htmlFor="content"
                                    className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text"
                                >
                                    Uitleg
                                </label>
                                <textarea
                                    id="content"
                                    className="w-full p-3 h-20 bg-primary-bg border-2 border-primary-border rounded-lg text-sm text-primary-text focus:outline-none focus:border-primary-accent"
                                    placeholder="Context en behoefte"
                                    value={formData.content}
                                    onChange={(e) =>
                                        setFormData({ ...formData, content: e.target.value })
                                    }
                                />
                            </div>

                            <div>
                                <label
                                    htmlFor="file-upload"
                                    className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text"
                                >
                                    Foto (optioneel)
                                </label>
                                <input
                                    id="file-upload"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setImages(Array.from(e.target.files))}
                                    className="w-full p-2 bg-primary-bg border-2 border-primary-border rounded-lg text-sm text-primary-text file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-primary-text file:text-primary-bg hover:file:cursor-pointer"
                                />
                            </div>

                            <div className="relative flex items-center justify-between p-4 bg-primary-bg-cards border-2 border-primary-border rounded-2xl transition-all duration-200 hover:border-primary-accent/50 focus-within:ring-2 focus-within:ring-primary-accent/30 group">
                                <div className="flex flex-col gap-0.5 select-none pr-4">
                                    <label
                                        htmlFor="is_anonymous"
                                        className="text-xs font-black uppercase tracking-widest text-primary-text cursor-pointer"
                                    >
                                        Anoniem melden
                                    </label>
                                </div>

                                <div className="relative flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_anonymous"
                                        checked={formData.is_anonymous}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                is_anonymous: e.target.checked,
                                            })
                                        }
                                        className="peer appearance-none w-6 h-6 rounded-lg border-2 border-primary-border bg-primary-bg checked:bg-primary-text checked:border-primary-text transition-all duration-150 cursor-pointer focus:ring-0 focus:outline-none"
                                    />
                                    <svg
                                        className="absolute left-1.5 top-1.5 w-3 h-3 text-primary-bg pointer-events-none opacity-0 scale-50 peer-checked:opacity-100 peer-checked:scale-100 transition-all duration-150"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth={4}
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={() => setShowConfirm(true)}
                                disabled={isCheckingDuplicates || isSubmitting || !hasContent}
                                className="w-full h-12 mt-4 bg-primary-text text-primary-bg hover:bg-primary-accent font-black uppercase tracking-widest rounded-xl transition-all shadow-md cursor-pointer flex items-center justify-center active:scale-[0.98] disabled:opacity-50"
                            >
                                Verstuur melding
                            </button>
                        </div>
                    </section>
                </main>

                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300 self-end"></div>

                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M4 20 L15 4 L18 5 L10 20" />
                                <line x1="2" y1="20" x2="22" y2="20" />
                                <line x1="14" y1="6" x2="20" y2="20" className="opacity-40" />
                                <line x1="13" y1="9" x2="17" y2="20" className="opacity-40" />
                            </svg>
                        </div>
                        <div className="w-[1px] h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <rect x="2" y="10" width="6" height="11" />
                                <rect x="9" y="3" width="6" height="18" />
                                <rect x="16" y="8" width="6" height="13" />
                            </svg>
                        </div>
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
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
        </div>
    );
}
