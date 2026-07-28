import { Link } from 'react-router-dom';
import { Car, Calendar, PlusCircle, AlertTriangle, CheckCircle, Clock } from 'lucide-react';

interface AgencyAdminDashboardProps {
  data: {
    stats: {
      total_vehicles: number;
      available_vehicles: number;
      reserved_vehicles: number;
      maintenance_vehicles: number;
      pending_reservations: number;
      confirmed_reservations: number;
      completed_reservations: number;
    };
    recent_reservations: any[];
    recent_vehicles: any[];
    attention_vehicles: any[];
  };
}

const AgencyAdminDashboard = ({ data }: AgencyAdminDashboardProps) => {
  const { stats, recent_reservations, recent_vehicles, attention_vehicles } = data;

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <h1>Agency Command Center</h1>
        <div style={{ display: 'flex', gap: '1rem' }}>
          <Link to="/fleet/new" className="btn btn-primary">
            <PlusCircle size={18} /> Add Vehicle
          </Link>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '1.5rem', marginBottom: '2rem' }}>
        
        {/* Vehicles Stats */}
        <div className="card" style={{ padding: '1.5rem' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', marginBottom: '1rem' }}>
            <div style={{ padding: '0.75rem', background: '#f0fdf4', color: '#22c55e', borderRadius: '0.5rem' }}>
              <Car size={20} />
            </div>
            <h3 style={{ margin: 0, fontSize: '1rem' }}>Fleet Overview</h3>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
            <span style={{ color: 'var(--text-muted)' }}>Total Vehicles</span>
            <span style={{ fontWeight: 'bold' }}>{stats.total_vehicles}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
            <span style={{ color: 'var(--text-muted)' }}>Available</span>
            <span style={{ fontWeight: 500, color: 'var(--success-color)' }}>{stats.available_vehicles}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
            <span style={{ color: 'var(--text-muted)' }}>Reserved</span>
            <span style={{ fontWeight: 500, color: 'var(--warning-color)' }}>{stats.reserved_vehicles}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between' }}>
            <span style={{ color: 'var(--text-muted)' }}>In Maintenance</span>
            <span style={{ fontWeight: 500, color: 'var(--danger-color)' }}>{stats.maintenance_vehicles}</span>
          </div>
        </div>

        {/* Reservations Stats */}
        <div className="card" style={{ padding: '1.5rem' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', marginBottom: '1rem' }}>
            <div style={{ padding: '0.75rem', background: '#eff6ff', color: '#3b82f6', borderRadius: '0.5rem' }}>
              <Calendar size={20} />
            </div>
            <h3 style={{ margin: 0, fontSize: '1rem' }}>Reservations</h3>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
            <span style={{ color: 'var(--text-muted)' }}><Clock size={14} style={{ display: 'inline', verticalAlign: 'middle', marginRight: '4px' }}/> Pending</span>
            <span style={{ fontWeight: 500, color: '#f59e0b' }}>{stats.pending_reservations}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
            <span style={{ color: 'var(--text-muted)' }}><CheckCircle size={14} style={{ display: 'inline', verticalAlign: 'middle', marginRight: '4px' }}/> Confirmed</span>
            <span style={{ fontWeight: 500, color: '#10b981' }}>{stats.confirmed_reservations}</span>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between' }}>
            <span style={{ color: 'var(--text-muted)' }}>Completed</span>
            <span style={{ fontWeight: 500 }}>{stats.completed_reservations}</span>
          </div>
        </div>

      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))', gap: '2rem' }}>
        
        {/* Recent Reservations */}
        <div>
          <h2 style={{ fontSize: '1.25rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <Calendar size={20} /> Recent Reservations
          </h2>
          <div className="card">
            {recent_reservations.length === 0 ? (
              <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>
                <p>No reservations yet.</p>
              </div>
            ) : (
              <div className="table-wrapper">
                <table>
                  <thead>
                    <tr>
                      <th>Customer</th>
                      <th>Vehicle</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recent_reservations.map((res: any) => (
                      <tr key={res.id}>
                        <td style={{ fontWeight: 500 }}>
                          <Link to={`/reservations/${res.id}`}>{res.customer_name}</Link>
                        </td>
                        <td>{res.vehicle?.make} {res.vehicle?.model}</td>
                        <td>
                          <span className={`badge badge-${res.status === 'pending' ? 'pending' : (res.status === 'confirmed' || res.status === 'completed' ? 'confirmed' : 'cancelled')}`}>
                            {res.status}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

        {/* Recent Vehicles */}
        <div>
          <h2 style={{ fontSize: '1.25rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <Car size={20} /> Recent Vehicles
          </h2>
          <div className="card">
            {recent_vehicles.length === 0 ? (
              <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>
                <p style={{ marginBottom: '1rem' }}>No vehicles yet.</p>
                <Link to="/fleet/new" className="btn btn-primary">Add your first vehicle</Link>
              </div>
            ) : (
              <div className="table-wrapper">
                <table>
                  <thead>
                    <tr>
                      <th>Make & Model</th>
                      <th>Plate</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recent_vehicles.map((vehicle: any) => (
                      <tr key={vehicle.id}>
                        <td>
                          <Link to={`/fleet/${vehicle.id}/edit`} style={{ fontWeight: 500 }}>
                            {vehicle.make} {vehicle.model}
                          </Link>
                        </td>
                        <td>{vehicle.license_plate}</td>
                        <td>
                          <span className={`badge badge-${vehicle.status === 'available' ? 'confirmed' : (vehicle.status === 'reserved' ? 'pending' : 'cancelled')}`}>
                            {vehicle.status}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

        {/* Vehicles Needing Attention */}
        <div style={{ gridColumn: '1 / -1' }}>
          <h2 style={{ fontSize: '1.25rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#ef4444' }}>
            <AlertTriangle size={20} /> Vehicles Needing Attention
          </h2>
          <div className="card">
            {attention_vehicles.length === 0 ? (
              <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--success-color)' }}>
                <CheckCircle size={32} style={{ marginBottom: '0.5rem' }} />
                <p>All vehicles are operational.</p>
              </div>
            ) : (
              <div className="table-wrapper">
                <table>
                  <thead>
                    <tr>
                      <th>Make & Model</th>
                      <th>Plate</th>
                      <th>Current Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {attention_vehicles.map((vehicle: any) => (
                      <tr key={vehicle.id}>
                        <td style={{ fontWeight: 500 }}>{vehicle.make} {vehicle.model}</td>
                        <td>{vehicle.license_plate}</td>
                        <td>
                          <span className="badge badge-cancelled">
                            {vehicle.status}
                          </span>
                        </td>
                        <td>
                          <Link to={`/fleet/${vehicle.id}/edit`} style={{ color: 'var(--primary-color)' }}>Update Status</Link>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

      </div>
    </div>
  );
};

export default AgencyAdminDashboard;
