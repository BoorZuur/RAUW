import "./User_styling.css"

export default function U_Nav(){
    return(
        <>
            <nav className="navbar">
                <div className="nav-brand">
                    <div className="logo-icon">
                        <span className="material-symbols-outlinend target-eye">visibility</span>
                    </div>
                    <div className="brand-text">
                        <span className="brand-name">RAUW</span>
                        <span className="brand-sub">ROTTERDAM</span>
                    </div>
                </div>

                <div className="nav-menu">
                    <a href="#" className="nav-link active">
                        <span className="material-symbols-outlined icon-small">home</span>
                        Feed
                    </a>
                    <a href="#" className="nav-link">
                        <span className="material-symbols-outlined icon-small">warning</span>
                        Melden
                    </a>
                    <a href="#" className="nav-link">
                        <span className="material-symbols-outlined icon-small">map</span>
                        Kaart
                    </a>
                </div>

                <div className="nav-utilities">
                    <button className="util-btn" id="theme-toggle" aria-label="Toggle dark mode">
                        <span className="material-symbols-outlined">dark_mode</span>
                    </button>
                    <button className="util-btn notification-btn" aria-label="Notifications">
                        <span className="material-symbols-outlined">notifications</span>
                        <span className="notification-dot"></span>
                    </button>
                    <button className="util-btn" aria-label="User profile">
                        <span className="material-symbols-outlined">person</span>
                    </button>
                </div>
            </nav>
        </>
    )
}