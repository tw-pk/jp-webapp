import { defineStore } from 'pinia'
import axiosIns from "@axios";


export const useInviteMemberStore = defineStore('InviteMemberStore', {
    actions: {
        createInvitations(data){
            return new Promise((resolve, reject) => {
                axiosIns.post('/api/auth/invitations/store', data)
                .then(response => resolve(response))
                .catch(error => reject(error))
            })
        }
    }
})
