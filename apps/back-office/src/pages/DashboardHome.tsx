import { useAuth } from '../context/AuthContext';

const DashboardHome = () => {
  const { user } = useAuth();

  return (
    <div>
      <h1 style={{ marginBottom: '2rem' }}>Welcome, {user?.name}</h1>
      
      <div className="card">
        <h3 style={{ marginBottom: '1rem' }}>Dashboard Overview</h3>
        <p style={{ color: 'var(--text-muted)' }}>
          Welcome to Locarion Back-Office. Use the sidebar to manage your fleet and reservations.
        </p>
      </div>
    </div>
  );
};

export default DashboardHome;
