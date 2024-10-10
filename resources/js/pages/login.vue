<script setup>
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import AuthProvider from '@/views/pages/authentication/AuthProvider.vue'
import axios from '@axios'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import {
  emailValidator,
  requiredValidator,
} from '@validators'
import { ref } from 'vue'
import { useTheme } from 'vuetify'
import { VForm } from 'vuetify/components/VForm'
import { useAuthStore } from '../store/auth'

const authThemeImg = useGenerateImageVariant(authV2LoginIllustrationLight, authV2LoginIllustrationDark, authV2LoginIllustrationBorderedLight, authV2LoginIllustrationBorderedDark, true)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)
const isPasswordVisible = ref(false)
const route = useRoute()
const router = useRouter()
const ability = useAppAbility()

const errors = ref({
  email: undefined,
  password: undefined,
})

const refVForm = ref()
const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const isDisabled = ref(false)

const vuetifyTheme = useTheme()
const currentThemeName = vuetifyTheme.name.value

vuetifyTheme.themes.value[currentThemeName].colors.primary = '#38A6E3'

const authStore = useAuthStore()

const verificationMessage = ref(authStore.verificationMessage)

const login = () => {
  axios.post('/api/auth/login', {
    email: email.value,
    password: password.value,
  }).then( response => {
      
    const { accessToken, userData, userAbilities } = response.data
    
    console.log('userAbilities userAbilities userAbilities')
    console.log(userAbilities)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))
    ability.update(userAbilities)
    localStorage.setItem('userData', JSON.stringify(userData))
    localStorage.setItem('accessToken', JSON.stringify(accessToken))
    
    isDisabled.value = false
    router.replace(route.query.to ? String(route.query.to) : '/dashboard')
  }).catch(e => {
    isDisabled.value = false
    errors.value = e.response.data
  })
}

const onSubmit = () => {
  isDisabled.value = true
  event.preventDefault()
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid){
      login()
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
    <VImg
      src="./images/assets/shape.png"
      class="top_shape"
    />

    <VImg
      src="./images/assets/bottom-shape.png"
      class="bottom_shape"
    />

    <VCol
      cols="12"
      lg="12"
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
              {{ themeConfig.app.title }}
            </h3>
          </VRow>

          <h4
            class="text-h4 mb-3"
            style="font-weight: 600;"
          >
            Welcome to <span class="text-capitalize"> {{ themeConfig.app.title }} </span>!
          </h4>
          <p class="mb-0">
            Please sign-in to your account and start the adventure
          </p>
        </VCardText>
        <VAlert
          v-if="verificationMessage"
          border="start"
          color="success"
          variant="tonal"
        >
          Success! {{ verificationMessage }}
        </VAlert>
        <VCardText>
          <VForm
            ref="refVForm"
            class="__login_form"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- verify -->
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  label="Email"
                  type="email"
                  autofocus
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="password"
                  label="Password"
                  :rules="[requiredValidator]"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />

                <div class="d-flex align-center flex-wrap justify-space-between mt-2 mb-4">
                  <VCheckbox
                    v-model="rememberMe"
                    label="Remember me"
                  />
                  <RouterLink
                    class="text-primary ms-2 mb-1"
                    :to="{ name: 'forgot-password' }"
                  >
                    Forgot Password?
                  </RouterLink>
                </div>

                <VBtn
                  block
                  type="submit"
                  :disabled="isDisabled"
                  :loading="isDisabled"
                >
                  Login
                </VBtn>
              </VCol>

              <!-- create account -->
              <VCol
                cols="12"
                class="text-center"
              >
                <span>New on our platform?</span>
                <RouterLink
                  class="text-primary ms-2"
                  :to="{ name: 'register' }"
                >
                  Create an account
                </RouterLink>
              </VCol>
              <VCol
                cols="12"
                class="d-flex align-center"
              >
                <VDivider />
                <span class="mx-4">or</span>
                <VDivider />
              </VCol>

              <!-- auth providers -->
              <VCol
                cols="12"
                class="text-center"
              >
                <AuthProvider />
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
  redirectIfLoggedIn: true
  title: JotPhone - Login
</route>
