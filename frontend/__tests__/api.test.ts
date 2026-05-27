import { fetchApi } from '@/utils/api';

describe('API Utility', () => {
  beforeEach(() => {
    global.fetch = jest.fn();
    Storage.prototype.getItem = jest.fn(() => 'mock-token');
  });

  afterEach(() => {
    jest.resetAllMocks();
  });

  it('adds authorization header when token exists', async () => {
    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      json: async () => ({ success: true }),
    });

    await fetchApi('/test');

    expect(global.fetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/test'),
      expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: 'Bearer mock-token'
        })
      })
    );
  });

  it('throws error on non-ok response', async () => {
    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: false,
      status: 400,
      json: async () => ({ message: 'Validation failed' }),
    });

    await expect(fetchApi('/test')).rejects.toThrow('Validation failed');
  });
});
