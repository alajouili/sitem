import axiosClient from './axiosClient';

export const reportService = {
  async summary() {
    const { data } = await axiosClient.get('/reports/summary');
    return data.data;
  },

  async exportCsv() {
    const response = await axiosClient.get('/reports/export', { responseType: 'blob' });
    return response.data;
  },

  async logs({ page = 1, perPage = 50, entityType, entityId } = {}) {
    const { data } = await axiosClient.get('/reports/logs', {
      params: { page, per_page: perPage, entity_type: entityType, entity_id: entityId },
    });
    return data.data;
  },
};