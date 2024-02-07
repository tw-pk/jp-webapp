import Csrf from "@/apis/csrf"
import axiosIns from "@axios"

export default {

  async findContact(data){
    return new Promise((resolve, reject) => {
      axiosIns.post('/api/auth/contact/fetch', data)
        .then(response => resolve(response))
        .catch(error => reject(error))
    })
  },

}
