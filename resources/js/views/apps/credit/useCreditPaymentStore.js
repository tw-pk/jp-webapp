import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useCreditPaymentStore = defineStore('CreditPaymentStore', {
  actions: {

    // 👉 Fetch Credit Payment 
    fetchCreditPayment(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/credit-payment', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
  
    // 👉 Add Credit Payment 
    addCreditPayments(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/credit-payment', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      }) 
    },
      
  },
})

