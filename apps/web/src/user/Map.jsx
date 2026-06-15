import React, { useEffect, useState, useRef } from 'react';
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import U_Nav from '../components/U_Nav';
import axiosOriginal from 'axios';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import Footer from "../components/Footer.jsx";

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
    // useRef om de actieve tilelayer bij te houden buiten de React state om
    const activeTileLayer = useRef(null);

    const [reports, setReports] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [filters, setFilters] = useState({ district: 'all', view: 'all' });
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);

    useEffect(() => {
        let observer = null;

        if (!leafletMap.current) {
            const regionBounds = L.latLngBounds(
                [51.75, 4.05],
                [52.00, 4.65]
            );

            leafletMap.current = L.map(mapRef.current, {
                zoomControl: false,
                maxBounds: regionBounds,
                maxBoundsViscosity: 1.0,
                minZoom: 10
            }).setView([51.9225, 4.47917], 13);

            L.control.zoom({ position: 'bottomright' }).addTo(leafletMap.current);

            const lightTiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
            });

            const darkTiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
            });

            // Initialiseer met de juiste modus op basis van de html/body klasse
            const isDarkInitial = document.documentElement.classList.contains('dark') || document.body.classList.contains('dark');

            activeTileLayer.current = isDarkInitial ? darkTiles : lightTiles;
            activeTileLayer.current.addTo(leafletMap.current);

            // De MutationObserver kijkt nu naar activeTileLayer.current om closures te voorkomen
            observer = new MutationObserver(() => {
                const isDarkNow = document.documentElement.classList.contains('dark') || document.body.classList.contains('dark');
                const nextLayer = isDarkNow ? darkTiles : lightTiles;

                if (activeTileLayer.current !== nextLayer) {
                    leafletMap.current.removeLayer(activeTileLayer.current);
                    nextLayer.addTo(leafletMap.current);
                    activeTileLayer.current = nextLayer;
                }
            });

            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        }

        const fetchData = async () => {
            const token = localStorage.getItem('auth_token');
            const config = { headers: { 'Authorization': `Bearer ${token}` } };
            try {
                const [issuesRes, distRes] = await Promise.all([
                    axiosOriginal.get('http://localhost:8001/api/issues', config),
                    axiosOriginal.get('http://localhost:8001/api/districts', config)
                ]);
                setReports(issuesRes.data.data || issuesRes.data);
                setDistricts(distRes.data.data || distRes.data);
            } catch (err) { console.error("Data error", err); }
        };
        fetchData();

        return () => {
            if (observer) {
                observer.disconnect();
            }
        };
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
            <header className="h-20 shrink-0"><U_Nav /></header>

            <main className="z-10 grow w-full max-w-6xl mx-auto px-6 pt-26 mt-8 pb-12 md:p-8 flex flex-col md:flex-row gap-6 overflow-hidden">
                <section className="flex-1 h-[50vh] md:h-auto bg-primary-bg-cards border-2 border-primary-border rounded-3xl overflow-hidden relative shadow-md">
                    <div ref={mapRef} className="absolute inset-0 z-0" />
                </section>

                <aside className="w-full md:w-80 bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-8 shrink-0 overflow-y-auto space-y-8 shadow-md">
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

            <Footer/>
        </div>
    );
}