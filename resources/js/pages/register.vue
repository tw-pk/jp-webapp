<script setup>
import User from "@/apis/user"
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import AuthProvider from '@/views/pages/authentication/AuthProvider.vue'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2RegisterIllustrationBorderedDark from '@images/pages/auth-v2-register-illustration-bordered-dark.png'
import authV2RegisterIllustrationBorderedLight from '@images/pages/auth-v2-register-illustration-bordered-light.png'
import authV2RegisterIllustrationDark from '@images/pages/auth-v2-register-illustration-dark.png'
import authV2RegisterIllustrationLight from '@images/pages/auth-v2-register-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import {
  alphaDashValidator, confirmedValidator,
  emailValidator, passwordValidator,
  requiredValidator,
} from '@validators'
import { VForm } from 'vuetify/components/VForm'
import { useAuthStore } from '../store/auth'

const refVForm = ref()
const firstname = ref('')
const lastname = ref('')
const email = ref('')
const password = ref('')
const c_password = ref('')
const privacyPolicies = ref(true)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')

// Router
const route = useRoute()
const router = useRouter()

// Ability
const ability = useAppAbility()

// Form Errors
const errors = ref({
  firstname: undefined,
  email: undefined,
  password: undefined,
  c_password: undefined,
})

//auth store
const authStore = useAuthStore()

const register = () => {

  const formData = {
    firstname: firstname.value,
    lastname: lastname.value,
    email: email.value,
    password: password.value,
    c_password: password.value,
    privacyPolicies: privacyPolicies.value,
  }
  
  User.register(formData)
    .then(res => {
     
      // if(res.data.status){
      //   const id = res.data.lastInsertedId

      //   router.replace(route.query.to ? String(route.query.to) : `/verify-phone/${id}`)
      // }
      
      authStore.setVerificationMessage('A verification email has been sent to your email. Please login to verify your account.')
      router.replace(route.query.to ? String(route.query.to) : '/login')
      

      // Redirect to `to` query if exist or redirect to index route
      //isSnackbarVisible.value = true

      // snackbarActionColor.value = 'success'
      // snackbarMessage.value = 'A verification email has been sent to your email please login to verify your account.'

      //isSnackbarVisible.value = false
      
      // setTimeout(() => {
      //   snackbarActionColor.value = 'success'
      //   isSnackbarVisible.value = true
      //   snackbarMessage.value = 'Redirecting to login page....'
      //   isLoading.value = false
      //   router.replace(route.query.to ? String(route.query.to) : '/login')
      // }, 1200)
    })
    .catch(e => {
      isLoading.value = false
      if (e.response.status === 422) {
        snackbarActionColor.value = 'error'
        snackbarMessage.value = 'Unable to process your request, errors found!'
        isSnackbarVisible.value = true
        errors.value = e.response.data.errors
      }
    })

}

const imageVariant = useGenerateImageVariant(authV2RegisterIllustrationLight, authV2RegisterIllustrationDark, authV2RegisterIllustrationBorderedLight, authV2RegisterIllustrationBorderedDark, true)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)
const isPasswordVisible = ref(false)

const onSubmit = () => {
  event.preventDefault()
  isLoading.value = true
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid){
      register()
    }else{
      isLoading.value = false
    }
      
  })
}

onMounted(() => {
  if(route.query.invitation){
    User.verifyInvitationLink({
      invitationToken: route.query.invitation,
      email: route.query.email,
    }).then(response => {
      firstname.value = response.data.firstname
      lastname.value = response.data.lastname
      email.value = response.data.email
    })
  }
})
</script>

<template>
  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
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
            Create a new account
          </h4>
          <p class="mb-0">
            Make your app management easy and fun!
          </p>
        </VCardText>

        <VCardText>
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- First name -->
              <VCol cols="12">
                <AppTextField
                  v-model="firstname"
                  autofocus
                  :rules="[requiredValidator, alphaDashValidator]"
                  label="First name"
                  :error-messages="errors.firstname"
                />
              </VCol>

              <!-- Last name -->
              <VCol cols="12">
                <AppTextField
                  v-model="lastname"
                  :rules="[requiredValidator, alphaDashValidator]"
                  label="Last name"
                />
              </VCol>

              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                  label="Email"
                  type="email"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <div class="mb-2">
                  <AppTextField
                    v-model="password"
                    :rules="[requiredValidator, passwordValidator]"
                    :error-messages="errors.password"
                    label="Password"
                    :type="isPasswordVisible ? 'text' : 'password'"
                    :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                    @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  />
                </div>

                <div class="">
                  <AppTextField
                    v-model="c_password"
                    :rules="[requiredValidator, passwordValidator, confirmedValidator(c_password, password)]"
                    :error-messages="errors.c_password"
                    :type="isPasswordVisible ? 'text' : 'password'"
                    label="Confirm Password"
                  />
                </div>

                <div class="d-flex align-center mt-2 mb-4">
                  <VCheckbox
                    id="privacy-policy"
                    v-model="privacyPolicies"
                    :rules="[requiredValidator]"
                    inline
                  >
                    <template #label>
                      <span class="me-1">
                        I agree to
                        <a
                          href="javascript:void(0)"
                          class="text-primary"
                        >privacy policy & terms</a>
                      </span>
                    </template>
                  </VCheckbox>
                </div>

                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                >
                  Sign up
                </VBtn>
              </VCol>

              <!-- create account -->
              <VCol
                cols="12"
                class="text-center text-base"
              >
                <span>Already have an account?</span>
                <RouterLink
                  class="text-primary ms-2"
                  :to="{ name: 'login' }"
                >
                  Sign in instead
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
@use "@core-scss/template/pages/page-auth.scss";
</style>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Auth
  redirectIfLoggedIn: true
  guestOnly: true
</route>
