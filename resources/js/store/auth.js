// auth.js

import { defineStore } from 'pinia'

export const useAuthStore = defineStore({
  id: 'auth',
  state: () => ({
    verificationMessage: null,
  }),
  actions: {
    setVerificationMessage(message) {
      this.verificationMessage = message
    },
  },
})
