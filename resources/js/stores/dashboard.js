import { defineStore } from 'pinia';
import axios from 'axios';

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    metrics: null,
    chart: [],
    recentItems: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchDashboard() {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get('/v1/dashboard');
        const data = response.data.data;
        this.metrics = data.metrics;
        this.chart = data.chart || [];
        this.recentItems = data.recent_projects || data.recent_activities || [];
      } catch (err) {
        this.error = err.response?.data?.message || 'Gagal memuat dashboard.';
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
  },
});
