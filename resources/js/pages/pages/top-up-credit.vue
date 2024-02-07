<script setup>
import {useTopUpCreditStore} from "@/views/apps/credit/useTopUpCreditStore"
import axiosIns from "@axios"

const route = useRoute()
const router = useRouter()
const isLoading = ref(false)
const isDisabled = ref(true)

const topUpCreditStore = useTopUpCreditStore()

const currentCredit = ref(0)
const autoCreditEnabled = ref(false)
const autoCreditValue = ref()

const availableCreditLimits = ref()
const loading = ref(false)
const topUpId = ref()
const isPaymentSuccessful = ref(null)
const rechargeAmount = ref('')
const creditInfoUpdated = ref(false)
const checkoutUrl = ref('')

const submit = () => {
  window.location.href = checkoutUrl.value
}

onUnmounted(() => {
  checkoutUrl.value = ''
})

const createStripeSession = data => {
  isLoading.value = true
  isDisabled.value = true
  axiosIns.post('/api/auth/stripe/session/top-up/create', data)
    .then(res => {
      checkoutUrl.value = res.data.url
      isLoading.value = false
      isDisabled.value = false
    })
    .catch(error => console.log(error))
}

const updateCreditInfo = () => {
  axiosIns.post('/api/auth/credit-info/update', {
    autoCreditEnabled: autoCreditEnabled.value,
    autoCreditPrice: autoCreditValue.value,
    rechargeAmount: rechargeAmount.value
  })
    .then(res => {
      if (res.data.status) {
        creditInfoUpdated.value = true
      }
    })
    .catch(error => console.log(error))
}

watch(topUpId, value => {
  createStripeSession({topUpId: value})
})

const toggleSwitch = () => {
  autoCreditEnabled.value = !autoCreditEnabled.value
}

onMounted(() => {
  if (route.query.payment) {
    if (route.query.payment === 'success') {
      isPaymentSuccessful.value = true
    }
    router.replace(route.path)
  }

  loading.value = true
  topUpCreditStore.fetchTopUpCreditInfo()
    .then(res => {
      currentCredit.value = res.data.credit
      if(res.data.autoCredit){
        autoCreditEnabled.value = true
        autoCreditValue.value = res.data.threshold_value
        rechargeAmount.value = res.data.recharge_value
      }
    })
    .catch(error => {
      console.log(error)
    })

  topUpCreditStore.fetchCreditLimits()
    .then(res => {
      availableCreditLimits.value = res.data
    })
    .catch(error => {
      console.log(error)
    })
    .finally(() => {
      loading.value = false
    })

})
</script>

<template>
  <div>
    <div class="pb-4 text-h4">
      Top Up Credit
    </div>
    <div class="pb-4 text-h4 text-center text-success">
      Credit Balance: {{ currentCredit }}
    </div>
    <VRow class="match-height">
      <VCol cols="12">
        <VAlert closable v-if="isPaymentSuccessful" color="success" class="mb-2">
          Payment Successful
        </VAlert>
        <VCard>
          <VCardText>
            <div class="ml-3 text-h5">
              Add JotCall Credit
            </div>
            <VCol
              cols="12"
              md="6"
            >
              <AppAutocomplete
                v-model="topUpId"
                label="Amount"
                :items="availableCreditLimits"
                item-title="price"
                item-value="id"
                :loading="loading"
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VBtn
                :loading="isLoading"
                :disabled="isDisabled"
                @click="submit"
              >
                Proceed To Payment
              </VBtn>
            </VCol>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12">
        <VAlert closable v-if="creditInfoUpdated" color="success" class="mb-2">
          Credit Info Updated Successfully
        </VAlert>
        <VCard>
          <VCardText>
            <div class="ml-3 text-h5">
              Setup auto top-up
            </div>

            <VCol cols="12">
              <VRow no-gutters>
                <VCol
                  cols="12"
                  md="4"
                >
                  <label>Switch On or Off Auto Recharge Settings</label>
                </VCol>

                <VCol
                  cols="12"
                  md="8"
                >
                  <VSwitch
                    v-model="autoCreditEnabled"
                    color="success"
                    @click="toggleSwitch"
                  />
                </VCol>
              </VRow>
            </VCol>

            <VCol cols="12">
              <VRow no-gutters>
                <VCol
                  cols="12"
                  md="4"
                >
                  <label>Automatically recharge my account when <br> balance below</label>
                </VCol>

                <VCol
                  cols="12"
                  md="6"
                >
                  <AppAutocomplete
                    v-model="autoCreditValue"
                    :items="availableCreditLimits"
                    item-title="price"
                    item-value="id"
                    :loading="loading"
                    :disabled="!autoCreditEnabled"
                  />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol
                  cols="12"
                  md="4"
                >
                  <label>Recharge amount</label>
                </VCol>

                <VCol
                  cols="12"
                  md="6"
                >
                  <AppAutocomplete
                    v-model="rechargeAmount"
                    :items="availableCreditLimits"
                    item-title="price"
                    item-value="id"
                    :loading="loading"
                    :disabled="!autoCreditEnabled"
                  />
                </VCol>
              </VRow>
            </VCol>


            <VCol
              cols="12"
              md="6"
            >
              <VBtn
                :loading="isLoading"
                :disabled="!autoCreditEnabled"
                @click="updateCreditInfo"
              >
                Save Changes
              </VBtn>
            </VCol>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
