import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useCallerIdStore = defineStore('CallerIdStore', {
  actions: {
    // 👉 Fetch caller ids
    fetchCallerIds(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/setting', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Add caller ids
    addCallerId(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/callerids', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

  },
})

