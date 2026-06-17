import React, { useState, useEffect } from 'react';
import { downloadProtectedFile } from '../services/issueChatService';
import { Download, Image as ImageIcon, FileText } from 'lucide-react';

export default function AuthAttachment({ attachment }) {
    const [objectUrl, setObjectUrl] = useState(null);
    const [isLoading, setIsLoading] = useState(false);

    const isImage = attachment.file_type?.startsWith('image/');

    const handleDownload = async () => {
        if (objectUrl) {
            triggerDownload(objectUrl, attachment.original_name);
            return;
        }
        
        try {
            setIsLoading(true);
            const url = await downloadProtectedFile(attachment.download_url);
            setObjectUrl(url);
            triggerDownload(url, attachment.original_name);
        } catch (err) {
            console.error("Download failed", err);
        } finally {
            setIsLoading(false);
        }
    };

    const triggerDownload = (url, filename) => {
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    };

    // Auto-load image for preview
    useEffect(() => {
        if (isImage && attachment.download_url) {
            let isMounted = true;
            downloadProtectedFile(attachment.download_url)
                .then(url => {
                    if (isMounted) setObjectUrl(url);
                })
                .catch(console.error);
            return () => { isMounted = false; };
        }
    }, [isImage, attachment.download_url]);

    if (isImage) {
        return (
            <div className="mt-2 rounded-xl overflow-hidden border border-primary-border max-w-xs cursor-pointer relative group" onClick={handleDownload} title="Klik om te downloaden">
                {objectUrl ? (
                    <img src={objectUrl} alt={attachment.original_name} className="w-full h-auto object-cover max-h-48" />
                ) : (
                    <div className="w-full h-32 bg-primary-bg flex items-center justify-center animate-pulse">
                        <ImageIcon className="text-secondary-text opacity-50" />
                    </div>
                )}
                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <Download className="text-white" />
                </div>
            </div>
        );
    }

    // Generic file fallback
    return (
        <div 
            onClick={handleDownload}
            className="mt-2 flex items-center gap-3 bg-primary-bg border border-primary-border p-3 rounded-xl cursor-pointer hover:bg-primary-border transition-colors max-w-xs"
            title="Klik om te downloaden"
        >
            <div className="bg-primary-bg-cards p-2 rounded-lg text-primary-text">
                <FileText size={20} />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold truncate text-primary-text">{attachment.original_name}</p>
                <p className="text-xs text-secondary-text">{isLoading ? 'Downloaden...' : `${(attachment.file_size / 1024).toFixed(1)} KB`}</p>
            </div>
            <Download size={16} className="text-secondary-text shrink-0" />
        </div>
    );
}
