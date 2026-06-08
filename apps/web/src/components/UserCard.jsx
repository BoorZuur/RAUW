import React from 'react';

export default function UserCard({flag, onAction}) {
    const isProblematicUser = flag.reported_user?.flag_count >= 3;

    // Check of er al een actie is uitgevoerd op deze flag
    const isProcessed = flag.action_taken !== "In afwachting";

    // Verbeterde colorcoding voor de status badges
    const getStatusStyle = (status) => {
        switch (status) {
            case 'In afwachting':
                return 'bg-orange-50 text-orange-700 border border-orange-200';
            case 'In Review gezet':
                return 'bg-amber-50 text-amber-700 border border-amber-200';
            case 'Genegeerd':
                return 'bg-green-50 text-green-700 border border-green-200';
            case 'Content Verwijderd':
            case 'User Verwijderd':
            case 'Verwijderd':
                return 'bg-red-50 text-red-700 border border-red-200';
            default:
                return 'bg-stone-100 text-stone-600 border border-stone-200';
        }
    };

    return (
        <tr className="hover:bg-stone-50/40 transition-colors">
            {/* 1. ID */}
            <td className="py-4 px-6 font-bold text-stone-900 font-mono">
                #{flag.id}
            </td>

            {/* 2. Gekoppeld aan */}
            <td className="py-4 px-6 text-xs font-mono">
                {flag.issue_id && <div className="text-amber-700 bg-amber-50 px-2 py-1 rounded inline-block">Issue
                    #{flag.issue_id}</div>}
                {flag.comment_id && <div className="text-blue-700 bg-blue-50 px-2 py-1 rounded inline-block">Reactie
                    #{flag.comment_id}</div>}
                {flag.message_id && <div className="text-purple-700 bg-purple-50 px-2 py-1 rounded inline-block">Bericht
                    #{flag.message_id}</div>}
            </td>

            {/* 3. Reden & Trigger */}
            <td className="py-4 px-6">
                <div className="font-semibold text-stone-800">{flag.flag_reason}</div>
                {flag.matched_keyword && (
                    <div className="text-xs text-red-500 mt-0.5 font-medium">
                        Trigger: <code
                        className="bg-stone-900 text-white font-mono px-1.5 py-0.5 rounded text-[11px]">"{flag.matched_keyword}"</code>
                    </div>
                )}
            </td>

            {/* 4. Gerapporteerde Gebruiker */}
            <td className="py-4 px-6">
                <div className="flex flex-col gap-1">
                    <span className="font-semibold text-stone-900">
                        {flag.reported_user?.username || "Onbekend"}
                    </span>
                    <div className="flex items-center gap-1.5">
                        <span className="text-xs text-stone-400">
                            {flag.reported_user?.email}
                        </span>
                        {isProblematicUser && (
                            <span
                                className="bg-red-100 text-red-700 text-[10px] font-bold px-1.5 py-0.5 rounded animate-pulse">
                                {flag.reported_user.flag_count}x geflagged
                            </span>
                        )}
                    </div>
                </div>
            </td>

            {/* 5. Geflagged door */}
            <td className="py-4 px-6">
                <div className="text-stone-800 font-medium">
                    {flag.flagged_by_officer?.username || "Onbekend"}
                </div>
                <div className="text-xs text-stone-400 font-mono">
                    Badge: {flag.flagged_by_officer?.badge_number || "Nvt"}
                </div>
            </td>

            {/* 6. Status (Nu ALTIJD op 1 regel via whitespace-nowrap) */}
            <td className="py-4 px-6">
                <span
                    className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap ${getStatusStyle(flag.action_taken)}`}>
                    {flag.action_taken}
                </span>
            </td>

            {/* 7. Actie-knoppen (Worden disabled zodra isProcessed true is) */}
            <td className="py-4 px-6 text-right whitespace-nowrap">
                <div className="flex justify-end gap-2">
                    <button
                        onClick={() => onAction(flag.id, 'negeren')}
                        disabled={isProcessed}
                        className={`px-3 py-1.5 border rounded-lg text-xs font-bold transition-colors ${
                            isProcessed
                                ? 'bg-stone-50 border-stone-200 text-stone-400 cursor-not-allowed opacity-60'
                                : 'border-stone-200 text-stone-600 hover:bg-green-50 hover:text-green-700 hover:border-green-200'
                        }`}
                    >
                        Negeren
                    </button>
                    <button
                        onClick={() => onAction(flag.id, 'delete_content')}
                        disabled={isProcessed}
                        className={`px-3 py-1.5 border rounded-lg text-xs font-bold transition-colors ${
                            isProcessed
                                ? 'bg-stone-50 border-stone-200 text-stone-400 cursor-not-allowed opacity-60'
                                : 'border-red-200 text-red-600 hover:bg-red-50'
                        }`}
                    >
                        Verwijder Content
                    </button>
                    <button
                        onClick={() => onAction(flag.id, 'delete_user')}
                        disabled={isProcessed}
                        className={`px-3 py-1.5 border rounded-lg text-xs font-bold shadow-sm transition-colors ${
                            isProcessed
                                ? 'bg-stone-50 border-stone-200 text-stone-400 cursor-not-allowed opacity-60 shadow-none'
                                : 'bg-red-600 border-transparent text-white hover:bg-red-700'
                        }`}
                    >
                        Verwijder User
                    </button>
                </div>
            </td>
        </tr>
    );
}