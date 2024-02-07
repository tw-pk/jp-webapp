import Csrf from "@/apis/csrf"
import axiosIns from "@axios"

export default {
    async plan() {
        await Csrf.getCookie()

        return axiosIns.post('/api/auth/subscription/plan')
    },

    async paymentMethods() {
        await Csrf.getCookie()

        return axiosIns.post('api/auth/stripe/payment-methods')
    },

    async defaultMethodsCheck(){
        await Csrf.getCookie()

        return axiosIns.post('api/auth/stripe/default-payment-method-check')
    }
}
