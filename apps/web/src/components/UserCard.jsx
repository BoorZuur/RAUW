import React, {useState, useRef, useEffect} from 'react';

export default function UserCard({flag, onAction}) {
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const menuRef = useRef(null);

    const isProblematicUser = flag.reported_user?.flag_count >= 3;
    const isProcessed = flag.action_taken !== "In afwachting";
    
    useEffect(() => {
        function handleClickOutside(event) {
            if (menuRef.current && !menuRef.current.contains(event.target)) {
                setIsMenuOpen(false);
            }
        }

        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

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

    const handleMenuAction = (actionType) => {
        onAction(flag.id, actionType);
        setIsMenuOpen(false);
    };

    return (
        <tr className="hover:bg-stone-50/40 transition-colors">
            {/* 1. ID */}
            <td className="py-4 px-6 font-bold text-stone-900 font-mono w-[70px]">
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
                {!flag.issue_id && !flag.comment_id && !flag.message_id &&
                    <span className="text-stone-400 italic">Nvt</span>}
            </td>

            {/* 3. Reden & Trigger */}
            <td className="py-4 px-6">
                <div className="font-semibold text-stone-800 truncate">{flag.flag_reason}</div>
                {flag.matched_keyword ? (
                    <div className="text-xs text-stone-800 mt-0.5 font-medium truncate">
                        Trigger: <code
                        className=" text-red-500 font-mono px-1.5 py-0.5 rounded text-[11px]">"{flag.matched_keyword}"</code>
                    </div>
                ) : (
                    <div className="text-xs text-stone-400 mt-0.5 italic">Geen triggerwoord</div>
                )}
            </td>

            {/* 4. Gerapporteerde Gebruiker */}
            <td className="py-4 px-6">
                <div className="flex flex-col gap-0.5 min-w-0">
                    <span className="font-semibold text-stone-900 truncate">
                        {flag.reported_user?.username || "Onbekend"}
                    </span>
                    <div className="flex items-center gap-1.5 min-w-0">
                        <span className="text-xs text-stone-400 truncate max-w-[140px]">
                            {flag.reported_user?.email}
                        </span>
                        {isProblematicUser && (
                            <span
                                className="bg-red-100 text-red-700 text-[10px] font-bold px-1.5 py-0.5 rounded whitespace-nowrap">
                                {flag.reported_user.flag_count}x
                            </span>
                        )}
                    </div>
                </div>
            </td>

            {/* 5. Geflagged door */}
            <td className="py-4 px-6">
                <div className="text-stone-800 font-medium truncate">
                    {flag.flagged_by_officer?.username || "Onbekend"}
                </div>
                <div className="text-xs text-stone-400 font-mono">
                    Badge: {flag.flagged_by_officer?.badge_number || "Nvt"}
                </div>
            </td>

            {/* 6. Status */}
            <td className="py-4 px-6">
                <span
                    className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap ${getStatusStyle(flag.action_taken)}`}>
                    {flag.action_taken}
                </span>
            </td>

            {/* 7. Dropdown */}
            <td className="py-4 px-6 text-right relative" menu-align-container="true">
                {isProcessed ? (
                    <span className="text-xs text-stone-400 italic pr-2 font-medium">Afgehandeld</span>
                ) : (
                    <div className="inline-block text-left" ref={menuRef}>
                        <button
                            onClick={() => setIsMenuOpen(!isMenuOpen)}
                            className="bg-white border border-stone-200 text-stone-700 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm hover:bg-stone-50 flex items-center gap-1.5 ml-auto transition-colors"
                        >
                            Acties
                            <svg
                                className={`w-3 h-3 text-stone-500 transition-transform ${isMenuOpen ? 'rotate-180' : ''}`}
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5"
                                      d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        {isMenuOpen && (
                            <div
                                className="absolute right-6 mt-1 w-44 bg-white rounded-xl border border-stone-200 shadow-lg z-50 overflow-hidden py-1 animate-in fade-in duration-100 text-left">
                                <button
                                    onClick={() => handleMenuAction('negeren')}
                                    className="w-full px-4 py-2 text-xs text-left text-stone-700 hover:bg-green-50 hover:text-green-700 font-semibold transition-colors"
                                >
                                    Signaal negeren
                                </button>
                                <button
                                    onClick={() => handleMenuAction('delete_content')}
                                    className="w-full px-4 py-2 text-xs text-left text-red-600 hover:bg-red-50 font-semibold transition-colors border-t border-stone-100"
                                >
                                    Verwijder Content
                                </button>
                                <button
                                    onClick={() => handleMenuAction('delete_user')}
                                    className="w-full px-4 py-2 text-xs text-left text-white bg-red-600 hover:bg-red-700 font-semibold transition-colors"
                                >
                                    Verwijder Gebruiker
                                </button>
                            </div>
                        )}
                    </div>
                )}
            </td>
        </tr>
    );
}