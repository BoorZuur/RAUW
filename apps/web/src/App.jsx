import './App.css'
import {useState} from 'react'
import {createBrowserRouter, RouterProvider} from "react-router-dom";

import CommandCenter from "./handhaver/CommandCenter.jsx";
import SectorSettings from "./handhaver/SectorSettings.jsx";
import ServiceProfile from "./handhaver/ServiceProfile.jsx";
import M_dashboard from "./manager/M_dashboard.jsx";
import H_ReportsOverview from "./handhaver/H_ReportsOverview.jsx";
import FlaggedDashboard from "./manager/Flagged_Dashboard.jsx";
import ReportsOverview from "./manager/M_ReportsOverview.jsx";
import UserManagement from "./manager/UserManagement.jsx";
import ManagerLogin from "./manager/M_Login.jsx";
import HandhaverLogin from "./handhaver/H_Login.jsx";
import HandhaverRegister from "./handhaver/H_Register.jsx";


function Layout() {
}

function App() {
    const router = createBrowserRouter([{
        children: [
            {path: "/", element: <CommandCenter/>},

            {path: "/boa_dashboard", element: <H_dashboard/>},
            {path: "/manager_dashboard", element: <M_dashboard/>},

            {path: "/gebruiker_management", element: <UserManagement/>},
            {path: "/manger_login", element: <ManagerLogin/>},
            {path: "/handhaver_login", element: <HandhaverLogin/>},
            {path: "/handhaver_registratie", element: <HandhaverRegister/>},

            {path: "/rapport", element: <H_ReportsOverview/>},
            {path: "/flagged_dashboard", element: <FlaggedDashboard/>},
            {path: "/reports_overview", element: <ReportsOverview/>},
            {path: "/sector_instellingen", element: <SectorSettings/>},
            {path: "/dienstprofiel", element: <ServiceProfile/>},

        ]
    }]);
    return <RouterProvider router={router}/>;
}

export default App
