import axiosIns from "@axios";

export default {

    async retrieveToken(){
        return axiosIns.post('api/auth/twilio/capability/token')
    },

    async setCurrentNumber(data){
      return axiosIns.put('api/auth/dialer/current-number', data)
    },

    data(){
      return {        
        selectedTeamMember,        
      }
    },
    

    async holdCall() {
      console.log('inside resume call');
      try {
        const response = await axios.post('/api/hold-call', {
          CallSid: this.callSid
        });
        console.log('Call placed on hold:', response.data);
      } catch (error) {
        console.error('Error holding call:', error);
      }
    },

    async resumeCall() {
      console.log('inside resume call');
      
      try {
        const response = await axios.post('/api/resume-call', {
          CallSid: this.callSid
        });
        console.log('Call resumed:', response.data);
      } catch (error) {
        console.error('Error resuming call:', error);
      }
    },

    async toggleHold() {
      console.log('inside toggle cal funtion');
      
      if (this.isOnHold) {
        await this.resumeCall();
      } else {
        await this.holdCall();
      }
      this.isOnHold = !this.isOnHold; // Toggle the hold state
    },
}
