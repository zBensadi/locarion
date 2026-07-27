import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { apiClient } from '../api';

interface Reservation {
  id: string;
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  start_date: string;
  end_date: string;
  daily_rate_snapshot: number;
  total_price: number;
  status: string;
  created_at: string;
  vehicle?: { make: string; model: string; year: number; license_plate: string };
}

const ReservationDetail = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  
  const [reservation, setReservation] = useState<Reservation | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [actionLoading, setActionLoading] = useState(false);

  const fetchReservation = async () => {
    try {
      const response = await apiClient.get(`/reservations/${id}`);
      setReservation(response.data.data);
    } catch (err) {
      setError('Failed to load reservation details.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReservation();
  }, [id]);

  const handleUpdateStatus = async (status: string) => {
    setActionLoading(true);
    try {
      await apiClient.put(`/reservations/${id}/status`, { status });
      await fetchReservation();
    } catch (err: any) {
      setError(err.response?.data?.message || `Failed to update status to ${status}`);
    } finally {
      setActionLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error || !reservation) return <div className="error-text">{error}</div>;

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <h1>Reservation Details</h1>
        <button onClick={() => navigate('/reservations')} className="btn" style={{ background: '#f1f5f9' }}>
          Back to List
        </button>
      </div>

      <div className="grid" style={{ gridTemplateColumns: '1fr 1fr', gap: '2rem' }}>
        <div className="card">
          <h3 style={{ marginBottom: '1rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>Customer Information</h3>
          <p><strong>Name:</strong> {reservation.customer_name}</p>
          <p><strong>Email:</strong> {reservation.customer_email}</p>
          <p><strong>Phone:</strong> {reservation.customer_phone || 'N/A'}</p>
          <p><strong>Requested On:</strong> {new Date(reservation.created_at).toLocaleString()}</p>
        </div>

        <div className="card">
          <h3 style={{ marginBottom: '1rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>Vehicle Information</h3>
          {reservation.vehicle ? (
            <>
              <p><strong>Vehicle:</strong> {reservation.vehicle.year} {reservation.vehicle.make} {reservation.vehicle.model}</p>
              <p><strong>License Plate:</strong> {reservation.vehicle.license_plate}</p>
            </>
          ) : (
            <p>Vehicle information unavailable.</p>
          )}
        </div>

        <div className="card">
          <h3 style={{ marginBottom: '1rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>Booking Details</h3>
          <p><strong>Start Date:</strong> {reservation.start_date}</p>
          <p><strong>End Date:</strong> {reservation.end_date}</p>
          <p><strong>Daily Rate (Snapshot):</strong> ${(reservation.daily_rate_snapshot / 100).toFixed(2)}</p>
          <p style={{ fontSize: '1.25rem', marginTop: '1rem' }}>
            <strong>Total Price:</strong> <span style={{ color: 'var(--primary-color)' }}>${(reservation.total_price / 100).toFixed(2)}</span>
          </p>
        </div>

        <div className="card">
          <h3 style={{ marginBottom: '1rem', borderBottom: '1px solid var(--border-color)', paddingBottom: '0.5rem' }}>Status Management</h3>
          <p style={{ marginBottom: '1.5rem' }}>
            <strong>Current Status:</strong> <span className={`badge badge-${reservation.status}`}>{reservation.status}</span>
          </p>

          <div style={{ display: 'flex', gap: '1rem', flexWrap: 'wrap' }}>
            {reservation.status === 'pending' && (
              <>
                <button 
                  className="btn btn-success" 
                  onClick={() => handleUpdateStatus('confirmed')} 
                  disabled={actionLoading}
                >
                  Approve Reservation
                </button>
                <button 
                  className="btn btn-danger" 
                  onClick={() => handleUpdateStatus('rejected')} 
                  disabled={actionLoading}
                >
                  Reject
                </button>
              </>
            )}

            {reservation.status === 'confirmed' && (
              <>
                <button 
                  className="btn" 
                  style={{ background: '#4338ca', color: 'white' }}
                  onClick={() => handleUpdateStatus('completed')} 
                  disabled={actionLoading}
                >
                  Mark as Completed
                </button>
                <button 
                  className="btn" 
                  style={{ background: '#4b5563', color: 'white' }}
                  onClick={() => handleUpdateStatus('cancelled')} 
                  disabled={actionLoading}
                >
                  Cancel Booking
                </button>
              </>
            )}

            {(reservation.status === 'rejected' || reservation.status === 'cancelled' || reservation.status === 'completed') && (
              <p style={{ color: 'var(--text-muted)' }}>This reservation is in a terminal state and cannot be modified further.</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default ReservationDetail;
