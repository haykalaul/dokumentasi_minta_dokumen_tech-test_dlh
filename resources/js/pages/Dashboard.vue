<script setup>
import { onMounted, ref, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useDashboardStore } from '../stores/dashboard';
import { useProjectsStore } from '../stores/projects';
import Navbar from '../components/Navbar.vue';

const authStore = useAuthStore();
const dashboardStore = useDashboardStore();
const projectsStore = useProjectsStore();

const search = ref('');
const statusFilter = ref('');

// Load initial dashboard metrics & project list
onMounted(async () => {
  await dashboardStore.fetchDashboard();
  loadProjects();
});

const loadProjects = (cursor = null) => {
  const params = {};
  if (search.value) params.search = search.value;
  if (statusFilter.value) params.status = statusFilter.value;
  if (cursor) params.cursor = cursor;
  projectsStore.fetchProjects(params);
};

// Search & filter watchers
watch([search, statusFilter], () => {
  loadProjects();
});

// Chart setup for reviewers
const chartOptions = {
  chart: {
    id: 'monthly-submissions',
    toolbar: { show: false },
    fontFamily: 'Instrument Sans, sans-serif'
  },
  xaxis: {
    categories: []
  },
  colors: ['#1e3a8a'],
  stroke: { curve: 'smooth', width: 3 },
  dataLabels: { enabled: false },
  grid: { borderColor: '#f1f5f9' },
};

