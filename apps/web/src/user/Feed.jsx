import React from 'react';
import MainStoryCard from '../components/MainStoryCard.jsx';

export default function Feed() {
    // Testdata gebaseerd op Rotterdamse meldingen
    const mockMeldingen = [
        {
            id: 1,
            title: "Structurele verlichtingsproblemen en jeugdoverlast bij Stationsplein",
            description: "Al weken zijn meerdere lantaarnpalen bij het Stationsplein defect. 's Avonds na 21:00 verzamelen zich groepen jongeren die overlast veroorzaken. Bewoners voelen zich onveilig bij het passeren. Verzoek aan handhaving: structurele surveillance-rondes en...",
            location: "UITGELICHT • ROTTERDAM",
            district: "CENTRUM",
            date: "6 dagen geleden",
            followers: 19,
            imageUrl: "https://images.unsplash.com/photo-1477959858617-67f85cf4f1df?auto=format&fit=crop&w=900&q=80"
        },
        {
            id: 2,
            title: "Afvaldumping en rondslingerend grofvuil in de Coolsingel-zijstraten",
            description: "Bij de containerlocaties wordt er herhaaldelijk vuilnis naast de bakken geplaatst. Dit trekt ongedierte aan en zorgt voor een rommelig straatbeeld. Graag extra controle of camera-handhaving op deze hotspots.",
            location: "MELDING • ROTTERDAM",
            district: "CENTRUM",
            date: "2 dagen geleden",
            followers: 8,
            imageUrl: "https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?auto=format&fit=crop&w=900&q=80"
        },
        {
            id: 3,
            title: "Fietspad blokkade door foutgeparkeerde deelscooters",
            description: "Op de hoek van de Meent staan structureel deelscooters midden op het fietspad geparkeerd. Dit zorgt voor gevaarlijke situaties voor overstekende fietsers en voetgangers.",
            location: "MELDING • ROTTERDAM",
            district: "CENTRUM",
            date: "3 dagen geleden",
            followers: 12,
            imageUrl: "https://images.unsplash.com/photo-1556155092-490a1ba16284?auto=format&fit=crop&w=900&q=80"
        }
    ];

    // we splitsen de data: de eerste is voor de MainStoryCard, de rest is voor de lijst eronder
    const [highlightedStory, ...otherStories] = mockMeldingen;

    const handleCardClick = (id) => {
        console.log(`Kaart met ID ${id} is aangeklikt!`);
    };

    return (
        <div className="min-h-screen bg-stone-100 py-10 px-4 sm:px-6 lg:px-8">
            <div className="max-w-4xl mx-auto flex flex-col gap-10">

                {/* 1. SECTIE: DE HOOFDMELDING (Maximaal 1) */}
                {highlightedStory && (
                    <section>
                        <h2 className="text-xs font-bold tracking-widest text-stone-400 uppercase mb-3">
                            Belangrijkste signaal
                        </h2>
                        <MainStoryCard
                            title={highlightedStory.title}
                            description={highlightedStory.description}
                            location={highlightedStory.location}
                            district={highlightedStory.district}
                            date={highlightedStory.date}
                            followers={highlightedStory.followers}
                            imageUrl={highlightedStory.imageUrl}
                            onClick={() => handleCardClick(highlightedStory.id)}
                        />
                    </section>
                )}

                <hr className="border-stone-200"/>

                {/* 2. SECTIE: OVERIGE MELDINGEN */}
                <section>
                    <h2 className="text-xs font-bold tracking-widest text-stone-400 uppercase mb-4">
                        Andere meldingen in de buurt
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {otherStories.map((story) => (
                            <div
                                key={story.id}
                                onClick={() => handleCardClick(story.id)}
                                className="bg-white p-5 rounded-xl border border-stone-200/60 shadow-sm hover:shadow-md cursor-pointer transition-all flex flex-col justify-between"
                            >
                                <div>
                                    <span
                                        className="text-[10px] font-bold text-stone-400 uppercase tracking-wider block mb-1">
                                        {story.district}
                                    </span>
                                    <h4 className="font-bold text-stone-900 text-base mb-2 line-clamp-1">
                                        {story.title}
                                    </h4>
                                    <p className="text-stone-500 text-sm line-clamp-2 mb-4">
                                        {story.description}
                                    </p>
                                </div>
                                <div
                                    className="text-xs text-stone-400 flex justify-between items-center border-t border-stone-50 pt-3">
                                    <span>{story.followers} volgers</span>
                                    <span>{story.date}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

            </div>
        </div>
    );
}