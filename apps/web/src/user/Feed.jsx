import React, { useState, useEffect } from 'react';
import axios from 'axios';
import MainStoryCard from '../components/MainStoryCard.jsx';
import StoryDetailModal from '../components/StoryDetailModal.jsx';
import Nav from '../components/U_Nav.jsx';
import Footer from '../components/Footer.jsx';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001',
    headers: { 'Accept': 'application/json' }
});

apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

export default function Feed() {
    const [mainStory, setMainStory] = useState(null);
    const [otherStories, setOtherStories] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [selectedIssue, setSelectedIssue] = useState(null);

    useEffect(() => {
        apiClient.get('/api/issues')
            .then(res => {
                const data = Array.isArray(res.data) ? res.data : (res.data.data || []);

                // 1. Filter: Alleen de verhalen die exact de status 'gesloten' hebben
                const closedIssues = data.filter(s => s.status === 'gesloten');

                // 2. Sorteren op de nieuwste meldingen eerst (meest recent gesloten/aangemaakt)
                const sortedClosed = [...closedIssues].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                // 3. De allernieuwste gesloten melding wordt de 'mainStory', de rest gaat naar 'otherStories'
                if (sortedClosed.length > 0) {
                    setMainStory(sortedClosed[0]);
                    setOtherStories(sortedClosed.slice(1));
                } else {
                    setMainStory(null);
                    setOtherStories([]);
                }

                setIsLoading(false);
            })
            .catch(() => setIsLoading(false));
    }, []);

    return (
        <div className="min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300">
            <Nav/>

            <main className="z-10 flex-grow w-full max-w-6xl mx-auto px-6 pt-26 mt-8 pb-12">
                {isLoading ? (
                    <div className="text-center py-20 text-secondary-text">Verhalen laden...</div>
                ) : (
                    <>
                        {mainStory || otherStories.length > 0 ? (
                            <div className="flex flex-col gap-12">
                                {mainStory && (
                                    <section aria-labelledby="main-story-heading">
                                        {/* Aangepaste titel die past bij de context van gesloten zaken */}
                                        <h2 id="main-story-heading" className="text-xs font-bold tracking-[0.2em] text-secondary-text uppercase mb-4">
                                            Meest recent opgelost
                                        </h2>
                                        <MainStoryCard
                                            issue={mainStory}
                                            onClick={() => setSelectedIssue(mainStory)}
                                        />
                                    </section>
                                )}

                                {otherStories.length > 0 && (
                                    <>
                                        <hr className="border-primary-border"/>
                                        <section aria-labelledby="other-stories-heading">
                                            <h2 id="other-stories-heading" className="text-xs font-bold tracking-[0.2em] text-secondary-text uppercase mb-6">
                                                Eerder opgeloste meldingen
                                            </h2>
                                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                                {otherStories.map((story) => (
                                                    <article
                                                        key={story.id}
                                                        onClick={() => setSelectedIssue(story)}
                                                        className="bg-primary-bg-cards p-6 rounded-2xl border-2 border-primary-border hover:border-primary-accent transition-all cursor-pointer focus-within:ring-2 focus-within:ring-primary-accent"
                                                        tabIndex={0}
                                                    >
                                                        <h3 className="font-headline text-lg font-bold text-primary-text mb-2 line-clamp-2">{story.title}</h3>
                                                        <p className="font-body text-secondary-text text-sm mb-4 line-clamp-3">{story.content}</p>

                                                        <div className="text-[10px] uppercase font-bold tracking-widest text-primary-accent border-t border-primary-border pt-4">
                                                            {story.district?.name || 'Onbekende wijk'} • {new Date(story.created_at).toLocaleDateString()}
                                                        </div>
                                                    </article>
                                                ))}
                                            </div>
                                        </section>
                                    </>
                                )}
                            </div>
                        ) : (
                            <div className="text-center py-20 text-secondary-text">
                                Er zijn op dit moment nog geen opgeloste meldingen.
                            </div>
                        )}
                    </>
                )}
            </main>

            <Footer />

            {selectedIssue && (
                <StoryDetailModal
                    issue={selectedIssue}
                    onClose={() => setSelectedIssue(null)}
                />
            )}
        </div>
    );
}