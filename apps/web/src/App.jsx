import './App.css';
import { createBrowserRouter, RouterProvider, Navigate, Outlet } from "react-router-dom";

// Imports
import Onboarding from "./user/Onboarding.jsx";
import Login from "./user/U_Login.jsx";
import Register from "./user/U_Register.jsx";
import Map from "./user/Map.jsx";
import Feed from "./user/Feed.jsx";
import Report from "./user/Report.jsx";
import NewsFeed from "./user/NewsFeed.jsx";
// import Chat from "./user/U_Chat.jsx";
import Account from "./user/Account.jsx";
import AccountSettings from "./user/AccountSettings.jsx";

import CommandCenter from "./handhaver/CommandCenter.jsx";
import SectorSettings from "./handhaver/SectorSettings.jsx";
import H_ReportsOverview from "./handhaver/H_ReportsOverview.jsx";
import ServiceProfile from "./handhaver/ServiceProfile.jsx";
// import H_Chat from "./handhaver/H_Chat.jsx";
import HandhaverLogin from "./handhaver/H_Login.jsx";
import HandhaverRegister from "./handhaver/H_Register.jsx";

// Beveiligingscomponent
const PortalGuard = ({ allowedType }) => {
    const token = localStorage.getItem("auth_token");
    const type = localStorage.getItem("user_type");

    if (!token) return <Navigate to="/login" replace />;
    return type === allowedType ? <Outlet /> : <Navigate to="/" replace />;
};

function App() {
    const router = createBrowserRouter([
        // 1. Publieke routes
        {path: "/", element: <Onboarding/>},
        {path: "/login", element: <Login/>},
        {path: "/registreer", element: <Register/>},
        {path: "/loginhandhaver", element: <HandhaverLogin/>},
        {path: "/registreerhandhaver", element: <HandhaverRegister/>},

        // 2. User Routes
        {
            element: <PortalGuard allowedType="user" />,
            children: [
                { path: "/map", element: <Map /> },
                { path: "/feed", element: <Feed /> },
                { path: "/meld", element: <Report /> },
                { path: "/nieuws", element: <NewsFeed /> },
                // { path: "/chat", element: <Chat /> },
                { path: "/account", element: <Account /> },
                { path: "/instellingen", element: <AccountSettings /> },
            ]
        },

        // 3. Handhaver (BOA) Routes
        {
            element: <PortalGuard allowedType="officer" />,
            children: [
                { path: "/meldingen", element: <CommandCenter /> },
                { path: "/rapport", element: <H_ReportsOverview /> },
                { path: "/sectorinstellingen", element: <SectorSettings /> },
                { path: "/dienstprofiel", element: <ServiceProfile /> },
                // { path: "/chat", element: <H_Chat /> },
            ]
        },

    ]);

    return <RouterProvider router={router} />;
}

export default App;