import { Link } from 'react-router-dom';

const ReservationSuccessPage = () => {
  return (
    <div className="container" style={{ textAlign: 'center', padding: '4rem 0' }}>
      <div style={{ background: '#ecfdf5', color: 'var(--success-color)', display: 'inline-block', padding: '1rem', borderRadius: '50%', marginBottom: '1.5rem' }}>
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
      </div>
      <h1 style={{ marginBottom: '1rem' }}>Reservation Submitted Successfully!</h1>
      <p style={{ fontSize: '1.25rem', color: 'var(--text-muted)', maxWidth: '600px', margin: '0 auto 2rem auto' }}>
        Thank you for your reservation request. The agency has received your booking and will review it shortly. 
        You will be contacted via email once your reservation is confirmed.
      </p>
      <Link to="/vehicles" className="btn btn-primary" style={{ padding: '0.75rem 2rem' }}>
        Return to Vehicles
      </Link>
    </div>
  );
};

export default ReservationSuccessPage;
