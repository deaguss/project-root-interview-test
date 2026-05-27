import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import TaskForm from '@/components/TaskForm';
import { ToastProvider } from '@/contexts/ToastContext';

jest.mock('@/utils/api', () => ({
  fetchApi: jest.fn(),
}));

import { fetchApi } from '@/utils/api';

describe('TaskForm Component', () => {
  const mockOnSuccess = jest.fn();
  const mockOnCancel = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  const renderForm = (task?: any) => {
    return render(
      <ToastProvider>
        <TaskForm task={task} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
      </ToastProvider>
    );
  };

  it('renders create form correctly', () => {
    renderForm();
    expect(screen.getByText('Create New Task')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Save' })).toBeInTheDocument();
  });

  it('renders edit form with existing data', () => {
    const task = { id: 1, title: 'Test Task', status: 'pending', priority: 'high' };
    renderForm(task);
    
    expect(screen.getByText('Edit Task')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Test Task')).toBeInTheDocument();
  });

  it('calls fetchApi on submit', async () => {
    (fetchApi as jest.Mock).mockResolvedValueOnce({ data: {} });
    
    renderForm();
    
    const titleInput = screen.getByLabelText(/Title/i);
    fireEvent.change(titleInput, { target: { value: 'New Task' } });
    
    const saveButton = screen.getByRole('button', { name: /Save/i });
    fireEvent.click(saveButton);
    
    await waitFor(() => {
      expect(fetchApi).toHaveBeenCalledWith('/tasks', expect.objectContaining({
        method: 'POST',
      }));
      expect(mockOnSuccess).toHaveBeenCalled();
    });
  });
});
