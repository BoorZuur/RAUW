import "../App.css"
import HM_Nav from "../components/HM_Nav.jsx";
import "./Handhaver_styling.css"

export default function SectorSettings() {
    return (
        <>
            <div className="app-layout">
            <HM_Nav></HM_Nav>
            <main className="main-content">
                {/*🌟: the header has the cards in them, we might wanna make these into components*/}
                <header className="page-header">
                    <div className="header-left">
                        <div className="header-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24">
                                <path fill="currentColor"
                                      d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c-.19-.15-.24-.42-.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.09.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3-1.07-3-3.5s1.07-3.5 3-3.5 3 1.07 3 3.5-1.07 3.5-3 3.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h1>Sector Instellingen</h1>
                            <p className="subtitle">Selecteer jouw zorgwijken in Rotterdam</p>
                        </div>
                    </div>
                    <button className="btn-save">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor"
                                  d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                        </svg>
                        Opslaan
                    </button>
                </header>

                <div className="counter-banner">
                    <span className="location-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-313-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    </span>
                    <p>
                        <strong id="selected-count">0</strong> van 74 Rotterdamse wijken geselecteerd als jouw zorggebied
                    </p>
                </div>

                <section className="selection-card">
                    <h2>Alle Wijken Rotterdam</h2>
                    <p className="instruction">Klik op een wijk om deze aan jouw zorggebied toe te voegen of te
                        verwijderen</p>

                    {/*🌟: Need to connect this to the back-end!*/}
                    <div className="wijken-grid">
                        <button className="wijk-item">Achterveld</button>
                        <button className="wijk-item">Afrikaanderwijk</button>
                        <button className="wijk-item">Agniese buurt</button>
                        <button className="wijk-item">Bergpolder</button>

                        <button className="wijk-item">Blijdorp</button>
                        <button className="wijk-item">Bloemhof</button>
                        <button className="wijk-item">Bospolder</button>
                        <button className="wijk-item">Carnisse</button>

                        <button className="wijk-item">Charlois</button>
                        <button className="wijk-item">Cool</button>
                        <button className="wijk-item">Crooswijk</button>
                        <button className="wijk-item">Delfshaven</button>

                        <button className="wijk-item">Dorp</button>
                        <button className="wijk-item">Droogbloem</button>
                        <button className="wijk-item">Feijenoord</button>
                        <button className="wijk-item">Hillegersberg</button>

                        <button className="wijk-item">Hillegersberg-Noord</button>
                        <button className="wijk-item">Hillegersberg-Zuid</button>
                        <button className="wijk-item">Hillesluis</button>
                        <button className="wijk-item">Hoogvliet Noord</button>

                        <button className="wijk-item">Hoogvliet Zuid</button>
                        <button className="wijk-item">IJsselmonde</button>
                        <button className="wijk-item">Katendrecht</button>
                        <button className="wijk-item">Katenrecht</button>

                        <button className="wijk-item">Kleinpolder</button>
                        <button className="wijk-item">Kralingen-Oost</button>
                        <button className="wijk-item">Kralingen-West</button>
                        <button className="wijk-item">Kralingse Bos</button>

                        <button className="wijk-item">Liskwartier</button>
                        <button className="wijk-item">Lombardijen</button>
                        <button className="wijk-item">Middelland</button>
                        <button className="wijk-item">Molenlaankwartier</button>

                        <button className="wijk-item">Nesselande</button>
                        <button className="wijk-item">Nieuw-Crooswijk</button>
                        <button className="wijk-item">Nieuweland</button>
                        <button className="wijk-item">Nieuwe Westen</button>

                        <button className="wijk-item">Noordereiland</button>
                        <button className="wijk-item">Ommoord</button>
                        <button className="wijk-item">Oosterflank</button>
                        <button className="wijk-item">Oud-Charlois</button>

                        <button className="wijk-item">Oud-Crooswijk</button>
                        <button className="wijk-item">Oudeland</button>
                        <button className="wijk-item">Oude Noorden</button>
                        <button className="wijk-item">Oud-IJsselmonde</button>

                        <button className="wijk-item">Oude Westen</button>
                        <button className="wijk-item">Overschie</button>
                        <button className="wijk-item">Pendrecht</button>
                        <button className="wijk-item">Pernis</button>

                        <button className="wijk-item">Prinsenland</button>
                        <button className="wijk-item">Provenierswijk</button>
                        <button className="wijk-item">Reyeroord</button>
                        <button className="wijk-item">Rozenburg</button>

                        <button className="wijk-item">Rubroek</button>
                        <button className="wijk-item">Ruigeplaatbos</button>
                        <button className="wijk-item">Scheepvaartkwartier</button>
                        <button className="wijk-item">Schiebroek</button>

                        <button className="wijk-item">Schieveen</button>
                        <button className="wijk-item">Schiemond</button>
                        <button className="wijk-item">Spangen</button>
                        <button className="wijk-item">Sportdorp</button>

                        <button className="wijk-item">Stadsdriehoek</button>
                        <button className="wijk-item">Struisenburg</button>
                        <button className="wijk-item">Tarwewijk</button>
                        <button className="wijk-item">Terbregge</button>

                        <button className="wijk-item">Tussendijken</button>
                        <button className="wijk-item">Tussenwater</button>
                        <button className="wijk-item">Vreewijk</button>
                        <button className="wijk-item">Westpunt</button>

                        <button className="wijk-item">Zalmplaat</button>
                        <button className="wijk-item">Zestienhoven</button>
                        <button className="wijk-item">Zevenkamp</button>
                        <button className="wijk-item">Zuidwijk</button>
                    </div>
                </section>
            </main>
            </div>
        </>
    );
}