const getChartSeries = () => {
  chartOptions.xaxis.categories = dashboardStore.chart.map(c => c.label);
  return [{
    name: 'Total Pengajuan',
    data: dashboardStore.chart.map(c => c.count)
  }];
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-800 border-gray-200';
    case 'submitted': return 'bg-blue-50 text-blue-700 border-blue-200';
    case 'under_review': return 'bg-yellow-50 text-yellow-700 border-yellow-200';
    case 'revision_required': return 'bg-orange-50 text-orange-700 border-orange-200';
    case 'approved': return 'bg-green-50 text-green-700 border-green-200';
    case 'rejected': return 'bg-red-50 text-red-700 border-red-200';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatStatusText = (status) => {
  switch (status) {
    case 'draft': return 'Draft';
    case 'submitted': return 'Dikirim';
    case 'under_review': return 'Ditinjau';
    case 'revision_required': return 'Butuh Revisi';
    case 'approved': return 'Disetujui';
    case 'rejected': return 'Ditolak';
    default: return status;
  }
};
</script>

<template>
  <Navbar />

  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    
    <!-- Welcome Header Banner -->
    <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
      <div>
        <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight">Selamat Datang, {{ authStore.user?.name }}</h2>
        <p class="text-xs sm:text-sm text-blue-100 mt-1">Gunakan dashboard ini untuk memantau kelayakan dokumen pengajuan instansi.</p>
      </div>
      <router-link 
        v-if="authStore.isApplicant" 
        :to="{ name: 'ProjectCreate' }" 
        class="bg-white hover:bg-slate-100 text-blue-900 font-bold px-5 py-2.5 rounded-xl shadow-sm text-sm transition-transform hover:scale-[1.02] flex items-center space-x-1"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        <span>Buat Permohonan</span>
      </router-link>
    </div>

    <!-- Statistics Dashboard Grid -->
    <section>
      <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4">Statistik Permohonan</h3>
      
      <!-- Skeletons for Statistics Loading -->
      <div v-if="dashboardStore.loading && !dashboardStore.metrics" class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div v-for="i in 5" :key="i" class="bg-white h-24 rounded-2xl animate-pulse border border-slate-200"></div>
      </div>

      <!-- Applicant Statistics -->
      <div v-else-if="authStore.isApplicant && dashboardStore.metrics" class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between">
          <span class="text-xs font-semibold text-slate-500">Total Pengajuan</span>
          <span class="text-2xl sm:text-3xl font-extrabold text-blue-900 mt-2">{{ dashboardStore.metrics.total }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-gray-400">
          <span class="text-xs font-semibold text-slate-500">Draft</span>
          <span class="text-2xl sm:text-3xl font-extrabold text-slate-700 mt-2">{{ dashboardStore.metrics.draft }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-blue-500">
          <span class="text-xs font-semibold text-slate-500">Dikirim</span>
          <span class="text-2xl sm:text-3xl font-extrabold text-blue-600 mt-2">{{ dashboardStore.metrics.submitted }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-orange-500">
          <span class="text-xs font-semibold text-slate-500">Butuh Revisi</span>
          <span class="text-2xl sm:text-3xl font-extrabold text-orange-600 mt-2">{{ dashboardStore.metrics.revision }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-green-500">
          <span class="text-xs font-semibold text-slate-500">Disetujui</span>
          <span class="text-2xl sm:text-3xl font-extrabold text-green-600 mt-2">{{ dashboardStore.metrics.approved }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-red-500">
          <span class="text-xs font-semibold text-slate-500">Ditolak</span>
          <span class="text-2xl sm:text-3xl font-extrabold text-red-600 mt-2">{{ dashboardStore.metrics.rejected }}</span>
        </div>
      </div>

      <!-- Reviewer Statistics -->
      <div v-else-if="authStore.isReviewer && dashboardStore.metrics" class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between">
          <span class="text-xs font-semibold text-slate-500">Total Pengajuan</span>
          <span class="text-3xl font-extrabold text-blue-900 mt-2">{{ dashboardStore.metrics.total }}</span>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-blue-500">
          <span class="text-xs font-semibold text-slate-500">Menunggu Review</span>
          <span class="text-3xl font-extrabold text-blue-600 mt-2">{{ dashboardStore.metrics.pending_review }}</span>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-orange-500">
          <span class="text-xs font-semibold text-slate-500">Meminta Revisi</span>
          <span class="text-3xl font-extrabold text-orange-600 mt-2">{{ dashboardStore.metrics.revision }}</span>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-green-500">
          <span class="text-xs font-semibold text-slate-500">Disetujui</span>
          <span class="text-3xl font-extrabold text-green-600 mt-2">{{ dashboardStore.metrics.approved }}</span>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between border-l-4 border-l-red-500">
          <span class="text-xs font-semibold text-slate-500">Ditolak</span>
          <span class="text-3xl font-extrabold text-red-600 mt-2">{{ dashboardStore.metrics.rejected }}</span>
        </div>
      </div>
    </section>

    <!-- Main Content Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Projects List Section (Left / Width: 2 cols) -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-4">
            <div>
              <h3 class="text-lg font-bold text-slate-800">Daftar Permohonan Dokumen</h3>
              <p class="text-xs text-slate-500 mt-0.5">Daftar pengajuan dokumen evaluasi lingkungan hidup.</p>
            </div>
            
            <!-- Filters -->
            <div class="flex flex-wrap gap-2 w-full sm:w-auto">
              <input 
                v-model="search" 
                type="text" 
                placeholder="Cari permohonan..." 
                class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-900 w-full sm:w-48"
              />
              <select 
                v-model="statusFilter"
                class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-900 bg-white"
              >
                <option value="">Semua Status</option>
                <option v-if="authStore.isApplicant" value="draft">Draft</option>
                <option value="submitted">Dikirim</option>
                <option value="under_review">Ditinjau</option>
                <option value="revision_required">Butuh Revisi</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
              </select>
            </div>
          </div>

          <!-- Loading Skeleton for Project List -->
          <div v-if="projectsStore.loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="h-20 bg-slate-50 rounded-xl animate-pulse border border-slate-100"></div>
          </div>

          <!-- Empty State -->
          <div v-else-if="projectsStore.projects.length === 0" class="text-center py-12 space-y-3">
            <div class="inline-flex p-3 bg-slate-100 rounded-full text-slate-400">
              <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h4 class="text-sm font-bold text-slate-700">Tidak ada pengajuan ditemukan</h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Silakan ubah filter pencarian Anda atau buat permohonan kelayakan dokumen yang baru.</p>
          </div>

          <!-- Project List Cards -->
          <div v-else class="space-y-4">
            <div 
              v-for="project in projectsStore.projects" 
              :key="project.id"
              class="border border-slate-150 rounded-xl p-4 hover:bg-slate-50 transition-colors flex justify-between items-center group relative cursor-pointer"
              @click="$router.push({ name: 'ProjectDetail', params: { id: project.id } })"
            >
              <div class="space-y-1.5 pr-4 flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border', getStatusBadgeClass(project.status)]">
                    {{ formatStatusText(project.status) }}
                  </span>
                  <span class="text-[10px] text-slate-400 font-medium">
                    {{ new Date(project.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'}) }}
                  </span>
                </div>
                <h4 class="font-bold text-sm text-slate-800 truncate group-hover:text-blue-900">{{ project.title }}</h4>
                <p class="text-xs text-slate-500 line-clamp-1 pr-6">{{ project.description }}</p>
                <div v-if="authStore.isReviewer && project.user" class="text-[10px] text-slate-400 font-medium flex items-center space-x-1">
                  <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span>Pemohon: {{ project.user.name }} ({{ project.user.email }})</span>
                </div>
              </div>
              
              <!-- Chevron Link icon -->
              <div class="text-slate-300 group-hover:text-blue-900 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>

            <!-- Cursor Pagination Controls -->
            <div class="flex justify-between items-center pt-4 border-t border-slate-100" v-if="projectsStore.nextCursor || projectsStore.prevCursor">
              <button 
                @click="loadProjects(projectsStore.prevCursor)"
                :disabled="!projectsStore.prevCursor"
                class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
              >
                Sebelumnya
              </button>
              <button 
                @click="loadProjects(projectsStore.nextCursor)"
                :disabled="!projectsStore.nextCursor"
                class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
              >
                Berikutnya
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column (Reviewer Monthly Graph & Recent Logs) -->
      <div class="space-y-6">
        
        <!-- Reviewer Chart.js/ApexCharts Section -->
        <div v-if="authStore.isReviewer && dashboardStore.chart.length > 0" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
          <h3 class="text-sm font-bold text-slate-700 mb-4 uppercase tracking-wider">Tren Pengajuan Bulanan</h3>
          <div class="w-full">
            <apexchart 
              type="line" 
              height="200" 
              :options="chartOptions" 
              :series="getChartSeries()"
            />
          </div>
        </div>

        <!-- Recent Logs Section -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">
            {{ authStore.isReviewer ? 'Aktivitas Penilaian Terbaru' : 'Status Pengajuan Terbaru' }}
          </h3>
          
          <div v-if="dashboardStore.loading && dashboardStore.recentItems.length === 0" class="space-y-3">
            <div v-for="i in 3" :key="i" class="h-12 bg-slate-50 rounded-xl animate-pulse"></div>
          </div>

          <div v-else-if="dashboardStore.recentItems.length === 0" class="text-center py-6 text-xs text-slate-400">
            Belum ada aktivitas terekam.
          </div>

          <!-- Activity Chronological List -->
          <div v-else class="space-y-4">
            <!-- Reviewer logs representation -->
            <div 
              v-if="authStore.isReviewer" 
              v-for="log in dashboardStore.recentItems" 
              :key="log.id" 
              class="text-xs border-b border-slate-100 pb-3 last:border-0 last:pb-0 space-y-1"
            >
              <div class="flex justify-between items-center">
                <span class="font-bold text-slate-800">{{ log.user.name }}</span>
                <span class="text-[10px] text-slate-400">{{ new Date(log.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}</span>
              </div>
              <p class="text-slate-600">
                Menjalankan aksi <span class="font-semibold text-blue-900">{{ log.action }}</span> pada status permohonan.
              </p>
              <p class="text-slate-400 truncate italic">"{{ log.notes }}"</p>
            </div>

            <!-- Applicant recent projects list -->
            <div 
              v-if="authStore.isApplicant" 
              v-for="proj in dashboardStore.recentItems" 
              :key="proj.id" 
              class="text-xs border-b border-slate-100 pb-3 last:border-0 last:pb-0 flex justify-between items-center"
            >
              <div class="min-w-0 pr-2">
                <h4 class="font-bold text-slate-800 truncate">{{ proj.title }}</h4>
                <p class="text-[10px] text-slate-400">Dibuat: {{ new Date(proj.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}</p>
              </div>
              <span :class="['px-2.5 py-0.5 rounded-full text-[9px] font-bold border shrink-0', getStatusBadgeClass(proj.status)]">
                {{ formatStatusText(proj.status) }}
              </span>
            </div>
          </div>
        </div>

      </div>

    </div>

  </main>
</template>
