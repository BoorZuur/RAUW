import './App.css'
import {useState} from 'react'
import {createBrowserRouter, RouterProvider} from "react-router-dom";

import CommandCenter from "./handhaver/CommandCenter.jsx";
import H_dashboard from "./handhaver/H_dashboard.jsx";
import SectorSettings from "./handhaver/SectorSettings.jsx";
import ServiceProfile from "./handhaver/ServiceProfile.jsx";
import M_dashboard from "./manager/M_dashboard.jsx";
import H_ReportsOverview from "./handhaver/H_ReportsOverview.jsx";
import Flagged_Dashboard from "./manager/Flagged_Dashboard.jsx";
import ReportsOverview from "./manager/M_ReportsOverview.jsx";


function Layout() {
}

function App() {
    const router = createBrowserRouter([{
        children: [
            {path: "/", element: <CommandCenter/>},

            {path: "/boa_dashboard", element: <H_dashboard/>},
            {path: "/manager_dashboard", element: <M_dashboard/>},

            {path: "/rapport", element: <H_ReportsOverview/>},
            {path: "/flagged_dashboard", element: <Flagged_Dashboard/>},
            {path: "/reports_overview", element: <ReportsOverview/>},
            {path: "/sector_instellingen", element: <SectorSettings/>},
            {path: "/dienstprofiel", element: <ServiceProfile/>},

        ]
    }]);
    return <RouterProvider router={router}/>;
}

export default App
