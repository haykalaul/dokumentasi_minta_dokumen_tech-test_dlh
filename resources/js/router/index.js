import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../pages/Login.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../pages/Register.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('../pages/Dashboard.vue'),
    meta: { auth: true },
  },
  {
    path: '/projects/create',
    name: 'ProjectCreate',
    component: () => import('../pages/ProjectForm.vue'),
    meta: { auth: true, role: 'applicant' },
  },
  {
    path: '/projects/:id/edit',
    name: 'ProjectEdit',
    component: () => import('../pages/ProjectForm.vue'),
    meta: { auth: true, role: 'applicant' },
  },
  {
    path: '/projects/:id',
    name: 'ProjectDetail',
    component: () => import('../pages/ProjectDetail.vue'),
    meta: { auth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Try to restore user profile if token exists but user details are empty
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser();
    } catch (err) {
      console.error('Failed to restore user session:', err);
    }
  }

  const isAuthenticated = authStore.isAuthenticated;

  // Guest-only guard
  if (to.meta.guest && isAuthenticated) {
    return next({ name: 'Dashboard' });
  }

  // Auth guard
  if (to.meta.auth && !isAuthenticated) {
    return next({ name: 'Login' });
  }

  // Role check guard
  if (to.meta.role) {
    if (to.meta.role === 'applicant' && !authStore.isApplicant) {
      return next({ name: 'Dashboard' });
    }
    if (to.meta.role === 'reviewer' && !authStore.isReviewer) {
      return next({ name: 'Dashboard' });
    }
  }

  next();
});

export default router;
