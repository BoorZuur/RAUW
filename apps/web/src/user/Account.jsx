import React, { useState } from 'react';
import Navbar from '../components/U_Nav.jsx';

export default function Profile() {
    const [isDark, setIsDark] = useState(false);
    const [name, setName] = useState('Raven');

    // Toggle dark mode logic
    const toggleTheme = () => {
        setIsDark(!isDark);
        document.body.style.backgroundColor = isDark ? 'var(--primary-bg)' : 'var(--primary-bg-d)';
    };

    return (
        <div style={{ backgroundColor: 'var(--primary-bg)', minHeight: '100vh' }}>
            <Navbar toggleTheme={toggleTheme} isDark={isDark} />

            <main className="pt-32 px-10">
                {/* Profile Card using your card1 concept */}
                <div className="card1 w-full max-w-2xl mx-auto p-6 mb-8 items-center">
                    <label className="w-20 h-20 rounded-full bg-(--primary-border-cards)] flex items-center justify-center cursor-pointer">
                        📷 <input type="file" className="hidden" />
                    </label>
                    <input
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        className="text-2xl font-bold bg-transparent border-none focus:outline-none"
                        style={{ color: 'var(--primary-text)' }}
                    />
                    <button className="text-xs px-4 py-2 border rounded-xl" style={{ borderColor: 'var(--primary-border-cards)', color: 'var(--secondary-text)' }}>
                        Instellingen
                    </button>
                </div>

                {/* Reports Tabs Filter */}
                <div className="flex gap-4 justify-center mb-10">
                    {['Nieuw', 'In behandeling', 'Surveillance-route', 'Opgelost'].map(tab => (
                        <button key={tab} className="px-6 py-2 rounded-xl text-sm font-bold border-2" style={{ borderColor: 'var(--primary-border-cards)', color: 'var(--primary-text)' }}>
                            {tab}
                        </button>
                    ))}
                </div>
            </main>
        </div>
    );
}