<script setup>
import router from "@/router"
import { StripeCheckout } from '@vue-stripe/vue-stripe'
import axiosIns from "@axios"

const publishableKey = import.meta.env.VITE_STRIPE_KEY
const sessionId = ref(null)
const checkoutRef = ref(null)
const totalNumbers = ref(0)
const subtotal = ref(0)
const isDisabled = ref(false)
const isLoading = ref(false)

const submit = () => {
  checkoutRef.value.redirectToCheckout()
}

const fetchUserNumbersTotal = () => {
  isDisabled.value = true
  isLoading.value = true
  axiosIns.post('/api/auth/user-numbers/total')
    .then(res => {
      console.log(res.data.status)
      if(res.data.status){
        isDisabled.value = false
        isLoading.value = false

        totalNumbers.value = res.data.totalNumbers
        subtotal.value = res.data.subtotal
      }
    })
}

const createStripeSession = () => {
  axiosIns.post('/api/auth/stripe/session/create')
    .then(res => {
      sessionId.value = res.data.checkout.id
    })
}

const refetchData = hideOverlay => {
  fetchUserNumbersTotal()
  setTimeout(hideOverlay, 3000)
}

onMounted(() => {
  fetchUserNumbersTotal()
  createStripeSession()
})
</script>

<template>
  <VRow no-gutters>
    <VBtn
      class="mt-4 ml-5"
      type="button"
      @click="router.go(-1)"
    >
      <VIcon
        icon="tabler-chevron-left"
        class="flip-in-rtl mr-2"
      />
      back
    </VBtn>
    <VCol
      cols="12"
      lg="12"
      class="auth-card-v2 d-flex align-center justify-center mt-6"
    >
      <VCol
        cols="12"
        md="5"
      >
        <AppCardActions
          title="Summary"
          action-refresh
          variant="outlined"
          @refresh="refetchData"
        >
          <!-- 👉 payment offer -->


          <VDivider />

          <!-- 👉 Price details -->
          <VCardText>
            <h6 class="text-h6 font-weight-bold mb-3">
              Price Details
            </h6>

            <div class="text-high-emphasis">
              <div class="d-flex justify-space-between mb-2">
                <span>Total Phone Numbers</span>
                <span>{{ totalNumbers }}</span>
              </div>


              <div class="d-flex justify-space-between mb-2">
                <span>Subtotal</span>
                <span>{{ subtotal }}</span>
              </div>
            </div>
          </VCardText>

          <VDivider />

          <VCardText class="d-flex justify-space-between py-4">
            <h6 class="text-base font-weight-bold">
              Total
            </h6>
            <h6 class="text-base font-weight-bold">
              {{ subtotal }}
            </h6>
          </VCardText>
        </AppCardActions>

        <StripeCheckout
          ref="checkoutRef"
          mode="payment"
          :pk="publishableKey"
          :session-id="sessionId"
        />

        <VBtn
          block
          class="mt-4"
          :loading="isLoading"
          :disabled="isLoading"
          @click="submit"
        >
          Checkout
        </VBtn>
      </VCol>
    </VCol>
  </VRow>
</template>

<route lang="yaml">
meta:
  layout: blank
  action: view
  subject: Auth
</route>
