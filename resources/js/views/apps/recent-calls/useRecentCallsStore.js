import axiosIns from "@axios"
import { defineStore } from 'pinia'

export const useRecentCallsStore = defineStore('RecentCallsStore', {
  actions: {
    // 👉 Fetch Recent Calls
    fetchRecentCalls(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/recent-calls/list', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch Recent Calls Contact
    fetchRecentCallsContact(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/recent-calls-contact/list', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch Member List
    fetchMemberList(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/fetch/members')
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
        axiosIns.post('/api/auth/fetch/note', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },




  },
})
