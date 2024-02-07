import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useVoiceMailStore = defineStore('VoiceMailStore', {
  actions: {
    // 👉 Fetch voice mail
    fetchVoiceMail(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/setting', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Add voice mail
    addVoiceMail(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/voicemail', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

  },
})

