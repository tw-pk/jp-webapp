import axiosIns from "@axios"
import { defineStore } from 'pinia'

export const useAnalysisDashStore = defineStore('AnalysisDashStore', {
  actions: {
    // 👉 Fetch numbers analysis
    async fetchNumbers(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/dashboard/number/analysis', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch members analysis
    async fetchMembers(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/dashboard/member-list', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },



  },
})
