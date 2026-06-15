import React, { useState, forwardRef, useImperativeHandle } from 'react';
import { X, MessageSquare } from 'lucide-react';

const Notification = forwardRef((props, ref) => {
    const [notif, setNotif] = useState(null);

    useImperativeHandle(ref, () => ({
        show: (title, message, type = 'info', duration = 4000) => {
            setNotif({ title, message, type });
            setTimeout(() => setNotif(null), duration);
        }
    }));

    if (!notif) return null;

    return (
        <div className="fixed top-24 right-6 z-9999 w-80 animate-fade-in">
            <div className="bg-stone-900 border border-stone-700 rounded-2xl shadow-2xl p-4 flex items-center gap-3">
                <div className="p-2 bg-primary-accent/20 rounded-full">
                    <MessageSquare className="w-4 h-4 text-primary-accent" />
                </div>
                <div className="flex-1 min-w-0">
                    <h4 className="text-[13px] font-black text-white">{notif.title}</h4>
                    <p className="text-[12px] text-stone-400 truncate">{notif.message}</p>
                </div>
                <button onClick={() => setNotif(null)} className="text-stone-500 hover:text-white">
                    <X className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
});

export default Notification;