import React, { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// We want to return a color based on status
const getStatusColor = (status) => {
    switch (status) {
        case 'open':
            return '#dc2626'; // red-600
        case 'in_behandeling':
            return '#f59e0b'; // amber-500
        case 'opgelost':
            return '#10b981'; // emerald-500
        case 'gesloten':
            return '#9ca3af'; // gray-400
        default:
            return '#3b82f6'; // blue-500
    }
};

const createCustomIcon = (status) => {
    const color = getStatusColor(status);
    
    // We use a simple div icon with Tailwind-like inline styles to make it look like a radar dot
    const html = `
        <div style="
            position: relative;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: ${color};
            border: 2px solid white;
            box-shadow: 0 0 4px rgba(0,0,0,0.4);
        "></div>
    `;

    return L.divIcon({
        html: html,
        className: 'custom-leaflet-marker',
        iconSize: [16, 16],
        iconAnchor: [8, 8] // Center of the circle
    });
};

export default function IncidentMap({ issues, onSelectIssue }) {
    const mapRef = useRef(null);
    const leafletMap = useRef(null);
    const markersLayer = useRef(L.layerGroup()); // Group to easily clear all markers

    useEffect(() => {
        if (!leafletMap.current) {
            // Setup map
            leafletMap.current = L.map(mapRef.current, {
                minZoom: 10,
                maxZoom: 18
            }).setView([51.9225, 4.47917], 12); // Default Rotterdam center

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap & CartoDB'
            }).addTo(leafletMap.current);

            // Add the markers layer group to the map
            markersLayer.current.addTo(leafletMap.current);
        }
    }, []);

    useEffect(() => {
        if (!leafletMap.current) return;

        // Clear existing markers
        markersLayer.current.clearLayers();

        if (!issues || issues.length === 0) return;

        const bounds = L.latLngBounds();
        let validMarkersCount = 0;

        issues.forEach(issue => {
            // Check if issue has valid coordinates
            if (issue.latitude && issue.longitude) {
                const lat = parseFloat(issue.latitude);
                const lng = parseFloat(issue.longitude);
                
                if (!isNaN(lat) && !isNaN(lng)) {
                    const marker = L.marker([lat, lng], {
                        icon: createCustomIcon(issue.status)
                    });

                    // Add click event to marker
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

        // Only fit bounds if we actually added markers, else fallback to default view
        if (validMarkersCount > 0) {
            leafletMap.current.fitBounds(bounds, {
                padding: [50, 50],
                maxZoom: 16 // Prevent zooming too far in if there's only 1 issue
            });
        }

    }, [issues, onSelectIssue]);

    return <div ref={mapRef} className="h-full w-full rounded-2xl border border-stone-200" style={{ zIndex: 10 }} />;
}
