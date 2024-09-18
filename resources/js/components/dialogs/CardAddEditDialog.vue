<script setup>
import { defineEmits, defineProps, ref, toRaw, watch } from 'vue'

const props = defineProps({
  cardDetails: {
    type: Object,
    required: false,
    default: () => ({
      number: '',
      name: '',
      expiry: '',
      cvv: '',
      isPrimary: false,
      type: '',
    }),
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'submit',
  'update:isDialogVisible',
])

const expiryMonth = ref('')
const expiryYear = ref('')

const cardDetails = ref(structuredClone(toRaw(props.cardDetails)))

watch(props, () => {
  cardDetails.value = structuredClone(toRaw(props.cardDetails))

  const [expMonth, expYear] = cardDetails.value.expiry.split('/')

  expiryMonth.value = expMonth || ''
  expiryYear.value = expYear || ''
})

const formSubmit = () => {
  cardDetails.value.expiry = `${expiryMonth.value}/${expiryYear.value}`
  emit('submit', cardDetails.value)

  //emit('update:isDialogVisible', false)
}

const dialogModelValueUpdate = val => {
  emit('update:isDialogVisible', val)
}

const postalCodeRules = [
  v => !!v || 'Postal code is required',
  v => /^[0-9]{5}(?:-[0-9]{4})?$/.test(v) || 'Postal code must be valid',
]

const expiryMonthRules = [
  v => !!v || 'Expiry month is required',
  v => /^[0-1]?[0-9]$/.test(v) || 'Invalid month',
]

const expiryYearRules = [
  v => !!v || 'Expiry year is required',
  v => /^[2-9][0-9]{3}$/.test(v) || 'Invalid year',
]


const cardholderNameRules = [
  v => !!v || 'Cardholder Name is required',
  v => v.length >= 2 || 'Name must be at least 2 characters long',
  v => /^[A-Za-z\s]+$/.test(v) || 'Name must only contain letters and spaces',
]

const expiryMonthList = [
  { name: '01', value: '01' },
  { name: '02', value: '02' },
  { name: '03', value: '03' },
  { name: '04', value: '04' },
  { name: '05', value: '05' },
  { name: '06', value: '06' },
  { name: '07', value: '07' },
  { name: '08', value: '08' },
  { name: '09', value: '09' },
  { name: '10', value: '10' },
  { name: '11', value: '11' },
  { name: '12', value: '12' },
]

const expiryYearList = ['2024', '2025', '2026', '2027', '2028', '2029', '2030', '2031', '2032', '2033', '2034', '2035', '2036', '2037', '2038', '2039', '2040', '2041', '2042', '2043']
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 580"
    :model-value="props.isDialogVisible"
    @update:model-value="dialogModelValueUpdate"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="dialogModelValueUpdate(false)" />

    <VCard class="pa-5 pa-sm-8">
      <!-- 👉 Title -->
      <VCardItem class="text-center">
        <VCardTitle class="text-h5 font-weight-medium mb-3">
          {{ props.cardDetails.name ? 'Edit Card' : 'Add New Card' }}
        </VCardTitle>
        <p class="mb-0">
          {{ props.cardDetails.name ? 'Edit your saved card details' : 'Add your saved card details' }}
        </p>
        <p
          v-if="cardDetails.number"
          class="mb-0"
        >
          {{ cardDetails.number }}
        </p>
      </VCardItem>

      <VCardText class="pt-6">
        <VForm @submit.prevent="() => {}">
          <VRow>
            <!-- 👉 Expiry month -->
            <VCol cols="6">
              <AppAutocomplete
                v-model="expiryMonth"
                label="Expiry month"
                :items="expiryMonthList"
                item-title="name"
                item-value="value"
                required 
                :rules="expiryMonthRules"
                @focus="expiryMonthList"
              />
            </VCol>

            <!-- 👉 Expiry year -->
            <VCol cols="6">
              <AppAutocomplete
                v-model="expiryYear"
                label="Expiry year"
                :items="expiryYearList"
                required
                :rules="expiryYearRules"
                @focus="expiryYearList"
              />
            </VCol>
            

            <!-- 👉 Card Name -->
            <VCol
              cols="12"
              md="6"
            >
              <AppTextField
                v-model="cardDetails.name"
                label="Cardholder Name"
                required
                :rules="cardholderNameRules"
              />
            </VCol>

            <!-- 👉 Zip/Postal -->
            <VCol cols="6">
              <AppTextField
                v-model="cardDetails.postalCode"
                label="Zip/Postal"
                :rules="postalCodeRules"
              />
            </VCol>

            <!-- 👉 Card Primary Set -->
            <VCol cols="12">
              <VSwitch
                v-model="cardDetails.isPrimary"
                label="Set as primary card"
              />
            </VCol>

            <!-- 👉 Card actions -->
            <VCol
              cols="12"
              class="text-center"
            >
              <VBtn
                class="me-3"
                type="submit"
                @click="formSubmit"
              >
                Submit
              </VBtn>
              <VBtn
                color="secondary"
                variant="tonal"
                @click="$emit('update:isDialogVisible', false)"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
