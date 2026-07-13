import axiosClient from './axiosClient';

export const authService = {
  async login(email, password) {
    const { data } = await axiosClient.post('/auth/login', { email, password });
    return data.data; // { token, expires_at, user }
  },

  async logout() {
    await axiosClient.post('/auth/logout');
  },

  async me() {
    const { data } = await axiosClient.get('/auth/me');
    return data.data;
  },
};