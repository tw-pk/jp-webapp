<script setup>
import User from "@/apis/user"
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import {
  emailValidator,
  requiredValidator,
} from '@validators'

const refVForm = ref()
const email = ref('')
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)
const isAlertVisible = ref(false)
const alertMessage = ref('')
const alertActionColor = ref(' ')
const isDisabled = ref(false)

const errors = ref({
  email: undefined,
})

const resetPassword = () => {
  isDisabled.value = true
  User.sendForgotPasswordMail({
    email: email.value,
  }).then(res => {
    email.value = ''
    isDisabled.value = false
    if(res.data.status){
      alertMessage.value = 'Success! ' + res.data.message
      alertActionColor.value = 'success'
      isAlertVisible.value = true
      refVForm.value.reset()
    }else{
      alertMessage.value = 'Error! ' + res.data.message
      alertActionColor.value = 'error'
      isAlertVisible.value = true
    }
  })
    .catch(res => {
      isDisabled.value = false
      alertMessage.value = 'Error! ' + res?.response?.data?.message
      alertActionColor.value = 'error'
      isAlertVisible.value = true
    })
}

const submitResetPassword = () => {
  isDisabled.value = true
  event.preventDefault()
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid){
      resetPassword()
    }else{
      isDisabled.value = false
    }
  })
}
</script>

<template>
  <VRow
    class="auth-wrapper bg-surface"
    no-gutters
  >
    <VCol
      cols="12"
      lg="12"
      class="d-flex align-center justify-center"
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
            Forgot Password?
          </h4>
          <p class="mb-0">
            Enter your email, and we'll send you instructions to reset your password
          </p>
        </VCardText>
        
        <VCardText>
          <VAlert
            v-if="isAlertVisible"
            border="start"
            :color="alertActionColor"
            variant="tonal"
          >
            {{ alertMessage }}
          </VAlert>
          <VForm
            ref="refVForm"
            @submit.prevent="submitResetPassword"
          >
            <VRow>
              <!-- verify -->
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  autofocus
                  label="Email"
                  type="email"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- Reset link -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :disabled="isDisabled"
                  :loading="isDisabled"
                >
                  Send Reset Link
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <RouterLink
                  class="d-flex align-center justify-center"
                  :to="{ name: 'login' }"
                >
                  <VIcon
                    icon="tabler-chevron-left"
                    class="flip-in-rtl"
                  />
                  <span>Back to login</span>
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Auth
</route>
