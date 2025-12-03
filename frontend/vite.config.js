import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const apiUrl = env.VITE_API_BASE_URL || (mode === 'development' ? 'http://localhost:8000' : '')
  const escapedApiUrl = apiUrl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

  return {
    plugins: [
      vue(),
      vueDevTools(),
      VitePWA({
        registerType: 'autoUpdate',
        includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'masked-icon.svg'],
        manifest: {
          name: 'EyePub',
          short_name: 'EyePub',
          description: 'Publishing for Eyes - Digital Signage Player',
          theme_color: '#000000',
          icons: [
            {
              src: 'pwa-192x192.png',
              sizes: '192x192',
              type: 'image/png'
            },
            {
              src: 'pwa-512x512.png',
              sizes: '512x512',
              type: 'image/png'
            }
          ]
        },
        workbox: {
          runtimeCaching: [
            {
              // Cache images only. Videos (mp4/mov) often return 206 Partial Content which Cache API doesn't support well without plugins.
              urlPattern: new RegExp(`^${escapedApiUrl}/storage/.*\\.(png|jpg|jpeg|svg|gif|webp)$`, 'i'),
              handler: 'CacheFirst',
              method: 'GET',
              options: {
                cacheName: 'media-images-cache',
                expiration: {
                  maxEntries: 100,
                  maxAgeSeconds: 60 * 60 * 24 * 30 // 30 Days
                },
                cacheableResponse: {
                  statuses: [0, 200]
                }
              }
            },
            {
              // Cache external images (e.g. Unsplash, other domains)
              // Note: Opaque responses (CORS missing) will take up significant quota (~7MB per file)
              urlPattern: ({ request }) => request.destination === 'image',
              handler: 'StaleWhileRevalidate',
              options: {
                cacheName: 'external-images-cache',
                expiration: {
                  maxEntries: 50,
                  maxAgeSeconds: 60 * 60 * 24 * 7 // 7 Days
                },
                cacheableResponse: {
                  statuses: [0, 200]
                }
              }
            },
            {
              urlPattern: new RegExp(`^${escapedApiUrl}/api/.*`, 'i'),
              handler: 'NetworkFirst',
              method: 'GET',
              options: {
                cacheName: 'api-cache',
                expiration: {
                  maxEntries: 50,
                  maxAgeSeconds: 60 * 60 * 24 // 1 Day
                },
                cacheableResponse: {
                  statuses: [0, 200]
                }
              }
            }
          ]
        },
        devOptions: {
          enabled: true
        }
      })
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      },
    },
    server: {
      proxy: {
        '/api': {
          target: 'http://127.0.0.1:8000',
          changeOrigin: true,
          secure: false,
        }
      }
    }
  }
})
