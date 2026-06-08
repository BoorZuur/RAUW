import React from 'react';
import MainStoryCard from '../components/MainStoryCard.jsx';

export default function Feed() {

    const mockMeldingen = [
        {
            id: 2,
            title: "Afvaldumping en rondslingerend grofvuil in de Coolsingel-zijstraten",
            description: "Bij de containerlocaties wordt er herhaaldelijk vuilnis naast de bakken geplaatst. Dit trekt ongedierte aan en zorgt voor een rommelig straatbeeld. Graag extra controle of camera-handhaving op deze hotspots.",
            type: "MELDING",
            district: "CENTRUM",
            date: "2 dagen geleden",
            followers: 8,
            imageUrl: "https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?auto=format&fit=crop&w=900&q=80"
        },
        {
            id: 1,
            title: "Structurele verlichtingsproblemen en jeugdoverlast bij Stationsplein",
            description: "Al weken zijn meerdere lantaarnpalen bij het Stationsplein defect. 's Avonds na 21:00 verzamelen zich groepen jongeren die overlast veroorzaken. Bewoners voelen zich onveilig bij het passeren. Verzoek aan handhaving: structurele surveillance-rondes en...",
            type: "UITGELICHT", // De frontend vist deze er straks automatisch uit!
            district: "CENTRUM",
            date: "6 dagen geleden",
            followers: 19,
            imageUrl: "https://images.unsplash.com/photo-1477959858617-67f85cf4f1df?auto=format&fit=crop&w=900&q=80"
        },
        {
            id: 3,
            title: "Fietspad blokkade door foutgeparkeerde deelscooters",
            description: "Op de hoek van de Meent staan structureel deelscooters midden op het fietspad geparkeerd. Dit zorgt voor gevaarlijke situaties voor overstekende fietsers en voetgangers.",
            type: "MELDING",
            district: "CENTRUM",
            date: "3 dagen geleden",
            followers: 12,
            imageUrl: "https://images.unsplash.com/photo-1556155092-490a1ba16284?auto=format&fit=crop&w=900&q=80"
        }
    ];


    // 1. Zoek de melding die het type "UITGELICHT" heeft
    const highlightedStory = mockMeldingen.find(story => story.type === "UITGELICHT");

    // 2. Mocht er GEEN uitgelichte melding zijn, pak dan als fallback gewoon de allereerste melding
    const mainStory = highlightedStory || mockMeldingen[0];

    // 3. De overige verhalen zijn alle meldingen BEHALVE degene die we hierboven bovenaan zetten
    const otherStories = mockMeldingen.filter(story => story.id !== mainStory?.id);

    const handleCardClick = (id) => {
        console.log(`Kaart met ID ${id} is aangeklikt!`);
    };

    const renderLocationText = (type) => {
        const isUitgelicht = type === "UITGELICHT";
        return (
            <>
                <span className={isUitgelicht ? "text-[#ef7d14]" : "text-stone-400"}>
                    {type}
                </span>
                <span className="text-stone-400"> • ROTTERDAM</span>
            </>
        );
    };

    return (
        <div className="min-h-screen bg-stone-100 py-10 px-4 sm:px-6 lg:px-8">
            <div className="max-w-6xl mx-auto flex flex-col gap-10">

                {/* 1. Main Story, always 1 */}
                {mainStory && (
                    <section>
                        <h2 className="text-xs font-bold tracking-widest text-stone-400 uppercase mb-3">
                            Belangrijkste signaal
                        </h2>
                        <MainStoryCard
                            title={mainStory.title}
                            description={mainStory.description}
                            location={renderLocationText(mainStory.type)}
                            district={mainStory.district}
                            date={mainStory.date}
                            followers={mainStory.followers}
                            imageUrl={mainStory.imageUrl}
                            onClick={() => handleCardClick(mainStory.id)}
                        />
                    </section>
                )}

                <hr className="border-stone-200"/>

                {/* 2. Other Reports */}
                <section>
                    <h2 className="text-xs font-bold tracking-widest text-stone-400 uppercase mb-4">
                        Andere meldingen in de buurt
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {otherStories.map((story) => (
                            <div
                                key={story.id}
                                onClick={() => handleCardClick(story.id)}
                                className="bg-white p-5 rounded-xl border border-stone-200/60 shadow-sm hover:shadow-md cursor-pointer transition-all flex flex-col justify-between"
                            >
                                <div>
                                    <span className="text-[10px] font-bold uppercase tracking-wider block mb-2">
                                        {renderLocationText(story.type)}
                                    </span>
                                    <h4 className="font-bold text-stone-900 text-base mb-2 line-clamp-1">
                                        {story.title}
                                    </h4>
                                    <p className="text-stone-500 text-sm line-clamp-2 mb-4">
                                        {story.description}
                                    </p>
                                </div>
                                <div
                                    className="text-xs text-stone-400 flex justify-between items-center border-t border-stone-100 pt-3">
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