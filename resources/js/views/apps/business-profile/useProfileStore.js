import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useProfileStore = defineStore('ProfileStore', {
  actions: {
    
    // 👉 Add Twilio Secondary Profile 10DLC
    addProfile(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/create/profile/ten-dlc', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch countries 
    fetchCountries() { 
      return axiosIns.post('/api/auth/fetch/countries')
        .then(response => response.data)
        .catch(error => {
          console.error('Failed to fetch country list:', error)
          throw error
        })
    },
    
    // 👉 Delete User
    deleteTeam(id) {
      return new Promise((resolve, reject) => {
        axiosIns.delete(`api/auth/team/delete/${id}`).then(response => resolve(response)).catch(error => reject(error))
      })
    },
  },
})
