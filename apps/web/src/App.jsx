import './App.css';
import { createBrowserRouter, RouterProvider, Navigate, Outlet } from "react-router-dom";

// Imports
import Onboarding from "./user/Onboarding.jsx";
import Login from "./user/U_Login.jsx";
import Register from "./user/U_Register.jsx";
import Map from "./user/Map.jsx";
import Feed from "./user/Feed.jsx";
import Report from "./user/Report.jsx";
// import NewsFeed from "./user/Newsfeed.jsx";
import Account from "./user/Account.jsx";
import AccountSettings from "./user/AccountSettings.jsx";

// import CommandCenter from "./handhaver/CommandCenter.jsx";
// import SectorSettings from "./handhaver/SectorSettings.jsx";
// import H_ReportsOverview from "./handhaver/H_ReportsOverview.jsx";
import HandhaverLogin from "./handhaver/H_Login.jsx";
import HandhaverRegister from "./handhaver/H_Register.jsx";

// import M_dashboard from "./manager/M_dashboard.jsx";
// import FlaggedDashboard from "./manager/Flagged_Dashboard.jsx";
// import ReportsOverview from "./manager/M_ReportsOverview.jsx";
// import UserManagement from "./manager/UserManagement.jsx";
import ManagerLogin from "./manager/M_Login.jsx";

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
        {path: "/loginmanager", element: <ManagerLogin/>},

        { path: "/account", element: <Account /> },
        // 2. User Routes
        {
            element: <PortalGuard allowedType="user" />,
            children: [
                { path: "/map", element: <Map /> },
                { path: "/feed", element: <Feed /> },
                { path: "/meld", element: <Report /> },
                // { path: "/nieuws", element: <NewsFeed /> },
                { path: "/account", element: <Account /> },
                { path: "/instellingen", element: <AccountSettings /> },
            ]
        },

        // 3. Handhaver (BOA) Routes
        // {
        //     element: <PortalGuard allowedType="officer" />,
        //     children: [
        //         { path: "/meldingen", element: <CommandCenter /> },
        //         { path: "/rapport", element: <H_ReportsOverview /> },
        //         { path: "/sectorinstellingen", element: <SectorSettings /> },
        //     ]
        // },

        // 4. Manager Routes
        // {
        //     element: <PortalGuard allowedType="manager" />,
        //     children: [
        //         { path: "/dashboard", element: <M_dashboard /> },
        //         { path: "/flaggeddashboard", element: <FlaggedDashboard /> },
        //         { path: "/rapportoverzicht", element: <ReportsOverview /> },
        //         { path: "/gebruikermanagement", element: <UserManagement /> },
        //     ]
        // }
    ]);

    return <RouterProvider router={router}/>;
}

export default App;