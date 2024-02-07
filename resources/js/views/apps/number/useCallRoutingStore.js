import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useCallRoutingStore = defineStore('CallRoutingStore', {
  actions: {
    
    // 👉 Fetch call Routing
    fetchCallRouting(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/setting', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Add call routing
    addCallRouting(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/callrouting', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

  },
})

