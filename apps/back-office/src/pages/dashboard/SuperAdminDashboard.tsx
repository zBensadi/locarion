import { Link } from 'react-router-dom';
import { Users, Building, Car, Calendar, PlusCircle, Activity } from 'lucide-react';

interface SuperAdminDashboardProps {
  data: {
    stats: {
      total_agencies: number;
      active_agencies: number;
      total_vehicles: number;
      total_reservations: number;
      total_users: number;
    };
    recent_agencies: any[];
    recent_reservations: any[];
    activity: {
      recent_vehicle_created_at: string | null;
      recent_reservation_created_at: string | null;
      recent_agency_created_at: string | null;
    };
  };
}

const SuperAdminDashboard = ({ data }: SuperAdminDashboardProps) => {
  const { stats, recent_agencies, recent_reservations, activity } = data;

  const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString();
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <h1>Platform Command Center</h1>
        <div style={{ display: 'flex', gap: '1rem' }}>
          <Link to="/admin/agencies/new" className="btn btn-primary">
            <PlusCircle size={18} /> Create Agency
          </Link>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '1.5rem', marginBottom: '2rem' }}>
        <div className="card" style={{ padding: '1.5rem', display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <div style={{ padding: '1rem', background: '#eff6ff', color: '#3b82f6', borderRadius: '0.5rem' }}>
            <Building size={24} />
          </div>
          <div>
            <h3 style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Total Agencies</h3>
            <p style={{ fontSize: '1.75rem', fontWeight: 'bold' }}>{stats.total_agencies}</p>
            <span style={{ fontSize: '0.75rem', color: 'var(--success-color)' }}>{stats.active_agencies} Active</span>
          </div>
        </div>

        <div className="card" style={{ padding: '1.5rem', display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <div style={{ padding: '1rem', background: '#f0fdf4', color: '#22c55e', borderRadius: '0.5rem' }}>
            <Car size={24} />
          </div>
          <div>
            <h3 style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Total Vehicles</h3>
            <p style={{ fontSize: '1.75rem', fontWeight: 'bold' }}>{stats.total_vehicles}</p>
          </div>
        </div>

        <div className="card" style={{ padding: '1.5rem', display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <div style={{ padding: '1rem', background: '#fef2f2', color: '#ef4444', borderRadius: '0.5rem' }}>
            <Calendar size={24} />
          </div>
          <div>
            <h3 style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Total Reservations</h3>
            <p style={{ fontSize: '1.75rem', fontWeight: 'bold' }}>{stats.total_reservations}</p>
          </div>
        </div>

        <div className="card" style={{ padding: '1.5rem', display: 'flex', alignItems: 'center', gap: '1rem' }}>
          <div style={{ padding: '1rem', background: '#f3e8ff', color: '#a855f7', borderRadius: '0.5rem' }}>
            <Users size={24} />
          </div>
          <div>
            <h3 style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Total Users</h3>
            <p style={{ fontSize: '1.75rem', fontWeight: 'bold' }}>{stats.total_users}</p>
          </div>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))', gap: '2rem' }}>
        
        {/* Recent Agencies */}
        <div>
          <h2 style={{ fontSize: '1.25rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <Building size={20} /> Recent Agencies
          </h2>
          <div className="card">
            {recent_agencies.length === 0 ? (
              <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>
                <p style={{ marginBottom: '1rem' }}>No agencies created yet.</p>
                <Link to="/admin/agencies/new" className="btn btn-primary">Create your first agency</Link>
              </div>
            ) : (
              <div className="table-wrapper">
                <table>
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Status</th>
                      <th>Created</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recent_agencies.map((agency: any) => (
                      <tr key={agency.id}>
                        <td><Link to={`/admin/agencies/${agency.id}/edit`} style={{ fontWeight: 500 }}>{agency.name}</Link></td>
                        <td>
                          <span className={`badge ${agency.status === 'active' ? 'badge-confirmed' : 'badge-cancelled'}`}>
                            {agency.status}
                          </span>
                        </td>
                        <td>{new Date(agency.created_at).toLocaleDateString()}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

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
                      <th>Vehicle</th>
                      <th>Agency</th>
                      <th>Dates</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recent_reservations.map((res: any) => (
                      <tr key={res.id}>
                        <td style={{ fontWeight: 500 }}>{res.vehicle?.make} {res.vehicle?.model}</td>
                        <td style={{ color: 'var(--text-muted)' }}>{res.agency?.name}</td>
                        <td style={{ fontSize: '0.875rem' }}>
                          {new Date(res.start_date).toLocaleDateString()} - {new Date(res.end_date).toLocaleDateString()}
                        </td>
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

        {/* Platform Activity */}
        <div style={{ gridColumn: '1 / -1' }}>
          <h2 style={{ fontSize: '1.25rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
            <Activity size={20} /> Platform Activity
          </h2>
          <div className="card" style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '1.5rem', padding: '1.5rem' }}>
            <div>
              <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem', marginBottom: '0.25rem' }}>Latest Agency Created</p>
              <p style={{ fontWeight: 500 }}>{formatDate(activity.recent_agency_created_at)}</p>
            </div>
            <div>
              <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem', marginBottom: '0.25rem' }}>Latest Vehicle Added</p>
              <p style={{ fontWeight: 500 }}>{formatDate(activity.recent_vehicle_created_at)}</p>
            </div>
            <div>
              <p style={{ color: 'var(--text-muted)', fontSize: '0.875rem', marginBottom: '0.25rem' }}>Latest Reservation</p>
              <p style={{ fontWeight: 500 }}>{formatDate(activity.recent_reservation_created_at)}</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
};

export default SuperAdminDashboard;
