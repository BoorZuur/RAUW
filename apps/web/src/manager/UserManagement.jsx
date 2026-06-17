import "../App.css"
import HM_Nav from "../components/HM_Nav.jsx";
import "./Manager_styling.css"

function UserManagement() {
    return (
        <>
            <HM_Nav></HM_Nav>
            <main className="management-container">

                <div className="left-column">

                    <header className="page-header">
                    </header>

                    <div className="search-bar-wrapper">
                        <span className="search-icon">🔍</span>
                        <input type="text" placeholder="Zoek met badge nummer..." className="search-input"/>
                    </div>

                    <div className="officer-card-vertical">
                        <div className="card-badge">Badge nr. 192830</div>

                        <div className="shield-avatar-large">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>

                        <h2 className="officer-name">Raven</h2>
                        <p className="officer-email">boa@rauw.nl</p>
                        <p className="officer-role">Buitengewoon Opsporingsambtenaar</p>
                        <div className="location-tag">Rotterdam-West</div>

                        <div className="card-actions">
                            <button className="btn btn-promote">
                                <span className="arrow-up-icon">↑</span> Update naar manager
                            </button>
                            <button className="btn btn-delete" aria-label="Verwijderen">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path
                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>

                <div className="right-column">

                    <div className="history-card active-row">
                        <div className="shield-avatar-small">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <span className="history-name">Raven</span>
                    </div>

                    <div className="history-panel">

                        <div className="panel-header">
                            <h3 className="history-title">Zoek geschiedenis</h3>
                            <div className="compact-pagination">
                                <button className="btn-arrow" aria-label="Vorige pagina">‹</button>
                                <button className="btn-arrow" aria-label="Volgende pagina">›</button>
                            </div>
                        </div>

                        <div className="history-stack">
                            <div className="history-card">
                                <div className="shield-avatar-small">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                </div>
                                <span className="history-name">Raven</span>
                            </div>

                            <div className="history-card">
                                <div className="shield-avatar-small">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                </div>
                                <span className="history-name">Raven</span>
                            </div>

                            <div className="history-card">
                                <div className="shield-avatar-small">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                </div>
                                <span className="history-name">Raven</span>
                            </div>
                        </div>

                    </div>

                </div>

            </main>
        </>
    );
}

export default UserManagement