import axiosIns from "@axios"
import { defineStore } from 'pinia'

export const useLiveCallsStore = defineStore('LiveCallsStore', {
  actions: {
    // 👉 Fetch live calls (right side)
    fetchLiveCalls(data) {
      return axiosIns.post('/api/auth/dashboard/live/calls', data)
    },

    // 👉 Fetch Member List with avatar
    fetchMembersforChart() {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/fetch/members-for-chart')
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch Apex Chart Report Data
    fetchApexChartReport(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/fetch/apex-chart-report', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
  
  },
})
