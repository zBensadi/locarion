import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiClient } from '../../api';
import { Plus } from 'lucide-react';

interface Agency {
  id: string;
  name: string;
  slug: string;
  status: string;
  created_at: string;
}

const AgenciesList = () => {
  const [agencies, setAgencies] = useState<Agency[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [stats, setStats] = useState({ total: 0, active: 0, inactive: 0 });
  const [search, setSearch] = useState('');

  const fetchAgencies = async (searchQuery = '') => {
    try {
      setLoading(true);
      const response = await apiClient.get('/admin/agencies', { params: { search: searchQuery } });
      setAgencies(response.data.data);
      setStats(response.data.stats);
    } catch (err) {
      setError('Failed to fetch agencies.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAgencies(search);
  }, [search]);

  const toggleStatus = async (id: string, currentStatus: string) => {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const confirmMessage = newStatus === 'inactive' 
      ? 'Are you sure you want to deactivate this agency?' 
      : 'Are you sure you want to activate this agency?';
      
    if (!window.confirm(confirmMessage)) return;

    try {
      // Find agency data to send in PUT request
      const agency = agencies.find(a => a.id === id);
      if (!agency) return;

      await apiClient.put(`/admin/agencies/${id}`, {
        name: agency.name,
        slug: agency.slug,
        status: newStatus
      });
      fetchAgencies(search);
    } catch (err) {
      alert('Failed to update agency status.');
    }
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <h1>Agencies</h1>
        <Link to="/admin/agencies/new" className="btn btn-primary">
          <Plus size={18} /> Create Agency
        </Link>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '1.5rem', marginBottom: '2rem' }}>
        <div className="card" style={{ padding: '1rem' }}>
          <h3 style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Total Agencies</h3>
          <p style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>{stats.total}</p>
        </div>
        <div className="card" style={{ padding: '1rem' }}>
          <h3 style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Active Agencies</h3>
          <p style={{ fontSize: '1.5rem', fontWeight: 'bold', color: 'var(--success-color)' }}>{stats.active}</p>
        </div>
        <div className="card" style={{ padding: '1rem' }}>
          <h3 style={{ color: 'var(--text-muted)', fontSize: '0.875rem' }}>Inactive Agencies</h3>
          <p style={{ fontSize: '1.5rem', fontWeight: 'bold', color: 'var(--danger-color)' }}>{stats.inactive}</p>
        </div>
      </div>

      <div className="card">
        <div style={{ marginBottom: '1.5rem' }}>
          <input 
            type="text" 
            placeholder="Search agencies..." 
            className="form-input" 
            style={{ maxWidth: '300px' }}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>

        {loading && agencies.length === 0 ? (
          <p>Loading agencies...</p>
        ) : (
          <div className="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Slug</th>
                  <th>Created</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {error ? (
                  <tr>
                    <td colSpan={5} style={{ textAlign: 'center', color: 'var(--danger-color)' }}>{error}</td>
                  </tr>
                ) : agencies.length === 0 ? (
                  <tr>
                    <td colSpan={5} style={{ textAlign: 'center', color: 'var(--text-muted)' }}>
                      No agencies found.
                    </td>
                  </tr>
                ) : (
                  agencies.map(agency => (
                    <tr key={agency.id}>
                      <td>{agency.name}</td>
                      <td>{agency.slug}</td>
                      <td>{new Date(agency.created_at).toLocaleDateString()}</td>
                      <td>
                        <span className={`badge ${agency.status === 'active' ? 'badge-confirmed' : 'badge-cancelled'}`}>
                          {agency.status}
                        </span>
                      </td>
                      <td style={{ display: 'flex', gap: '1rem' }}>
                        <Link to={`/admin/agencies/${agency.id}/edit`} style={{ fontWeight: 500 }}>Edit</Link>
                        <button 
                          onClick={() => toggleStatus(agency.id, agency.status)}
                          style={{ background: 'none', border: 'none', color: agency.status === 'active' ? 'var(--danger-color)' : 'var(--success-color)', cursor: 'pointer', fontWeight: 500 }}
                        >
                          {agency.status === 'active' ? 'Deactivate' : 'Activate'}
                        </button>
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

export default AgenciesList;
