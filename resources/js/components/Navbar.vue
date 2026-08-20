<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
  if (confirm('Apakah Anda yakin ingin keluar?')) {
    await authStore.logout();
    router.push({ name: 'Login' });
  }
};
</script>

<template>
  <header class="bg-blue-900 text-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Logo and App Name -->
        <div class="flex items-center space-x-3">
          <div class="bg-white p-1.5 rounded-full flex items-center justify-center">
            <svg class="h-6 w-6 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <div>
            <h1 class="font-bold text-sm sm:text-base leading-tight tracking-wide">DLH DOKUMEN KELAYAKAN</h1>
            <p class="text-[10px] text-blue-200">Sistem Pendokumentasian Permohonan Pemerintah</p>
          </div>
        </div>

        <!-- User Profile & Action -->
        <div class="flex items-center space-x-4" v-if="authStore.user">
          <div class="hidden sm:block text-right">
            <div class="text-sm font-semibold">{{ authStore.user.name }}</div>
            <div class="text-xs text-blue-200 uppercase tracking-wider font-medium">
              {{ authStore.isReviewer ? 'Reviewer' : 'Applicant' }}
            </div>
          </div>
          
          <button 
            @click="handleLogout" 
            class="bg-blue-800 hover:bg-blue-700 text-white p-2 rounded-lg transition-colors flex items-center justify-center space-x-1 text-sm font-medium"
            title="Keluar Aplikasi"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="hidden sm:inline">Keluar</span>
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
