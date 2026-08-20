<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useProjectsStore } from '../stores/projects';
import Navbar from '../components/Navbar.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const projectsStore = useProjectsStore();

const projectId = ref(null);
const showActionModal = ref(false);
const modalActionType = ref(''); // revision, approve, reject
const modalNotes = ref('');
const modalError = ref(null);

onMounted(() => {
  projectId.value = route.params.id;
  loadProject();
});

const loadProject = async () => {
  await projectsStore.fetchProject(projectId.value);
};

const handleApplicantSubmit = async () => {
  if (confirm('Apakah Anda yakin ingin mengirimkan permohonan ini untuk dinilai? Anda tidak dapat merubah data ini setelah dikirim.')) {
    try {
      const success = await projectsStore.submitProject(projectId.value);
      if (success) {
        alert('Permohonan berhasil dikirim.');
        loadProject();
      }
    } catch (err) {
      alert(projectsStore.error || 'Gagal mengirim permohonan.');
    }
  }
};

const handleApplicantDelete = async () => {
  if (confirm('Apakah Anda yakin ingin menghapus permohonan draft ini secara permanen? Tindakan ini tidak dapat dibatalkan.')) {
    try {
      const success = await projectsStore.deleteProject(projectId.value);
      if (success) {
        alert('Permohonan berhasil dihapus.');
        router.push({ name: 'Dashboard' });
      }
    } catch (err) {
      alert(projectsStore.error || 'Gagal menghapus permohonan.');
    }
  }
};

const handleReviewerStartReview = async () => {
  if (confirm('Apakah Anda yakin ingin memulai penilaian untuk dokumen ini? Status dokumen akan berubah menjadi Ditinjau.')) {
    try {
      const success = await projectsStore.startReview(projectId.value);
      if (success) {
        alert('Status permohonan diubah menjadi Ditinjau.');
        loadProject();
      }
    } catch (err) {
      alert(projectsStore.error || 'Gagal memulai peninjauan.');
    }
  }
};

const openActionModal = (type) => {
  modalActionType.value = type;
  modalNotes.value = '';
  modalError.value = null;
  showActionModal.value = true;
};

const closeActionModal = () => {
  showActionModal.value = false;
};

