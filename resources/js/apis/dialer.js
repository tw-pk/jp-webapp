import axiosIns from "@axios";

export default {

    async retrieveToken(){
        return axiosIns.post('api/auth/twilio/capability/token')
    },

    async setCurrentNumber(data){
      return axiosIns.put('api/auth/dialer/current-number', data)
    },
    
}
