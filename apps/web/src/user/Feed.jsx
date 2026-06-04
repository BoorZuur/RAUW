import React from 'react';
import StoryCard from "../components/StoryCard.jsx";

export default function ResolvedReportsFeed() {

    const resolvedReports = [
        {
            id: "res-1",
            title: "milieu",
            location: "Javastraat 112",
            date: "27 mei 2026",
            imageUrl: "https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=500" // Vervang met je eigen asset of prullenbak-foto
        },
        {
            id: "res-2",
            title: "straatwerk",
            location: "Schoolstraat 8",
            date: "25 mei 2026",
            imageUrl: "https://images.unsplash.com/photo-1517649763962-0c623066013b?w=500"
        }
    ];

    const handleCardClick = (id) => {
        console.log(`Navigeer naar de succes-detailpagina van melding: ${id}`);
    };

    return (
        <div className="bg-[#fcfaf4] p-6 min-h-screen">
            <div className="mb-6">
                <h3 className="text-xl font-bold text-stone-900 tracking-tight">
                    Afgehandelde meldingen
                </h3>
                <p className="text-xs text-stone-400 font-medium mt-0.5">
                    Signalen die door BOA's zijn opgelost
                </p>
            </div>

            {/* Grid Layout */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-6xl">
                {resolvedReports.map((item) => (
                    <StoryCard
                        key={item.id}
                        title={item.title}
                        location={item.location}
                        date={item.date}
                        imageUrl={item.imageUrl}
                        onClick={() => handleRowClick(item.id)} // Dit triggert jouw navigatiefunctie
                    />
                ))}
            </div>
        </div>
    );
}