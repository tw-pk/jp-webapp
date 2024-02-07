import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useSettingRoleStore = defineStore('SettingRoleStore', {
  actions: {
    // 👉 Fetch Roles data
    fetchRoles(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/role', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Add Role
    addRole(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/add/role', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Delete Role
    deleteRole(id) {
      return new Promise((resolve, reject) => {
        axiosIns.delete(`api/auth/role/delete/${id}`)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
  
      
  },
})

