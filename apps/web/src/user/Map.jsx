import React, { useEffect, useState, useRef } from 'react';
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import U_Nav from '../components/U_Nav';
import axios from 'axios';
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

    const [reports, setReports] = useState([]);
    const [districts, setDistricts] = useState([]);
    const [filters, setFilters] = useState({ district: 'all', view: 'all' });
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);

    useEffect(() => {
        if (!leafletMap.current) {
            // Verruimde bounds: Zuid-West (Hoek van Holland) naar Noord-Oost
            const regionBounds = L.latLngBounds(
                [51.75, 4.05],
                [52.00, 4.65]
            );

            leafletMap.current = L.map(mapRef.current, {
                zoomControl: false,
                maxBounds: regionBounds,
                maxBoundsViscosity: 1.0,
                minZoom: 10 // Verlaagd zodat je het overzicht houdt
            }).setView([51.9225, 4.47917], 13);

            L.control.zoom({ position: 'bottomright' }).addTo(leafletMap.current);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(leafletMap.current);
        }

        const fetchData = async () => {
            const token = localStorage.getItem('auth_token');
            const config = { headers: { 'Authorization': `Bearer ${token}` } };
            try {
                const [issuesRes, distRes] = await Promise.all([
                    axios.get('http://localhost:8001/api/issues', config),
                    axios.get('http://localhost:8001/api/districts', config)
                ]);
                setReports(issuesRes.data.data || issuesRes.data);
                setDistricts(distRes.data.data || distRes.data);
            } catch (err) { console.error("Data error", err); }
        };
        fetchData();
    }, []);

    useEffect(() => {
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
                    marker.bindPopup(`
                        <div class="p-2">
                            <h3 class="font-bold text-lg">${report.title || "Geen titel"}</h3>
                            <button id="btn-${report.id}" class="mt-2 text-primary-accent font-bold underline">Bekijk details</button>
                        </div>
                    `);
                    marker.on('popupopen', () => {
                        const btn = document.getElementById(`btn-${report.id}`);
                        if (btn) btn.onclick = () => console.log("Details:", report.id);
                    });
                    markersLayer.current.push(marker);
                }
            });
    }, [reports, filters]);

    return (
        <div className="min-h-screen w-full flex flex-col bg-primary-bg overflow-hidden">
            <header className="h-20 shrink-0"><U_Nav /></header>
            <main className="z-10 grow w-full max-w-6xl mx-auto px-6 pt-26 mt-8 pb-12 md:p-8 flex gap-6 overflow-hidden">
                <section className="flex-1 bg-primary-bg-cards border-2 border-primary-border rounded-3xl overflow-hidden relative shadow-lg">
                    <div ref={mapRef} className="absolute inset-0" />
                </section>

                <aside className="w-80 bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-8 shrink-0 overflow-y-auto space-y-8">
                    <h1 className="text-2xl font-black text-primary-text">Kaartbeheer</h1>

                    {/* Categorie Dropdown */}
                    <div className="relative">
                        <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Categorie</label>
                        <div onClick={() => setIsDropdownOpen(!isDropdownOpen)} className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl cursor-pointer flex justify-between items-center text-primary-text">
            <span className="text-sm font-medium">
                {filters.district === 'all' ? "Alle wijken" : districts.find(d=>d.id.toString()===filters.district)?.name}
            </span>
                            <span className="text-secondary-text">▼</span>
                        </div>
                        {isDropdownOpen && (
                            <div className="absolute z-50 w-full mt-2 max-h-60 overflow-y-auto bg-primary-bg border-2 border-primary-border rounded-xl p-2 shadow-2xl text-primary-text">
                                <div onClick={() => {setFilters({...filters, district: 'all'}); setIsDropdownOpen(false)}} className="p-3 cursor-pointer hover:bg-primary-border/20 text-sm">Alle wijken</div>
                                {districts.map(d => (
                                    <div key={d.id} onClick={() => {setFilters({...filters, district: d.id.toString()}); setIsDropdownOpen(false)}} className="p-3 cursor-pointer hover:bg-primary-border/20 text-sm">{d.name}</div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Weergave Buttons */}
                    <div>
                        <label className="block text-[10px] font-black mb-2 uppercase text-secondary-text">Weergave</label>
                        <div className="grid grid-cols-1 gap-2">
                            {[
                                { label: 'Alle', value: 'all' },
                                { label: 'Mijn verhalen', value: 'mine' },
                                { label: 'Afgehandeld', value: 'resolved' }
                            ].map((btn) => (
                                <button
                                    key={btn.value}
                                    onClick={() => setFilters({...filters, view: btn.value})}
                                    className={`px-4 py-3 rounded-lg text-xs font-black uppercase tracking-widest transition-all ${
                                        filters.view === btn.value
                                            ? 'bg-primary-accent text-white'
                                            : 'bg-primary-border text-primary-text hover:bg-primary-border/80'
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