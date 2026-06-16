import React, { useState, useEffect } from 'react';
import axios from 'axios';
import InformationCard from "../components/InformationCard.jsx";
import "./Handhaver_styling.css"
import "../App.css"
import HM_Nav from "../components/HM_Nav.jsx";

function H_ReportsOverview() {
    const [issues, setIssues] = useState([]);
    const [loading, setLoading] = useState(true);
    const [viewMode, setViewMode] = useState('month'); // 'month' or 'year'
    const [currentDate, setCurrentDate] = useState(new Date());

    useEffect(() => {
        const fetchIssues = async () => {
            setLoading(true);
            try {
                const token = localStorage.getItem('auth_token');
                
                let dateFrom = new Date(currentDate);
                let dateTo = new Date(currentDate);
                
                if (viewMode === 'month') {
                    dateFrom.setDate(1);
                    dateTo.setMonth(dateTo.getMonth() + 1);
                    dateTo.setDate(0);
                } else {
                    dateFrom.setMonth(0, 1);
                    dateTo.setMonth(11, 31);
                }
                
                const fromStr = dateFrom.toISOString().split('T')[0];
                const toStr = dateTo.toISOString().split('T')[0];
                
                const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/issues?date_from=${fromStr}&date_to=${toStr}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                
                const issuesData = response.data.data || response.data;
                setIssues(Array.isArray(issuesData) ? issuesData : []);
            } catch (error) {
                console.error("Error fetching issues:", error);
            } finally {
                setLoading(false);
            }
        };

        fetchIssues();
    }, [viewMode, currentDate]);

    const handlePrev = () => {
        setCurrentDate(prev => {
            const newDate = new Date(prev);
            if (viewMode === 'month') newDate.setMonth(newDate.getMonth() - 1);
            else newDate.setFullYear(newDate.getFullYear() - 1);
            return newDate;
        });
    };

    const handleNext = () => {
        setCurrentDate(prev => {
            const newDate = new Date(prev);
            if (viewMode === 'month') newDate.setMonth(newDate.getMonth() + 1);
            else newDate.setFullYear(newDate.getFullYear() + 1);
            return newDate;
        });
    };

    const displayDate = viewMode === 'month' 
        ? currentDate.toLocaleDateString('nl-NL', { month: 'long', year: 'numeric' })
        : currentDate.getFullYear().toString();

    const totalIssues = issues.length;
    const resolvedCount = issues.filter(issue => issue.status === 'opgelost' || issue.status === 'gesloten').length;
    const solutionPercentage = totalIssues > 0 ? Math.round((resolvedCount / totalIssues) * 100) + '%' : '0%';
    const highUrgencyCount = issues.filter(issue => issue.priority === 1).length;

    // Basic aggregations for the charts
    const categoryCounts = {};
    const urgencyCounts = {};
    const statusCounts = {};

    issues.forEach(issue => {
        const cat = issue.category?.name || 'Onbekend';
        categoryCounts[cat] = (categoryCounts[cat] || 0) + 1;

        const prio = issue.priority === 1 ? 'Hoog' : (issue.priority === 2 ? 'Middel' : 'Laag');
        urgencyCounts[prio] = (urgencyCounts[prio] || 0) + 1;

        const st = issue.status || 'Onbekend';
        statusCounts[st] = (statusCounts[st] || 0) + 1;
    });

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
                        <button 
                            className={`btn-toggle ${viewMode === 'month' ? 'active' : ''}`}
                            onClick={() => setViewMode('month')}
                        >
                            📅 Maand
                        </button>
                        <button 
                            className={`btn-toggle ${viewMode === 'year' ? 'active' : ''}`}
                            onClick={() => setViewMode('year')}
                        >
                            📈 Jaar
                        </button>
                    </div>
                </header>

                <div className="date-navigator flex justify-center items-center gap-4 my-6">
                    <button className="nav-arrow text-[var(--text-dark)] hover:text-emerald-600 text-3xl font-bold px-4" onClick={handlePrev}>‹</button>
                    <span className="current-date capitalize text-[var(--text-dark)] font-bold text-xl">{displayDate}</span>
                    <button className="nav-arrow text-[var(--text-dark)] hover:text-emerald-600 text-3xl font-bold px-4" onClick={handleNext}>›</button>
                </div>

                {loading && <p className="text-stone-500 text-sm">Rapporten laden...</p>}

                <section className={loading ? 'opacity-50 pointer-events-none' : ''}>
                <div className="flex flex-wrap gap-4 w-full">
                    <InformationCard
                        titel="Totaal Meldingen"
                        aantal={totalIssues.toString()}
                        kleur="text-stone-900"
                    />

                    <InformationCard
                        titel="Oplossingspercentage"
                        aantal={solutionPercentage}
                        kleur="text-yellow-500"
                    />

                    <InformationCard
                        titel="Afgehandeld"
                        aantal={resolvedCount.toString()}
                        kleur="text-emerald-600"
                    />

                    <InformationCard
                        titel="Hoge Urgentie"
                        aantal={highUrgencyCount.toString()}
                        kleur="text-red-600"
                    />
                </div>
                </section>

                <section className={`reports-charts-grid ${loading ? 'opacity-50 pointer-events-none' : ''}`}>
                    <div className="analytics-card">
                        <h3>Meldingen per Categorie</h3>
                        {Object.keys(categoryCounts).length > 0 ? (
                            <ul className="mt-4 space-y-2">
                                {Object.entries(categoryCounts).map(([cat, count]) => (
                                    <li key={cat} className="flex justify-between text-sm">
                                        <span>{cat}</span>
                                        <span className="font-bold">{count}</span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="analytics-empty-state">
                                <p>Geen data beschikbaar in deze periode</p>
                            </div>
                        )}
                    </div>

                    <div className="analytics-card">
                        <h3>Urgentieverdeling</h3>
                        {Object.keys(urgencyCounts).length > 0 ? (
                            <ul className="mt-4 space-y-2">
                                {Object.entries(urgencyCounts).map(([prio, count]) => (
                                    <li key={prio} className="flex justify-between text-sm">
                                        <span>{prio}</span>
                                        <span className="font-bold">{count}</span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="analytics-empty-state">
                                <p>Geen data beschikbaar in deze periode</p>
                            </div>
                        )}
                    </div>

                    <div className="analytics-card full-width-mobile">
                        <h3>Status Verdeling</h3>
                        {Object.keys(statusCounts).length > 0 ? (
                            <ul className="mt-4 space-y-2">
                                {Object.entries(statusCounts).map(([st, count]) => (
                                    <li key={st} className="flex justify-between text-sm">
                                        <span className="capitalize">{st.replace('_', ' ')}</span>
                                        <span className="font-bold">{count}</span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="analytics-empty-state">
                                <p>Geen data beschikbaar in deze periode</p>
                            </div>
                        )}
                    </div>
                </section>

            </main>
        </div>
        </>
    );
}

export default H_ReportsOverview;