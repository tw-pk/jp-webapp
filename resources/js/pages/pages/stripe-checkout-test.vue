<script setup>
import { StripeCheckout } from '@vue-stripe/vue-stripe'
import axiosIns from "@axios"

const publishableKey = import.meta.env.VITE_STRIPE_KEY
const sessionId = ref(null)
const checkoutRef = ref(null)

const submit = () => {
  checkoutRef.value.redirectToCheckout()
}

const createStripeSession = () => {
  axiosIns.post('/api/auth/stripe/session/create')
    .then(res => {
      sessionId.value = res.data.checkout.id
    })
}

createStripeSession()

</script>

<template>
  <div class="">
    <StripeCheckout
      ref="checkoutRef"
      mode="payment"
      :pk="publishableKey"
      :session-id="sessionId"
    />
    <VBtn @click="submit">Pay now!</VBtn>
  </div>
</template>

<style scoped lang="scss">

</style>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Auth
</route>
