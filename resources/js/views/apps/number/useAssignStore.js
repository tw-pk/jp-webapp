import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useAssignStore = defineStore('AssignStore', {
  actions: {
    // 👉 Fetch Members
    fetchMembers(params) { return axiosIns.post('api/auth/team/fetch/members', { params }) },

    // 👉 Fetch Members
    fetchTeams(params) { return axiosIns.post('api/auth/team/fetch/teams', { params }) },

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