const handleReviewerAction = async () => {
  modalError.value = null;
  const isNotesRequired = modalActionType.value === 'revision' || modalActionType.value === 'reject';
  
  if (isNotesRequired && (!modalNotes.value || modalNotes.value.trim().length < 5)) {
    modalError.value = 'Catatan minimal 5 karakter wajib diisi.';
    return;
  }

  try {
    let success = false;
    if (modalActionType.value === 'approve') {
      success = await projectsStore.approveProject(projectId.value, modalNotes.value);
    } else if (modalActionType.value === 'reject') {
      success = await projectsStore.rejectProject(projectId.value, modalNotes.value);
    } else if (modalActionType.value === 'revision') {
      success = await projectsStore.requestRevision(projectId.value, modalNotes.value);
    }

    if (success) {
      alert('Tindakan penilaian berhasil disimpan.');
      closeActionModal();
      loadProject();
    }
  } catch (err) {
    modalError.value = projectsStore.error || 'Gagal mengeksekusi tindakan.';
  }
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'draft': return 'bg-gray-150 text-gray-800 border-gray-300';
    case 'submitted': return 'bg-blue-100 text-blue-700 border-blue-300';
    case 'under_review': return 'bg-yellow-100 text-yellow-700 border-yellow-300';
    case 'revision_required': return 'bg-orange-100 text-orange-700 border-orange-300';
    case 'approved': return 'bg-green-100 text-green-700 border-green-300';
    case 'rejected': return 'bg-red-100 text-red-700 border-red-300';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const formatStatusText = (status) => {
  switch (status) {
    case 'draft': return 'Draft';
    case 'submitted': return 'Menunggu Tinjauan';
    case 'under_review': return 'Sedang Ditinjau';
    case 'revision_required': return 'Butuh Revisi';
    case 'approved': return 'Disetujui / Layak';
    case 'rejected': return 'Ditolak / Tidak Layak';
    default: return status;
  }
};
</script>

<template>
  <Navbar />

  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    
    <!-- Back Navigation Link -->
    <div>
      <router-link 
        :to="{ name: 'Dashboard' }" 
        class="inline-flex items-center space-x-1.5 text-xs font-semibold text-slate-500 hover:text-blue-900 transition-colors"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Kembali ke Dashboard</span>
      </router-link>
    </div>

    <!-- Skeletons for Project Loading -->
    <div v-if="projectsStore.loading && !projectsStore.activeProject" class="space-y-6 animate-pulse">
      <div class="h-28 bg-white rounded-2xl border border-slate-200"></div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 h-96 bg-white rounded-2xl border border-slate-200"></div>
        <div class="h-96 bg-white rounded-2xl border border-slate-200"></div>
      </div>
    </div>

    <!-- Main Project Detail Content -->
    <div v-else-if="projectsStore.activeProject" class="space-y-8">
      
      <!-- Top Title and Header Details Card -->
      <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="space-y-2 flex-1 min-w-0">
          <div class="flex items-center space-x-3 flex-wrap gap-y-2">
            <span :class="['px-3 py-1 rounded-full text-xs font-bold border', getStatusBadgeClass(projectsStore.activeProject.status)]">
              {{ formatStatusText(projectsStore.activeProject.status) }}
            </span>
            <span class="text-xs text-slate-400 font-medium">
              Tanggal Pengajuan: {{ new Date(projectsStore.activeProject.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}
            </span>
          </div>
          <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">{{ projectsStore.activeProject.title }}</h2>
        </div>

        <!-- Conditional Workflow Operations (Action Buttons) -->
        <div class="flex flex-wrap gap-2 w-full md:w-auto border-t border-slate-100 pt-4 md:border-0 md:pt-0 shrink-0">
          
          <!-- APPLICANT ACTIONS -->
          <template v-if="authStore.isApplicant && authStore.user.id === projectsStore.activeProject.user?.id">
            <router-link 
              v-if="['draft', 'revision_required'].includes(projectsStore.activeProject.status)"
              :to="{ name: 'ProjectEdit', params: { id: projectsStore.activeProject.id } }"
              class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold shadow-sm transition-colors"
            >
              Edit Permohonan
            </router-link>
            
            <button 
              v-if="['draft', 'revision_required'].includes(projectsStore.activeProject.status)"
              @click="handleApplicantSubmit"
              class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors"
            >
              Submit Permohonan
            </button>
            
            <button 
              v-if="projectsStore.activeProject.status === 'draft'"
              @click="handleApplicantDelete"
              class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-lg text-sm font-semibold shadow-sm transition-colors"
            >
              Hapus Draft
            </button>
          </template>

          <!-- REVIEWER ACTIONS -->
          <template v-if="authStore.isReviewer">
            <button 
              v-if="projectsStore.activeProject.status === 'submitted'"
              @click="handleReviewerStartReview"
              class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors"
            >
              Mulai Penilaian (Ditinjau)
            </button>

            <template v-if="projectsStore.activeProject.status === 'under_review'">
              <button 
                @click="openActionModal('revision')"
                class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors"
              >
                Minta Revisi
              </button>
              
              <button 
                @click="openActionModal('approve')"
                class="px-4 py-2 bg-green-700 hover:bg-green-600 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors"
              >
                Setujui (Lolos Kelayakan)
              </button>
              
              <button 
                @click="openActionModal('reject')"
                class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors"
              >
                Tolak (Tidak Layak)
              </button>
            </template>
          </template>

        </div>
      </div>

      <!-- Detail Info Columns split -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Document parameters card (Left - width: 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
            
            <!-- Description -->
            <div class="space-y-2">
              <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Deskripsi Rencana Kegiatan</h3>
              <p class="text-slate-600 text-sm whitespace-pre-line leading-relaxed">
                {{ projectsStore.activeProject.description }}
              </p>
            </div>

            <!-- Uploaded Documents Attachment list -->
            <div class="space-y-3">
              <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Berkas Lampiran Persyaratan</h3>
              
              <div 
                v-for="doc in projectsStore.activeProject.documents" 
                :key="doc.id"
                class="flex items-center justify-between p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs"
              >
                <div class="flex items-center space-x-3 min-w-0 pr-4">
                  <div class="bg-blue-100 text-blue-900 p-2 rounded-lg shrink-0">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <div class="font-bold text-slate-800 truncate">{{ doc.original_name }}</div>
                    <div class="text-slate-400 mt-0.5">
                      {{ (doc.file_size / 1024 / 1024).toFixed(2) }} MB • {{ doc.mime_type }}
                    </div>
                  </div>
                </div>

                <!-- Secured download stream URL link -->
                <a 
                  :href="`/api/v1/projects/${projectsStore.activeProject.id}/documents/${doc.id}`"
                  target="_blank"
                  class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-semibold px-3 py-1.5 rounded-lg shadow-sm flex items-center space-x-1 hover:text-blue-900 transition-colors shrink-0"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  <span class="hidden sm:inline">Unduh Berkas</span>
                </a>
              </div>
            </div>

            <!-- Ownership Metadata details -->
            <div class="border-t border-slate-100 pt-4 flex flex-col sm:flex-row justify-between text-xs text-slate-400 gap-2">
              <div>
                Diajukan Oleh Instansi: <span class="font-bold text-slate-600">{{ projectsStore.activeProject.user?.name }}</span>
              </div>
              <div>
                Alamat Surat/Email: <span class="font-bold text-slate-600">{{ projectsStore.activeProject.user?.email }}</span>
              </div>
            </div>

          </div>
        </div>

        <!-- History/Audit timeline log (Right - width: 1 col) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6">
          <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider border-b border-slate-100 pb-3">Histori Penilaian & Alur</h3>
          
          <div class="flow-root">
            <ul role="list" class="-mb-8">
              <li 
                v-for="(log, logIdx) in projectsStore.activeProject.approval_logs" 
                :key="log.id"
              >
                <div class="relative pb-8">
                  <!-- Connector line -->
                  <span 
                    v-if="logIdx !== projectsStore.activeProject.approval_logs.length - 1" 
                    class="absolute top-4 left-4 -ml-0.5 h-full w-0.5 bg-slate-200" 
                    aria-hidden="true"
                  ></span>
                  
                  <div class="relative flex space-x-3">
                    <!-- Connector dot icons -->
                    <div>
                      <span class="h-8 w-8 rounded-full bg-blue-900 text-white flex items-center justify-center ring-8 ring-white shrink-0">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                      </span>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 min-w-0 pt-1.5">
                      <div class="flex justify-between items-baseline gap-2">
                        <p class="text-xs font-bold text-slate-800">
                          {{ log.user?.name }} 
                          <span class="font-normal text-slate-400">({{ log.action }})</span>
                        </p>
                        <p class="text-[10px] text-slate-400 whitespace-nowrap">
                          {{ new Date(log.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}
                        </p>
                      </div>
                      <p class="text-[10px] text-slate-500 mt-1">
                        Status akhir: <span class="font-semibold text-blue-900">{{ formatStatusText(log.new_status) }}</span>
                      </p>
                      <p v-if="log.notes" class="text-xs text-slate-500 bg-slate-50 border border-slate-200 p-2 rounded-lg mt-1.5 italic">
                        "{{ log.notes }}"
                      </p>
                    </div>

                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>

      </div>

    </div>

    <!-- Reviewer Decision Modal Note Box -->
    <div 
      v-if="showActionModal" 
      class="fixed z-50 inset-0 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
    >
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <h3 class="text-lg font-bold text-slate-800">
          {{ modalActionType === 'approve' ? 'Konfirmasi Persetujuan' : modalActionType === 'reject' ? 'Konfirmasi Penolakan' : 'Minta Perbaikan / Revisi' }}
        </h3>
        
        <!-- Error -->
        <p v-if="modalError" class="text-xs text-red-600 bg-red-50 p-2 rounded border border-red-200">{{ modalError }}</p>
        
        <p class="text-xs text-slate-500">
          {{ modalActionType === 'approve' ? 'Berikan catatan untuk permohonan yang disetujui (opsional).' : 'Silakan masukkan alasan/catatan detail perihal keputusan ini (wajib, minimal 5 karakter).' }}
        </p>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Evaluasi Penilai</label>
          <textarea 
            v-model="modalNotes"
            rows="4"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-900"
            placeholder="Tuliskan catatan teknis detail penilaian berkas..."
          ></textarea>
        </div>

        <div class="flex justify-end space-x-2 pt-2">
          <button 
            @click="closeActionModal" 
            class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50"
          >
            Batal
          </button>
          
          <button 
            @click="handleReviewerAction"
            class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold"
            :disabled="projectsStore.actionLoading"
          >
            Simpan Keputusan
          </button>
        </div>
      </div>
    </div>

  </main>
</template>
