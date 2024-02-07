import axiosIns from "@axios"
import { defineStore } from 'pinia'

export const useRecentCallsDashStore = defineStore('RecentCallsDashStore', {
  actions: {
    // 👉 Fetch Recent Calls
    fetchRecentCalls(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/recent-calls-dash/list', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Add Notes
    addNote(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/note', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch Recent Calls
    fetchNote(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/note', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },


  },
})
