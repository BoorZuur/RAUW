import React, { useState, useEffect } from 'react';
import axios from 'axios';
import InformationCard from "../components/InformationCard.jsx";
import HM_Nav from "../components/HM_Nav.jsx";
import { ChartColumn, ChartNoAxesCombined, Calendar} from 'lucide-react';

export default function H_ReportsOverview() {
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
        <div className="flex flex-col md:flex-row min-h-screen bg-primary-bg font-label antialiased text-primary-text">
            <HM_Nav/>
            <main className="flex-1 p-4 sm:p-6 md:p-8 w-full max-w-7xl mx-auto">

                <header
                    className="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 md:mb-8 gap-4 pb-4 border-b border-primary-border/40 lg:border-none">
                    <div className="flex gap-4 items-center">
                        <div
                            className="p-3 bg-primary-bg-cards rounded-xl text-2xl border border-primary-border shadow-sm shrink-0">
                            <ChartColumn/>
                        </div>
                        <div>
                            <h1 className="text-2xl sm:text-3xl font-bold font-headline text-primary-text">Statistiek
                                Rapporten</h1>
                            <p className="text-secondary-text text-xs sm:text-sm">Overzicht van incidenten en
                                prestaties</p>
                        </div>
                    </div>

                    <div
                        className="flex w-full lg:w-auto bg-primary-bg-cards p-1 rounded-xl border border-primary-border">
                        <button
                            className={`flex-1 lg:flex-none flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg text-sm sm:text-base font-bold transition-all cursor-pointer ${viewMode === 'month' ? 'bg-primary-accent text-white shadow-sm' : 'text-primary-text hover:bg-primary-bg/50'}`}
                            onClick={() => setViewMode('month')}
                        >
                            <Calendar className="w-4 h-4 shrink-0"/>
                            <span>Maand</span>
                        </button>
                        <button
                            className={`flex-1 lg:flex-none flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 rounded-lg text-sm sm:text-base font-bold transition-all cursor-pointer ${viewMode === 'year' ? 'bg-primary-accent text-white shadow-sm' : 'text-primary-text hover:bg-primary-bg/50'}`}
                            onClick={() => setViewMode('year')}
                        >
                            <ChartNoAxesCombined className="w-4 h-4 shrink-0"/>
                            <span>Jaar</span>
                        </button>
                    </div>
                </header>

                <div className="flex justify-center items-center gap-4 sm:gap-6 my-6 md:my-8 select-none">
                    <button
                        className="text-secondary-text hover:text-primary-accent text-4xl font-light h-10 w-10 flex items-center justify-center rounded-full hover:bg-primary-bg-cards transition-colors cursor-pointer"
                        onClick={handlePrev}
                    >
                        ‹
                    </button>
                    <span
                        className="capitalize text-primary-text font-bold text-lg sm:text-xl min-w-[140px] text-center">
                    {displayDate}
                </span>
                    <button
                        className="text-secondary-text hover:text-primary-accent text-4xl font-light h-10 w-10 flex items-center justify-center rounded-full hover:bg-primary-bg-cards transition-colors cursor-pointer"
                        onClick={handleNext}
                    >
                        ›
                    </button>
                </div>

                {loading && (
                    <div className="w-full text-center py-4 animate-pulse">
                        <p className="text-secondary-text text-sm">Rapporten laden...</p>
                    </div>
                )}

                <section
                    className={`transition-opacity duration-200 ${loading ? 'opacity-40 pointer-events-none' : 'opacity-100'}`}>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 w-full">
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
