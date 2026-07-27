import { Outlet, Link } from 'react-router-dom';

const PublicLayout = () => {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
      <header style={{ background: 'var(--surface-color)', borderBottom: '1px solid var(--border-color)', padding: '1rem 0' }}>
        <div className="container" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Link to="/" style={{ fontSize: '1.5rem', fontWeight: 'bold', color: 'var(--text-main)' }}>
            Locarion
          </Link>
          <nav>
            <Link to="/vehicles" style={{ fontWeight: 500, marginRight: '1.5rem' }}>Browse Vehicles</Link>
            <a href="http://localhost:5174" className="btn btn-primary">Agency Login</a>
          </nav>
        </div>
      </header>

      <main style={{ flex: 1, padding: '2rem 0' }}>
        <Outlet />
      </main>

      <footer style={{ background: 'var(--surface-color)', borderTop: '1px solid var(--border-color)', padding: '2rem 0', textAlign: 'center', color: 'var(--text-muted)' }}>
        <div className="container">
          <p>&copy; {new Date().getFullYear()} Locarion. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
};

export default PublicLayout;
