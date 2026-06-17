import React, { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

const getDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371e3;
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

export default function NativeLeafletMap({ position, setPosition, setFormData, districts, onLocationError }) {
    const mapRef = useRef(null);
    const leafletMap = useRef(null);
    const markerRef = useRef(null);

    useEffect(() => {
        if (!leafletMap.current) {
            const rotterdamBounds = L.latLngBounds(
                L.latLng(51.80, 4.00),
                L.latLng(52.05, 4.65)
            );

            leafletMap.current = L.map(mapRef.current, {
                maxBounds: rotterdamBounds,
                maxBoundsViscosity: 1.0,
                minZoom: 11,
                maxZoom: 18,

                scrollWheelZoom: false,
                tap: L.Browser.mobile ? false : true,
                bounceAtZoomLimits: false
            }).setView([51.9225, 4.47917], 13);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap & CartoDB'
            }).addTo(leafletMap.current);

            leafletMap.current.on('click', async (e) => {
                const { lat, lng } = e.latlng;
                let insideKnownDistrict = false;

                if (districts && districts.length > 0) {
                    insideKnownDistrict = districts.some(d => {
                        const dist = getDistance(lat, lng, d.center_lat, d.center_lng);
                        const searchRadius = d.radius_meters + 2000;
                        return dist < searchRadius;
                    });
                }

                if (!insideKnownDistrict) {
                    if (onLocationError) {
                        onLocationError("Deze locatie valt buiten een bekend wijkgebied binnen de database van de Gemeente Rotterdam.");
                    }
                    return;
                }

                try {
                    const res = await axios.get(`https://nominatim.openstreetmap.org/reverse`, {
                        params: { lat, lon: lng, format: 'json' }
                    });

                    const addr = res.data.address;
                    if (addr) {
                        setPosition({ lat, lng });
                        setFormData(prev => ({
                            ...prev,
                            address: [addr.road, addr.house_number].filter(Boolean).join(' ')
                        }));
                    }
                } catch (err) {
                    console.error("Fout bij ophalen adres:", err);
                }
            });
        }
    }, [districts, setFormData, setPosition, onLocationError]);

    useEffect(() => {
        if (position && leafletMap.current) {
            if (markerRef.current) {
                leafletMap.current.removeLayer(markerRef.current);
            }
            markerRef.current = L.marker([position.lat, position.lng]).addTo(leafletMap.current);
            leafletMap.current.setView([position.lat, position.lng], 15);
        }
    }, [position]);

    return (
        <div
            ref={mapRef}
            className="h-80 md:h-full w-full rounded-2xl border border-primary-border shadow-sm overflow-hidden z-10"
        />
    );
}