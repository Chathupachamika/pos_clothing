import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  server: {
    port: 3000,
    proxy:{
      '/api':{
        target:'http://localhost:5000',
        changeOrigin:true,
        rewrite:(path)=>path.replace(/^\/api/,''),
      }
    }
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./tests/setup.js']
  },

  build: {
    outDir: 'dist', // Ensure this is correctly set
  }

})
