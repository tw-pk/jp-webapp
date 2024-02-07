// userStore.js
import { defineStore } from 'pinia'

export const useUserStore = defineStore('userStore', {
  state: () => ({
    userData: null,
  }),

  getters: {
    getUserData: state => state.userData,
  },

  actions: {
    async setUserData(userData) {
      console.log('actions data')
      
      const data = await userData
    
      this.userData = data
    },
  },
})
