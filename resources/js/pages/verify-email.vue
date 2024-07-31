<script setup>
import User from "@/apis/user"
import AppOtpInput from "@core/components/app-form-elements/AppOtpInput.vue"
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-verify-email-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-verify-email-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { ref } from 'vue'
import { useTheme } from 'vuetify'


const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

const router = useRouter()
const route = useRoute()
const verificationStatus = ref('Verify My Account')
const user = ref(null)
const email = ref('')
const otp = ref('')
const defaultValue = ref('')
const vuetifyTheme = useTheme()
const currentThemeName = vuetifyTheme.name.value
const otp_timer = ref('')
const isResendDisabled = ref(false)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')

vuetifyTheme.themes.value[currentThemeName].colors.primary = '#38A6E3'

User.auth()
  .then(response => response.data)
  .then(userData => {
    user.value = userData
    email.value = userData.email
  })
  .catch(error => {
    isSnackbarVisible.value = true
    snackbarMessage.value = error
    snackbarActionColor.value = 'error'
  })

const resendEmail = () => {
  defaultValue.value = ''
  User.resendEmail().then(response => {
    isResendDisabled.value = true
    isSnackbarVisible.value = true
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = 'success'
    timer(180)
  })
    .catch(error => {
      isResendDisabled.value = true
      isSnackbarVisible.value = true
      snackbarMessage.value = error
      snackbarActionColor.value = 'error'
    })
}

const timerOn = ref(true)

function timer(remaining) {
  var m = Math.floor(remaining / 60)
  var s = remaining % 60

  m = m < 10 ? '0' + m : m
  s = s < 10 ? '0' + s : s
  otp_timer.value = m + ':' + s
  remaining -= 1

  if(remaining >= 0 && timerOn.value) {
    setTimeout(function() {
      timer(remaining)
    }, 1000)

    return
  }

  if(!timerOn.value) {
    // Do validate stuff here
    return
  }

  // Do timeout stuff here
  isResendDisabled.value = false

}

const verifyOtp = () => {
  isLoading.value = true
  verificationStatus.value = 'Verifying'
  User.verifyEmail({
    otp: otp.value,
  }).then(response => {
    if(response.data.status){
      isLoading.value = false
      isResendDisabled.value = true
      snackbarMessage.value = 'Email verified successfully, Redirecting...'
      snackbarActionColor.value = 'success'
      isSnackbarVisible.value = true
      router.push('/')
    }else{
      isLoading.value = false
      verificationStatus.value = 'Verify My Account'
      snackbarMessage.value = response.data.message
      snackbarActionColor.value = 'error'
      isSnackbarVisible.value = true
    }
  }).catch(error => {
    isLoading.value = false
    verificationStatus.value = 'Verify My Account'

    snackbarMessage.value = error?.response?.data?.message
    snackbarActionColor.value = 'error'
    isSnackbarVisible.value = true
  })
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
            Verify your email ✉️
          </h4>
          <p class="mb-2">
            Account activation code has been sent to your email {{ email }}. Please enter the code below
          </p>
        </VCardText>

        <VCardText>
          <VForm @submit.prevent="verifyOtp">
            <VRow>
              <!-- verify -->
              <VCol cols="12">
                <AppOtpInput
                  :default="defaultValue"
                  :total-input="6"
                  @update-otp="otp = $event"
                />
              </VCol>

              <!-- reset password -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :disabled="isLoading"
                  :loading="isLoading"
                >
                  {{ verificationStatus }}
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <div class="d-flex justify-center align-center flex-wrap">
                  <VRow
                    justify="center"
                    style="align-items: center;"
                  >
                    <span class="me-1 pa-0 ma-0">Didn't get the code?</span>
                    <VBtn
                      color="primary"
                      variant="plain"
                      class="pa-0 ma-0"
                      :disabled="isResendDisabled"
                      @click.prevent="resendEmail"
                    >
                      Resend
                    </VBtn>
                  </VRow>
                </div>
              </VCol>
              <VCol
                v-if="isResendDisabled"
                cols="12"
              >
                <VRow justify="center">
                  <span
                    v-if="isResendDisabled"
                    class="me-1"
                  >OTP will expire in {{ otp_timer }}</span>
                </VRow>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
  <!-- Snackbar -->
  <VSnackbar v-model="isSnackbarVisible">
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
