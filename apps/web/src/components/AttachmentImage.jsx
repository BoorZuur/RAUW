import React, { useEffect, useState } from 'react';

export default function AttachmentImage({ attachment, className, alt = "Afbeelding" }) {
    const [src, setSrc] = useState(null);

    useEffect(() => {
        if (!attachment || !attachment.download_url) return;

        const token = localStorage.getItem('auth_token');
        let objectUrl = null;
        let cancelled = false;

        fetch(attachment.download_url, {
            headers: { 'Authorization': `Bearer ${token}` },
        })
            .then(res => res.ok ? res.blob() : Promise.reject())
            .then(blob => {
                if (!cancelled) {
                    objectUrl = URL.createObjectURL(blob);
                    setSrc(objectUrl);
                }
            })
            .catch(err => console.error("Fout bij laden afbeelding:", err));

        return () => {
            cancelled = true;
            if (objectUrl) URL.revokeObjectURL(objectUrl);
        };
    }, [attachment]);

    if (!src) return <div className={`${className} bg-stone-800 animate-pulse`} />;
    return <img src={src} alt={alt} className={className} />;
}