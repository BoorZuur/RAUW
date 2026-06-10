import "../App.css";
import React from 'react';
import ReportCard from "../components/H_SignalCard.jsx";
import HM_Nav from "../components/HM_Nav.jsx";
import "./Handhaver_styling.css"
import "../components/MapComponent.jsx"
import NativeLeafletMap from "../components/MapComponent.jsx";

function CommandCenter() {
    return (
        <div className="app-layout">
            <HM_Nav></HM_Nav>
            <main className="main-content dashboard-container">

                <section className="metrics-row">
                    <div className="metric-card">
                        <div className="metric-info">
                            <span className="metric-title">ACTIEVE WIJKUITDAGINGEN</span>
                            <span className="metric-value">7</span>
                        </div>
                        <div className="metric-icon warning">⚠️</div>
                    </div>

                    <div className="metric-card">
                        <div className="metric-info">
                            <span className="metric-title">GEM. OPVOLGTIJD</span>
                            <span className="metric-value">-413 min</span>
                        </div>
                        <div className="metric-icon clock">🕒</div>
                    </div>

                    <div className="metric-card">
                        <div className="metric-info">
                            <span className="metric-title">COMMUNITY SCORE</span>
                            <span className="metric-value">80%</span>
                        </div>
                        <div className="metric-icon community">👥</div>
                    </div>
                </section>

                <section className="filter-bar">
                    <div className="search-wrapper">
                        <span className="search-icon">🔍</span>
                        <input type="text" placeholder="Zoek meldingen..." className="search-input" />
                    </div>
                    <select className="filter-select"><option>Tijd</option></select>
                    <select className="filter-select"><option>Alle</option></select>
                    <select className="filter-select"><option>Alle Categorieën</option></select>
                </section>

                <div className="dashboard-workspace">

                    {/*🌟: replace with working map*/}
                    <div className="map-panel">
                        <div className="map-placeholder">
                            <NativeLeafletMap/>
                        </div>
                    </div>

                    <aside className="incident-panel">
                        <h3 className="queue-title">Incidentenwachtrij <span className="queue-count">(12)</span></h3>
                        <div className="incident-queue-list">
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
                    </aside>

                </div>


            </main>
        </div>
    );
}

export default CommandCenter;