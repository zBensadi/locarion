import { useState, useEffect } from 'react';
import { apiClient } from '../api';
import SuperAdminDashboard from './dashboard/SuperAdminDashboard';
import AgencyAdminDashboard from './dashboard/AgencyAdminDashboard';

const DashboardHome = () => {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchDashboard = async () => {
      try {
        const response = await apiClient.get('/dashboard');
        setData(response.data);
      } catch (err) {
        setError('Failed to load dashboard data.');
      } finally {
        setLoading(false);
      }
    };
    fetchDashboard();
  }, []);

  if (loading) {
    return (
      <div style={{ padding: '2rem', display: 'flex', flexDirection: 'column', gap: '2rem' }}>
        <div style={{ height: '2rem', width: '30%', background: '#f1f5f9', borderRadius: '0.25rem' }} className="skeleton" />
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '1.5rem' }}>
          <div style={{ height: '6rem', background: '#f1f5f9', borderRadius: '0.5rem' }} className="skeleton" />
          <div style={{ height: '6rem', background: '#f1f5f9', borderRadius: '0.5rem' }} className="skeleton" />
          <div style={{ height: '6rem', background: '#f1f5f9', borderRadius: '0.5rem' }} className="skeleton" />
          <div style={{ height: '6rem', background: '#f1f5f9', borderRadius: '0.5rem' }} className="skeleton" />
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))', gap: '2rem' }}>
          <div style={{ height: '16rem', background: '#f1f5f9', borderRadius: '0.5rem' }} className="skeleton" />
          <div style={{ height: '16rem', background: '#f1f5f9', borderRadius: '0.5rem' }} className="skeleton" />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div style={{ padding: '2rem', color: 'var(--danger-color)' }}>
        <h2>Error</h2>
        <p>{error}</p>
      </div>
    );
  }

  if (data?.role === 'super-admin') {
    return <SuperAdminDashboard data={data} />;
  }

  return <AgencyAdminDashboard data={data} />;
};

export default DashboardHome;
