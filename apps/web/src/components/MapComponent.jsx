import React, { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

export default function NativeLeafletMap({ position, setPosition, setFormData }) {
    const mapRef = useRef(null);
    const leafletMap = useRef(null);
    const markerRef = useRef(null);

    useEffect(() => {
        if (!leafletMap.current) {
            leafletMap.current = L.map(mapRef.current).setView([51.9225, 4.47917], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(leafletMap.current);
            leafletMap.current.on('click', async (e) => {
                const { lat, lng } = e.latlng;
                setPosition({ lat, lng });
                try {
                    const res = await axios.get(`https://nominatim.openstreetmap.org/reverse`, { params: { lat, lon: lng, format: 'json' } });
                    const addr = res.data.address;
                    setFormData(prev => ({ ...prev, address: [addr.road, addr.house_number].filter(Boolean).join(' ') }));
                } catch (err) { console.error(err); }
            });
        }
    }, []);

    useEffect(() => {
        if (position && leafletMap.current) {
            if (markerRef.current) {
                leafletMap.current.removeLayer(markerRef.current);
            }
            markerRef.current = L.marker([position.lat, position.lng]).addTo(leafletMap.current);
            leafletMap.current.setView([position.lat, position.lng], 15);
        }
    }, [position]);

    return <div ref={mapRef} className="h-full w-full" />;
}