import React, { useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/router';
import { fetchApi } from '@/utils/api';
import TaskForm from '@/components/TaskForm';
import DragDropUpload from '@/components/DragDropUpload';
import TaskComments from '@/components/TaskComments';
import { useToast } from '@/contexts/ToastContext';

export default function Dashboard() {
  const [tasks, setTasks] = useState<any[]>([]);
  const [user, setUser] = useState<any>(null);
  const [showForm, setShowForm] = useState(false);
  const [editingTask, setEditingTask] = useState<any>(null);
  const [expandedTask, setExpandedTask] = useState<number | null>(null);
  
  // Filters
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [filterPriority, setFilterPriority] = useState('');

  const router = useRouter();
  const { showToast } = useToast();

  const loadTasks = useCallback(async () => {
    try {
      let query = `/tasks?per_page=50`;
      if (search) query += `&search=${encodeURIComponent(search)}`;
      if (filterStatus) query += `&status=${encodeURIComponent(filterStatus)}`;
      if (filterPriority) query += `&priority=${encodeURIComponent(filterPriority)}`;

      const res = await fetchApi(query);
      setTasks(res.data.data || []);
    } catch (err) {
      console.error(err);
    }
  }, [search, filterStatus, filterPriority]);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');
    
    if (!token) {
      router.push('/login');
      return;
    }
    if (userData) setUser(JSON.parse(userData));

    loadTasks();

    const eventSource = new EventSource('http://localhost:8081/sse.php');
    eventSource.onmessage = (e) => {
      if (e.data !== 'ping') {
        try {
          const data = JSON.parse(e.data);
          if (data.type === 'tasks_updated') {
            loadTasks();
          }
        } catch (err) {}
      }
    };

    return () => eventSource.close();
  }, [loadTasks, router]);

  const handleLogout = async () => {
    try {
      await fetchApi('/auth/logout', { method: 'POST' });
    } catch (err) {} 
    finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      router.push('/login');
    }
  };

  const handleDelete = async (id: number) => {
    if (confirm('Are you sure you want to delete this task?')) {
      try {
        await fetchApi(`/tasks/${id}`, { method: 'DELETE' });
        showToast('Task deleted successfully', 'success');
        loadTasks();
      } catch (err: any) {
        showToast(err.message || 'Failed to delete', 'error');
      }
    }
  };

  if (!user) return <div style={{ padding: '20px' }}>Loading...</div>;

  return (
    <div style={{ padding: '20px', fontFamily: 'sans-serif', maxWidth: '1000px', margin: '0 auto' }}>
      <header style={{ 
        display: 'flex', justifyContent: 'space-between', alignItems: 'center',
        borderBottom: '1px solid #ccc', paddingBottom: '10px', marginBottom: '20px', flexWrap: 'wrap', gap: '10px'
      }}>
        <h2 style={{ margin: 0 }}>Task Dashboard</h2>
        <div>
          <span style={{ marginRight: '15px' }}>Welcome, {user.name}</span>
          <button onClick={handleLogout} style={{ padding: '5px 10px', cursor: 'pointer' }}>Logout</button>
        </div>
      </header>

      <main>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px', flexWrap: 'wrap', gap: '10px' }}>
          <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
            <input 
              type="text" 
              placeholder="Search tasks..." 
              value={search}
              onChange={e => setSearch(e.target.value)}
              style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}
            />
            <select value={filterStatus} onChange={e => setFilterStatus(e.target.value)} style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}>
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
            <select value={filterPriority} onChange={e => setFilterPriority(e.target.value)} style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}>
              <option value="">All Priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          
          <button 
            onClick={() => { setEditingTask(null); setShowForm(!showForm); }}
            style={{ padding: '8px 15px', background: '#333', color: 'white', border: 'none', cursor: 'pointer', borderRadius: '4px' }}
          >
            {showForm ? 'Close Form' : '+ New Task'}
          </button>
        </div>

        {showForm && (
          <TaskForm 
            task={editingTask}
            onSuccess={() => {
              setShowForm(false);
              showToast(editingTask ? 'Task updated' : 'Task created', 'success');
              loadTasks();
            }}
            onCancel={() => setShowForm(false)}
          />
        )}

        <div style={{ overflowX: 'auto' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: '600px' }}>
            <thead>
              <tr style={{ background: '#f5f5f5', textAlign: 'left' }}>
                <th style={{ padding: '10px', border: '1px solid #ccc' }}>ID</th>
                <th style={{ padding: '10px', border: '1px solid #ccc' }}>Title</th>
                <th style={{ padding: '10px', border: '1px solid #ccc' }}>Status</th>
                <th style={{ padding: '10px', border: '1px solid #ccc' }}>Priority</th>
                <th style={{ padding: '10px', border: '1px solid #ccc', width: '200px' }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {tasks.length > 0 ? (
                tasks.map(task => (
                  <React.Fragment key={task.id}>
                    <tr>
                      <td style={{ padding: '10px', border: '1px solid #ccc' }}>{task.id}</td>
                      <td style={{ padding: '10px', border: '1px solid #ccc' }}>{task.title}</td>
                      <td style={{ padding: '10px', border: '1px solid #ccc' }}>
                        <span style={{ 
                          padding: '3px 8px', borderRadius: '12px', fontSize: '12px',
                          background: task.status === 'completed' ? '#d4edda' : task.status === 'in_progress' ? '#fff3cd' : '#e2e3e5'
                        }}>
                          {task.status}
                        </span>
                      </td>
                      <td style={{ padding: '10px', border: '1px solid #ccc' }}>{task.priority}</td>
                      <td style={{ padding: '10px', border: '1px solid #ccc' }}>
                        <button 
                          onClick={() => setExpandedTask(expandedTask === task.id ? null : task.id)}
                          style={{ marginRight: '5px', padding: '4px 8px', cursor: 'pointer' }}
                        >
                          {expandedTask === task.id ? 'Hide' : 'Details'}
                        </button>
                        <button 
                          onClick={() => { setEditingTask(task); setShowForm(true); }}
                          style={{ marginRight: '5px', padding: '4px 8px', cursor: 'pointer' }}
                        >
                          Edit
                        </button>
                        <button 
                          onClick={() => handleDelete(task.id)}
                          style={{ padding: '4px 8px', background: '#dc3545', color: 'white', border: 'none', cursor: 'pointer' }}
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                    
                    {expandedTask === task.id && (
                      <tr>
                        <td colSpan={5} style={{ padding: '15px', border: '1px solid #ccc', background: '#fafafa' }}>
                          <p><strong>Description:</strong> {task.description || 'No description'}</p>
                          
                          <div style={{ marginTop: '15px' }}>
                            <strong>Attachments:</strong>
                            <DragDropUpload taskId={task.id} onUploadSuccess={loadTasks} />
                          </div>
                          
                          <TaskComments taskId={task.id} />
                        </td>
                      </tr>
                    )}
                  </React.Fragment>
                ))
              ) : (
                <tr>
                  <td colSpan={5} style={{ padding: '10px', border: '1px solid #ccc', textAlign: 'center' }}>
                    No tasks found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </main>
    </div>
  );
}
