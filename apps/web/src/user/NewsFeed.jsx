import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import { Bell, Shield, Loader2, Filter, ChevronDown, MapPin } from 'lucide-react';
import Nav from '../components/U_Nav.jsx';
import Footer from '../components/Footer.jsx';
import { useTheme } from '../ThemeContext.jsx';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { 'Accept': 'application/json' }
});

apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

export default function NewsFeed() {
    const { isDark } = useTheme();
    const [posts, setPosts] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [profileLoading, setProfileLoading] = useState(true);
    const [postsLoading, setPostsLoading] = useState(false);
    const [selectedDistrict, setSelectedDistrict] = useState('all');
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        const loadProfile = async () => {
            setProfileLoading(true);
            setError('');
            try {
                const meRes = await apiClient.get('/auth/me');
                const profile = meRes.data?.profile || meRes.data;
                setDistricts(profile?.districts || []);
            } catch (err) {
                setError('Kon wijken niet laden.');
            } finally {
                setProfileLoading(false);
            }
        };

        loadProfile();
    }, []);

    useEffect(() => {
        if (profileLoading) return;

        const loadPosts = async () => {
            setPostsLoading(true);
            setError('');
            try {
                const params = selectedDistrict !== 'all' ? { district_id: selectedDistrict } : {};
                const res = await apiClient.get('/community-posts', { params });
                const data = res.data?.data || res.data || [];
                const sorted = (Array.isArray(data) ? data : []).sort(
                    (a, b) => new Date(b.created_at) - new Date(a.created_at)
                );
                setPosts(sorted);
            } catch (err) {
                setError('Kon wijknieuws niet laden.');
                setPosts([]);
            } finally {
                setPostsLoading(false);
            }
        };

        loadPosts();
    }, [selectedDistrict, districts, profileLoading]);

    const selectedLabel =
        selectedDistrict === 'all'
            ? 'Alle geselecteerde wijken'
            : districts.find(d => d.id.toString() === selectedDistrict)?.name || 'Wijk';

    return (
        <div className={`min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300 ${isDark ? 'dark' : ''}`}>
            <div className="z-50"><Nav /></div>

            <div className="grow flex w-full pt-26 min-h-0">
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 opacity-35">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40"></div>
                </div>

                <main className="grow w-full max-w-3xl mx-auto px-6 mt-8 pb-6 flex flex-col">
                    <header className="relative flex items-center justify-center mb-8 shrink-0 min-h-18">
                        <div className="absolute left-0 p-4 bg-primary-accent/10 rounded-2xl">
                            <Bell className="w-8 h-8 text-primary-accent" />
                        </div>
                        <div className="text-center px-16">
                            <h1 className="text-3xl font-headline font-black text-white">Nieuws & Updates</h1>
                            <p className="text-secondary-text font-label text-sm mt-1">Officiële berichten uit de wijk</p>
                        </div>
                    </header>

                    {profileLoading ? (
                        <div className="flex flex-col items-center justify-center py-20 gap-4">
                            <Loader2 className="w-10 h-10 animate-spin text-primary-accent" />
                            <p className="text-sm text-secondary-text font-label">Wijken laden...</p>
                        </div>
                    ) : districts.length === 0 ? (
                        error ? (
                            <p className="text-center text-red-500 text-sm py-20">{error}</p>
                        ) : (
                        <div className="text-center py-20 border-2 border-dashed border-primary-border rounded-3xl">
                            <MapPin className="mx-auto mb-4 text-primary-accent" size={32} />
                            <p className="text-primary-text font-bold mb-2">Geen wijken geselecteerd</p>
                            <p className="text-sm text-secondary-text mb-6">
                                Kies wijken in je instellingen om wijknieuws te zien.
                            </p>
                            <Link
                                to="/instellingen"
                                className="inline-flex px-6 py-3 bg-primary-accent text-primary-bg rounded-xl font-bold text-sm"
                            >
                                Naar instellingen
                            </Link>
                        </div>
                        )
                    ) : (
                        <>
                            <div className="relative mb-4 shrink-0">
                                <button
                                    onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                                    className="flex items-center justify-between w-full md:w-64 px-4 py-3 bg-primary-bg-cards border border-primary-border rounded-xl text-sm font-bold hover:border-primary-accent transition-all"
                                >
                                    <span className="flex items-center gap-2">
                                        <Filter size={16} className="text-primary-accent" />
                                        {selectedLabel}
                                    </span>
                                    <ChevronDown size={16} className={`transition-transform ${isDropdownOpen ? 'rotate-180' : ''}`} />
                                </button>

                                {isDropdownOpen && (
                                    <div className="absolute z-40 w-full md:w-64 mt-2 bg-primary-bg-cards border border-primary-border rounded-xl shadow-2xl py-1 max-h-60 overflow-y-auto custom-scrollbar">
                                        <button
                                            onClick={() => { setSelectedDistrict('all'); setIsDropdownOpen(false); }}
                                            className="block w-full text-left px-4 py-3 text-sm hover:bg-primary-bg transition-colors"
                                        >
                                            Alle geselecteerde wijken
                                        </button>
                                        {districts.map(d => (
                                            <button
                                                key={d.id}
                                                onClick={() => { setSelectedDistrict(d.id.toString()); setIsDropdownOpen(false); }}
                                                className="block w-full text-left px-4 py-3 text-sm hover:bg-primary-bg transition-colors"
                                            >
                                                {d.name}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            <div className="h-200 max-h-[calc(100vh-18rem)] overflow-y-scroll custom-scrollbar pr-1 rounded-3xl border border-primary-border bg-primary-bg/40">
                                {postsLoading ? (
                                    <div className="flex flex-col items-center justify-center h-full gap-4">
                                        <Loader2 className="w-10 h-10 animate-spin text-primary-accent" />
                                        <p className="text-sm text-secondary-text font-label">Berichten laden...</p>
                                    </div>
                                ) : error ? (
                                    <p className="text-center text-red-500 text-sm py-20">{error}</p>
                                ) : posts.length === 0 ? (
                                    <div className="flex items-center justify-center h-full text-center px-6 border-2 border-dashed border-primary-border rounded-3xl m-1">
                                        <p className="text-secondary-text">Er zijn momenteel geen berichten gevonden.</p>
                                    </div>
                                ) : (
                                    <div className="space-y-8 p-2">
                                        {posts.map((post) => (
                                            <article key={post.id} className="bg-primary-bg-cards border border-primary-border p-8 rounded-3xl shadow-sm hover:border-primary-accent transition-all duration-300 min-h-42">
                                                <div className="flex items-center justify-between gap-4 mb-4">
                                                    <div className="flex items-center gap-2">
                                                        <Shield className="w-4 h-4 text-primary-accent" />
                                                        <span className="text-[10px] font-label font-black uppercase tracking-widest text-primary-accent">
                                                            {post.author_name || 'Wijkbeheerder'}
                                                        </span>
                                                    </div>
                                                    {post.district?.name ? (
                                                        <span className="text-[10px] font-label font-black uppercase tracking-widest text-primary-accent">
                                                            {post.district.name}
                                                        </span>
                                                    ) : null}
                                                </div>
                                                <h2 className="font-headline text-2xl font-bold text-white mb-3">{post.title}</h2>
                                                <p className="font-body text-stone-300 text-sm leading-relaxed mb-6">{post.content}</p>
                                                <time className="text-[10px] font-label font-bold text-secondary-text uppercase tracking-widest opacity-60">
                                                    {new Date(post.created_at).toLocaleDateString('nl-NL')}
                                                </time>
                                            </article>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </>
                    )}
                </main>

                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 opacity-35 self-end">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 self-end"></div>
                </div>
            </div>

            <div className="z-10 bg-primary-bg"><Footer /></div>
        </div>
    );
}
