import axiosIns from "@axios"
import { defineStore } from 'pinia'

export const useNumberSelectionStore = defineStore('NumberSelectionStore', {
  actions: {
    // 👉 Fetch numbers
    fetchNumbers(data) {
      return axiosIns.post('/api/auth/numbers/list', data)
    },
    fetchExistingNumber() {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/numbers')
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
    storeNumber(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/number/store', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
    purchaseNumber(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/purchase/number', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
  },
})
