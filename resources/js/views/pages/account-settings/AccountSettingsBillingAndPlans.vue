<script setup>
import { StripeElement, StripeElements } from 'vue-stripe-js'
import BillingHistoryTable from './BillingHistoryTable.vue'

// Images
import Subscription from "@/apis/subscription"
import axiosIns from '@axios'
import mastercard from '@images/icons/payments/mastercard.png'
import visa from '@images/icons/payments/visa.png'

const selectedPaymentMethod = ref('credit-debit-atm-card')
const isPricingPlanDialogVisible = ref(false)
const isConfirmDialogVisible = ref(false)
const isCardEditDialogVisible = ref(false)
const isCardDetailSaveBilling = ref(false)
const currentCardDetails = ref()
const pmIdToDelete = ref(null)
const isConfirmDialogOpen = ref(false)
const isDisabled = ref(false)
const isLoading = ref(false)

const userPlan = ref({
  name: '',
  expiry: '',
  price: '',
  onTrial: '',
  totalDays: '',
  daysSpent: '',
})

const creditCards = ref([])

const countryList = [
  'United States',
  'Canada',
  'United Kingdom',
  'Australia',
  'New Zealand',
  'India',
  'Russia',
  'China',
  'Japan',
]

const cardRef = ref()
const cardError = ref('')

//
// // Replace 'your-publishable-key' with your actual Stripe publishable API key
// const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_KEY);

// Create a Card Element and mount it to the #card-element div
const appearance = {
  theme: 'stripe',
}

const style = {
  base: {
    color: '#777581',
    fontFamily: '"Public Sans", sans-serif, -apple-system, blinkmacsystemfont, "Segoe UI", roboto, "Helvetica Neue", arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"',
    fontSmoothing: 'antialiased',
    fontSize: '15px',
    '::placeholder': {
      color: '#777581',
    },
  },
  invalid: {
    color: 'red',
    iconColor: 'red',
  },
}

const elementsOptions = ref({
  appearance: appearance,
  style: style,
})

const cardNumber = ref()
const cardName = ref('')
const cardExpiryDate = ref('')
const cardCvv = ref()
const cardLastFour = ref()
const elms = ref()
const key = import.meta.env.VITE_STRIPE_KEY
const noDefaultMethod = ref(true)

const resetPaymentForm = () => {
  // cardNumber.value = ''
  // cardName.value = ''
  // cardExpiryDate.value = ''
  // cardCvv.value = 0X0
  // selectedPaymentMethod.value = 'credit-debit-atm-card'
  isCardDetailSaveBilling.value = false
  cardError.value = ''

  const elements = elms.value.elements

  elements.getElement('card').clear()     
}

onMounted(async() => {
  
  Subscription.defaultMethodsCheck()
    .then(res => {
      noDefaultMethod.value = res.data.hasDefaultPaymentMethod
    })
    .catch(error => {
      console.log(error)
    })

  fetchPaymentMethods()

  Subscription.plan()
    .then(res => {
      if(res.data.status){
        userPlan.value = {
          name: res.data.subscription.name,
          expiry: res.data.subscription.expiry,
          price: res.data.subscription.price,
          onTrial: res.data.subscription.onTrial,
          onGracePeriod: res.data.subscription.onGracePeriod,
          totalDays: res.data.subscription.totalDays,
          daysSpent: res.data.subscription.daysSpent,
          credit: res.data.subscription.credit,
        }
      }
    })
})

const fetchPaymentMethods = async () => {
  Subscription.paymentMethods()
    .then(res => {
      creditCards.value = []
      for (var i = 0; i < res.data.length; i++){
        creditCards.value.push({
          pmId: res.data[i].id,
          name: res.data[i].cardName,
          number: '**** **** **** '+res.data[i].cardLastFour,
          expiry: res.data[i].cardExpiryDate,
          postalCode: res.data[i].postalCode,
          isPrimary: res.data[i].isDefault,
          type: res.data[i].brand === 'visa' ? 'visa' : 'mastercard',
          cvv: '',
          image: res.data[i].brand === 'visa' ? visa : mastercard,
          cardLastFour: res.data[i].cardLastFour,
        })
      }
    })
}

