import { Target, CircleUser, Settings, ChartColumnStacked, MessageCircleMore } from 'lucide-react';

export const navLinks = [
    {
        id: 'command-center',
        title: 'Command Center',
        to: '/meldingen',
        roles: ['handhaver'],
        icon: Target
    },
    {
        id: 'dienstprofiel',
        title: 'Dienstprofiel',
        to: '/dienstprofiel',
        roles: ['handhaver'],
        icon: CircleUser
    },
    {
        id: 'sectorinstellingen',
        title: 'Sector Instellingen',
        to: '/sectorinstellingen',
        roles: ['handhaver'],
        icon: Settings
    },
    {
        id: 'rapporten',
        title: 'Rapporten',
        to: '/rapport',
        roles: ['handhaver'],
        icon: ChartColumnStacked
    },
    {
        id: 'handhaverchat',
        title: 'Chat',
        to: '/handhaverchat',
        roles: ['handhaver'],
        icon: MessageCircleMore
    },
    {
        id: 'manager-dashboard',
        title: 'Dashboard',
        to: '/dashboard',
        roles: ['manager'],
        iconPath: "M4 13h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zm0 8h6c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1zm10 0h6c.55 0 1-.45 1-1v-8c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1zM14 4v4c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1h-6c-.55 0-1 .45-1 1z"
    },
    {
        id: 'flags-dashboard',
        title: 'Gerapporteerd',
        to: '/flaggeddashboard',
        roles: ['manager'],
        iconPath: "M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"
    },
    {
        id: 'manager_rapporten',
        title: 'Rapporten',
        to: '/rapportenoverzicht',
        roles: ['manager'],
        iconPath: "M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 16H6c-.55 0-1-.45-1-1V6c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v12c0 .55-.45 1-1 1zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"
    },
    {
        id: 'gebruikers_management',
        title: 'Teams',
        to: '/gebruikersmanagement',
        roles: ['manager'],
        iconPath: "M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"
    },
];