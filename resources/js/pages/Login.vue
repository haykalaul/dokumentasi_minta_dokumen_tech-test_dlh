<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const errorMsg = ref(null);

const handleLogin = async () => {
  errorMsg.value = null;
  if (!email.value || !password.value) {
    errorMsg.value = 'Silakan masukkan email dan password.';
    return;
  }

  try {
    const success = await authStore.login(email.value, password.value);
    if (success) {
      router.push({ name: 'Dashboard' });
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Login gagal. Silakan periksa kredensial Anda.';
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <!-- DLH Logo Icon -->
      <div class="inline-flex bg-blue-900 text-white p-3.5 rounded-full shadow-md justify-center items-center mb-4">
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
      </div>
      <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">DLH DOKUMEN KELAYAKAN</h2>
      <p class="mt-2 text-sm text-slate-600">Sistem Pendokumentasian Permohonan Dokumen Kelayakan</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow-sm rounded-2xl sm:px-10 border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800 mb-6">Masuk Ke Akun Anda</h3>

        <!-- Error Notification -->
        <div v-if="errorMsg" class="bg-red-50 text-red-700 p-3 rounded-lg border border-red-200 mb-4 text-sm flex items-start space-x-2">
          <svg class="h-5 w-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ errorMsg }}</span>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label for="email" class="block text-sm font-semibold text-slate-700">Alamat Email</label>
            <div class="mt-1.5">
              <input 
                id="email" 
                v-model="email" 
                type="email" 
                required 
                class="w-full px-3.5 py-2 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm"
                placeholder="nama@domain.com"
                :disabled="authStore.loading"
              />
            </div>
          </div>

          <div>
            <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
            <div class="mt-1.5">
              <input 
                id="password" 
                v-model="password" 
                type="password" 
                required 
                class="w-full px-3.5 py-2 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-blue-900 text-sm"
                placeholder="••••••••"
                :disabled="authStore.loading"
              />
            </div>
          </div>

          <div>
            <button 
              type="submit" 
              class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-900 transition-colors"
              :disabled="authStore.loading"
            >
              <!-- Spinner -->
              <svg v-if="authStore.loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ authStore.loading ? 'Sedang Masuk...' : 'Masuk' }}
            </button>
          </div>
        </form>

        <div class="mt-6 text-center text-sm text-slate-600">
          Belum punya akun pemohon? 
          <router-link :to="{ name: 'Register' }" class="font-semibold text-blue-900 hover:underline">
            Daftar Sekarang
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
