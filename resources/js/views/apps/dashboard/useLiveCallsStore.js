import axiosIns from "@axios"
import { defineStore } from 'pinia'

export const useLiveCallsStore = defineStore('LiveCallsStore', {
  actions: {
    // 👉 Fetch live calls (right side)
    fetchLiveCalls(data) {
      return axiosIns.post('/api/auth/dashboard/live/calls', data)
    },

    // 👉 Fetch live calls past
    fetchLiveCallsPast(data) {
      return axiosIns.post('/api/auth/dashboard/live/calls/past', data)
    },

  
  },
})
