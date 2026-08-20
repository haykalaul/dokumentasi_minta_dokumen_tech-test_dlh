<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useProjectsStore } from '../stores/projects';
import Navbar from '../components/Navbar.vue';

const route = useRoute();
const router = useRouter();
const projectsStore = useProjectsStore();

const isEditMode = ref(false);
const projectId = ref(null);

const title = ref('');
const description = ref('');
const documentFile = ref(null);
const existingDocumentName = ref('');

const errorMsg = ref(null);
const validationErrors = ref({});

onMounted(async () => {
  projectId.value = route.params.id;
  if (projectId.value) {
    isEditMode.value = true;
    await loadProjectDetails();
  }
});

const loadProjectDetails = async () => {
  await projectsStore.fetchProject(projectId.value);
  const project = projectsStore.activeProject;
  
  if (project) {
    // Check if the user is authorized to edit this state
    if (project.status !== 'draft' && project.status !== 'revision_required') {
      alert('Dokumen yang sudah dikirim tidak dapat diedit kecuali diminta revisi.');
      router.push({ name: 'Dashboard' });
      return;
    }
    
    title.value = project.title;
    description.value = project.description;
    if (project.documents && project.documents.length > 0) {
      existingDocumentName.value = project.documents[0].original_name;
    }
  } else {
    router.push({ name: 'Dashboard' });
  }
};

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Max size 10MB (10 * 1024 * 1024 bytes)
  if (file.size > 10485760) {
    errorMsg.value = 'Ukuran file tidak boleh melebihi 10MB.';
    documentFile.value = null;
    event.target.value = ''; // Reset input
    return;
  }

  errorMsg.value = null;
  documentFile.value = file;
};

const handleSubmit = async () => {
  errorMsg.value = null;
  validationErrors.value = {};

  if (!title.value || !description.value) {
    errorMsg.value = 'Judul dan deskripsi wajib diisi.';
    return;
  }

  if (!isEditMode.value && !documentFile.value) {
    errorMsg.value = 'Dokumen persyaratan kelayakan wajib diunggah.';
    return;
  }

  const formData = new FormData();
  formData.append('title', title.value);
  formData.append('description', description.value);
  if (documentFile.value) {
    formData.append('document', documentFile.value);
  }

  try {
    let success = false;
    if (isEditMode.value) {
      success = await projectsStore.updateProject(projectId.value, formData);
    } else {
      success = await projectsStore.createProject(formData);
    }

    if (success) {
      alert(isEditMode.value ? 'Permohonan berhasil diperbarui.' : 'Permohonan berhasil dibuat.');
      router.push({ name: 'Dashboard' });
    }
  } catch (err) {
    if (err.response?.status === 422) {
      validationErrors.value = err.response?.data?.errors || {};
      errorMsg.value = err.response?.data?.message || 'Validasi pengisian form gagal.';
    } else {
      errorMsg.value = err.response?.data?.message || 'Terjadi kesalahan sistem, silakan coba lagi.';
    }
  }
};
</script>

<template>
  <Navbar />

  <main class="flex-1 max-w-3xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
      
      <!-- Form Header -->
      <div class="border-b border-slate-100 pb-4">
        <h2 class="text-xl font-extrabold text-slate-800">
          {{ isEditMode ? 'Edit Permohonan Kelayakan' : 'Buat Permohonan Baru' }}
        </h2>
        <p class="text-xs text-slate-500 mt-1">
          Lengkapi detail formulir pengajuan dan unggah berkas kelayakan lingkungan hidup.
        </p>
      </div>

      <!-- Error Notification -->
      <div v-if="errorMsg" class="bg-red-50 text-red-700 p-3.5 rounded-lg border border-red-200 text-sm flex items-start space-x-2">
        <svg class="h-5 w-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ errorMsg }}</span>
      </div>

      <!-- Form Inputs -->
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div>
          <label for="title" class="block text-sm font-semibold text-slate-700">Judul Pengajuan / Nama Project</label>
          <div class="mt-1.5">
            <input 
              id="title"
              v-model="title"
              type="text"
              required
              class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm"
              placeholder="Contoh: AMDAL Pembangunan Pabrik Kertas PT. Sukses"
              :disabled="projectsStore.actionLoading"
            />
            <p v-if="validationErrors.title" class="mt-1 text-xs text-red-600">{{ validationErrors.title[0] }}</p>
          </div>
        </div>

        <div>
          <label for="description" class="block text-sm font-semibold text-slate-700">Deskripsi Rencana Kegiatan</label>
          <div class="mt-1.5">
            <textarea 
              id="description"
              v-model="description"
              rows="5"
              required
              class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm"
              placeholder="Tuliskan secara lengkap detail rencana pembangunan, kapasitas produksi, dan cakupan luas area project..."
              :disabled="projectsStore.actionLoading"
            ></textarea>
            <p v-if="validationErrors.description" class="mt-1 text-xs text-red-600">{{ validationErrors.description[0] }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700">Unggah Dokumen Pendukung</label>
          <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-blue-900 transition-colors bg-slate-50">
            <div class="space-y-1 text-center">
              <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <div class="flex text-sm text-slate-600 justify-center">
                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-semibold text-blue-900 hover:text-blue-800 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-900 px-1 border border-slate-200">
                  <span>Pilih berkas file</span>
                  <input 
                    id="file-upload" 
                    name="file-upload" 
                    type="file" 
                    class="sr-only" 
                    accept=".pdf,.doc,.docx,.xls,.xlsx"
                    @change="handleFileChange"
                    :disabled="projectsStore.actionLoading"
                  />
                </label>
                <p class="pl-1">atau drag and drop</p>
              </div>
              <p class="text-xs text-slate-500">Mendukung file: PDF, DOC, DOCX, XLS, XLSX (Maks. 10MB)</p>
            </div>
          </div>
          
          <!-- File selection feedback -->
          <div v-if="documentFile || existingDocumentName" class="mt-3 flex items-center space-x-2 bg-blue-50 text-blue-700 p-2.5 rounded-lg border border-blue-200 text-xs">
            <svg class="h-4 w-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="truncate">
              {{ documentFile ? `File terpilih: ${documentFile.name}` : `File tersimpan: ${existingDocumentName}` }}
            </span>
          </div>
          <p v-if="validationErrors.document" class="mt-1 text-xs text-red-600">{{ validationErrors.document[0] }}</p>
        </div>

        <!-- Form Buttons -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
          <router-link 
            :to="{ name: 'Dashboard' }" 
            class="px-4 py-2 border border-slate-350 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Batal
          </router-link>
          
          <button 
            type="submit"
            class="px-5 py-2 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-900 transition-colors flex items-center"
            :disabled="projectsStore.actionLoading"
          >
            <!-- Spinner -->
            <svg v-if="projectsStore.actionLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ projectsStore.actionLoading ? 'Menyimpan...' : 'Simpan Permohonan' }}
          </button>
        </div>

      </form>
    </div>
  </main>
</template>
