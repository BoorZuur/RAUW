import React from 'react';
import {Link, useLocation} from 'react-router-dom'; // Toegevoegd voor de werking van de links
import "./HM_styling.css"

export default function HM_Nav(){
    const location = useLocation(); // Slaat de huidige actieve route op

    return (
        <>
            <section className="sidebar">
                <div className="logo-container">
                    <div className="logo-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path fill="currentColor"
                                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm0-12c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z"/>
                        </svg>
                    </div>
                    <div className="logo-text">
                        <h2>RAUW</h2>
                        <p>BOA COMMAND</p>
                    </div>
                </div>

                <nav className="nav-menu">
                    {/* Command Center link */}
                    <Link to="/commando_centrum"
                          className={`nav-item ${location.pathname === '/command_centrum' ? 'active' : ''}`}>
                        <svg className="nav-icon" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                  d="M4 13h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zm0 8h6c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1zm10 0h6c.55 0 1-.45 1-1v-8c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zM14 4v4c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1z"/>
                        </svg>
                        <span>Command Center</span>
                        {location.pathname === '/commando_centrum' && <span className="dot-indicator"></span>}
                    </Link>

                    {/* Dienstprofiel link */}
                    <Link to="/dienstprofiel"
                          className={`nav-item ${location.pathname === '/dienstprofiel' ? 'active' : ''}`}>
                        <svg className="nav-icon" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                        <span>Dienstprofiel</span>
                        {location.pathname === '/dienstprofiel' && <span className="dot-indicator"></span>}
                    </Link>

                    {/* Sector Instellingen link */}
                    <Link to="/sector_instellingen"
                          className={`nav-item ${location.pathname === '/sector_instellingen' ? 'active' : ''}`}>
                        <svg className="nav-icon" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                  d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3-1.07-3-3.5s1.07-3.5 3-3.5 3 1.07 3 3.5-1.07 3.5-3 3.5z"/>
                        </svg>
                        <span>Sector Instellingen</span>
                        {location.pathname === '/sector_instellingen' && <span className="dot-indicator"></span>}
                    </Link>

                    {/* Rapporten link */}
                    <Link to="/handhaver_rapport"
                          className={`nav-item ${location.pathname === '/handhaver_rapport' ? 'active' : ''}`}>
                        <svg className="nav-icon" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                  d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 16H6c-.55 0-1-.45-1-1V6c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v12c0 .55-.45 1-1 1zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                        </svg>
                        <span>Rapporten</span>
                        {location.pathname === '/handhaver_rapport' && <span className="dot-indicator"></span>}
                    </Link>
                </nav>

                <hr/>

                <div className="sidebar-footer">
                    <div className="toggle-container">
                        <span className="toggle-label">Dagmodus</span>
                        <label className="switch">
                            <input type="checkbox" id="mode-toggle"/>
                            <span className="slider"></span>
                        </label>
                    </div>

                    {/* Uitloggen link */}
                    <Link to="/handhaver_login" className="nav-item logout-btn">
                        <svg className="nav-icon" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                  d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                        </svg>
                        <span>Uitloggen</span>
                    </Link>
                </div>
            </section>
        </>
    )
}