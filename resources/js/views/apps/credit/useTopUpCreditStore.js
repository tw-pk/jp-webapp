import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useTopUpCreditStore = defineStore('TopUpCreditStore', {
  actions: {

    // 👉 Fetch Top Up Credit Data
    fetchTopUpCreditInfo() {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/top-up-credit')
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch Top Up Credit Data
    addTopUpCredit(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/top-up-credit', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    fetchCreditLimits() {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/top-up-limits')
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

  },
})

