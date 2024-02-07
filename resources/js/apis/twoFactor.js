import Csrf from "@/apis/csrf"
import axiosIns from "@axios"

export default {

  async enableTwoFactor(data){
    await Csrf.getCookie()

    return axiosIns.post('/api/auth/2fa/enable', data)
  },

  async verifyCode(data){
    await Csrf.getCookie()

    return axiosIns.post('/api/auth/2fa/verify-code', data)
  },

  async verifySession(){
    await Csrf.getCookie()

    return axiosIns.post('/api/auth/2fa/verify-session')
  },

  async profile(){
    await Csrf.getCookie()

    return axiosIns.post('/api/auth/2fa/profile')
  },

  async generateCode(data){
    await Csrf.getCookie()

    return axiosIns.post('/api/auth/2fa/generate-code', data)
  },

  async isEnabled(){
    await Csrf.getCookie()

    return axiosIns.post('/api/auth/2fa/is-enabled')
  },

  async disableTwoFactor(){
    await Csrf.getCookie()

    return axiosIns.post('/api/auth/2fa/disable')
  },
}
