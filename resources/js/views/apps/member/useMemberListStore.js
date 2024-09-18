import axiosIns from '@/plugins/axios'
import { defineStore } from 'pinia'

export const useMemberListStore = defineStore('MemberListStore', {
  actions: {
    // 👉 Fetch users data
    fetchMembers(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/members/list', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
    

    // 👉 Fetch user phone numbers 
    fetchNumbers(params) { return axiosIns.post('api/auth/fetch/user/numbers', { params }) },

    // 👉 Fetch Roles 
    fetchRoles(params) { return axiosIns.post('api/auth/fetch/roles', { params }) },

    // 👉 Add User
    addMember(memberData) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/members/add', {
          member: memberData,
        }).then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 fetch single user
    fetchMember(id) {
      return new Promise((resolve, reject) => {
        axiosIns.get(`api/auth/members/${id}`).then(response => resolve(response)).catch(error => reject(error))
      })
    },

    // 👉 fetch member detail
    fetchMemberDetail(data){
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/member/detail', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Delete User
    deleteMember(id) {
      return new Promise((resolve, reject) => {
        axiosIns.delete(`api/auth/members/${id}`).then(response => resolve(response)).catch(error => reject(error))
      })
    },

  },
})
