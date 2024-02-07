import axiosIns from "@axios"
import { defineStore } from 'pinia'

export const useLiveCallsStore = defineStore('LiveCallsStore', {
  actions: {
    // 👉 Fetch live calls (right side)
    fetchLiveCalls(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/dashboard/live/calls', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch live calls past
    fetchLiveCallsPast(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/dashboard/live/calls/past', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

  
  },
})
