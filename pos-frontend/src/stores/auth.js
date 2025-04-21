import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  const user = ref({
    name: 'Admin User',
    email: 'admin@example.com',
    role: 'admin'
  });

  const isAuthenticated = ref(true);

  const login = (credentials) => {
    // In a real app, this would make an API call
    console.log('Login with:', credentials);
    isAuthenticated.value = true;
  };

  const logout = () => {
    // In a real app, this would make an API call
    isAuthenticated.value = false;
  };

  return {
    user,
    isAuthenticated,
    login,
    logout
  };
});