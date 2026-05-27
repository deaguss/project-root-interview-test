import React, { useState, useEffect, useRef } from 'react';
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
  ws?: WebSocket | null;
}

export default function TaskComments({ taskId, ws }: TaskCommentsProps) {
  const [comments, setComments] = useState<Comment[]>([]);
  const [newComment, setNewComment] = useState('');
  const [loading, setLoading] = useState(false);
  const [typingUsers, setTypingUsers] = useState<string[]>([]);
  
  const typingTimeouts = useRef<Record<string, NodeJS.Timeout>>({});
  let debounceTimeout = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    loadComments();
    
    if (ws) {
      const handleWsMessage = (e: MessageEvent) => {
        try {
          const data = JSON.parse(e.data);
          if (data.type === 'typing' && data.taskId === taskId) {
            const userName = data.user.name;
            setTypingUsers(prev => prev.includes(userName) ? prev : [...prev, userName]);
            
            if (typingTimeouts.current[userName]) {
              clearTimeout(typingTimeouts.current[userName]);
            }
            
            typingTimeouts.current[userName] = setTimeout(() => {
              setTypingUsers(prev => prev.filter(n => n !== userName));
            }, 3000);
          } else if (data.type === 'stop_typing' && data.taskId === taskId) {
            setTypingUsers(prev => prev.filter(n => n !== data.user.name));
          }
        } catch (err) {}
      };

      ws.addEventListener('message', handleWsMessage);
      
      return () => {
        ws.removeEventListener('message', handleWsMessage);
      };
    }
  }, [taskId, ws]);

  const handleKeyDown = () => {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify({ type: 'typing', taskId }));
      
      if (debounceTimeout.current) clearTimeout(debounceTimeout.current);
      debounceTimeout.current = setTimeout(() => {
        ws.send(JSON.stringify({ type: 'stop_typing', taskId }));
      }, 2000);
    }
  };

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

      <form onSubmit={handleSubmit} style={{ display: 'flex', flexWrap: 'wrap', gap: '10px' }}>
        <div style={{ width: '100%', minHeight: '20px', fontSize: '12px', color: '#666', fontStyle: 'italic' }}>
          {typingUsers.length > 0 && `${typingUsers.join(', ')} ${typingUsers.length > 1 ? 'are' : 'is'} typing...`}
        </div>
        <input 
          type="text" 
          value={newComment} 
          onChange={e => setNewComment(e.target.value)} 
          onKeyDown={handleKeyDown}
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
