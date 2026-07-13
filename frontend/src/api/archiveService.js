import axiosClient from './axiosClient';

export const archiveService = {
  async list({ page = 1, perPage = 20, category, status, search } = {}) {
    const { data } = await axiosClient.get('/archives', {
      params: { page, per_page: perPage, category, status, search },
    });
    return data.data; // { items, total, page }
  },

  async get(id) {
    const { data } = await axiosClient.get(`/archives/${id}`);
    return data.data;
  },

  async create(payload) {
    const { data } = await axiosClient.post('/archives', payload);
    return data.data;
  },

  async update(id, payload) {
    const { data } = await axiosClient.put(`/archives/${id}`, payload);
    return data.data;
  },

  async remove(id) {
    await axiosClient.delete(`/archives/${id}`);
  },

  async images(id) {
    const { data } = await axiosClient.get(`/archives/${id}/images`);
    return data.data;
  },
};