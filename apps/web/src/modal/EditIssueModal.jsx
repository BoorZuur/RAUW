import React, { useState } from 'react';
import axios from 'axios';

const apiClient = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: { 'Accept': 'application/json' }
});

export default function EditIssueModal({ issue, onClose, onUpdateSuccess }) {
    const [formData, setFormData] = useState({ title: issue.title, content: issue.content });

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await apiClient.put(`/issues/${issue.id}`, formData);
            onUpdateSuccess();
            onClose();
        } catch (err) {
            alert('Kon niet opslaan.');
        }
    };

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <form onSubmit={handleSubmit} className="bg-primary-bg-cards border border-primary-border p-6 rounded-2xl w-full max-w-md">
                <h2 className="text-xl font-bold mb-4">Issue bewerken</h2>
                <input
                    className="w-full p-3 mb-3 bg-primary-bg border border-primary-border rounded-xl"
                    value={formData.title}
                    onChange={e => setFormData({...formData, title: e.target.value})}
                />
                <textarea
                    className="w-full p-3 mb-4 bg-primary-bg border border-primary-border rounded-xl h-32"
                    value={formData.content}
                    onChange={e => setFormData({...formData, content: e.target.value})}
                />
                <div className="flex gap-2">
                    <button type="button" onClick={onClose} className="px-4 py-2 bg-stone-800 rounded-lg">Annuleren</button>
                    <button type="submit" className="px-4 py-2 bg-primary-accent rounded-lg">Opslaan</button>
                </div>
            </form>
        </div>
    );
}