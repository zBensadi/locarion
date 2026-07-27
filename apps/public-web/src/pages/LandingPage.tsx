import { Link } from 'react-router-dom';

const LandingPage = () => {
  return (
    <div className="container" style={{ textAlign: 'center', padding: '4rem 0' }}>
      <h1 style={{ fontSize: '3rem', marginBottom: '1rem' }}>Find Your Perfect Ride</h1>
      <p style={{ fontSize: '1.25rem', color: 'var(--text-muted)', marginBottom: '2rem', maxWidth: '600px', margin: '0 auto 2rem auto' }}>
        Locarion connects you with the best car rental agencies. Browse a wide selection of vehicles and book your next trip with ease.
      </p>
      <Link to="/vehicles" className="btn btn-primary" style={{ fontSize: '1.25rem', padding: '0.75rem 2rem' }}>
        Browse Vehicles
      </Link>
    </div>
  );
};

export default LandingPage;
