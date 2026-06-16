import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Bell, Shield, Filter, Loader2 } from 'lucide-react';
import Nav from '../components/U_Nav.jsx';
import Footer from '../components/Footer.jsx';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { 'Accept': 'application/json' }
});

export default function NewsFeed() {
    const [news, setNews] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState('all');
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);

    // Hardcoded wijken voor nu, zodat je filter direct werkt
    const districts = [
        { id: 'all', name: 'Alle wijken' },
        { id: '1', name: 'Centrum' },
        { id: '2', name: 'Feijenoord' },
        { id: '3', name: 'Kralingen' }
    ];

    useEffect(() => {
        const fetchNews = async () => {
            try {
                const response = await apiClient.get('/news');
                setNews(response.data.data || response.data || []);
            } catch (err) {
                console.warn("API 404 - Gebruik mock data voor UI");
                // Mock data om te bewijzen dat de UI werkt
                setNews([
                    { id: 1, title: 'Onderhoud straatverlichting', content: 'Controle op het plein.', district_id: '1', officer_name: 'Wijkbeheerder', created_at: '2026-06-15' },
                    { id: 2, title: 'Buurtbijeenkomst', content: 'Welkom in Feijenoord.', district_id: '2', officer_name: 'Gemeente', created_at: '2026-06-14' }
                ]);
            } finally {
                setLoading(false);
            }
        };
        fetchNews();
    }, []);

    const filteredNews = filter === 'all'
        ? news
        : news.filter(item => item.district_id?.toString() === filter);

    return (
        <div className="min-h-screen w-full flex flex-col bg-primary-bg text-primary-text transition-colors duration-300 overflow-x-hidden">
            <header className="fixed top-0 w-full z-50"><Nav /></header>

            <div className="grow flex w-full pt-26">
                {/* LINKER ZIJBALK - IDENTIEK AAN KAART */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 opacity-35 hover:opacity-100 transition-all duration-500">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors"></div>
                    <div className="flex flex-col items-center gap-12 my-auto">
                        <Shield className="w-10 h-10 text-primary-text group-hover:text-primary-accent transition-colors" />
                        <div className="w-px h-6 bg-primary-text/20"></div>
                        <div className="w-10 h-10 border border-primary-text/20 rounded-full group-hover:border-primary-accent transition-colors"></div>
                    </div>
                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent"></div>
                </div>

                {/* MIDDEN */}
                <main className="grow w-full max-w-6xl mx-auto px-6 mt-8 pb-12 md:p-8 flex flex-col md:flex-row gap-6">
                    <section className="flex-1 space-y-8">
                        <header className="flex items-center gap-4">
                            <div className="p-4 bg-primary-accent/10 rounded-2xl"><Bell className="w-8 h-8 text-primary-accent" /></div>
                            <div>
                                <h1 className="text-3xl font-black text-white">Nieuws & Updates</h1>
                            </div>
                        </header>

                        {loading ? <Loader2 className="animate-spin mx-auto" /> : filteredNews.map(item => (
                            <article key={item.id} className="bg-primary-bg-cards border-2 border-primary-border p-8 rounded-3xl hover:border-primary-accent transition-all">
                                <h2 className="text-2xl font-bold text-white mb-2">{item.title}</h2>
                                <p className="text-stone-300 text-sm mb-4">{item.content}</p>
                                <span className="text-[10px] font-black uppercase text-primary-accent">{item.officer_name}</span>
                            </article>
                        ))}
                    </section>

                    {/* RECHTER ZIJBALK (FILTER) */}
                    <aside className="w-full md:w-80 bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-8 shrink-0 h-fit space-y-4">
                        <h2 className="font-bold text-primary-text">Wijk Filter</h2>
                        <div className="relative">
                            <div onClick={() => setIsDropdownOpen(!isDropdownOpen)} className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl cursor-pointer flex justify-between">
                                {districts.find(d => d.id === filter)?.name || "Alle wijken"}
                                <span>{isDropdownOpen ? '▲' : '▼'}</span>
                            </div>
                            {isDropdownOpen && (
                                <div className="absolute z-50 w-full mt-2 bg-primary-bg-cards border border-primary-border rounded-xl p-2">
                                    {districts.map(d => (
                                        <div key={d.id} onClick={() => {setFilter(d.id); setIsDropdownOpen(false)}} className="p-3 cursor-pointer hover:bg-primary-bg rounded-lg">{d.name}</div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </aside>
                </main>

                {/* RECHTER ZIJBALK - IDENTIEK AAN KAART */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 opacity-35 hover:opacity-100 transition-all duration-500 self-end">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent self-end"></div>
                </div>
            </div>
            <Footer />
        </div>
    );
}