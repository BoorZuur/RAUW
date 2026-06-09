import "../App.css";
import React from 'react';
import ReportCard from "../components/H_SignalCard.jsx";

function CommandCenter() {
    return (
        <div className="p-4 space-y-6">
            <h1 className="text-xl font-bold text-stone-800 mb-4">Command Center</h1>

            <div className="grid gap-4">
                <ReportCard
                    title="Groep jongeren intimiderend..."
                    status="Afgehandeld"
                    priority="red"
                    description="Al weken lang staat er een groep van 5-8 jongeren..."
                    location="Marconiplein, Rotterdam-West"
                    time="08:08"
                    reporter="Sandra de Vries"
                    tags={["Surveillance gewenst"]}
                />
                <ReportCard
                    title="Groep jongeren intimiderend..."
                    status="In behandeling"
                    priority="green"
                    description="Al weken lang staat er een groep van 5-8 jongeren..."
                    location="Marconiplein, Rotterdam-West"
                    time="08:08"
                    reporter="Sandra de Vries"
                    tags={["Surveillance gewenst"]}
                />
            </div>
        </div>
    );
}

export default CommandCenter;