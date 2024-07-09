<script setup>
import User from "@/apis/user"
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-verify-email-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-verify-email-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { phoneValidator, requiredValidator } from "@validators"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useTheme } from 'vuetify'
import { useAuthStore } from '../../store/auth'

const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

const vuetifyTheme = useTheme()
const currentThemeName = vuetifyTheme.name.value

vuetifyTheme.themes.value[currentThemeName].colors.primary = '#38A6E3'

//auth store
const authStore = useAuthStore()

const route = useRoute()
const router = useRouter()

const lastInsertedId = route.params.id

console.log('lastInsertedId')
console.log(lastInsertedId)

const otp = ref(null)
const otp_timer = ref('')
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const phoneNumber = ref('')
const otpGenerated = ref(false)

const error = ref({
  phoneNumber: undefined,
  channel: undefined,
  otp: undefined,
})


const formSubmit = async () => {
  if (phoneNumber.value) {
    if(otpGenerated.value){
      if (otp.value ==null || otp.value =='' ) {
        error.value = {
          otp: 'Please enter a valid otp',
        }
      }  
      await User.verifyCode({
        lastInsertedId: lastInsertedId,
        'to': phoneNumber.value,
        'code': otp.value,
      })
        .then(res => {
          if (res.data.status) {
            otpGenerated.value = false
          
            snackbarMessage.value = res.data.message
            snackbarActionColor.value = `success`
            isSnackbarVisible.value = true
            cancelVerifyPhone()
          }else{
            snackbarMessage.value = res.data.message
            snackbarActionColor.value = `error`
            isSnackbarVisible.value = true
          }
        }).catch(error => {
          error.value = error.response.data.errors
          
        })
    }else{
      await User.verifyPhoneNumber({
        'lastInsertedId': lastInsertedId,
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

const changeNumber = () => {
  otpGenerated.value = false
  phoneNumber.value = ''
  otp.value = ''
}

const cancelVerifyPhone = () => {
  authStore.setVerificationMessage('A verification email has been sent to your email. Please login to verify your account.')
      
  router.replace(route.query.to ? String(route.query.to) : '/login')
}
</script>

<template>
  <VRow
    class="auth-wrapper bg-surface with_rectangles"
    no-gutters
  >
    <VCol
      cols="12"
      md="12"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <VRow class="pa-5 justify-center mb-3 __align-items-center">
            <VNodeRenderer
              :nodes="themeConfig.app.logo"
              class=""
            />

            <h3
              class="text-h3 ml-1"
              style="font-weight: 700;"
            >
              JotPhone
            </h3>
          </VRow>

          <h4
            class="text-h4 mb-3"
            style="font-weight: 600;"
          >
            Verify your phone?
          </h4>
          <p class="mb-2">
            Enter your mobile number with country code and we will send you a verification code. 
          </p>
        </VCardText>

        <VCardText class="pt-6">
          <VForm @submit.prevent="() => {}">
            <AppTextField
              v-model="phoneNumber"
              :rules="[requiredValidator, phoneValidator]"
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
                @click="cancelVerifyPhone"
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
    </VCol>
  </VRow>
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
</template>

<style lang="scss">
@use "resources/styles/@core/template/pages/page-auth";
</style>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Auth
  redirectIfEmailVerified: true
</route>
