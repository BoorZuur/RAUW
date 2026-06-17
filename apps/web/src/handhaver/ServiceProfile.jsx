import React, { useState, useEffect } from 'react';
import axios from 'axios';
import ShiftStatCard from '../components/ShiftStatCard.jsx';
import HM_Nav from "../components/HM_Nav.jsx";
import { Calendar, ShieldAlert, CircleCheck, ChartColumn, MessageCircleQuestionMark, Eye, ShieldUser } from 'lucide-react';

export default function ServiceProfile() {
    const [officer, setOfficer] = useState(null);
    const [issues, setIssues] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const token = localStorage.getItem('auth_token');

                const meRes = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/auth/me`, {
                    headers: {'Authorization': `Bearer ${token}`}
                });
                const officerData = meRes.data?.profile || meRes.data;
                setOfficer(officerData);

                const issuesRes = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/issues?assigned_officer_id=${officerData.id}`, {
                    headers: {'Authorization': `Bearer ${token}`}
                });

                const issuesData = issuesRes.data.data || issuesRes.data;
                setIssues(Array.isArray(issuesData) ? issuesData : []);
            } catch (error) {
                console.error("Error fetching profile data:", error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    // Issues are already filtered to this officer by the API
    const myIssues = issues;

    const totalAssigned = myIssues.length;
    const resolvedCount = myIssues.filter(i => i.status === 'opgelost' || i.status === 'gesloten').length;
    const inProgressCount = myIssues.filter(i => i.status === 'in_behandeling').length;

    // Issues created this month
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    const thisMonthCount = myIssues.filter(i => {
        if (!i.created_at) return false;
        const d = new Date(i.created_at);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    }).length;

    // Recent activity: sort my issues by updated_at descending, take top 5
    const recentActivity = [...myIssues].sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at)).slice(0, 5);

    return (
        <div className="flex flex-col md:flex-row min-h-screen bg-primary-bg font-label antialiased text-primary-text">
            <HM_Nav/>

            <main className="flex-1 p-4 sm:p-6 md:p-8 w-full max-w-7xl mx-auto flex flex-col gap-6 md:gap-8">

                <header
                    className="bg-primary-bg-cards border border-primary-border p-5 sm:p-6 rounded-2xl flex flex-col sm:flex-row items-center text-center sm:text-left gap-4 sm:gap-6 shadow-sm">
                    <div
                        className="p-4 bg-primary-bg rounded-xl border border-primary-border text-primary-text shrink-0">
                        <ShieldUser className="w-6 h-6"/>
                    </div>
                    <div className="min-w-0 w-full">
                        <h2 className="text-xl sm:text-2xl font-bold font-headline text-primary-text truncate">
                            {officer?.username || "Laden..."}
                        </h2>
                        <p className="text-sm text-secondary-text truncate">
                            {officer?.email || ""}
                        </p>
                        <span
                            className="inline-block mt-2 px-3 py-1 bg-primary-accent/10 text-primary-accent text-xs font-bold rounded-lg uppercase tracking-wider">
                        Buitengewoon Opsporingsambtenaar
                    </span>
                    </div>
                </header>

                <section>
                    <h3 className="text-base sm:text-lg font-bold font-headline text-primary-text mb-4">Dienststatistieken</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <ShiftStatCard titel="Totaal Toegewezen" aantal={totalAssigned} iconBgColor="bg-primary-bg">
                            <Eye className="w-6 h-6"/>
                        </ShiftStatCard>
                        <ShiftStatCard titel="Updates Geplaatst" aantal={0} iconBgColor="bg-primary-bg">
                            <MessageCircleQuestionMark className="w-6 h-6"/>
                        </ShiftStatCard>
                        <ShiftStatCard titel="Actieve Surveillances" aantal={0} iconBgColor="bg-primary-bg">
                            <ShieldAlert className="w-6 h-6"/>
                        </ShiftStatCard>
                        <ShiftStatCard titel="Afgehandeld" aantal={resolvedCount} iconBgColor="bg-primary-bg">
                            <ChartColumn className="w-6 h-6"/>
                        </ShiftStatCard>
                        <ShiftStatCard titel="In Behandeling" aantal={inProgressCount} iconBgColor="bg-primary-bg">
                            <CircleCheck className="w-6 h-6"/>
                        </ShiftStatCard>
                        <ShiftStatCard titel="Deze Maand" aantal={thisMonthCount} iconBgColor="bg-primary-bg">
                            <Calendar className="w-6 h-6"/>
                        </ShiftStatCard>
                    </div>
                </section>

                <section className="flex-1">
                    <h3 className="text-base sm:text-lg font-bold font-headline text-primary-text mb-4">Recente
                        Activiteit</h3>
                    <div className="bg-primary-bg-cards border border-primary-border rounded-2xl p-5 sm:p-6 shadow-sm">
                        {loading ? (
                            <p className="text-secondary-text text-sm font-body">Laden...</p>
                        ) : recentActivity.length === 0 ? (
                            <p className="text-secondary-text text-sm font-body">Nog geen toegewezen incidenten</p>
                        ) : (
                            <ul className="space-y-4">
                                {recentActivity.map(issue => (
                                    <li key={issue.id}
                                        className="border-b border-primary-border pb-4 last:border-0 last:pb-0">
                                        <div className="flex justify-between items-start gap-4 mb-1">
                                            <strong
                                                className="text-sm sm:text-base font-semibold text-primary-text break-words min-w-0 flex-1">
                                                {issue.title || 'Onbekend incident'}
                                            </strong>
                                            <span
                                                className="text-xs text-secondary-text shrink-0 whitespace-nowrap pt-0.5">
                                            {issue.updated_at ? new Date(issue.updated_at).toLocaleDateString() : ''}
                                        </span>
                                        </div>
                                        <p className="text-xs sm:text-sm text-secondary-text capitalize font-body">
                                            Status: <span
                                            className="font-medium text-primary-text">{issue.status.replace('_', ' ')}</span>
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>
            </main>
        </div>
    );
}