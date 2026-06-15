import React, { useEffect, useState, useRef } from 'react';
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import axios from 'axios';
import U_Nav from '../components/U_Nav';
import Footer from "../components/Footer.jsx";
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { 'Accept': 'application/json' }
});

apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

const DefaultIcon = L.icon({
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41]
});
L.Marker.prototype.options.icon = DefaultIcon;

export default function MapOverview() {
    const mapRef = useRef(null);
    const leafletMap = useRef(null);
    const markersLayer = useRef([]);

    const [reports, setReports] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [filters, setFilters] = useState({ district: 'all', view: 'all' });
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);

    useEffect(() => {
        if (!leafletMap.current) {
            const regionBounds = L.latLngBounds([51.75, 4.05], [52.00, 4.65]);
            leafletMap.current = L.map(mapRef.current, {
                zoomControl: false,
                maxBounds: regionBounds,
                maxBoundsViscosity: 1.0,
                minZoom: 10
            }).setView([51.9225, 4.47917], 13);

            L.control.zoom({ position: 'bottomright' }).addTo(leafletMap.current);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
            }).addTo(leafletMap.current);
        }

        const fetchData = async () => {
            try {
                // Nu gebruiken we de centrale apiClient zonder handmatige headers
                const [issuesRes, distRes] = await Promise.all([
                    apiClient.get('/issues'),
                    apiClient.get('/districts')
                ]);
                setReports(issuesRes.data.data || issuesRes.data);
                setDistricts(distRes.data.data || distRes.data);
            } catch (err) {
                console.error("Data error", err);
            }
        };
        fetchData();
    }, []);

    useEffect(() => {
        if (!leafletMap.current) return;

        markersLayer.current.forEach(m => leafletMap.current.removeLayer(m));
        markersLayer.current = [];

        const currentUserId = localStorage.getItem('user_id');

        reports
            .filter(r => {
                const matchesDistrict = filters.district === 'all' ? true : r.district_id?.toString() === filters.district;

                let matchesView = true;
                if (filters.view === 'mine') {
                    matchesView = r.user_id?.toString() === currentUserId?.toString();
                } else if (filters.view === 'resolved') {
                    matchesView = r.status === 'opgelost' || r.status === 'gesloten';
                }

                return matchesDistrict && matchesView;
            })
            .forEach(report => {
                if (report.latitude && report.longitude) {
                    const marker = L.marker([report.latitude, report.longitude]).addTo(leafletMap.current);

                    const popupContent = `
                        <div class="font-label text-primary-text p-1 m-0">
                            <h3 class="font-headline font-black text-base mb-1">
                                ${report.title || "Geen titel"}
                            </h3>
                            <p class="text-xs text-secondary-text mb-3">
                                ${report.address || "Geen adres"}
                            </p>
                            <button id="btn-${report.id}" class="w-full py-2 px-3 bg-primary-accent text-white text-[10px] font-black uppercase tracking-widest rounded-lg cursor-pointer hover:brightness-110 transition-all">
                                Bekijk details
                            </button>
                        </div>
                    `;

                    marker.bindPopup(popupContent, {
                        className: 'custom-leaflet-popup'
                    });

                    marker.on('popupopen', () => {
                        const btn = document.getElementById(`btn-${report.id}`);
                        if (btn) btn.onclick = () => console.log("Details:", report.id);
                    });
                    markersLayer.current.push(marker);
                }
            });
    }, [reports, filters, districts]);

    return (
        <div className="min-h-screen w-full flex flex-col bg-primary-bg text-primary-text overflow-hidden transition-colors duration-300">
            <header className="fixed top-0 w-full z-50"><U_Nav /></header>

            {/* Flex wrapper voor de tijdlijnen aan de zijkant + hoofdcontent */}
            <div className="grow flex w-full pt-26">

                {/* ==================== LINKER PANEL: HANDHAVING & TOEZICHT KETEN ==================== */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-l border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300"></div>

                    {/* Verticale stapeling van Toezicht iconen */}
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">

                        {/* Icoon 1: Schild (Veiligheid) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <circle cx="12" cy="11" r="2" className="opacity-60" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 2: Handdruk (Samenwerking) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 3: Checkmark (Afgehandeld) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="22 11.08 12 19 9 16" />
                                <path d="M22 4L12 14.01l-3-3" className="opacity-40" />
                                <circle cx="12" cy="12" r="10" strokeDasharray="3 3" className="opacity-50" />
                            </svg>
                        </div>
                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300"></div>
                </div>

                {/* ==================== MIDDEN: KAART OVERVIEW CONTENT ==================== */}
                <main className="z-10 grow w-full max-w-6xl mx-auto px-6 mt-8 pb-12 md:p-8 flex flex-col md:flex-row gap-6 overflow-hidden">
                    <section className="flex-1 h-[50vh] md:h-auto bg-primary-bg-cards border-2 border-primary-border rounded-3xl overflow-hidden relative shadow-md">
                        <div ref={mapRef} className="absolute inset-0 z-0" />
                    </section>

                    <aside className="w-full md:w-80 bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-8 shrink-0 overflow-y-auto space-y-8 shadow-md custom-scrollbar">
                        <h1 className="text-2xl font-black text-primary-text tracking-tight">Kaartbeheer</h1>

                        <div className="relative">
                            <label htmlFor="content" className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text">
                                Wijk Filter
                            </label>
                            <div onClick={() => setIsDropdownOpen(!isDropdownOpen)} className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl cursor-pointer flex justify-between items-center text-primary-text hover:border-primary-accent transition-colors">
                                <span className="text-sm font-bold">
                                    {filters.district === 'all' ? "Alle wijken" : districts.find(d=>d.id.toString()===filters.district)?.name}
                                </span>
                                <span className="text-xs text-secondary-text">{isDropdownOpen ? '▲' : '▼'}</span>
                            </div>
                            {isDropdownOpen && (
                                <div className="absolute z-50 w-full mt-2 max-h-60 overflow-y-auto bg-primary-bg-cards border-2 border-primary-border rounded-xl p-2 shadow-2xl text-primary-text custom-scrollbar">
                                    <div onClick={() => {setFilters({...filters, district: 'all'}); setIsDropdownOpen(false)}} className="p-3 cursor-pointer hover:bg-primary-bg rounded-lg text-sm font-medium">Alle wijken</div>
                                    {districts.map(d => (
                                        <div key={d.id} onClick={() => {setFilters({...filters, district: d.id.toString()}); setIsDropdownOpen(false)}} className="p-3 cursor-pointer hover:bg-primary-bg rounded-lg text-sm font-medium">{d.name}</div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div>
                            <label htmlFor="content" className="text-left block text-xs font-bold tracking-wider uppercase mb-1.5 text-primary-text">
                                Weergave
                            </label>
                            <div className="grid grid-cols-1 gap-2">
                                {[
                                    { label: 'Alle meldingen', value: 'all' },
                                    { label: 'Mijn verhalen', value: 'mine' },
                                    { label: 'Afgehandeld', value: 'resolved' }
                                ].map((btn) => (
                                    <button
                                        key={btn.value}
                                        onClick={() => setFilters({...filters, view: btn.value})}
                                        className={`px-4 py-3.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all cursor-pointer border-2 ${
                                            filters.view === btn.value
                                                ? 'bg-primary-accent border-primary-accent text-white shadow-sm'
                                                : 'bg-primary-bg border-primary-border text-primary-text hover:border-primary-accent'
                                        }`}
                                    >
                                        {btn.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </aside>
                </main>

                {/* ==================== RECHTER PANEL: ROTTERDAM IDENTITEIT ==================== */}
                <div className="group hidden lg:flex w-1/12 xl:w-2/12 flex-col justify-between p-6 select-none opacity-35 hover:opacity-100 transition-all duration-500 ease-in-out cursor-default relative">
                    <div className="w-3 h-3 border-t border-r border-primary-text/40 group-hover:border-primary-accent transition-colors duration-300 self-end"></div>

                    {/* Verticale stapeling van Rotterdamse iconen */}
                    <div className="w-full flex flex-col items-center justify-center gap-12 my-auto transition-transform duration-500">

                        {/* Icoon 1: Erasmusbrug */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M4 20 L15 4 L18 5 L10 20" />
                                <line x1="2" y1="20" x2="22" y2="20" />
                                <line x1="14" y1="6" x2="20" y2="20" className="opacity-40" />
                                <line x1="13" y1="9" x2="17" y2="20" className="opacity-40" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-px h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 2: Stad / Architectuur (De Wijken) */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-75">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <rect x="2" y="10" width="6" height="11" />
                                <rect x="9" y="3" width="6" height="18" />
                                <rect x="16" y="8" width="6" height="13" />
                            </svg>
                        </div>

                        {/* Verbindingslijntje */}
                        <div className="w-[1px] h-6 bg-primary-text/20 group-hover:bg-primary-accent/40 transition-colors"></div>

                        {/* Icoon 3: Maritiem / Haven */}
                        <div className="flex flex-col items-center group-hover:scale-105 transition-transform duration-300 delay-150">
                            <svg className="w-10 h-10 stroke-current text-primary-text group-hover:text-primary-accent transition-colors duration-500" viewBox="0 0 24 24" fill="none" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="8" y1="9" x2="16" y2="9" />
                                <path d="M5 12a7 7 0 0 0 14 0" />
                                <circle cx="12" cy="4" r="1" />
                            </svg>
                        </div>
                    </div>

                    <div className="w-6 h-px bg-primary-text/30 group-hover:bg-primary-accent transition-colors duration-300 self-end"></div>
                </div>

            </div>

            <div className="z-10 bg-primary-bg">
                <Footer />
            </div>
        </div>
    );
}