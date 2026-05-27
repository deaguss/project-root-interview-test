import React, { useState, useRef } from 'react';
import { useToast } from '@/contexts/ToastContext';

interface DragDropUploadProps {
  taskId: number;
  onUploadSuccess?: () => void;
}

export default function DragDropUpload({ taskId, onUploadSuccess }: DragDropUploadProps) {
  const [isDragging, setIsDragging] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const { showToast } = useToast();

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      uploadFile(e.dataTransfer.files[0]);
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      uploadFile(e.target.files[0]);
    }
  };

  const uploadFile = (file: File) => {
    setUploading(true);
    setProgress(0);

    const formData = new FormData();
    formData.append('attachment', file);

    const xhr = new XMLHttpRequest();
    const token = localStorage.getItem('token');

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        const percent = Math.round((event.loaded / event.total) * 100);
        setProgress(percent);
      }
    };

    xhr.onload = () => {
      setUploading(false);
      if (xhr.status >= 200 && xhr.status < 300) {
        showToast('File uploaded successfully!', 'success');
        if (onUploadSuccess) onUploadSuccess();
        if (fileInputRef.current) fileInputRef.current.value = '';
      } else {
        let msg = 'Upload failed';
        try {
          const res = JSON.parse(xhr.responseText);
          if (res.message) msg = res.message;
        } catch (e) {}
        showToast(msg, 'error');
      }
    };

    xhr.onerror = () => {
      setUploading(false);
      showToast('Network error occurred during upload', 'error');
    };

    xhr.open('POST', `http://localhost:8080/api/tasks/${taskId}/attachments`);
    if (token) {
      xhr.setRequestHeader('Authorization', `Bearer ${token}`);
    }
    xhr.send(formData);
  };

  return (
    <div style={{ marginTop: '15px' }}>
      <div 
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => !uploading && fileInputRef.current?.click()}
        style={{
          border: isDragging ? '2px dashed #000' : '2px dashed #ccc',
          background: isDragging ? '#f0f0f0' : '#fafafa',
          padding: '20px',
          textAlign: 'center',
          cursor: uploading ? 'not-allowed' : 'pointer',
          borderRadius: '4px',
          position: 'relative'
        }}
      >
        {uploading ? (
          <div>
            <p>Uploading... {progress}%</p>
            <div style={{ width: '100%', background: '#ddd', height: '5px', borderRadius: '3px', marginTop: '10px' }}>
              <div style={{ width: `${progress}%`, background: '#28a745', height: '100%', borderRadius: '3px', transition: 'width 0.2s' }}></div>
            </div>
          </div>
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
    </div>
  );
}
