import React, { useState, useEffect } from 'react';
import axios from 'axios';
import InformationCard from "../components/InformationCard.jsx";
import HM_Nav from "../components/HM_Nav.jsx";
import { ChartColumn, ChartNoAxesCombined, Calendar} from 'lucide-react';

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
                    headers: {'Authorization': `Bearer ${token}`}
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
        ? currentDate.toLocaleDateString('nl-NL', {month: 'long', year: 'numeric'})
        : currentDate.getFullYear().toString();

    const totalIssues = issues.length;
    const resolvedCount = issues.filter(issue => issue.status === 'opgelost' || issue.status === 'gesloten').length;
    const solutionPercentage = totalIssues > 0 ? Math.round((resolvedCount / totalIssues) * 100) + '%' : '0%';
    const highUrgencyCount = issues.filter(issue => issue.priority === 1).length;

    const resolvedIssuesReport = issues.filter(issue => issue.status === 'in_behandeling' || issue.status === 'opgelost' || issue.status === 'gesloten');
    let avgResponseTimeText = "N/A";
    if (resolvedIssuesReport.length > 0) {
        let totalTime = 0;
        let count = 0;
        resolvedIssuesReport.forEach(issue => {
            if (issue.updated_at && issue.created_at) {
                const updated = new Date(issue.updated_at);
                const created = new Date(issue.created_at);
                if (updated > created) {
                    totalTime += (updated - created) / (1000 * 60);
                    count++;
                }
            }
        });
        if (count > 0) {
            avgResponseTimeText = `${Math.round(totalTime / count)} min`;
        }
    }

    return (
        <div className="flex min-h-screen bg-primary-bg">
            <HM_Nav/>
            <main className="flex-1 p-8">
                <header className="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <div className="flex gap-4 items-center">
                        <div
                            className="p-3 bg-primary-bg-cards rounded-xl text-2xl border border-primary-border shadow-sm">
                            <ChartColumn />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold text-primary-text">Statistiek Rapporten</h1>
                            <p className="text-secondary-text">Overzicht van incidenten en prestaties</p>
                        </div>
                    </div>

                    <div className="flex bg-primary-bg-cards p-1 rounded-xl border border-primary-border">
                        <button
                            className={`px-6 py-2 rounded-lg font-bold transition-all ${viewMode === 'month' ? 'bg-primary-accent text-white' : 'text-primary-text'}`}
                            onClick={() => setViewMode('month')}
                        >
                            <Calendar />Maand
                        </button>
                        <button
                            className={`px-6 py-2 rounded-lg font-bold transition-all ${viewMode === 'year' ? 'bg-primary-accent text-white' : 'text-primary-text'}`}
                            onClick={() => setViewMode('year')}
                        >
                            <ChartNoAxesCombined />Jaar
                        </button>
                    </div>
                </header>

                <div className="flex justify-center items-center gap-6 my-8">
                    <button
                        className="text-primary-text hover:text-primary-accent text-3xl font-bold px-4 transition-colors"
                        onClick={handlePrev}>‹
                    </button>
                    <span className="capitalize text-primary-text font-bold text-xl">{displayDate}</span>
                    <button
                        className="text-primary-text hover:text-primary-accent text-3xl font-bold px-4 transition-colors"
                        onClick={handleNext}>›
                    </button>
                </div>

                {loading && <p className="text-secondary-text text-sm">Rapporten laden...</p>}

                <section className={loading ? 'opacity-50 pointer-events-none' : ''}>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 w-full">
                        <InformationCard
                            titel="Totaal Meldingen"
                            aantal={totalIssues.toString()}
                            kleur="text-primary-text"
                        />
                        <InformationCard
                            titel="Oplossingspercentage"
                            aantal={solutionPercentage}
                            kleur="text-primary-accent"
                        />
                        <InformationCard
                            titel="Afgehandeld"
                            aantal={resolvedCount.toString()}
                            kleur="text-secondary-accent"
                        />
                        <InformationCard
                            titel="Hoge Urgentie"
                            aantal={highUrgencyCount.toString()}
                            kleur="text-red-500"
                        />
                        <InformationCard
                            titel="Gem. Opvolgtijd"
                            aantal={avgResponseTimeText}
                            kleur="text-blue-500"
                        />
                    </div>
                </section>
            </main>
        </div>
    );
}

export default H_ReportsOverview;