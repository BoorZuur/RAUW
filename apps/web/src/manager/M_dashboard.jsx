import "../App.css"
import BOANavbar from "../components/HM_Nav.jsx";

function M_dashboard() {
    return (
        <div className="dashboard-container">
            <BOANavbar/>
            
            <div className="dashboard-content">
                <h1>Manager Dashboard</h1>
                <p>Welcome back!</p>
            </div>
        </div>
    );
}

export default M_dashboard