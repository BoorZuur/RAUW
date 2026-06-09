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
    const [meldingen, setMeldingen] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [selectedIssue, setSelectedIssue] = useState(null);

    useEffect(() => {
        apiClient.get('/api/issues')
            .then(res => {
                setMeldingen(Array.isArray(res.data) ? res.data : (res.data.data || []));
                setIsLoading(false);
            })
            .catch(() => setIsLoading(false));
    }, []);

    const highlightedStory = meldingen.find(s => s.type === "UITGELICHT") || meldingen[0];
    const otherStories = meldingen.filter(s => s.id !== highlightedStory?.id);

    return (
        <div className="min-h-screen bg-primary-bg text-primary-text transition-colors duration-300">
            <Nav/>

            <main className="z-10 grow w-full max-w-6xl mx-auto px-6 pt-26 mt-8 pb-12">
                {isLoading ? (
                    <div className="text-center py-20 text-secondary-text">Verhalen laden...</div>
                ) : (
                    <div className="flex flex-col gap-12">
                        {highlightedStory && (
                            <section aria-labelledby="main-story-heading">
                                <h2 id="main-story-heading" className="text-xs font-bold tracking-[0.2em] text-secondary-text uppercase mb-4">
                                    Belangrijkste signaal
                                </h2>
                                <MainStoryCard
                                    issue={highlightedStory}
                                    onClick={() => setSelectedIssue(highlightedStory)}
                                />
                            </section>
                        )}

                        <hr className="border-primary-border"/>

                        <section aria-labelledby="other-stories-heading">
                            <h2 id="other-stories-heading" className="text-xs font-bold tracking-[0.2em] text-secondary-text uppercase mb-6">
                                Andere meldingen
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
                                            {story.district} • {new Date(story.created_at).toLocaleDateString()}
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </section>
                    </div>
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