/* eslint-disable import/order */
// import '@/@fake-db/db'
//import '@/@iconify/icons-bundle'

import App from '@/App.vue'
import { createApp } from 'vue'

// import { useUserStore } from '@/store/userStore'

import ability from '@/plugins/casl/ability'
import i18n from '@/plugins/i18n'
import layoutsPlugin from '@/plugins/layouts'
import vuetify from '@/plugins/vuetify'
import { loadFonts } from '@/plugins/webfontloader'
import router from '@/router'
import { abilitiesPlugin } from '@casl/vue'
import '@core-scss/template/index.scss'
import '@styles/styles.scss'
import { createPinia } from 'pinia'

loadFonts()

import Echo from "laravel-echo"

import axiosIns from "@axios"
import Pusher from 'pusher-js'

window.Pusher = Pusher

// Pusher.logToConsole = true

// For ssl connections use this
window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: 'mt1',
  wssHost: import.meta.env.VITE_PUSHER_HOST,
  wsHost: import.meta.env.VITE_PUSHER_HOST, // Your domain
  encrypted: true,
  wsPort: 6001, // Https port
  wssPort: 6001, // Https port
  enableStats: false, // Change this to your liking this disables statistics
  forceTLS: import.meta.env.VITE_PUSHER_TLS === 'true',
  enabledTransports: ['ws', 'wss'],
  authorizer: (channel, options) => {
    return {
      authorize: (socketId, callback) => {
        axiosIns.post('/api/broadcasting/auth', {
          socket_id: socketId,
          channel_name: channel.name,
        })
          .then(response => {
            callback(false, response.data)
          })
          .catch(error => {
            callback(true, error)
          })
      },
    }
  },
})

// window.Echo = new Echo({
//   broadcaster: 'pusher',
//   key: import.meta.env.VITE_PUSHER_APP_KEY,
//   wsHost: import.meta.env.VITE_PUSHER_HOST,
//   wsPort: import.meta.env.VITE_PUSHER_PORT,
//   forceTLS: (import.meta.env.VITE_PUSHER_TLS === 'true'),
//   encrypted: true,
//   cluster: 'mt1',
//   enabledTransports: ['ws', 'wss'],
//   enableStats: false,
//   authorizer: (channel, options) => {
//     return {
//       authorize: (socketId, callback) => {
//         axiosIns.post('/api/broadcasting/auth', {
//           socket_id: socketId,
//           channel_name: channel.name,
//         })
//           .then(response => {
//             callback(false, response.data)
//           })
//           .catch(error => {
//             callback(true, error)
//           })
//       },
//     }
//   },
// })

// window.Echo = new Echo({
//   broadcaster: 'pusher',
//   key: import.meta.env.VITE_PUSHER_APP_KEY,
//   forceTLS: (import.meta.env.VITE_PUSHER_TLS === 'true'),
//   encrypted: true,
//   cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
//   authorizer: (channel, options) => {
//     return {
//       authorize: (socketId, callback) => {
//         axiosIns.post('api/broadcasting/auth', {
//           socket_id: socketId,
//           channel_name: channel.name,
//         })
//           .then(response => {
//             callback(false, response.data)
//           })
//           .catch(error => {
//             callback(true, error)
//           })
//       },
//     }
//   },
// })

// Create vue app
const app = createApp(App)

const pinia = createPinia()

app.use(pinia)

// Use plugins
app.use(vuetify)
app.use(router)
app.use(layoutsPlugin)
app.use(i18n)
app.use(abilitiesPlugin, ability, {
  useGlobalProperties: true,
})

// const userStore = useUserStore()

// Mount vue app
app.mount('#app')


