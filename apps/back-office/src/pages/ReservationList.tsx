import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiClient } from '../api';

interface Reservation {
  id: string;
  customer_name: string;
  start_date: string;
  end_date: string;
  total_price: number;
  status: string;
  vehicle?: { make: string; model: string; license_plate: string };
}

const ReservationList = () => {
  const [reservations, setReservations] = useState<Reservation[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchReservations = async () => {
    try {
      const response = await apiClient.get('/reservations');
      setReservations(response.data.data);
    } catch (error) {
      console.error('Failed to fetch reservations', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReservations();
  }, []);

  return (
    <div>
      <h1 style={{ marginBottom: '2rem' }}>Reservations</h1>

      <div className="card">
        {loading ? (
          <p>Loading reservations...</p>
        ) : (
          <div className="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Customer</th>
                  <th>Vehicle</th>
                  <th>Dates</th>
                  <th>Total Price</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {reservations.length === 0 ? (
                  <tr>
                    <td colSpan={6} style={{ textAlign: 'center', color: 'var(--text-muted)' }}>
                      No reservations found.
                    </td>
                  </tr>
                ) : (
                  reservations.map(reservation => (
                    <tr key={reservation.id}>
                      <td>{reservation.customer_name}</td>
                      <td>
                        {reservation.vehicle ? (
                          <>{reservation.vehicle.make} {reservation.vehicle.model} <br/><span style={{fontSize: '0.8rem', color: 'var(--text-muted)'}}>{reservation.vehicle.license_plate}</span></>
                        ) : 'Unknown Vehicle'}
                      </td>
                      <td>
                        {reservation.start_date} to {reservation.end_date}
                      </td>
                      <td>${(reservation.total_price / 100).toFixed(2)}</td>
                      <td>
                        <span className={`badge badge-${reservation.status}`}>
                          {reservation.status}
                        </span>
                      </td>
                      <td>
                        <Link to={`/reservations/${reservation.id}`} style={{ fontWeight: 500 }}>View</Link>
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

export default ReservationList;
