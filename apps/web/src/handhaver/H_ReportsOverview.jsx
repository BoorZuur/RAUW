import React from 'react';
import DataCard from '../components/HM_DataCard.jsx';
import InformationCard from "../components/InformationCard.jsx";

function H_ReportsOverview() {
    return (
        <div className="p-6 space-y-6 bg-stone-50 min-h-screen">

            {/* Stats */}
            <div className="flex flex-wrap gap-4 w-full">

                {/* Informationcard can be used to fill in other information  */}
                {/* Total */}
                <InformationCard
                    titel="Totaal Meldingen"
                    aantal="114"
                    kleur="text-stone-900"
                />

                <InformationCard
                    titel="Afgehandeld"
                    aantal="86"
                    kleur="text-emerald-600"
                />

                <InformationCard
                    titel="Hoge Urgentie"
                    aantal="12"
                    kleur="text-red-600"
                />
            </div>

            {/* Graphs */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

                <DataCard/>
            </div>

        </div>
    );
}

export default H_ReportsOverview;