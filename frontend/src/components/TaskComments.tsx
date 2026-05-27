import React, { useState, useEffect } from 'react';
import { fetchApi } from '@/utils/api';

interface Comment {
  id: number;
  task_id: number;
  user_id: number;
  user_name: string;
  comment: string;
  created_at: string;
}

interface TaskCommentsProps {
  taskId: number;
}

export default function TaskComments({ taskId }: TaskCommentsProps) {
  const [comments, setComments] = useState<Comment[]>([]);
  const [newComment, setNewComment] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadComments();
    
    const eventSource = new EventSource('http://localhost:8081/sse.php');
    eventSource.onmessage = (e) => {
      if (e.data !== 'ping') {
        try {
          const data = JSON.parse(e.data);
          if (data.type === 'tasks_updated') {
            loadComments();
          }
        } catch (err) {}
      }
    };

    return () => {
      eventSource.close();
    };
  }, [taskId]);

  const loadComments = async () => {
    try {
      const res = await fetchApi(`/tasks/${taskId}/comments`);
      setComments(res.data || []);
    } catch (err) {
      console.error('Failed to load comments', err);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newComment.trim()) return;
    
    setLoading(true);
    try {
      await fetchApi(`/tasks/${taskId}/comments`, {
        method: 'POST',
        body: JSON.stringify({ comment: newComment }),
      });
      setNewComment('');
      loadComments();
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ marginTop: '20px', borderTop: '1px solid #eee', paddingTop: '15px' }}>
      <h4>Comments</h4>
      
      <div style={{ maxHeight: '200px', overflowY: 'auto', marginBottom: '15px' }}>
        {comments.length > 0 ? (
          comments.map(c => (
            <div key={c.id} style={{ marginBottom: '10px', padding: '10px', background: '#f9f9f9', borderRadius: '4px' }}>
              <div style={{ fontSize: '12px', color: '#666', marginBottom: '4px' }}>
                <strong>{c.user_name}</strong> - {new Date(c.created_at).toLocaleString()}
              </div>
              <div>{c.comment}</div>
            </div>
          ))
        ) : (
          <p style={{ color: '#888', fontSize: '14px' }}>No comments yet.</p>
        )}
      </div>

      <form onSubmit={handleSubmit} style={{ display: 'flex', gap: '10px' }}>
        <input 
          type="text" 
          value={newComment} 
          onChange={e => setNewComment(e.target.value)} 
          placeholder="Add a comment..."
          style={{ flex: 1, padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}
        />
        <button 
          type="submit" 
          disabled={loading || !newComment.trim()}
          style={{ padding: '8px 15px', background: '#333', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
        >
          {loading ? 'Posting...' : 'Post'}
        </button>
      </form>
    </div>
  );
}
