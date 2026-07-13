import axiosClient from './axiosClient';

export const userService = {
  async list({ page = 1, perPage = 20, role } = {}) {
    const { data } = await axiosClient.get('/users', {
      params: { page, per_page: perPage, role },
    });
    return data.data;
  },

  async get(id) {
    const { data } = await axiosClient.get(`/users/${id}`);
    return data.data;
  },

  async create(payload) {
    const { data } = await axiosClient.post('/users', payload);
    return data.data;
  },

  async update(id, payload) {
    const { data } = await axiosClient.put(`/users/${id}`, payload);
    return data.data;
  },

  async remove(id) {
    await axiosClient.delete(`/users/${id}`);
  },
};