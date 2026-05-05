import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          react: ['react', 'react-dom', 'react-router-dom'],
          charts: ['chart.js', 'react-chartjs-2', 'recharts'],
          maps: ['leaflet', 'react-leaflet', 'leaflet.heat'],
          pdf: ['jspdf', 'jspdf-autotable', 'html2canvas'],
          scanner: ['html5-qrcode', 'qrcode.react'],
        },
      },
    },
  },
})
