import React, { useState, useEffect } from 'react';
import axios from 'axios';
import U_Nav from '../components/U_Nav';
import NativeLeafletMap from '../components/MapComponent.jsx';

export default function ReportIssue() {
    const [formData, setFormData] = useState({
        title: '',
        category_id: 1,
        address: '',
        neighborhood: '',
        content: '',
        is_anonymous: false
    });

    const [position, setPosition] = useState(null);
    const [showConfirm, setShowConfirm] = useState(false);
    const [error, setError] = useState(null);
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        axios.get('/api/categories')
            .then(res => setCategories(res.data))
            .catch(err => console.error("Kon categorieën niet laden", err));
    }, []);

    const handleSubmit = async (finalAnonStatus) => {
        if (!position) { setError("Selecteer een locatie op de kaart."); return; }
        try {
            await axios.post('/api/issues', { ...formData, ...position, is_anonymous: finalAnonStatus, category_id: parseInt(formData.category_id) });
            alert("Melding succesvol verzonden.");
            setShowConfirm(false);
        } catch (err) { setError("Verzenden mislukt."); }
    };

    return (
        <div className="min-h-screen bg-primary-bg text-primary-text flex flex-col transition-colors duration-300">
            <U_Nav />

            <main className="grow w-full max-w-6xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-2 gap-10">
                {/* Map Sectie */}
                <section className="h-137.5 bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-3 shadow-sm">
                    <div className="w-full h-full rounded-2xl overflow-hidden border border-primary-border/50">
                        <NativeLeafletMap position={position} setPosition={setPosition} setFormData={setFormData} />
                    </div>
                </section>

                {/* Formulier Sectie */}
                <section className="bg-primary-bg-cards border-2 border-primary-border rounded-3xl p-8 shadow-sm">
                    <h2 className="text-2xl font-black mb-8 uppercase tracking-widest text-primary-text">Signaal Melden</h2>

                    <div className="space-y-6">
                        <div>
                            <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Titel</label>
                            <input className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl focus:border-primary-accent outline-none"
                                   value={formData.title} onChange={e => setFormData({...formData, title: e.target.value})} />
                        </div>

                        <div>
                            <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Categorie</label>
                            <select className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl focus:border-primary-accent outline-none appearance-none"
                                    value={formData.category_id} onChange={e => setFormData({...formData, category_id: e.target.value})}>
                                {categories.map(cat => <option key={cat.id} value={cat.id}>{cat.name}</option>)}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Adres</label>
                                <input className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl"
                                       value={formData.address} onChange={e => setFormData({...formData, address: e.target.value})} />
                            </div>
                            <div>
                                <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Buurt</label>
                                <input className="w-full p-4 bg-primary-bg border-2 border-primary-border rounded-xl"
                                       value={formData.neighborhood} onChange={e => setFormData({...formData, neighborhood: e.target.value})} />
                            </div>
                        </div>

                        <div>
                            <label className="block text-[10px] font-black mb-2 uppercase tracking-widest text-secondary-text">Context & Behoefte</label>
                            <textarea className="w-full p-4 h-32 bg-primary-bg border-2 border-primary-border rounded-xl focus:border-primary-accent outline-none"
                                      value={formData.content} onChange={e => setFormData({...formData, content: e.target.value})} />
                        </div>

                        {/* Verbeterde Anoniem Toggle */}
                        <div onClick={() => setFormData({...formData, is_anonymous: !formData.is_anonymous})}
                             className={`cursor-pointer p-4 border-2 rounded-xl flex items-center justify-between transition-all ${formData.is_anonymous ? 'border-secondary-accent bg-secondary-accent/10' : 'border-primary-border bg-primary-bg'}`}>
                            <span className="font-bold text-sm">Anoniem signaleren</span>
                            <div className={`w-10 h-5 rounded-full relative transition-colors ${formData.is_anonymous ? 'bg-secondary-accent' : 'bg-primary-border'}`}>
                                <div className={`absolute top-1 w-3 h-3 rounded-full bg-white transition-all ${formData.is_anonymous ? 'left-6' : 'left-1'}`} />
                            </div>
                        </div>

                        <button onClick={() => setShowConfirm(true)}
                                className="w-full p-4 bg-primary-text text-primary-bg font-black rounded-xl uppercase tracking-widest text-xs hover:opacity-90 active:scale-[0.98] transition-all">
                            Verstuur melding naar handhaving
                        </button>
                    </div>
                </section>
            </main>
        </div>
    );
}