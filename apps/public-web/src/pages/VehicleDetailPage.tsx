import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../api';

interface Vehicle {
  id: string;
  make: string;
  model: string;
  year: number;
  daily_rate: number;
  category?: { name: string };
  agency?: { name: string };
}

const VehicleDetailPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  
  const [vehicle, setVehicle] = useState<Vehicle | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  
  const [formData, setFormData] = useState({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    start_date: '',
    end_date: '',
  });
  
  const [submitting, setSubmitting] = useState(false);
  const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>({});

  useEffect(() => {
    const fetchVehicle = async () => {
      try {
        const response = await api.get(`/public/vehicles/${id}`);
        setVehicle(response.data.data);
      } catch (err) {
        setError('Vehicle not found or no longer available.');
      } finally {
        setLoading(false);
      }
    };
    fetchVehicle();
  }, [id]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
    // Clear validation error when typing
    if (validationErrors[e.target.name]) {
      setValidationErrors({ ...validationErrors, [e.target.name]: [] });
    }
    // Also clear vehicle_id error if they change dates
    if (e.target.name === 'start_date' || e.target.name === 'end_date') {
      if (validationErrors.vehicle_id) {
        setValidationErrors({ ...validationErrors, vehicle_id: [] });
      }
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setValidationErrors({});
    setError('');

    try {
      await api.post('/public/reservations', {
        ...formData,
        vehicle_id: id,
      });
      navigate('/reservation/success');
    } catch (err: any) {
      if (err.response && err.response.status === 422) {
        setValidationErrors(err.response.data.errors);
      } else {
        setError('An unexpected error occurred. Please try again.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) return <div className="container"><p>Loading vehicle details...</p></div>;
  if (error || !vehicle) return <div className="container"><p className="error-text">{error}</p></div>;

  return (
    <div className="container">
      <div className="grid" style={{ gridTemplateColumns: '1fr 1fr' }}>
        <div>
          <h1 style={{ marginBottom: '0.5rem' }}>{vehicle.make} {vehicle.model}</h1>
          <p style={{ fontSize: '1.25rem', color: 'var(--text-muted)', marginBottom: '2rem' }}>
            {vehicle.year} • {vehicle.category?.name}
          </p>
          
          <div className="card" style={{ marginBottom: '2rem' }}>
            <h3 style={{ marginBottom: '1rem' }}>Pricing</h3>
            <p style={{ fontSize: '1.5rem', fontWeight: 'bold', color: 'var(--primary-color)' }}>
              ${(vehicle.daily_rate / 100).toFixed(2)} <span style={{ fontSize: '1rem', color: 'var(--text-muted)', fontWeight: 'normal' }}>/ day</span>
            </p>
          </div>
          
          <div className="card">
            <h3 style={{ marginBottom: '1rem' }}>Provider</h3>
            <p>{vehicle.agency?.name}</p>
          </div>
        </div>

        <div>
          <div className="card">
            <h2 style={{ marginBottom: '1.5rem' }}>Request Reservation</h2>
            
            {validationErrors.vehicle_id && (
              <div style={{ background: '#fee2e2', padding: '1rem', borderRadius: '0.375rem', marginBottom: '1.5rem' }}>
                <p className="error-text" style={{ margin: 0 }}>{validationErrors.vehicle_id[0]}</p>
              </div>
            )}
            
            <form onSubmit={handleSubmit}>
              <div className="form-group">
                <label className="form-label">Full Name *</label>
                <input required type="text" name="customer_name" className="form-input" value={formData.customer_name} onChange={handleChange} />
                {validationErrors.customer_name && <p className="error-text">{validationErrors.customer_name[0]}</p>}
              </div>
              
              <div className="form-group">
                <label className="form-label">Email Address *</label>
                <input required type="email" name="customer_email" className="form-input" value={formData.customer_email} onChange={handleChange} />
                {validationErrors.customer_email && <p className="error-text">{validationErrors.customer_email[0]}</p>}
              </div>

              <div className="form-group">
                <label className="form-label">Phone Number</label>
                <input type="tel" name="customer_phone" className="form-input" value={formData.customer_phone} onChange={handleChange} />
                {validationErrors.customer_phone && <p className="error-text">{validationErrors.customer_phone[0]}</p>}
              </div>
              
              <div className="grid" style={{ gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                <div className="form-group">
                  <label className="form-label">Start Date *</label>
                  <input required type="date" name="start_date" className="form-input" value={formData.start_date} onChange={handleChange} />
                  {validationErrors.start_date && <p className="error-text">{validationErrors.start_date[0]}</p>}
                </div>
                
                <div className="form-group">
                  <label className="form-label">End Date *</label>
                  <input required type="date" name="end_date" className="form-input" value={formData.end_date} onChange={handleChange} />
                  {validationErrors.end_date && <p className="error-text">{validationErrors.end_date[0]}</p>}
                </div>
              </div>
              
              <button type="submit" className="btn btn-primary" style={{ width: '100%', marginTop: '1rem' }} disabled={submitting}>
                {submitting ? 'Submitting...' : 'Submit Request'}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default VehicleDetailPage;
