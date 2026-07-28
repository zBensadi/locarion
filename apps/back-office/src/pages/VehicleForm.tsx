import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { apiClient } from '../api';

const VehicleForm = () => {
  const { id } = useParams<{ id: string }>();
  const isEditing = !!id;
  const navigate = useNavigate();

  const [categories, setCategories] = useState<{ id: string; name: string }[]>([]);
  const [loading, setLoading] = useState(isEditing);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  
  const [formData, setFormData] = useState({
    category_id: '',
    make: '',
    model: '',
    year: new Date().getFullYear(),
    license_plate: '',
    daily_rate: '',
    status: 'available',
  });

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await apiClient.get('/categories');
        setCategories(response.data.data);
        if (!isEditing && response.data.data.length > 0) {
          setFormData(prev => ({ ...prev, category_id: response.data.data[0].id }));
        }
      } catch (err) {
        setError('Failed to load categories');
      }
    };
    
    fetchCategories();

    if (isEditing) {
      const fetchVehicle = async () => {
        try {
          const response = await apiClient.get(`/vehicles/${id}`);
          const vehicle = response.data.data;
          setFormData({
            category_id: vehicle.category_id || '',
            make: vehicle.make,
            model: vehicle.model,
            year: vehicle.year,
            license_plate: vehicle.license_plate,
            daily_rate: (vehicle.daily_rate / 100).toString(),
            status: vehicle.status,
          });
        } catch (err) {
          setError('Failed to load vehicle details');
        } finally {
          setLoading(false);
        }
      };
      fetchVehicle();
    }
  }, [id, isEditing]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');

    const payload = {
      ...formData,
      daily_rate: Math.round(parseFloat(formData.daily_rate) * 100), // convert to cents
      year: parseInt(String(formData.year), 10),
    };

    try {
      if (isEditing) {
        await apiClient.put(`/vehicles/${id}`, payload);
      } else {
        await apiClient.post('/vehicles', payload);
      }
      navigate('/fleet');
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to save vehicle');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <h1 style={{ marginBottom: '2rem' }}>{isEditing ? 'Edit Vehicle' : 'Add New Vehicle'}</h1>

      <div className="card" style={{ maxWidth: '600px' }}>
        {error && (
          <div style={{ background: '#fee2e2', padding: '1rem', borderRadius: '0.375rem', marginBottom: '1.5rem' }}>
            <p className="error-text" style={{ margin: 0 }}>{error}</p>
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Category</label>
            <select name="category_id" className="form-select" value={formData.category_id} onChange={handleChange} required>
              <option value="">Select Category</option>
              {categories.map(c => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
            <div className="form-group">
              <label className="form-label">Make</label>
              <input type="text" name="make" className="form-input" value={formData.make} onChange={handleChange} required />
            </div>
            <div className="form-group">
              <label className="form-label">Model</label>
              <input type="text" name="model" className="form-input" value={formData.model} onChange={handleChange} required />
            </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
            <div className="form-group">
              <label className="form-label">Year</label>
              <input type="number" name="year" className="form-input" value={formData.year} onChange={handleChange} required />
            </div>
            <div className="form-group">
              <label className="form-label">License Plate</label>
              <input type="text" name="license_plate" className="form-input" value={formData.license_plate} onChange={handleChange} required />
            </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
            <div className="form-group">
              <label className="form-label">Daily Rate ($)</label>
              <input type="number" step="0.01" name="daily_rate" className="form-input" value={formData.daily_rate} onChange={handleChange} required />
            </div>
            <div className="form-group">
              <label className="form-label">Status</label>
              <select name="status" className="form-select" value={formData.status} onChange={handleChange} required>
                <option value="available">Available</option>
                <option value="reserved">Reserved</option>
                <option value="maintenance">Maintenance</option>
                <option value="retired">Retired</option>
              </select>
            </div>
          </div>

          <div style={{ display: 'flex', gap: '1rem', marginTop: '1.5rem' }}>
            <button type="submit" className="btn btn-primary" disabled={submitting}>
              {submitting ? 'Saving...' : 'Save Vehicle'}
            </button>
            <button type="button" className="btn" onClick={() => navigate('/fleet')} style={{ background: '#f1f5f9', color: 'var(--text-main)' }}>
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default VehicleForm;
