import React, { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export default function NativeLeafletMap({ position, setPosition, setFormData }) {
    const mapRef = useRef(null);
    const leafletMap = useRef(null);
    const markerRef = useRef(null);

    useEffect(() => {
        if (!leafletMap.current) {
            const rotterdamBounds = L.latLngBounds(
                [51.80, 4.30],
                [52.00, 4.65]
            );

            leafletMap.current = L.map(mapRef.current, {
                maxBounds: rotterdamBounds,
                maxBoundsViscosity: 1.0,
                minZoom: 12
            }).setView([51.9225, 4.47917], 13);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(leafletMap.current);

            leafletMap.current.on('click', (e) => {
                const { lat, lng } = e.latlng;
                setPosition({ lat, lng });
                setFormData(prev => ({
                    ...prev,
                    neighborhood: "Rotterdam",
                    cluster: "Centrum"
                }));
            });
        }
    }, [setFormData, setPosition]);


    useEffect(() => {
        if (position && leafletMap.current) {
            if (markerRef.current) leafletMap.current.removeLayer(markerRef.current);
            markerRef.current = L.marker([position.lat, position.lng]).addTo(leafletMap.current);
        }
    }, [position]);

    return <div ref={mapRef} className="h-full w-full" />;
}