import React, {useState} from 'react';
import UserCard from '../components/UserCard.jsx';
import HM_Nav from "../components/HM_Nav.jsx";

export default function FlagsDashboardPage() {
    const [flags, setFlags] = useState([
        {
            id: 1,
            issue_id: 104,
            comment_id: null,
            message_id: null,
            flag_source: "OFFICER",
            flag_reason: "Grof taalgebruik",
            matched_keyword: "klootzak",
            reported_user: {
                id: 42,
                username: "VervelendeBurger99",
                email: "v.burger@gmail.com",
                flag_count: 5
            },
            flagged_by_officer: {username: "Handhaver_Jan", badge_number: "ROT-4022"},
            counts_toward_review: true,
            action_taken: "In afwachting",
            flagged_at: "2026-06-08 10:15"
        },
        {
            id: 2,
            issue_id: null,
            comment_id: 452,
            message_id: null,
            flag_source: "OFFICER",
            flag_reason: "Ongepaste media",
            matched_keyword: null,
            reported_user: {
                id: 88,
                username: "Rotterdammer_010",
                email: "010feyenoord@live.nl",
                flag_count: 1
            },
            flagged_by_officer: {username: "Handhaver_Anouk", badge_number: "ROT-1109"},
            counts_toward_review: true,
            action_taken: "Verwijderd",
            flagged_at: "2026-06-07 14:22"
        }
    ]);

    const handleFlagAction = (flagId, action) => {
        setFlags(prevFlags =>
            prevFlags.map(f => {
                if (f.id === flagId) {
                    if (action === 'review_user' || action === 'delete_user') {
                        return {...f, action_taken: 'User Verwijderd'};
                    } else if (action === 'delete_content') {
                        return {...f, action_taken: 'Content Verwijderd'};
                    } else if (action === 'negeren') {
                        return {...f, action_taken: 'Genegeerd'};
                    }
                }
                return f;
            })
        );
    };

    return (
        <div className="flex flex-row min-h-screen w-full overflow-hidden bg-[#f4f4f3] app-layout">
            <HM_Nav role="manager"/>

            <main
                className="flex-1 ml-[260px] min-w-0 h-screen overflow-y-auto p-8 box-border main-content dashboard-container">
                <div
                    className="bg-white rounded-2xl border border-stone-200 shadow-sm overflow-hidden w-full max-w-7xl mx-auto">

                    {/* Header */}
                    <div
                        className="p-6 border-b border-stone-100 bg-stone-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h2 className="text-xl font-bold text-stone-900 tracking-tight">Content Moderatie &
                                Flags</h2>
                            <p className="text-sm text-stone-500 mt-0.5">Overzicht van alle gerapporteerde signalen die
                                een review vereisen.</p>
                        </div>
                        <span
                            className="bg-red-50 text-red-700 font-semibold text-xs px-3 py-1 rounded-full border border-red-100 shadow-sm">
                            {flags.filter(f => f.action_taken === "In afwachting").length} Openstaand
                        </span>
                    </div>

                    {/* Tabel Elementen */}
                    <div className="overflow-x-auto w-full">
                        <table className="w-full text-left border-collapse table-fixed min-w-[950px]">
                            <thead>
                            <tr className="border-b border-stone-200 text-stone-500 text-xs font-bold uppercase tracking-wider bg-stone-50/70">
                                <th className="py-4 px-6 w-[65px]">ID</th>
                                <th className="py-4 px-6 w-[15%]">Gekoppeld aan</th>
                                <th className="py-4 px-6 w-[22%]">Reden & Trigger</th>
                                <th className="py-4 px-6 w-[22%]">Gebruiker</th>
                                <th className="py-4 px-6 w-[17%]">Geflagged door</th>
                                <th className="py-4 px-6 w-[12%]">Status</th>
                                <th className="py-4 px-6 w-[12%] text-right">Acties</th>
                            </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100 text-sm text-stone-600">
                            {flags.map((flag) => (
                                <UserCard key={flag.id} flag={flag} onAction={handleFlagAction}/>
                            ))}
                            </tbody>
                        </table>
                    </div>

                </div>
            </main>
        </div>
    );
}