import { defineStore } from 'pinia'
import axiosIns from "@axios";

export const useCountriesStore = defineStore('CountryStore', {
  actions: {
    // 👉 Fetch twilio country 
    fetchTwilioCountryList() { 
      return axiosIns.post('/api/auth/twilio/country/list')
        .then(response => response.data)
        .catch(error => {
          console.error('Failed to fetch country list:', error)
          throw error
        })
    },

    // 👉 Fetch countries 
    fetchCountries() { 
      return axiosIns.post('/api/auth/fetch/countries')
        .then(response => response.data)
        .catch(error => {
          console.error('Failed to fetch country list:', error)
          throw error
        })
    },
    
    // 👉 Fetch state 
    fetchState(data)
    {  return new Promise((resolve, reject) => {
        axiosIns.post('/api/auth/fetch/state', data)
        .then(response => resolve(response))
        .catch(error => reject(error))
    })
    },


  },
})