const openEditCardDialog = cardDetails => {
  currentCardDetails.value = cardDetails
  isCardEditDialogVisible.value = true
}

const confirmDelete = async pmId => {
  pmIdToDelete.value = pmId
  isConfirmDialogOpen.value = true
}

const handleConfirmation = async action => {
  if(action===true && pmIdToDelete.value){
    try {
      await axiosIns.delete(`api/auth/stripe/payment-method/${pmIdToDelete.value}`)
      pmIdToDelete.value = null
      fetchPaymentMethods()
    } catch (error) {
      console.log('Error deleting card:', error)
    }
  }
}

const createPaymentMethod = async() => {
  const { data } = await axiosIns.post('api/auth/stripe/payment-method/store', {
    cardName: cardName.value,
    cardExpiry: cardExpiryDate.value,
    cardNumber: cardNumber.value,
    cardCvv: cardCvv.value,
  })

  console.log('datadatadata datadata12345678')
  console.log(data)
}

const createPaymentMethodCard = async () => {
  isDisabled.value = true
  isLoading.value = true
 
  const cardNumberElement = cardNumber.value.stripeElement

  const userData = JSON.parse(localStorage.getItem('userData') || 'null')
  const userCardName = `${userData?.firstname || ''} ${userData?.lastname || ''}`.trim()
  const userCardEmail = `${userData?.email ?? ''}`.trim()
  
  elms.value.instance.createPaymentMethod({
    type: 'card',
    card: cardNumberElement,
    billing_details: {
      name: userCardName,
      email: userCardEmail,
    },
  }).then( async result => {
  
    const { data } = await axiosIns.post('api/auth/stripe/payment-method/store', {
      pmId: result.paymentMethod.id,
      isCardSaveBilling: isCardDetailSaveBilling.value,
    })

    isDisabled.value = false
    isLoading.value = false 
    fetchPaymentMethods()
    resetPaymentForm()
  })
    .catch(error => {
      console.log('An error occurred:', error)
      isDisabled.value = false
      isLoading.value = false
    })
  
}

const updatePaymentMethod = async cardDetails => {
  try {
    const response = await axiosIns.post('api/auth/stripe/payment-method/update', {
      pmId: cardDetails.pmId,
      expiryDate: cardDetails.expiry,
      cardName: cardDetails.name,
      postalCode: cardDetails.postalCode,
      isPrimary: cardDetails.isPrimary,
    })


    if(response.data.status){
      isCardEditDialogVisible.value = false
      fetchPaymentMethods()
    }else{
      console.log(response.data)
    }
    
  } catch (error) {
    console.error('Error submitting card details:', error.response.data)
  }
}

const handleChange = event => {
  if (event.error) {
    cardError.value = event.error.message
  } else {
    cardError.value = ''
  }
}
</script>

