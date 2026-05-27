import { useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import { fetchApi } from '@/utils/api';

export default function Dashboard() {
  const [tasks, setTasks] = useState<any[]>([]);
  const [user, setUser] = useState<any>(null);
  const router = useRouter();

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');
    
    if (!token) {
      router.push('/login');
      return;
    }

    if (userData) {
      setUser(JSON.parse(userData));
    }

    loadTasks();
  }, []);

  const loadTasks = async () => {
    try {
      const res = await fetchApi('/tasks');
      setTasks(res.data.data || []);
    } catch (err) {
      console.error(err);
    }
  };

  const handleLogout = async () => {
    try {
      await fetchApi('/auth/logout', { method: 'POST' });
    } catch (err) {
      console.error(err);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      router.push('/login');
    }
  };

  if (!user) return <div style={{ padding: '20px' }}>Loading...</div>;

  return (
    <div style={{ padding: '20px', fontFamily: 'sans-serif' }}>
      <header style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px solid #ccc', paddingBottom: '10px', marginBottom: '20px' }}>
        <h2>Task Dashboard</h2>
        <div>
          <span style={{ marginRight: '15px' }}>Welcome, {user.name}</span>
          <button onClick={handleLogout}>Logout</button>
        </div>
      </header>

      <main>
        <h3>Your Tasks</h3>
        <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: '10px' }}>
          <thead>
            <tr style={{ background: '#f5f5f5', textAlign: 'left' }}>
              <th style={{ padding: '10px', border: '1px solid #ccc' }}>ID</th>
              <th style={{ padding: '10px', border: '1px solid #ccc' }}>Title</th>
              <th style={{ padding: '10px', border: '1px solid #ccc' }}>Status</th>
              <th style={{ padding: '10px', border: '1px solid #ccc' }}>Priority</th>
            </tr>
          </thead>
          <tbody>
            {tasks.length > 0 ? (
              tasks.map(task => (
                <tr key={task.id}>
                  <td style={{ padding: '10px', border: '1px solid #ccc' }}>{task.id}</td>
                  <td style={{ padding: '10px', border: '1px solid #ccc' }}>{task.title}</td>
                  <td style={{ padding: '10px', border: '1px solid #ccc' }}>{task.status}</td>
                  <td style={{ padding: '10px', border: '1px solid #ccc' }}>{task.priority}</td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan={4} style={{ padding: '10px', border: '1px solid #ccc', textAlign: 'center' }}>
                  No tasks found
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </main>
    </div>
  );
}
