import Csrf from "@/apis/csrf"
import axiosIns from "@axios"

export default {

  async register(formData) {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/register', formData)
  },

  async verifyPhoneNumber(formData) {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/verify-phone-number', formData)
  },

  async login(formData) {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/login', formData)
  },

  async resendEmail() {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/email/resend')
  },


  async verifyEmail(data) {
    await Csrf.getCookie()
    console.log(data)

    return axiosIns.post('api/auth/email/verify', data)
  },

  async logout() {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/logout')
  },

  async sendForgotPasswordMail(data) {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/forgot/password', data)
  },

  async verifyResetLink(data) {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/verify-reset-link', data)
  },

  async resetPassword(data) {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/reset-password', data)
  },

  async isUserSubscribed() {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/subscription/check')
  },

  async apiCallToGeneratePaymentIntent() {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/stripe/payment-intent/create')
  },

  async auth() {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/user')
  },

  async profileData() {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/user/profile/data')
  },

  async accountDeactivate() {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/user/account/deactivate')
  },

  async profileCountries() {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/fetch/countries')
  },

  async inviteMember(data) {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/members/add', data)
  },

  async verifyInvitationLink(data) {
    await Csrf.getCookie()

    return axiosIns.post('api/auth/invitation/verify', data)
  },

  async updatePassword(data) {
    await Csrf.getCookie()

    return axiosIns.patch('api/auth/password/update', data)
  },

  async isRole(){
    await Csrf.getCookie()

    return axiosIns.post('api/auth/user/role')
  },

}
