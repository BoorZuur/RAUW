import React, { useState, useEffect } from 'react';
import axios from 'axios';
import MainStoryCard from '../components/MainStoryCard.jsx';
import StoryDetailModal from '../modal/StoryDetailModal.jsx';
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

    const handleSelectIssue = async (issue) => {
        try {
            console.log("Fetching details en alle comments voor:", issue.id);
            const issueResponse = await apiClient.get(`/api/issues/${issue.id}`);
            const fullIssueData = issueResponse.data.data || issueResponse.data;
            let allComments = [];
            let nextPageUrl = `/api/issues/${issue.id}/comments`;
            try {
                while (nextPageUrl) {
                    const commentResponse = await apiClient.get(nextPageUrl);
                    const data = commentResponse.data.data || commentResponse.data;

                    allComments = [...allComments, ...data];
                    nextPageUrl = commentResponse.data.next_page_url;
                }
            } catch (commentErr) {
                console.warn("Kon (sommige) comments niet ophalen:", commentErr);
            }
            setSelectedIssue({
                ...fullIssueData,
                comments: allComments
            });

        } catch (err) {
            console.error('Kon issue details niet ophalen:', err);
            setSelectedIssue(issue);
        }
    };

    const handleAddComment = async (issueId, commentText) => {
        try {
            const response = await apiClient.post(`/api/issues/${issueId}/comments`, {
                content: commentText
            });
            const newComment = response.data.data || response.data;
            setSelectedIssue(prev => ({
                ...prev,
                comments: [...(prev.comments || []), newComment]
            }));
        } catch (err) {
            console.error('Kon reactie niet plaatsen:', err.response?.data || err);
        }
    };

    useEffect(() => {
        apiClient.get('/api/issues')
            .then(res => {
                const data = Array.isArray(res.data) ? res.data : (res.data.data || []);
                const closedIssues = data.filter(s => s.status === 'gesloten');
                const sortedClosed = [...closedIssues].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                if (sortedClosed.length > 0) {
                    setMainStory(sortedClosed[0]);
                    setOtherStories(sortedClosed.slice(1));
                }
                setIsLoading(false);
            })
            .catch(() => setIsLoading(false));
    }, []);

    return (
        <div className="min-h-screen flex flex-col bg-primary-bg text-primary-text transition-colors duration-300 overflow-x-hidden">

            <div className="z-50">
                <Nav/>
            </div>

            <div className="grow flex w-full pt-26">

                {/* ==================== LINKER PANEL: HANDHAVING & TOEZICHT KETEN ==================== */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">

                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300"></div>

                    {/* Verticale stapeling van Toezicht iconen */}
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">

                        {/* Icoon 1: Schild (Veiligheid) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <circle cx="12" cy="11" r="2" className="opacity-60" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 2: Handdruk (Samenwerking in de wijk) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 3: Checkmark (Opgeloste meldingen / Resultaat) */}
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

                {/* ==================== MIDDEN: DE VERHALEN FEED ==================== */}
                <main className="z-10 grow w-full max-w-5xl mx-auto px-6 mt-8 pb-12">
                    {isLoading ? (
                        <div className="text-center py-20 text-secondary-text font-label">Verhalen laden...</div>
                    ) : (
                        <>
                            {mainStory || otherStories.length > 0 ? (
                                <div className="flex flex-col gap-12">
                                    {mainStory && (
                                        <section aria-labelledby="main-story-heading">
                                            <h2 id="main-story-heading" className="text-xs font-bold tracking-[0.2em] text-secondary-text uppercase mb-4 font-label">
                                                Meest recent opgelost
                                            </h2>
                                            <MainStoryCard
                                                issue={mainStory}
                                                onClick={() => handleSelectIssue(mainStory)}
                                            />
                                        </section>
                                    )}

                                    {otherStories.length > 0 && (
                                        <>
                                            <hr className="border-primary-border"/>
                                            <section aria-labelledby="other-stories-heading">
                                                <h2 id="other-stories-heading" className="text-xs font-bold tracking-[0.2em] text-secondary-text uppercase mb-6 font-label">
                                                    Eerder opgeloste meldingen
                                                </h2>
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                                    {otherStories.map((story) => (
                                                        <article
                                                            key={story.id}
                                                            onClick={() => handleSelectIssue(story)}
                                                            className="bg-primary-bg-cards p-6 rounded-2xl border-2 border-primary-border hover:border-primary-accent transition-all cursor-pointer focus-within:ring-2 focus-within:ring-primary-accent"
                                                            tabIndex={0}
                                                        >
                                                            <h3 className="font-headline text-lg font-bold text-primary-text mb-2 line-clamp-2">{story.title}</h3>
                                                            <p className="font-body text-secondary-text text-sm mb-4 line-clamp-3">{story.content}</p>

                                                            <div className="font-label text-[10px] uppercase font-bold tracking-widest text-primary-accent border-t border-primary-border pt-4">
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
                                <div className="text-center py-20 text-secondary-text font-label">
                                    Er zijn op dit moment nog geen opgeloste meldingen.
                                </div>
                            )}
                        </>
                    )}
                </main>

                {/* ==================== RECHTER PANEL: ROTTERDAM IDENTITEIT ==================== */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">

                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300 self-end"></div>

                    {/* Verticale stapeling van Rotterdamse iconen */}
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">

                        {/* Icoon 1: Erasmusbrug */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M4 20 L15 4 L18 5 L10 20" />
                                <line x1="2" y1="20" x2="22" y2="20" />
                                <line x1="14" y1="6" x2="20" y2="20" className="opacity-40" />
                                <line x1="13" y1="9" x2="17" y2="20" className="opacity-40" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 2: Stad / Gebouwen (De Wijken) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <rect x="2" y="10" width="6" height="11" />
                                <rect x="9" y="3" width="6" height="18" />
                                <rect x="16" y="8" width="6" height="13" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 3: Maritiem / Haven (Anker en Water) */}
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

            {selectedIssue && (
                <StoryDetailModal
                    issue={selectedIssue}
                    onClose={() => setSelectedIssue(null)}
                    onAddComment={handleAddComment}
                />
            )}
        </div>
    );
}