<template>
  <VRow>
    <!-- 👉 Current Plan -->
    <VCol cols="12">
      <VCard title="Current Plan">
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              md="6"
            >
              <div>
                <div class="mb-6">
                  <h3 class="text-base font-weight-medium mb-1">
                    Your Current Plan is {{ userPlan.name }}
                  </h3>
                  <p class="text-base">
                    A simple start for everyone
                  </p>
                </div>

                <div class="mb-6">
                  <h3 class="text-base font-weight-medium mb-1">
                    Your Current Credit is ${{ userPlan.credit }}
                  </h3>
                  <p class="text-base">
                    Credit will allow you to access the features i.e. SMS, Calls etc. Once used can be obtained using our Top-Up Credit Feature
                  </p>
                </div>

                <div class="mb-6">
                  <h3 class="text-base font-weight-medium mb-1">
                    Active until {{ userPlan.expiry }}
                  </h3>
                  <p class="text-base">
                    We will send you a notification upon Subscription expiration
                  </p>
                </div>

                <div>
                  <h3 class="text-base font-weight-medium mb-1">
                    <span class="me-3">{{ userPlan.price }} Per Month</span>
                    <VChip
                      color="primary"
                      size="small"
                      label
                    >
                      {{ userPlan.onTrial ? 'Trial' : 'Current' }}
                    </VChip>
                  </h3>
                  <p class="text-base mb-0">
                    Standard plan
                  </p>
                </div>
              </div>
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VAlert
                color="warning"
                variant="tonal"
              >
                <VAlertTitle class="mb-1">
                  We need your attention!
                </VAlertTitle>

                <span>Your plan requires update</span>
              </VAlert>

              <!-- progress -->
              <h6 class="d-flex font-weight-medium text-base mt-4 mb-2">
                <span>Days</span>
                <VSpacer />
                <span>{{ userPlan.daysSpent }} of {{ userPlan.totalDays }} Days</span>
              </h6>

              <VProgressLinear
                color="primary"
                rounded
                height="12"
                :model-value="(userPlan.daysSpent / userPlan.totalDays) * 100"
              />

              <p class="text-base mt-2 mb-0">
                {{ userPlan.totalDays - userPlan.daysSpent }} days remaining until your plan requires update
              </p>
            </VCol>

            <VCol cols="12">
              <div class="d-flex flex-wrap gap-y-4">
                <VBtn 
                  class="me-3" 
                  @click="isPricingPlanDialogVisible = true" 
                > 
                  upgrade plan 
                </VBtn> 

                <VBtn
                  color="error"
                  variant="tonal"
                  @click="isConfirmDialogVisible = true"
                >
                  Cancel Subscription
                </VBtn>
              </div>
            </VCol>
          </VRow>

          <!-- 👉 Confirm Dialog -->
          <ConfirmDialog
            v-model:isDialogVisible="isConfirmDialogVisible"
            confirmation-question="Are you sure to cancel your subscription?"
            cancel-msg="Unsubscription Cancelled!!"
            cancel-title="Cancelled"
            confirm-msg="Your subscription cancelled successfully."
            confirm-title="Unsubscribed!"
          />

          <!-- 👉 plan and pricing dialog -->
          <PricingPlanDialog v-model:is-dialog-visible="isPricingPlanDialogVisible" />
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Payment Methods -->
    <VCol cols="12">
      <VAlert
        v-if="!noDefaultMethod"
        class="mb-2"
        color="error"
        variant="tonal"
      >
        You have multiple payment methods with us, please mark one of them as primary other wise you will not be able to use most of the features of the application!
      </VAlert>

      <VCard title="Payment Methods">
        <VCardText>
          <VForm @submit.prevent="createPaymentMethod">
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <VRow>
                  <VCol cols="12">
                    <VRow v-show="selectedPaymentMethod === 'credit-debit-atm-card'">
                      <!-- 👉 Card Number -->
                      <VCol cols="12">
                        <StripeElements
                          ref="elms"
                          v-slot="{ elements }"
                          :stripe-key="key"
                        >
                          <StripeElement
                            ref="cardNumber"
                            tabindex="0"
                            class="cardElement"
                            type="card"
                            :elements="elements"
                            :options="elementsOptions"
                            @change="handleChange"
                          />
                          <p
                            v-if="cardError"
                            class="error"
                          >
                            {{ cardError }}
                          </p>

                          <!--                      <VRow class="pa-3"> -->
                          <!--                        &lt;!&ndash; 👉 Name &ndash;&gt; -->
                          <!--                        <VCol -->
                          <!--                          cols="12" -->
                          <!--                          md="6" -->
                          <!--                        > -->
                          <!--                          <AppTextField -->
                          <!--                            v-model="cardName" -->
                          <!--                          /> -->
                          <!--                        </VCol> -->

                          <!--                        <VCol -->
                          <!--                          cols="5" -->
                          <!--                          md="3" -->
                          <!--                        > -->
                          <!--                          <StripeElement -->
                          <!--                            tabindex="1" -->
                          <!--                            class="cardElement" -->
                          <!--                            type="cardExpiry" -->
                          <!--                            :elements="elements" -->
                          <!--                            :options="elementsOptions" -->
                          <!--                          /> -->
                          <!--                        </VCol> -->

                          <!--                        &lt;!&ndash; 👉 Cvv code &ndash;&gt; -->
                          <!--                        <VCol -->
                          <!--                          cols="5" -->
                          <!--                          md="3" -->
                          <!--                        > -->
                          <!--                          <StripeElement -->
                          <!--                            tabindex="2" -->
                          <!--                            class="cardElement" -->
                          <!--                            type="cardCvc" -->
                          <!--                            :elements="elements" -->
                          <!--                            :options="elementsOptions" -->
                          <!--                          /> -->
                          <!--                        </VCol> -->
                          <!--                      </VRow> -->
                        </StripeElements>
                      </VCol>

                      <!--                      <VCol cols="12"> -->
                      <!--                        <AppTextField -->
                      <!--                          v-model="cardNumber" -->
                      <!--                          label="Card Number" -->
                      <!--                          type="text" -->
                      <!--                          maxlength="16" -->
                      <!--                        /> -->
                      <!--                      </VCol> -->

                      <!--                      &lt;!&ndash; 👉 Name &ndash;&gt; -->
                      <!--                      <VCol -->
                      <!--                        cols="12" -->
                      <!--                        md="6" -->
                      <!--                      > -->
                      <!--                        <AppTextField -->
                      <!--                          v-model="cardName" -->
                      <!--                          label="Name" -->
                      <!--                        /> -->
                      <!--                      </VCol> -->

                      <!--                      &lt;!&ndash; 👉 Expiry date &ndash;&gt; -->
                      <!--                      <VCol -->
                      <!--                        cols="6" -->
                      <!--                        md="3" -->
                      <!--                      > -->
                      <!--                        <AppTextField -->
                      <!--                          v-model="cardExpiryDate" -->
                      <!--                          label="Expiry Date" -->
                      <!--                        /> -->
                      <!--                      </VCol> -->

                      <!--                      &lt;!&ndash; 👉 Cvv code &ndash;&gt; -->
                      <!--                      <VCol -->
                      <!--                        cols="6" -->
                      <!--                        md="3" -->
                      <!--                      > -->
                      <!--                        <AppTextField -->
                      <!--                          v-model="cardCvv" -->
                      <!--                          type="text" -->
                      <!--                          label="CVV Code" -->
                      <!--                          maxlength="3" -->
                      <!--                        /> -->
                      <!--                      </VCol> -->

                      <!-- 👉 Future Billing switch -->
                      <VCol cols="12">
                        <VSwitch
                          v-model="isCardDetailSaveBilling"
                          density="compact"
                          label="Save card for future billing?"
                        />
                      </VCol>
                    </VRow>
                  </VCol>
                </VRow>
              </VCol>

              <!-- 👉 Saved Cards -->
              <VCol
                v-if="creditCards.length"
                cols="12"
                md="6"
              >
                <h6 class="text-base font-weight-medium mb-3">
                  My Cards
                </h6>

                <div class="d-flex flex-column gap-y-4">
                  <VCard
                    v-for="card in creditCards"
                    :key="card.name"
                    flat
                    variant="tonal"
                  >
                    <VCardText class="d-flex flex-sm-row flex-column pa-4">
                      <div class="text-no-wrap">
                        <VImg
                          :src="card.image"
                          width="46"
                        />
                        <h4 class="my-3 text-body-1">
                          <span class="me-2">
                            {{ card.name }}
                          </span>
                          <VChip
                            v-if="card.isPrimary"
                            label
                            color="primary"
                            size="small"
                          >
                            Primary
                          </VChip>
                        </h4>
                        <span class="text-base">**** **** **** {{ card.cardLastFour }}</span>
                      </div>

                      <VSpacer />

                      <div class="d-flex flex-column text-sm-end">
                        <div class="d-flex flex-wrap gap-4 order-sm-0 order-1">
                          <VBtn
                            variant="tonal"
                            color="primary"
                            @click="openEditCardDialog(card)"
                          >
                            Edit
                          </VBtn>
                          <VBtn
                            color="error"
                            variant="tonal"
                            @click="confirmDelete(card.pmId)"
                          >
                            Delete
                          </VBtn>
                        </div>
                        <span class="text-sm mt-sm-auto mb-sm-0 my-5 order-sm-1 order-0">Card expires at {{ card.expiry }}</span>
                      </div>
                    </VCardText>
                  </VCard>
                </div>

                <!-- 👉 Add Edit Card Dialog -->
                <CardAddEditDialog
                  v-model:isDialogVisible="isCardEditDialogVisible"
                  :card-details="currentCardDetails"
                  @submit="updatePaymentMethod"
                />
              </VCol>

              <!-- 👉 Payment method action button -->
              <VCol
                cols="12"
                class="d-flex flex-wrap gap-4"
              >
                <VBtn
                  type="submit"
                  :disabled="isDisabled"
                  :loading="isLoading"
                  @click.prevent="createPaymentMethodCard"
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

    <!-- 👉 Billing Address -->
    <!--
      <VCol cols="12">
      <VCard title="Billing Address">
      <VCardText>
      <VForm @submit.prevent="() => {}">
      <VRow>
      <VCol
      cols="12"
      md="6"
      >
      <AppTextField label="Company Name" />
      </VCol>

             
      <VCol
      cols="12"
      md="6"
      >
      <AppTextField label="Billing Email" />
      </VCol>

              
      <VCol
      cols="12"
      md="6"
      >
      <AppTextField label="Tax ID" />
      </VCol>

              
      <VCol
      cols="12"
      md="6"
      >
      <AppTextField label="VAT Number" />
      </VCol>

              
      <VCol
      cols="12"
      md="6"
      >
      <AppTextField
      dirty
      label="Phone Number"
      type="number"
      prefix="US (+1)"
      />
      </VCol>

              
      <VCol
      cols="12"
      md="6"
      >
      <AppSelect
      label="Country"
      :items="countryList"
      />
      </VCol>

              
      <VCol cols="12">
      <AppTextField label="Billing Address" />
      </VCol>

              
      <VCol
      cols="12"
      md="6"
      >
      <AppTextField label="State" />
      </VCol>

              
      <VCol
      cols="12"
      md="6"
      >
      <AppTextField
      label="Zip Code"
      type="number"
      />
      </VCol>

              
      <VCol
      cols="12"
      class="d-flex flex-wrap gap-4"
      >
      <VBtn type="submit">
      Save changes
      </VBtn>
      <VBtn
      type="reset"
      color="secondary"
      variant="tonal"
      >
      Reset
      </VBtn>
      </VCol>
      </VRow>
      </VForm>
      </VCardText>
      </VCard>
      </VCol>
    -->

    <!-- 👉 Billing History -->
    <VCol cols="12">
      <BillingHistoryTable />
    </VCol>
    <!-- Confirm Dialog -->
    <ConfirmDialog
      v-model:isDialogVisible="isConfirmDialogOpen"
      confirmation-question="Are you sure you want to delete your card?"
      confirm-title="Deleted!"
      confirm-msg="Your card has been deleted successfully."
      cancel-title="Cancelled"
      cancel-msg="Card deletion cancelled!"
      @confirm="handleConfirmation"
    />
  </VRow>
</template>


<style scoped lang="scss">
.cardElement {
  border: 1px solid gray;
  border-radius: 6px;
  background-color: transparent;
  font-size: 18px;
  padding-block: 10px;
  padding-inline: 14px;
}

.cardElement:focus-visible {
  border-color: #38a6e3;
}

.cardElement:focus {
  border-color: #38a6e3;
  outline: #38a6e3;
  outline-offset: 1px;
}

.StripeElement--complete {
  color: white !important;
}

.StripeElement--empty {
  color: white !important;
}

.StripeElement--focus {
  color: white !important;
}

.InputElement:focus {
  .cardElement {
    outline: #38a6e3;
  }
}

.ElementsApp input:focus {
  .cardElement {
    outline: #38a6e3;
  }
}

.ElementsApp,
.ElementsApp .InputElement:focus {
  .cardElement {
    outline: #38a6e3;
  }
}

.pricing-dialog {
  .pricing-title {
    font-size: 1.5rem !important;
  }

  .v-card {
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    box-shadow: none;
  }
}

.error {
  color: red;
  font-size: 14px;
  margin-block-start: 5px;
}
</style>
