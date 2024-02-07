import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useNotificationStore = defineStore('NotificationStore', {
  actions: {
    // 👉 Fetch notification data
    fetchNotifications() {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/notifications')
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
    

  },
})
