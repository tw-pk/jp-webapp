import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useContactStore = defineStore('ContactStore', {
  actions: {
    // 👉 Fetch Teams data
    fetchContacts(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/contact/list', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    getContactDetails(data){
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/contact/details', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Add contact
    addContact(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/contact', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Delete User
    deleteContact(id) {
      return new Promise((resolve, reject) => {
        axiosIns.delete(`api/auth/contact/delete/${id}`)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
      
  },
})
