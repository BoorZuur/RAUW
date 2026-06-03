import React, {useState} from 'react';

const LayoutGridIcon = ({className}) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round"
              d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
    </svg>
);

const UserIcon = ({className}) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
);

const SettingsIcon = ({className}) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
);

const ChartIcon = ({className}) => (
    <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
);

function BOANavbar() {
    const [isDarkMode, setIsDarkMode] = useState(false);
    const currentPath = "/handhaver/CommandCenter.jsx";

    const navItems = [
        {name: 'Command Center', href: '/handhaver/CommandCenter.jsx', icon: LayoutGridIcon},
        {name: 'Dienstprofiel', href: '/handhaver/Dienstprofiel.jsx', icon: UserIcon},
        {name: 'Sector Instellingen', href: '/handhaver/SectorInstellingen.jsx', icon: SettingsIcon},
        {name: 'Rapporten', href: '/handhaver/Rapporten.jsx', icon: ChartIcon},
    ];

    return (
        <aside
            className="w-64 h-screen bg-[#244335] text-white flex flex-col justify-between p-4 border-r border-emerald-900/30 select-none">

            {/* HEADER */}
            <div>
                <div className="flex items-center gap-3 px-2 py-4 mb-6">
                    <div className="w-10 h-10 bg-[#D4A325] rounded-xl flex items-center justify-center text-[#244335]">
                        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round"
                                  d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.536 9.636a3.5 3.5 0 000 4.728m-2.828-6.1a7 7 0 000 7.464"/>
                        </svg>
                    </div>
                    <div>
                        <h1 className="font-bold text-lg tracking-wide leading-none">RAUW</h1>
                        <span
                            className="text-xs text-emerald-300 font-medium tracking-widest uppercase">BOA Command</span>
                    </div>
                </div>

                {/* NAV */}
                <nav className="space-y-1">
                    {navItems.map((item) => {
                        const IconComponent = item.icon;
                        const isActive = currentPath === item.href;

                        return (
                            <a
                                key={item.name}
                                href={item.href}
                                className={`flex items-center justify-between px-3 py-3 rounded-xl transition-all duration-200 group text-sm font-medium ${
                                    isActive
                                        ? 'bg-[#2E5343] text-white'
                                        : 'text-emerald-200/80 hover:bg-[#2e5343]/50 hover:text-white'
                                }`}
                            >
                                <div className="flex items-center gap-3">
                                    <IconComponent
                                        className={`w-5 h-5 ${isActive ? 'text-white' : 'text-emerald-300 group-hover:text-white'}`}/>
                                    <span>{item.name}</span>
                                </div>

                                {isActive && (
                                    <span className="w-2 h-2 bg-[#D4A325] rounded-full"></span>
                                )}
                            </a>
                        );
                    })}
                </nav>
            </div>

            {/* 3. FOOTER */}
            <div className="border-t border-emerald-800/40 pt-4 space-y-2">

                {/* DARK/LIGHT THEME */}
                <div className="flex items-center justify-between px-3 py-3 text-sm font-medium text-emerald-200/80">
                    <div className="flex items-center gap-3">
                        <svg className="w-5 h-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Dagmodus</span>
                    </div>

                    <button
                        onClick={() => setIsDarkMode(!isDarkMode)}
                        className={`w-11 h-6 flex items-center rounded-full p-1 cursor-pointer transition-colors duration-300 ${
                            isDarkMode ? 'bg-[#D4A325]' : 'bg-stone-300'
                        }`}
                    >
                        <div
                            className={`bg-white w-4 h-4 rounded-full shadow-md transform transition-transform duration-300 ${
                                isDarkMode ? 'translate-x-5' : 'translate-x-0'
                            }`}/>
                    </button>
                </div>

                {/* LOGOUT */}
                <a
                    href="/logout"
                    className="flex items-center gap-3 px-3 py-3 text-sm font-medium text-emerald-200/80 hover:bg-[#2e5343]/50 hover:text-white rounded-xl transition-all"
                >
                    <svg className="w-5 h-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Uitloggen</span>
                </a>
            </div>

        </aside>
    );
}

export default BOANavbar;