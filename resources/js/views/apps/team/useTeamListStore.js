import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useTeamListStore = defineStore('TeamListStore', {
  actions: {
    // 👉 Fetch Teams data
    //fetchTeams(params) { return axiosIns.post('api/auth/team/list', { params }) },
    fetchTeams(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/team/list', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch Member list
    fetchMemberList(params) { return axiosIns.post('api/auth/team/fetch/members', { params }) },

    // 👉 Fetch Members
    fetchMembers(params) { return axiosIns.post('api/auth/team/fetch/members', { params }) },

    // 👉 Add Team
    addTeam(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/team/add', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

  

    // 👉 fetch single team
    fetchTeam(id) {
      return new Promise((resolve, reject) => {
        axiosIns.get(`api/auth/team/${id}`).then(response => resolve(response)).catch(error => reject(error))
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
