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
        <div className="flex min-h-screen bg-primary-bg">
            <HM_Nav/>
            <main className="flex-1 p-8">

                <header
                    className="bg-primary-bg-cards border border-primary-border p-6 rounded-2xl mb-8 flex items-center gap-6">
                    <div className="p-4 bg-primary-bg rounded-xl border border-primary-border text-primary-text">
                        <ShieldUser />
                    </div>
                    <div>
                        <h2 className="text-2xl font-bold text-primary-text">{officer?.username || "Laden..."}</h2>
                        <p className="text-secondary-text">{officer?.email || ""}</p>
                        <span
                            className="inline-block mt-2 px-3 py-1 bg-primary-accent/10 text-primary-accent text-xs font-bold rounded-lg">Buitengewoon Opsporingsambtenaar</span>
                    </div>
                </header>

                <section className="mb-8">
                    <h3 className="text-lg font-bold text-primary-text mb-4">Dienststatistieken</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <ShiftStatCard titel="Totaal Toegewezen" aantal={totalAssigned} iconBgColor="bg-primary-bg">
                            <Eye className="w-6 h-6" />
                        </ShiftStatCard>
                        <ShiftStatCard titel="Updates Geplaatst" aantal={0} iconBgColor="bg-primary-bg">
                            <MessageCircleQuestionMark className="w-6 h-6" />
                        </ShiftStatCard>
                        <ShiftStatCard titel="Actieve Surveillances" aantal={0} iconBgColor="bg-primary-bg">
                            <ShieldAlert className="w-6 h-6" />
                        </ShiftStatCard>
                        <ShiftStatCard titel="Afgehandeld" aantal={resolvedCount} iconBgColor="bg-primary-bg">
                            <ChartColumn className="w-6 h-6" />
                        </ShiftStatCard>
                        <ShiftStatCard titel="In Behandeling" aantal={inProgressCount} iconBgColor="bg-primary-bg">
                            <CircleCheck className="w-6 h-6" />
                        </ShiftStatCard>
                        <ShiftStatCard titel="Deze Maand" aantal={thisMonthCount} iconBgColor="bg-primary-bg">
                            <Calendar className="w-6 h-6" />
                        </ShiftStatCard>
                    </div>
                </section>

                <section>
                    <h3 className="text-lg font-bold text-primary-text mb-4">Recente Activiteit</h3>
                    <div className="bg-primary-bg-cards border border-primary-border rounded-2xl p-6">
                        {loading ? (
                            <p className="text-secondary-text text-sm">Laden...</p>
                        ) : recentActivity.length === 0 ? (
                            <p className="text-secondary-text text-sm">Nog geen toegewezen incidenten</p>
                        ) : (
                            <ul className="space-y-4">
                                {recentActivity.map(issue => (
                                    <li key={issue.id}
                                        className="border-b border-primary-border pb-4 last:border-0 last:pb-0">
                                        <div className="flex justify-between items-center mb-1">
                                            <strong
                                                className="text-primary-text">{issue.title || 'Onbekend incident'}</strong>
                                            <span className="text-xs text-secondary-text">
                                            {issue.updated_at ? new Date(issue.updated_at).toLocaleDateString() : ''}
                                        </span>
                                        </div>
                                        <p className="text-sm text-secondary-text capitalize">Status: {issue.status.replace('_', ' ')}</p>
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