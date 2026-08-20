import { defineStore } from 'pinia';
import axios from 'axios';

export const useProjectsStore = defineStore('projects', {
  state: () => ({
    projects: [],
    nextCursor: null,
    prevCursor: null,
    activeProject: null,
    loading: false,
    actionLoading: false,
    error: null,
  }),

  actions: {
    async fetchProjects(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get('/v1/projects', { params });
        const resData = response.data.data;
        this.projects = resData.data;
        this.nextCursor = resData.next_cursor;
        this.prevCursor = resData.prev_cursor;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal memuat daftar permohonan.';
      } finally {
        this.loading = false;
      }
    },

    async fetchProject(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get(`/v1/projects/${id}`);
        this.activeProject = response.data.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal memuat detail permohonan.';
      } finally {
        this.loading = false;
      }
    },

    async createProject(formData) {
      this.actionLoading = true;
      try {
        await axios.post('/v1/projects', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal membuat permohonan.';
        throw err;
      } finally {
        this.actionLoading = false;
      }
    },

    async updateProject(id, formData) {
      this.actionLoading = true;
      try {
        // Use POST with _method=PUT to allow files over multipart/form-data in Laravel
        formData.append('_method', 'PUT');
        await axios.post(`/v1/projects/${id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal memperbarui permohonan.';
        throw err;
      } finally {
        this.actionLoading = false;
      }
    },

    async deleteProject(id) {
      this.actionLoading = true;
      try {
        await axios.delete(`/v1/projects/${id}`);
        this.projects = this.projects.filter(p => p.id !== id);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal menghapus permohonan.';
        throw err;
      } finally {
        this.actionLoading = false;
      }
    },

    async submitProject(id) {
      this.actionLoading = true;
      try {
        await axios.post(`/v1/projects/${id}/submit`);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal mengirim permohonan.';
        throw err;
      } finally {
        this.actionLoading = false;
      }
    },

    async startReview(id) {
      this.actionLoading = true;
      try {
        await axios.post(`/v1/projects/${id}/reviews`);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal memulai peninjauan.';
        throw err;
      } finally {
        this.actionLoading = false;
      }
    },

    async requestRevision(id, notes) {
      this.actionLoading = true;
      try {
        await axios.post(`/v1/projects/${id}/revision`, { notes });
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal meminta revisi.';
        throw err;
      } finally {
        this.actionLoading = false;
      }
    },

    async approveProject(id, notes = '') {
      this.actionLoading = true;
      try {
        await axios.post(`/v1/projects/${id}/approve`, { notes });
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal menyetujui permohonan.';
        throw err;
      } finally {
        this.actionLoading = false;
      }
    },

    async rejectProject(id, notes) {
      this.actionLoading = true;
      try {
        await axios.post(`/v1/projects/${id}/reject`, { notes });
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal menolak permohonan.';
        throw err;
      } finally {
        this.actionLoading = false;
      }
    },
  },
});
