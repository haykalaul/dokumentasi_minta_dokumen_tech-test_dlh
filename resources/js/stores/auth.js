import { defineStore } from 'pinia';
import axios from 'axios';

// Configure Axios defaults
axios.defaults.baseURL = '/api';
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}, (error) => {
  return Promise.reject(error);
});

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token') || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isApplicant: (state) => state.user?.roles?.includes('applicant'),
    isReviewer: (state) => state.user?.roles?.includes('reviewer'),
  },

  actions: {
    async register(name, email, password, password_confirmation) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.post('/v1/auth/register', {
          name,
          email,
          password,
          password_confirmation,
        });

        const { access_token, user } = response.data.data;
        this.token = access_token;
        this.user = user;
        localStorage.setItem('auth_token', access_token);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Registrasi gagal.';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.post('/v1/auth/login', { email, password });
        const { access_token, user } = response.data.data;
        this.token = access_token;
        this.user = user;
        localStorage.setItem('auth_token', access_token);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Email atau password salah.';
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        await axios.post('/v1/auth/logout');
      } catch (err) {
        console.error('Logout error:', err);
      } finally {
        this.token = null;
        this.user = null;
        localStorage.removeItem('auth_token');
      }
    },

    async fetchUser() {
      if (!this.token) return null;
      this.loading = true;
      try {
        const response = await axios.get('/v1/auth/me');
        this.user = response.data.data.user;
        return this.user;
      } catch (err) {
        // Token might be invalid/expired
        this.logout();
        throw err;
      } finally {
        this.loading = false;
      }
    },
  },
});
