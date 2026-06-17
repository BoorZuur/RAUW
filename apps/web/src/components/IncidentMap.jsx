import React, { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const getStatusColor = (status) => {
    switch (status) {
        case 'open': return '#dc2626'; // red-600
        case 'in_behandeling': return '#f59e0b'; // amber-500
        case 'opgelost': return '#10b981'; // emerald-500
        case 'gesloten': return '#9ca3af'; // gray-400
        default: return '#3b82f6'; // blue-500
    }
};

const createCustomIcon = (status) => {
    const color = getStatusColor(status);

    const html = `
        <div style="
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        ">
            <div style="
                width: 16px;
                height: 16px;
                border-radius: 50%;
                background-color: ${color};
                border: 2px solid white;
                box-shadow: 0 0 6px rgba(0,0,0,0.35);
            "></div>
        </div>
    `;

    return L.divIcon({
        html: html,
        className: 'custom-leaflet-marker',
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });
};

export default function IncidentMap({ issues, onSelectIssue }) {
    const mapRef = useRef(null);
    const leafletMap = useRef(null);
    const markersLayer = useRef(L.layerGroup());

    useEffect(() => {
        if (!leafletMap.current) {
            leafletMap.current = L.map(mapRef.current, {
                minZoom: 10,
                maxZoom: 18,
                scrollWheelZoom: false,
                tap: L.Browser.mobile ? false : true
            }).setView([51.9225, 4.47917], 12);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap & CartoDB'
            }).addTo(leafletMap.current);

            markersLayer.current.addTo(leafletMap.current);
        }
    }, []);

    useEffect(() => {
        if (!leafletMap.current) return;

        markersLayer.current.clearLayers();
        if (!issues || issues.length === 0) return;

        const bounds = L.latLngBounds();
        let validMarkersCount = 0;

        issues.forEach(issue => {
            if (issue.latitude && issue.longitude) {
                const lat = parseFloat(issue.latitude);
                const lng = parseFloat(issue.longitude);

                if (!isNaN(lat) && !isNaN(lng)) {
                    const marker = L.marker([lat, lng], {
                        icon: createCustomIcon(issue.status)
                    });

                    marker.on('click', () => {
                        if (onSelectIssue) {
                            onSelectIssue(issue);
                        }
                    });

                    markersLayer.current.addLayer(marker);
                    bounds.extend([lat, lng]);
                    validMarkersCount++;
                }
            }
        });

        if (validMarkersCount > 0) {
            leafletMap.current.fitBounds(bounds, {
                padding: L.Browser.mobile ? [30, 30] : [50, 50],
                maxZoom: 15
            });
        }

    }, [issues, onSelectIssue]);

    return (
        <div
            ref={mapRef}
            className="h-[380px] md:h-full w-full rounded-2xl border border-stone-200 shadow-sm"
            style={{ zIndex: 10 }}
        />
    );
}