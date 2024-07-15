<script setup>
import twoFactor from "@/apis/twoFactor"
import { requiredValidator } from "@validators"

const props = defineProps({
  mobileNumber: {
    type: String,
    required: false,
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'submit',
])

const otpGenerated = ref(false)
const phoneNumber = ref(structuredClone(toRaw(props.mobileNumber)))
const otp = ref(null)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')

const error = ref({
  phoneNumber: undefined,
  channel: undefined,
  otp: undefined,
})

const formSubmit = async () => {
  if (phoneNumber.value) {
    emit('submit', phoneNumber.value)
    if(otpGenerated.value){
      if (otp.value ==null || otp.value =='' ) {
        error.value = {
          otp: 'Please enter a valid otp',
        }
      }  
      await twoFactor.verifyCode({
        'to': phoneNumber.value,
        'code': otp.value,
      })
        .then(res => {
          if (res.data.status) {
            otpGenerated.value = false
            emit('update:isDialogVisible', false)

            snackbarMessage.value = res.data.message
            snackbarActionColor.value = `success`
            isSnackbarVisible.value = true
          }
        }).catch(error => {
          error.value = error.response.data.errors
          
        })
    }else{
      await twoFactor.enableTwoFactor({
        'phoneNumber': phoneNumber.value,
        'channel': 'sms',
      })
        .then(res => {
          if (res.data.status) {
            otpGenerated.value = true
          }
        }).catch(error => {
          console.log(error)
          error.value = error.response.data.errors
        })
    }

  } else {
    error.value = {
      phoneNumber: 'Please enter a valid phone number',
    }
  }
}

const resetPhoneNumber = () => {
  phoneNumber.value = structuredClone(toRaw(props.mobileNumber))
  emit('update:isDialogVisible', false)
}

const dialogModelValueUpdate = val => {
  emit('update:isDialogVisible', val)
}

const changeNumber = () => {
  otpGenerated.value = false
  phoneNumber.value = ''
  otp.value = ''
}
</script>

<template>
  <VDialog
    max-width="787"
    :model-value="props.isDialogVisible"
    @update:model-value="dialogModelValueUpdate"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="dialogModelValueUpdate(false)" />

    <VCard class="pa-5 pa-sm-8">
      <VCardItem class="text-start">
        <VCardTitle class="text-h6 font-weight-medium mb-2">
          Verify Your Mobile Number for SMS
        </VCardTitle>
        <VCardSubtitle>
          <span class="text-base">
            Enter your mobile phone number with country code and we will send you a verification code.
          </span>
        </VCardSubtitle>
      </VCardItem>

      <VCardText class="pt-6">
        <VForm @submit.prevent="() => {}">
          <AppTextField
            v-model="phoneNumber"
            :rules="[requiredValidator]"
            :error-messages="error.phoneNumber"
            name="mobile"
            label="Phone Number"
            type="text"
            placeholder="+1XXXXXXXXXXX"
            class="mb-1"
            :disabled="otpGenerated"
          />
          <div
            v-if="otpGenerated"
            class="d-flex flex-wrap justify-end mb-2"
          >
            <a
              href="javascript:void(0)"
              class="text-decoration-none"
              @click="changeNumber"
            >Change number</a>
          </div>
          <div
            v-if="otpGenerated"
            class="d-flex flex-wrap"
          >
            <AppTextField
              v-model="otp"
              :rules="[requiredValidator]"
              :error-messages="error.otp"
              name="otp"
              label="OTP"
              type="number"
              min="0"
              max="6"
              placeholder="XXXXXX"
              class="mb-1"
            />
          </div>
          <div class="d-flex flex-wrap justify-end gap-4 mt-4">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="resetPhoneNumber"
            >
              Cancel
            </VBtn>
            <VBtn
              type="submit"
              @click.prevent="formSubmit"
            >
              continue
              <VIcon
                end
                icon="tabler-arrow-right"
                class="flip-in-rtl"
              />
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
  <!-- Snackbar -->
  <VSnackbar
    v-model="isSnackbarVisible"
    multi-line
    transition="scroll-y-reverse-transition"
    location="top end"
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
</template>
