import React from 'react';
// import DataCard from '../components/HM_DataCard.jsx';
import InformationCard from "../components/InformationCard.jsx";
import "./Handhaver_styling.css"
import "../App.css"
import HM_Nav from "../components/HM_Nav.jsx";

function H_ReportsOverview() {
    return (
        <>
        <div className="app-layout">

            <HM_Nav></HM_Nav>
            <main className="main-content reports-container">
                <header className="reports-header">
                    <div className="header-left">
                        <div className="header-icon-box">📊</div>
                        <div>
                            <h1>Statistiek Rapporten</h1>
                            <p className="subtitle">Overzicht van incidenten en prestaties</p>
                        </div>
                    </div>
                    <div className="header-toggle-buttons">
                        <button className="btn-toggle active">📅 Maand</button>
                        <button className="btn-toggle">📈 Jaar</button>
                    </div>
                </header>

                <div className="date-navigator">
                    <button className="nav-arrow">‹</button>
                    <span className="current-date">Juni 2026</span>
                    <button className="nav-arrow">›</button>
                </div>

                <section>
                <div className="flex flex-wrap gap-4 w-full">
                    <InformationCard
                        titel="Totaal Meldingen"
                        aantal="114"
                        kleur="text-stone-900"
                    />

                    <InformationCard
                        titel="Oplossingspercentage"
                        aantal="50%"
                        kleur="text-yellow-500"
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
                </section>

                <section className="reports-charts-grid">

                    <div className="analytics-card">
                        <h3>Meldingen per Categorie</h3>
                        <div className="analytics-empty-state">
                            <p>Geen data beschikbaar</p>
                        </div>
                    </div>

                    <div className="analytics-card">
                        <h3>Urgentieverdeling</h3>
                        <div className="analytics-empty-state">
                            <p>Geen data beschikbaar</p>
                        </div>
                    </div>

                    <div className="analytics-card full-width-mobile">
                        <h3>Status Verdeling</h3>
                        <div className="analytics-empty-state">
                            <p>Geen data beschikbaar</p>
                        </div>
                    </div>
                </section>

                {/* Graphs */}
                {/*<div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">*/}

                {/*    <DataCard/>*/}
                {/*</div>*/}
            </main>
        </div>
        </>
    );
}

export default H_ReportsOverview;