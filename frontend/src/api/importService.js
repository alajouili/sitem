import axiosClient from './axiosClient';

export const importService = {
  async upload(file, { label, onProgress } = {}) {
    const formData = new FormData();
    formData.append('file', file);
    if (label) formData.append('label', label);

    const { data } = await axiosClient.post('/imports', formData, {
      onUploadProgress: (event) => {
        if (onProgress && event.total) {
          onProgress(Math.round((event.loaded / event.total) * 100));
        }
      },
    });

    return data.data;
  },

  async list({ page = 1, perPage = 20 } = {}) {
    const { data } = await axiosClient.get('/imports', {
      params: { page, per_page: perPage },
    });
    return data.data;
  },

  async get(id) {
    const { data } = await axiosClient.get(`/imports/${id}`);
    return data.data;
  },
};