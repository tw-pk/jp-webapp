<script setup>
import User from "@/apis/user"
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import {
  confirmedValidator,
  passwordValidator,
  requiredValidator,
} from '@validators'
import { useAuthStore } from '../../store/auth'

const props = defineProps(['token'])

//auth store
const authStore = useAuthStore()

const errors = ref({
  newPassword: undefined,
  confirmPassword: undefined,
})

const newPassword = ref('')
const confirmPassword = ref('')
const isAlertVisible = ref(false)
const alertMessage = ref('')
const alertActionColor = ref(' ')
const isDisabled = ref(false)
const refVForm = ref()

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)
const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const route = useRoute()
const router = useRouter()

const resetPassword = () => {
  isDisabled.value = true
  User.resetPassword({
    password: newPassword.value,
    password_confirmation: confirmPassword.value,
    email: route.query.email,
    token: route.params.token,
  })
    .then(res => {
      isDisabled.value = false
      if(res.data.status){
        authStore.setVerificationMessage(res.data.message)
        router.push('/login')
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

const onSubmit = () => {
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
    no-gutters
    class="auth-wrapper bg-surface"
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
            Reset Password
          </h4>

          <p class="mb-0">
            for <span class="font-weight-bold">{{ route.query.email }}</span>
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
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="newPassword"
                  autofocus
                  label="New Password"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  :rules="[requiredValidator, passwordValidator]"
                  :error-messages="errors.newPassword"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <!-- Confirm Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="confirmPassword"
                  label="Confirm Password"
                  :rules="[requiredValidator, passwordValidator, confirmedValidator(confirmPassword, newPassword)]"
                  :error-messages="errors.confirmPassword"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <!-- Set password -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :disabled="isDisabled"
                  :loading="isDisabled"
                >
                  Set New Password
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
@use "@styles/@core/template/pages/page-auth";
</style>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Auth
</route>
