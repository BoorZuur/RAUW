import React from 'react';
import {Link, useLocation} from 'react-router-dom';
import {navLinks} from '../config/navConfig.js';
import "./HM_styling.css";

export default function HM_Nav({role = 'handhaver'}) {
    const location = useLocation();

    // Filter de links zodat alleen de links voor de huidige rol getoond worden
    const filteredLinks = navLinks.filter(link => link.roles.includes(role));

    return (
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
                    <p>{role === 'manager' ? 'MANAGEMENT' : 'BOA COMMAND'}</p>
                </div>
            </div>
            <nav className="nav-menu">
                {filteredLinks.map((link) => {
                    const isActive = location.pathname === link.to;
                    return (
                        <Link
                            key={link.id}
                            to={link.to}
                            className={`nav-item ${isActive ? 'active' : ''}`}
                        >
                            <svg className="nav-icon" viewBox="0 0 24 24">
                                <path fill="currentColor" d={link.iconPath}/>
                            </svg>
                            <span>{link.title}</span>
                            {isActive && <span className="dot-indicator"></span>}
                        </Link>
                    );
                })}
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
                <Link to="/handhaver_login" className="nav-item logout-btn">
                    <svg className="nav-icon" viewBox="0 0 24 24">
                        <path fill="currentColor"
                              d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                    </svg>
                    <span>Uitloggen</span>
                </Link>
            </div>
        </section>
    );
}