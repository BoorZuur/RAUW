import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Bell, Shield, Loader2, Filter, ChevronDown } from 'lucide-react';
import Nav from '../components/U_Nav.jsx';
import Footer from '../components/Footer.jsx';

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
    const [posts, setPosts] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedDistrict, setSelectedDistrict] = useState('all');
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);

    useEffect(() => {
        const fetchEverything = async () => {
            try {
                const [postsRes, distRes] = await Promise.all([
                    apiClient.get('/community-posts'),
                    apiClient.get('/districts')
                ]);

                setPosts(postsRes.data.data || postsRes.data || []);
                setDistricts(distRes.data.data || distRes.data || []);
            } catch (err) {
                console.error("Fout bij laden:", err);
                setPosts([]);
            } finally {
                setLoading(false);
            }
        };
        fetchEverything();
    }, []);

    const filteredPosts = selectedDistrict === 'all'
        ? posts
        : posts.filter(post => post.district_id?.toString() === selectedDistrict.toString());

    return (
        <div className="min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300">
            <div className="z-50"><Nav /></div>

            <div className="grow flex w-full pt-26">
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 opacity-35">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40"></div>
                </div>

                <main className="grow w-full max-w-3xl mx-auto px-6 mt-8 pb-12">
                    <header className="flex items-center gap-4 mb-8">
                        <div className="p-4 bg-primary-accent/10 rounded-2xl">
                            <Bell className="w-8 h-8 text-primary-accent" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-headline font-black text-white">Nieuws & Updates</h1>
                            <p className="text-secondary-text font-label text-sm mt-1">Officiële berichten uit de wijk</p>
                        </div>
                    </header>

                    {/* Dropdown Filter */}
                    <div className="relative mb-8">
                        <button
                            onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                            className="flex items-center justify-between w-full md:w-64 px-4 py-3 bg-primary-bg-cards border border-primary-border rounded-xl text-sm font-bold hover:border-primary-accent transition-all"
                        >
                            <span className="flex items-center gap-2">
                                <Filter size={16} className="text-primary-accent" />
                                {districts.find(d => d.id.toString() === selectedDistrict)?.name || "Alle wijken"}
                            </span>
                            <ChevronDown size={16} className={`transition-transform ${isDropdownOpen ? 'rotate-180' : ''}`} />
                        </button>

                        {isDropdownOpen && (
                            <div className="absolute z-40 w-full md:w-64 mt-2 bg-primary-bg-cards border border-primary-border rounded-xl shadow-2xl py-1 max-h-60 overflow-y-auto custom-scrollbar">
                                <button
                                    onClick={() => {setSelectedDistrict('all'); setIsDropdownOpen(false)}}
                                    className="block w-full text-left px-4 py-3 text-sm hover:bg-primary-bg transition-colors"
                                >
                                    Alle wijken
                                </button>
                                {districts.map(d => (
                                    <button
                                        key={d.id}
                                        onClick={() => {setSelectedDistrict(d.id.toString()); setIsDropdownOpen(false)}}
                                        className="block w-full text-left px-4 py-3 text-sm hover:bg-primary-bg transition-colors"
                                    >
                                        {d.name}
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {loading ? (
                        <div className="flex justify-center p-20"><Loader2 className="animate-spin text-primary-accent" /></div>
                    ) : filteredPosts.length === 0 ? (
                        <div className="text-center py-20 border-2 border-dashed border-primary-border rounded-3xl">
                            <p className="text-secondary-text">Er zijn momenteel geen berichten gevonden.</p>
                        </div>
                    ) : (
                        <div className="space-y-8">
                            {filteredPosts.map((post) => (
                                <article key={post.id} className="bg-primary-bg-cards border border-primary-border p-8 rounded-3xl shadow-sm hover:border-primary-accent transition-all duration-300">
                                    <div className="flex items-center gap-2 mb-4">
                                        <Shield className="w-4 h-4 text-primary-accent" />
                                        <span className="text-[10px] font-label font-black uppercase tracking-widest text-primary-accent">
                                            {post.author_name || 'Wijkbeheerder'}
                                        </span>
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
                </main>

                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 opacity-35 self-end">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 self-end"></div>
                </div>
            </div>

            <div className="z-10 bg-primary-bg"><Footer /></div>
        </div>
    );
}