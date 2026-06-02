import './App.css'
import {useState} from 'react'
import {createBrowserRouter, RouterProvider} from "react-router-dom";

import CommandCenter from "./handhaver/CommandCenter.jsx";
import H_dashboard from "./handhaver/H_dashboard.jsx";
import ReportOverview from "./handhaver/ReportOverview.jsx";
import SectorSettings from "./handhaver/SectorSettings.jsx";
import ServiceProfile from "./handhaver/ServiceProfile.jsx";
import M_dashboard from "./manager/M_dashboard.jsx";


function Layout() {
    return null;
}

function App() {
    const router = createBrowserRouter([{
        element: <Layout/>,
        children: [
            {path: "/", element: <CommandCenter/>},

            {path: "/BOA_dashboard", element: <H_dashboard/>},
            {path: "/Manager_dashboard", element: <M_dashboard/>},

            {path: "/rapport", element: <ReportOverview/>},
            {path: "/sector_instellingen", element: <SectorSettings/>},
            {path: "/dienstprofiel", element: <ServiceProfile/>},

        ]
    }]);
    return <RouterProvider router={router}/>;
}

export default App;