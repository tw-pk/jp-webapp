import axiosIns from "@axios"
import { defineStore } from 'pinia'
import { createDevice, getDevice } from './twilioDevice.js'

export const useDialerStore = defineStore('dialer', {
  state: () => ({
    // Your state properties here
  }),
  actions: {
    // Fetch the Twilio token from the Laravel API
    async fetchTwilioToken() {
      try {
        const response = await axiosIns.post('api/auth/twilio/capability/token')
        
        return response.data
      } catch (error) {
        console.error('Error fetching Twilio token:', error)
        throw error
      }
    },

    fetchUserOwnerNumbers(){
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/numbers/owned')
          .then(res => resolve(res))
          .catch(error => reject(error))
      })
    },

    // 👉 Fetch countries 
    async fetchCountries() { 
      return axiosIns.post('/api/auth/fetch/dialer/countries')
        .then(response => response.data)
        .catch(error => {
          console.error('Failed to fetch country list:', error)
          throw error
        })
    },

    // 👉 Fetch contact list
    fetchContacts(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/fetch/dialer/contacts', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // Initialize the Twilio device when needed
    async initializeTwilioDevice() {
      const token = await this.fetchTwilioToken()
      const device = createDevice(token)

      // Optionally, you can set the device in the state or perform other actions here
    },

    // 👉 Dialer Setting Save
    settingSave(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/dialer/setting/save', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },

    // 👉 Dialer Setting fetch
    async fetchSetting() { 
      return axiosIns.post('/api/auth/fetch/dialer/setting')
        .then(response => response.data)
        .catch(error => {
          console.error('Failed to fetch dialer setting:', error)
          throw error
        })
    },

    // 👉 Dialer fetch call logs
    fetchCallLogs(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/fetch/dialer/call-logs', data)
          .then(response => resolve(response))
          .catch(error => reject(error))
      })
    },
    

    //connect transfer call
    connectTransferCall(data) {
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/connect/transfer-call', data)
        .then(res => resolve(res))
        .catch(error => reject(error));
      });
    };
    

     //fetch team member        
     fetchMemberList() {      
      return new Promise((resolve, reject) => {
        axiosIns.post('api/auth/team/fetch/members')
          .then(response => {            
            resolve(response.data.inviteMembers);
          })
          .catch(error => reject(error));
      });

    }

  },
  getters: {
    // Get the Twilio device from the dedicated JavaScript file
    twilioDevice: () => getDevice(),
  },
})
