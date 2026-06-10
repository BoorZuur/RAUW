import React from 'react';
import ShiftStatCard from '../components/ShiftStatCard.jsx';
import HM_Nav from "../components/HM_Nav.jsx";
import "./Handhaver_styling.css";

export default function ServiceProfile() {
    return (
        <>
            <HM_Nav></HM_Nav>
            <main className="main-content profile-container">

                <header className="profile-user-card">
                    <div className="profile-avatar-box">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                        </svg>
                    </div>
                    <div className="profile-user-info">
                        <h2>Raven</h2>
                        <p className="profile-email">boa@rauw.nl</p>
                        <span className="profile-role-badge">Buitengewoon Opsporingsambtenaar</span>
                    </div>
                </header>

                <section className="profile-section">
                    <h3 className="section-title">Dienststatistieken</h3>
                    <div className="profile-metrics-grid">
                                <ShiftStatCard
                                    titel="Totaal Toegewezen"
                                    aantal={0}
                                    iconBgColor="bg-stone-100"
                                    icon={
                                        <svg className="w-5 h-5 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round"
                                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    }
                                />
                                <ShiftStatCard
                                    titel="Updates Geplaatst"
                                    aantal={0}
                                    iconBgColor="bg-amber-50"
                                    icon={
                                        <svg className="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round"
                                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                    }
                                />
                                <ShiftStatCard
                                    titel="Actieve Surveillances"
                                    aantal={0}
                                    iconBgColor="bg-emerald-50"
                                    icon={
                                        <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path strokeLinecap="round" strokeLinejoin="round"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    }
                                />
                                <ShiftStatCard
                                    titel="Afgehandeld"
                                    aantal={0}
                                    iconBgColor="bg-emerald-50"
                                    icon={
                                        <svg className="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round"
                                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    }
                                />
                                <ShiftStatCard
                                    titel="In Behandeling"
                                    aantal={0}
                                    iconBgColor="bg-amber-50"
                                    icon={
                                        <svg className="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round"
                                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    }
                                />
                                <ShiftStatCard
                                    titel="Deze Maand"
                                    aantal={0}
                                    iconBgColor="bg-stone-100"
                                    icon={
                                        <svg className="w-5 h-5 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round"
                                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    }
                                />
                    </div>
                </section>

                <section className="profile-section">
                    <h3 className="section-title">Recente Activiteit</h3>

                    <div className="activity-timeline-card">
                        <div className="timeline-empty-state">
                            <p>Nog geen toegewezen incidenten</p>
                        </div>
                    </div>
                </section>



            </main>
        </>
    );
}