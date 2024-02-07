import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const usePhoneNumberStore = defineStore('PhoneNumberStore', {
  actions: {
    // 👉 Fetch Teams data
    fetchPhoneNumbers(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/number/list', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
  
      
  },
})

