import './App.css'
import {createBrowserRouter, RouterProvider} from "react-router-dom";

// import Onboarding from "./user/Onboarding.jsx";
// import Feed from "./user/Feed.jsx";
// import Report from "./user/Report.jsx";
// import Account from "./user/Account.jsx";
// import Login from "./user/U_Login.jsx";
// import Register from "./user/U_Register.jsx";
// import Map from "./user/Map.jsx"
// import AccountSettings from "./user/AccountSettings.jsx"
//
//
import CommandCenter from "./handhaver/CommandCenter.jsx";
import SectorSettings from "./handhaver/SectorSettings.jsx";
import ServiceProfile from "./handhaver/ServiceProfile.jsx";
import H_ReportsOverview from "./handhaver/H_ReportsOverview.jsx";
import HandhaverLogin from "./handhaver/H_Login.jsx";
import HandhaverRegister from "./handhaver/H_Register.jsx";
//
// import M_dashboard from "./manager/M_dashboard.jsx";
// import FlaggedDashboard from "./manager/Flagged_Dashboard.jsx";
// import ReportsOverview from "./manager/M_ReportsOverview.jsx";
// import UserManagement from "./manager/UserManagement.jsx";
// import ManagerLogin from "./manager/M_Login.jsx";


function App() {
    const router = createBrowserRouter([{
        children: [
            // User Routes
            // {path: "/", element: <Onboarding/>},
            // {path: "/feed", element: <Feed/>},
            // {path: "/report", element: <Report/>},
            // {path: "/account", element: <Account/>},
            // {path: "/login", element: <Login/>},
            // {path: "/register", element: <Register/>},
            // {path: "/map", element: <Map/>},
            //     {path: "/account_settings", element: <AccountSettings/>},
            //
            //     // Handhaver Routes
                {path: "/CommandCenter", element: <CommandCenter/>},
                {path: "/handhaver_login", element: <HandhaverLogin/>},
                {path: "/handhaver_registratie", element: <HandhaverRegister/>},
                {path: "/rapport", element: <H_ReportsOverview/>},
                {path: "/sector_instellingen", element: <SectorSettings/>},
                {path: "/dienstprofiel", element: <ServiceProfile/>},
            //
            //
            //     // Manager Routes
            //     {path: "/manager_dashboard", element: <M_dashboard/>},
            //     {path: "/gebruiker_management", element: <UserManagement/>},
            //     {path: "/manger_login", element: <ManagerLogin/>},
            //     {path: "/flagged_dashboard", element: <FlaggedDashboard/>},
            //     {path: "/reports_overview", element: <ReportsOverview/>},
        ]
    }]);
    return <RouterProvider router={router}/>;
}

export default App