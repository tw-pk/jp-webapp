import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useCallForwardingStore = defineStore('CallForwardingStore', {
  actions: {
    
    // 👉 Fetch call Forwarding
    fetchCallForwarding(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/setting', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Add call routing
    addCallForwarding(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/callrouting', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

  },
})

