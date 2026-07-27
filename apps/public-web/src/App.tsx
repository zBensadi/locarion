import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import PublicLayout from './components/PublicLayout';
import LandingPage from './pages/LandingPage';
import VehicleSearchPage from './pages/VehicleSearchPage';
import VehicleDetailPage from './pages/VehicleDetailPage';
import ReservationSuccessPage from './pages/ReservationSuccessPage';

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<PublicLayout />}>
          <Route index element={<LandingPage />} />
          <Route path="vehicles" element={<VehicleSearchPage />} />
          <Route path="vehicles/:id" element={<VehicleDetailPage />} />
          <Route path="reservation/success" element={<ReservationSuccessPage />} />
        </Route>
      </Routes>
    </Router>
  );
}

export default App;
