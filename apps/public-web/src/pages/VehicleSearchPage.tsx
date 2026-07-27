import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../api';

interface Vehicle {
  id: string;
  make: string;
  model: string;
  year: number;
  daily_rate: number;
  category?: { name: string };
  agency?: { name: string };
}

const VehicleSearchPage = () => {
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchVehicles = async () => {
      try {
        const response = await api.get('/public/vehicles');
        setVehicles(response.data.data);
      } catch (err) {
        setError('Failed to load vehicles. Please try again later.');
      } finally {
        setLoading(false);
      }
    };
    fetchVehicles();
  }, []);

  return (
    <div className="container">
      <h1 style={{ marginBottom: '2rem' }}>Available Vehicles</h1>

      {loading && <p>Loading vehicles...</p>}
      {error && <p className="error-text">{error}</p>}
      
      {!loading && !error && vehicles.length === 0 && (
        <p>No vehicles are currently available.</p>
      )}

      <div className="grid grid-cols-3">
        {vehicles.map(vehicle => (
          <div key={vehicle.id} className="card" style={{ display: 'flex', flexDirection: 'column' }}>
            <h3 style={{ fontSize: '1.25rem', marginBottom: '0.5rem' }}>
              {vehicle.make} {vehicle.model} ({vehicle.year})
            </h3>
            <p style={{ color: 'var(--text-muted)', marginBottom: '1rem' }}>
              {vehicle.category?.name || 'Standard'} • {vehicle.agency?.name || 'Local Agency'}
            </p>
            <div style={{ marginTop: 'auto', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span style={{ fontWeight: 'bold', fontSize: '1.125rem' }}>
                ${(vehicle.daily_rate / 100).toFixed(2)} / day
              </span>
              <Link to={`/vehicles/${vehicle.id}`} className="btn btn-primary">
                View Details
              </Link>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default VehicleSearchPage;
