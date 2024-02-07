<script setup>
import { useCreditPaymentStore } from "@/views/apps/credit/useCreditPaymentStore"
import { requiredValidator } from '@validators'
import { ref } from "vue"

const creditPaymentStore = useCreditPaymentStore()
const selectedPaymentMethod = ref('credit_card')
const cardNumber = ref()
const cardName = ref('')
const cardExpiryDate = ref('')
const cardCvv = ref()
const cardLastFour = ref()
const isCardDetailSaveBilling = ref(false)
const form = ref()
const isDisabled = ref(false)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref('')

const addCreditPayments = () => {
  isDisabled.value = true
  isLoading.value = true
  creditPaymentStore.addCreditPayments({
    payment_method: selectedPaymentMethod.value,
    card_number: cardNumber.value,
    card_name: cardName.value,
    card_expiry_date: cardExpiryDate.value,
    card_cvv: cardCvv.value,
    card_detail_save: isCardDetailSaveBilling.value,
  }).then(response => {
    isDisabled.value = false
    isLoading.value = false
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = `success`
    isSnackbarVisible.value = true

  }).catch(error => {
    isDisabled.value = false
    isLoading.value = false
    let errorMsg = ''
    if (error.response && error.response.data && error.response.data.errors) {
      const errorMessages = error.response.data.errors
      const errorFields = Object.keys(errorMessages)
      for (const field of errorFields) {
        const fieldErrors = errorMessages[field]

        errorMsg += `${fieldErrors.join('\n')}\n`
      }
    } else {
      errorMsg = error.message
    }
    snackbarMessage.value = errorMsg
    snackbarActionColor.value = 'error'
    isSnackbarVisible.value = true
  })
}

const saveCreditPayments = () => {
  if (selectedPaymentMethod.value === 'credit_card') {
    form.value.validate().then(isValid => {
      if(isValid.valid === true ){
        form.value.resetValidation()
        addCreditPayments()
      }
    }).catch(err => {
      console.log(err)
    })
  }else{
    addCreditPayments()
  }
}

watch(selectedPaymentMethod, newValue => {

  if(newValue !=='credit_card'){
    form.value.reset()
    selectedPaymentMethod.value = 'paypal_account'
  }

})

const resetPaymentForm = () => {
  form.value.reset()
  selectedPaymentMethod.value = 'credit_card'
}
</script>

<template>
  <div>
    <div class="pb-4 text-h4">
      Payment
    </div>
    <VRow class="match-height">
      <VCol cols="12">
        <VCard title="Payment Methods">
          <VCardText>
            <VForm
              ref="form"
              lazy-validation
            >
              <VRow>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VRow>
                    <!-- 👉 card type switch -->
                    <VCol cols="12">
                      <VRadioGroup
                        v-model="selectedPaymentMethod"
                        inline
                      >
                        <VRadio
                          value="credit_card"
                          label="Credit/Debit/ATM Card"
                        />
                        <VRadio
                          value="paypal_account"
                          label="PayPal Account"
                        />
                      </VRadioGroup>
                    </VCol>

                    <VCol cols="12">
                      <VRow v-show="selectedPaymentMethod === 'credit_card'">
                        <!-- 👉 Card Number -->
                        <VCol cols="12">
                          <AppTextField
                            v-model="cardNumber"
                            label="Card Number"
                            type="number"
                            :rules="[requiredValidator]"
                            required
                          />
                        </VCol>

                        <!-- 👉 Name -->
                        <VCol
                          cols="12"
                          md="6"
                        >
                          <AppTextField
                            v-model="cardName"
                            label="Name"
                            :rules="[requiredValidator]"
                            required
                          />
                        </VCol>

                        <!-- 👉 Expiry date -->
                        <VCol
                          cols="6"
                          md="3"
                        >
                          <AppTextField
                            v-model="cardExpiryDate"
                            label="Expiry Date"
                            placeholder="MM/YY"
                            :rules="[requiredValidator]"
                            required
                          />
                        </VCol>

                        <!-- 👉 Cvv code -->
                        <VCol
                          cols="6"
                          md="3"
                        >
                          <AppTextField
                            v-model="cardCvv"
                            type="number"
                            label="CVV Code"
                            :rules="[requiredValidator]"
                            required
                          />
                        </VCol>

                        <!-- 👉 Future Billing switch -->
                        <VCol cols="12">
                          <VSwitch
                            v-model="isCardDetailSaveBilling"
                            density="compact"
                            label="Save card for future billing?"
                          />
                        </VCol>
                      </VRow>

                      <p
                        v-show="selectedPaymentMethod === 'paypal_account'"
                        class="text-base"
                      >
                        Cash on delivery is a mode of payment where you make the payment after the goods/services are received.
                      </p>
                      <p
                        v-show="selectedPaymentMethod === 'paypal_account'"
                        class="text-base"
                      >
                        You can pay cash or make the payment via debit/credit card directly to the delivery person.
                      </p>
                    </VCol>
                  </VRow>
                </VCol>

                <!-- 👉 Payment method action button -->
                <VCol
                  cols="12"
                  class="d-flex flex-wrap gap-4"
                >
                  <VBtn
                    :disabled="isDisabled" 
                    :loading="isLoading"
                    @click="saveCreditPayments"
                  >
                    Save changes
                  </VBtn>
                  <VBtn
                    color="secondary"
                    variant="tonal"
                    @click="resetPaymentForm"
                  >
                    Reset
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
      <!-- Snackbar -->
      <VSnackbar
        v-model="isSnackbarVisible"
        multi-line
      >
        {{ snackbarMessage }}
        <template #actions>
          <VBtn
            :color="snackbarActionColor"
            @click="isSnackbarVisible = false"
          >
            Close
          </VBtn>
        </template>
      </VSnackbar>
    </VRow>
  </div>
</template>
