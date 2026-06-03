import React from 'react';
import DataCard from "../components/HM_DataCard.jsx";

function H_ReportsOverview() {
    return (
        <div className="p-6 space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <DataCard/>
            </div>
        </div>
    );
}

export default H_ReportsOverview;