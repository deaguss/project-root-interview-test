import React, { useEffect, useState } from 'react';
import { fetchApi } from '@/utils/api';
import { useToast } from '@/contexts/ToastContext';

interface Attachment {
  id: number;
  task_id: number;
  file_name: string;
  file_path: string;
  file_size: number;
  mime_type: string;
  uploaded_at: string;
}

interface TaskAttachmentsProps {
  taskId: number;
  refreshTrigger?: number;
}

export default function TaskAttachments({ taskId, refreshTrigger }: TaskAttachmentsProps) {
  const [attachments, setAttachments] = useState<Attachment[]>([]);
  const { showToast } = useToast();

  useEffect(() => {
    loadAttachments();
  }, [taskId, refreshTrigger]);

  const loadAttachments = async () => {
    try {
      const res = await fetchApi(`/tasks/${taskId}/attachments`);
      setAttachments(res.data || []);
    } catch (err) {
      console.error(err);
    }
  };

  const handleDelete = async (id: number) => {
    if (confirm('Delete this attachment?')) {
      try {
        await fetchApi(`/attachments/${id}`, { method: 'DELETE' });
        showToast('Attachment deleted', 'success');
        loadAttachments();
      } catch (err: any) {
        showToast(err.message || 'Failed to delete attachment', 'error');
      }
    }
  };

  const handleDownload = async (id: number, fileName: string) => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch(`http://localhost:8080/api/attachments/${id}/download`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (!response.ok) throw new Error('Failed to download file');
      
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err: any) {
      showToast(err.message, 'error');
    }
  };

  if (attachments.length === 0) return null;

  return (
    <div style={{ marginTop: '10px' }}>
      <ul style={{ listStyleType: 'none', padding: 0 }}>
        {attachments.map(att => (
          <li key={att.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '8px', borderBottom: '1px solid #eee' }}>
            <div>
              <button 
                onClick={() => handleDownload(att.id, att.file_name)}
                style={{ background: 'none', border: 'none', color: '#0066cc', cursor: 'pointer', textDecoration: 'underline', padding: 0, fontSize: '16px' }}
              >
                {att.file_name}
              </button>
              <span style={{ color: '#888', fontSize: '12px', marginLeft: '10px' }}>
                ({Math.round(att.file_size / 1024)} KB)
              </span>
            </div>
            <button 
              onClick={() => handleDelete(att.id)}
              style={{ background: 'transparent', border: 'none', color: 'red', cursor: 'pointer', fontSize: '12px' }}
            >
              Delete
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
}
