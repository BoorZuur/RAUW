import './App.css';
import { createBrowserRouter, RouterProvider } from "react-router-dom";

// Imports
import Onboarding from "./user/Onboarding.jsx";
import Login from "./user/U_Login.jsx";
import Register from "./user/U_Register.jsx";
// import Map from "./user/Map.jsx";
// import Feed from "./user/Feed.jsx";
// import Report from "./user/Report.jsx";
// import NewsFeed from "./user/Newsfeed.jsx";
import Account from "./user/Account.jsx";
// import AccountSettings from "./user/AccountSettings.jsx";

// import CommandCenter from "./handhaver/CommandCenter.jsx";
// import SectorSettings from "./handhaver/SectorSettings.jsx";
// import ServiceProfile from "./handhaver/ServiceProfile.jsx";
// import H_ReportsOverview from "./handhaver/H_ReportsOverview.jsx";
import HandhaverLogin from "./handhaver/H_Login.jsx";
import HandhaverRegister from "./handhaver/H_Register.jsx";

// import M_dashboard from "./manager/M_dashboard.jsx";
// import FlaggedDashboard from "./manager/Flagged_Dashboard.jsx";
// import ReportsOverview from "./manager/M_ReportsOverview.jsx";
// import UserManagement from "./manager/UserManagement.jsx";
import ManagerLogin from "./manager/M_Login.jsx";

// Beveiligingscomponent
// const RoleProtectedRoute = ({ allowedRoles }) => {
//     const userRole = localStorage.getItem("userRole");
//     const isAuthenticated = !!localStorage.getItem("token");
//
//     if (!isAuthenticated) return <Navigate to="/login" />;
//     return allowedRoles.includes(userRole) ? <Outlet /> : <Navigate to="/login" />;
// };

function App() {
    const router = createBrowserRouter([
        // 1. Publieke routes
        { path: "/", element: <Onboarding /> },
        { path: "/login", element: <Login /> },
        { path: "/registreer", element: <Register /> },
        { path: "/loginhandhaver", element: <HandhaverLogin /> },
        { path: "/registreerhandhaver", element: <HandhaverRegister /> },
        { path: "/loginmanager", element: <ManagerLogin /> },

        // { path: "/map", element: <Map /> },
        // { path: "/feed", element: <Feed /> },
        // { path: "/meld", element: <Report /> },
        { path: "/account", element: <Account /> },
        // { path: "/instellingen", element: <AccountSettings /> },
        // { path: "/nieuws", element: <NewsFeed /> },
        //
        // { path: "/meldingen", element: <CommandCenter /> },
        // { path: "/dienstprofiel", element: <ServiceProfile /> },
        // { path: "/rapport", element: <H_ReportsOverview /> },
        // { path: "/sectorinstellingen", element: <SectorSettings /> },
        //
        // { path: "/dashboard", element: <M_dashboard /> },
        // { path: "/flaggedaccounts", element: <FlaggedDashboard /> },
        // { path: "/rapportoverzicht", element: <ReportsOverview /> },
        // { path: "/gebruikermanagement", element: <UserManagement /> },

        // 2. User Routes
        // {
        //     element: <RoleProtectedRoute allowedRoles={['officer']} />,
        //     children: [
        //         { path: "/map", element: <Map /> },
        //         { path: "/feed", element: <Feed /> },
        //         { path: "/meld", element: <Report /> },
        //         { path: "/nieuws", element: <NewsFeed /> },
        //         { path: "/account", element: <Account /> },
        //         { path: "/instellingen", element: <AccountSettings /> },
        //     ]
        // },
        //
        // // 3. Handhaver (BOA) Routes
        // {
        //     element: <RoleProtectedRoute allowedRoles={['boa']} />,
        //     children: [
        //         { path: "/meldingen", element: <CommandCenter /> },
        //         { path: "/dienstprofiel", element: <ServiceProfile /> },
        //         { path: "/rapport", element: <H_ReportsOverview /> },
        //         { path: "/sectorinstellingen", element: <SectorSettings /> },
        //     ]
        // },

        // 4. Manager Routes
        // {
        //     element: <RoleProtectedRoute allowedRoles={['manager']} />,
        //     children: [
        //         { path: "/dashboard", element: <M_dashboard /> },
        //         { path: "/flaggeddashboard", element: <FlaggedDashboard /> },
        //         { path: "/rapportoverzicht", element: <ReportsOverview /> },
        //         { path: "/gebruikermanagement", element: <UserManagement /> },
        //     ]
        // }
    ]);

    return <RouterProvider router={router} />;
}

export default App;