import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import LoginPage from './pages/LoginPage';
import DashboardLayout from './components/DashboardLayout';
import DashboardHome from './pages/DashboardHome';
import FleetList from './pages/FleetList';
import VehicleForm from './pages/VehicleForm';
import ReservationList from './pages/ReservationList';
import ReservationDetail from './pages/ReservationDetail';
import AgenciesList from './pages/admin/AgenciesList';
import AgencyForm from './pages/admin/AgencyForm';

const ProtectedRoute = ({ children }: { children: React.ReactNode }) => {
  const { user, loading } = useAuth();
  
  if (loading) return <div>Loading...</div>;
  if (!user) return <Navigate to="/login" replace />;
  
  return <>{children}</>;
};

const SuperAdminRoute = ({ children }: { children: React.ReactNode }) => {
  const { user } = useAuth();
  if (user && user.agency_id !== null) return <Navigate to="/" replace />;
  return <>{children}</>;
};

function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      
      <Route path="/" element={<ProtectedRoute><DashboardLayout /></ProtectedRoute>}>
        <Route index element={<DashboardHome />} />
        <Route path="fleet" element={<FleetList />} />
        <Route path="fleet/new" element={<VehicleForm />} />
        <Route path="fleet/:id/edit" element={<VehicleForm />} />
        
        <Route path="reservations" element={<ReservationList />} />
        <Route path="reservations/:id" element={<ReservationDetail />} />

        {/* Super Admin Routes */}
        <Route path="admin/agencies" element={<SuperAdminRoute><AgenciesList /></SuperAdminRoute>} />
        <Route path="admin/agencies/new" element={<SuperAdminRoute><AgencyForm /></SuperAdminRoute>} />
        <Route path="admin/agencies/:id/edit" element={<SuperAdminRoute><AgencyForm /></SuperAdminRoute>} />
      </Route>
    </Routes>
  );
}

function App() {
  return (
    <Router>
      <AuthProvider>
        <AppRoutes />
      </AuthProvider>
    </Router>
  );
}

export default App;
