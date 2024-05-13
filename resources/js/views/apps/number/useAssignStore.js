import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useAssignStore = defineStore('AssignStore', {
  actions: {
    // 👉 Fetch Members and Teams
    fetchMembersTeams(params) { return axiosIns.post('api/auth/team/fetch/members/teams', { params }) },

    // 👉 Fetch assign number
    fetchAssignNumber(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/assign/number', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Add assign number
    addAssignNumber(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/phone/assign', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

  },
})

