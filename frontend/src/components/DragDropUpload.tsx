import React, { useState, useRef } from 'react';
import { fetchApi } from '@/utils/api';

interface DragDropUploadProps {
  taskId: number;
  onUploadSuccess?: () => void;
}

export default function DragDropUpload({ taskId, onUploadSuccess }: DragDropUploadProps) {
  const [isDragging, setIsDragging] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
  };

  const handleDrop = async (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      await uploadFile(e.dataTransfer.files[0]);
    }
  };

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      await uploadFile(e.target.files[0]);
    }
  };

  const uploadFile = async (file: File) => {
    setError(null);
    setSuccess(null);
    setUploading(true);

    const formData = new FormData();
    formData.append('attachment', file);

    try {
      const token = localStorage.getItem('token');
      const response = await fetch(`http://localhost:8080/api/tasks/${taskId}/attachments`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData,
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Upload failed');
      }

      setSuccess('File uploaded successfully!');
      if (onUploadSuccess) onUploadSuccess();
      
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    } catch (err: any) {
      setError(err.message);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div style={{ marginTop: '15px' }}>
      <div 
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => fileInputRef.current?.click()}
        style={{
          border: isDragging ? '2px dashed #000' : '2px dashed #ccc',
          background: isDragging ? '#f0f0f0' : '#fafafa',
          padding: '20px',
          textAlign: 'center',
          cursor: 'pointer',
          borderRadius: '4px'
        }}
      >
        {uploading ? (
          <p>Uploading...</p>
        ) : (
          <p>Drag and drop a file here, or click to select</p>
        )}
        <input 
          type="file" 
          ref={fileInputRef} 
          onChange={handleFileChange} 
          style={{ display: 'none' }} 
        />
      </div>
      
      {error && <p style={{ color: 'red', fontSize: '14px', marginTop: '5px' }}>{error}</p>}
      {success && <p style={{ color: 'green', fontSize: '14px', marginTop: '5px' }}>{success}</p>}
    </div>
  );
}
