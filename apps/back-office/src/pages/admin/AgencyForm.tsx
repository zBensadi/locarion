import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { apiClient } from '../../api';

const AgencyForm = () => {
  const { id } = useParams<{ id: string }>();
  const isEditing = !!id;
  const navigate = useNavigate();

  const [loading, setLoading] = useState(isEditing);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  
  const [formData, setFormData] = useState({
    name: '',
    slug: '',
    status: 'active',
    admin_name: '',
    admin_email: '',
  });

  const [createdAdmin, setCreatedAdmin] = useState<{ email: string; temporary_password: string } | null>(null);

  useEffect(() => {
    if (isEditing) {
      const fetchAgency = async () => {
        try {
          const response = await apiClient.get(`/admin/agencies/${id}`);
          const agency = response.data.data;
          setFormData({
            name: agency.name,
            slug: agency.slug,
            status: agency.status,
            admin_name: '',
            admin_email: '',
          });
        } catch (err) {
          setError('Failed to load agency details');
        } finally {
          setLoading(false);
        }
      };
      fetchAgency();
    }
  }, [id, isEditing]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const generateSlug = () => {
    if (!formData.name) return;
    const slug = formData.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    setFormData({ ...formData, slug });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');

    try {
      if (isEditing) {
        await apiClient.put(`/admin/agencies/${id}`, {
          name: formData.name,
          slug: formData.slug,
          status: formData.status
        });
        navigate('/admin/agencies');
      } else {
        const response = await apiClient.post('/admin/agencies', formData);
        setCreatedAdmin(response.data.admin);
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to save agency');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) return <div>Loading...</div>;

  if (createdAdmin) {
    return (
      <div>
        <h1 style={{ marginBottom: '2rem' }}>Agency Created Successfully</h1>
        <div className="card" style={{ maxWidth: '600px' }}>
          <div style={{ background: '#d1fae5', padding: '1.5rem', borderRadius: '0.5rem', border: '1px solid #10b981' }}>
            <h3 style={{ color: '#065f46', marginBottom: '1rem' }}>Initial Admin Credentials</h3>
            <p style={{ marginBottom: '0.5rem', color: '#064e3b' }}>
              <strong>Email:</strong> {createdAdmin.email}
            </p>
            <p style={{ marginBottom: '1rem', color: '#064e3b' }}>
              <strong>Temporary Password:</strong> <span style={{ fontFamily: 'monospace', background: 'white', padding: '0.25rem 0.5rem', borderRadius: '0.25rem', border: '1px solid #cbd5e1' }}>{createdAdmin.temporary_password}</span>
            </p>
            <p style={{ fontSize: '0.875rem', color: '#047857' }}>
              Please copy this password securely. It will not be shown again.
            </p>
          </div>
          <button 
            className="btn btn-primary" 
            style={{ marginTop: '1.5rem' }}
            onClick={() => navigate('/admin/agencies')}
          >
            Back to Agencies
          </button>
        </div>
      </div>
    );
  }

  return (
    <div>
      <h1 style={{ marginBottom: '2rem' }}>{isEditing ? 'Edit Agency' : 'Create Agency'}</h1>

      <div className="card" style={{ maxWidth: '600px' }}>
        {error && (
          <div style={{ background: '#fee2e2', padding: '1rem', borderRadius: '0.375rem', marginBottom: '1.5rem' }}>
            <p className="error-text" style={{ margin: 0 }}>{error}</p>
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Agency Name</label>
            <input type="text" name="name" className="form-input" value={formData.name} onChange={handleChange} onBlur={!isEditing ? generateSlug : undefined} required />
          </div>
          
          <div className="form-group">
            <label className="form-label">Slug</label>
            <input type="text" name="slug" className="form-input" value={formData.slug} onChange={handleChange} required />
          </div>

          <div className="form-group">
            <label className="form-label">Status</label>
            <select name="status" className="form-select" value={formData.status} onChange={handleChange} required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          {!isEditing && (
            <>
              <h3 style={{ marginTop: '2rem', marginBottom: '1rem', paddingBottom: '0.5rem', borderBottom: '1px solid var(--border-color)' }}>
                Initial Admin Account
              </h3>
              
              <div className="form-group">
                <label className="form-label">Admin Name</label>
                <input type="text" name="admin_name" className="form-input" value={formData.admin_name} onChange={handleChange} required={!isEditing} />
              </div>
              
              <div className="form-group">
                <label className="form-label">Admin Email</label>
                <input type="email" name="admin_email" className="form-input" value={formData.admin_email} onChange={handleChange} required={!isEditing} />
              </div>
            </>
          )}

          <div style={{ display: 'flex', gap: '1rem', marginTop: '2rem' }}>
            <button type="submit" className="btn btn-primary" disabled={submitting}>
              {submitting ? 'Saving...' : (isEditing ? 'Update Agency' : 'Create Agency')}
            </button>
            <button type="button" className="btn" onClick={() => navigate('/admin/agencies')} style={{ background: '#f1f5f9', color: 'var(--text-main)' }}>
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default AgencyForm;
