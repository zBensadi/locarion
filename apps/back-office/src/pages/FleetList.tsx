import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiClient } from '../api';
import { Plus } from 'lucide-react';

interface Vehicle {
  id: string;
  make: string;
  model: string;
  year: number;
  license_plate: string;
  daily_rate: number;
  status: string;
  category?: { name: string };
}

const FleetList = () => {
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const fetchVehicles = async () => {
    try {
      const response = await apiClient.get('/vehicles');
      setVehicles(response.data.data);
    } catch (err) {
      setError('Failed to fetch vehicles. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchVehicles();
  }, []);

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <h1>Fleet Management</h1>
        <Link to="/fleet/new" className="btn btn-primary">
          <Plus size={18} /> Add Vehicle
        </Link>
      </div>

      <div className="card">
        {loading ? (
          <p>Loading fleet...</p>
        ) : (
          <div className="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Vehicle</th>
                  <th>Category</th>
                  <th>License Plate</th>
                  <th>Daily Rate</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {error ? (
                  <tr>
                    <td colSpan={6} style={{ textAlign: 'center', color: 'var(--danger-color)' }}>
                      {error}
                    </td>
                  </tr>
                ) : vehicles.length === 0 ? (
                  <tr>
                    <td colSpan={6} style={{ textAlign: 'center', color: 'var(--text-muted)' }}>
                      No vehicles found. Add your first vehicle to get started.
                    </td>
                  </tr>
                ) : (
                  vehicles.map(vehicle => (
                    <tr key={vehicle.id}>
                      <td>{vehicle.year} {vehicle.make} {vehicle.model}</td>
                      <td>{vehicle.category?.name || '-'}</td>
                      <td>{vehicle.license_plate}</td>
                      <td>${(vehicle.daily_rate / 100).toFixed(2)}</td>
                      <td>
                        <span className={`badge ${
                          vehicle.status === 'available' ? 'badge-confirmed' :
                          vehicle.status === 'reserved' ? 'badge-pending' :
                          vehicle.status === 'maintenance' ? 'badge-rejected' : 'badge-cancelled'
                        }`}>
                          {vehicle.status}
                        </span>
                      </td>
                      <td>
                        <Link to={`/fleet/${vehicle.id}/edit`} style={{ fontWeight: 500 }}>Edit</Link>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default FleetList;
