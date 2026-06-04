import React from 'react';
import USignalCard from "../components/U_SignalCard.jsx";

export default function MyReports() {

    const reports = [
        {
            id: "sig-123",
            title: "Onveilige verkeerssituatie kruispunt Bilderdijkstraat",
            location: "Bilderdijkstraat/Kinkerstraat",
            date: "27 mei 2026",
            status: "In behandeling"
        },
        {
            id: "sig-456",
            title: "Illegale afvaldumping achter Albert Heijn",
            location: "Javastraat 112",
            date: "27 mei 2026",
            status: "Afgehandeld"
        },
        {
            id: "sig-789",
            title: "Vandalisme fietsenrek bij school De Fontein",
            location: "Schoolstraat 8",
            date: "27 mei 2026",
            status: "Nieuw"
        }
    ];

    const handleRowClick = (id) => {
        console.log(`Navigate to detail page of report with given: ${id}`);
        // Navigation logic
    };

    return (
        <div className="bg-[#fcfaf4] p-6 min-h-screen flex justify-center items-start">
            <div className="bg-white rounded-2xl p-6 border border-stone-200/60 shadow-sm max-w-4xl w-full">

                <h3 className="text-xl font-bold text-stone-900 mb-4 tracking-tight">
                    Mijn signaleringen
                </h3>

                <div className="flex flex-col">
                    {reports.map((item) => (
                        <USignalCard
                            key={item.id}
                            title={item.title}
                            location={item.location}
                            date={item.date}
                            status={item.status}
                            onClick={() => handleRowClick(item.id)}
                        />
                    ))}
                </div>

            </div>
        </div>
    );
}