<script setup>
import User from "@/apis/user"
import axiosIns from "@axios"
import avatar from '@images/avatars/avatar-0.png'
import { onMounted, ref } from "vue"

const accountData = {
  avatar: avatar,
  avatarObject: [],
  firstName: '',
  lastName: '',
  email: '',
  organization: '',
  phoneNumber: '',
  address: '',
  state: '',
  zipcode: '',
  country: '',
  timezone: '',
  bio: '',
}

const refInputEl = ref(null)
const isConfirmDialogOpen = ref(false)
const isAccountDeactivated = ref(false)
const deactivated = ref(false)
const validateAccountDeactivation = [v => !!v || 'Please confirm account deactivation']

const accountDataLocal = ref(structuredClone(accountData))

const timezones = [
  '(GMT-11:00) International Date Line West',
  '(GMT-11:00) Midway Island',
  '(GMT-10:00) Hawaii',
  '(GMT-09:00) Alaska',
  '(GMT-08:00) Pacific Time (US & Canada)',
  '(GMT-08:00) Tijuana',
  '(GMT-07:00) Arizona',
  '(GMT-07:00) Chihuahua',
  '(GMT-07:00) La Paz',
  '(GMT-07:00) Mazatlan',
  '(GMT-07:00) Mountain Time (US & Canada)',
  '(GMT-06:00) Central America',
  '(GMT-06:00) Central Time (US & Canada)',
  '(GMT-06:00) Guadalajara',
  '(GMT-06:00) Mexico City',
  '(GMT-06:00) Monterrey',
  '(GMT-06:00) Saskatchewan',
  '(GMT-05:00) Bogota',
  '(GMT-05:00) Eastern Time (US & Canada)',
  '(GMT-05:00) Indiana (East)',
  '(GMT-05:00) Lima',
  '(GMT-05:00) Quito',
  '(GMT-04:00) Atlantic Time (Canada)',
  '(GMT-04:00) Caracas',
  '(GMT-04:00) La Paz',
  '(GMT-04:00) Santiago',
  '(GMT-03:30) Newfoundland',
  '(GMT-03:00) Brasilia',
  '(GMT-03:00) Buenos Aires',
  '(GMT-03:00) Georgetown',
  '(GMT-03:00) Greenland',
  '(GMT-02:00) Mid-Atlantic',
  '(GMT-01:00) Azores',
  '(GMT-01:00) Cape Verde Is.',
  '(GMT+00:00) Casablanca',
  '(GMT+00:00) Dublin',
  '(GMT+00:00) Edinburgh',
  '(GMT+00:00) Lisbon',
  '(GMT+00:00) London',
  '(GMT+05:00) Islamabad, Karachi',
]

const currencies = [
  'USD',
  'EUR',
  'GBP',
  'AUD',
  'BRL',
  'CAD',
  'CNY',
  'CZK',
  'DKK',
  'HKD',
  'HUF',
  'INR',
]

const phoneNumberElRef = ref(null)
const isError = ref(false)
const apiResponse = ref(false)
const responseMessage = ref('')
const emailDisabled = ref(true)
const formRef = ref(null)
const countries = ref([])

const resetForm = () => {
  accountDataLocal.value = structuredClone(accountData)
}

const handleConfirmation = async action => {
  if(action===true){
    try {
      const accountResponse = await User.accountDeactivate()
      if(accountResponse.data.status){
        deactivated.value = true
        isAccountDeactivated.value = false
      }
    } catch (error) {
      console.error('Error deactivating account:', error)
    }
  }
}

const onSubmit = () => {
  let formData = new FormData()
  formData.append("firstName", accountDataLocal.value.firstName)
  formData.append("lastName", accountDataLocal.value.lastName)
  formData.append("phoneNumber", accountDataLocal.value.phoneNumber)
  formData.append("address", accountDataLocal.value.address)
  formData.append("state", accountDataLocal.value.state)
  formData.append("country", accountDataLocal.value.country)
  formData.append("timezone", accountDataLocal.value.timezone)
  formData.append("organization", accountDataLocal.value.organization)
  formData.append("zipcode", accountDataLocal.value.zipcode)
  formData.append("bio", accountDataLocal.value.bio)

  if(accountDataLocal.value.avatar !== null || accountDataLocal.value.avatar !== ""){
    formData.append("avatar", accountDataLocal.value.avatarObject)
  }
  axiosIns.post('/api/auth/user/profile/update', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
    .then(res => {
      if(res.data.status){
        isError.value = false
        apiResponse.value = true
        responseMessage.value = res.data.message
      }
    })
    .catch(err => {
      apiResponse.value = true
      isError.value = true
      responseMessage.value = err.response.data.message
    })
}

const changeAvatar = file => {
  const fileReader = new FileReader()
  const { files } = file.target
  if (files && files.length) {
    fileReader.readAsDataURL(files[0])
    fileReader.onload = () => {
      if (typeof fileReader.result === 'string'){
        accountDataLocal.value.avatar = fileReader.result
        accountDataLocal.value.avatarObject = files[0]
      }
    }
  }
}

// reset avatar image
const resetAvatar = () => {
  accountDataLocal.value.avatar = accountData.avatar
  accountDataLocal.value.avatarObject = accountData.avatar
}


onMounted(async() => {
  const userData = await User.profileData()
  
  accountData.firstName = userData.data.firstName
  accountData.lastName = userData.data.lastName
  accountData.email = userData.data.email
  accountData.organization = userData.data.organization
  accountData.phoneNumber = userData.data.phoneNumber
  accountData.address = userData.data.address
  accountData.state = userData.data.state
  accountData.zipcode = userData.data.zipcode
  accountData.country = userData.data.country
  accountData.timezone = userData.data.timezone
  accountData.avatar = userData.data.avatar
  accountData.bio = userData.data.bio
  accountDataLocal.value = structuredClone(accountData)

  const countryList = await User.profileCountries()

  countries.value = countryList.data.countries
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <div
        v-if="apiResponse"
        class="my-3"
      >
        <VAlert
          density="compact"
          :color="isError ? 'error' : 'success'"
          variant="tonal"
          closable
        >
          {{ responseMessage }}
        </VAlert>
      </div>
      <VCard title="Profile Details">
        <VCardText class="d-flex">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            size="100"
            class="me-6"
            :image=" accountDataLocal ? ( accountDataLocal.avatar ?? avatar ) : avatar"
          />

          <!-- 👉 Upload Photo -->
          <form class="d-flex flex-column justify-center gap-4">
            <div class="d-flex flex-wrap gap-2">
              <VBtn
                color="primary"
                @click="refInputEl?.click()"
              >
                <VIcon
                  icon="tabler-cloud-upload"
                  class="d-sm-none"
                />
                <span class="d-none d-sm-block">Upload new photo</span>
              </VBtn>

              <input
                ref="refInputEl"
                type="file"
                name="file"
                accept=".jpeg,.png,.jpg"
                hidden
                @input="changeAvatar"
              >

              <VBtn
                type="reset"
                color="secondary"
                variant="tonal"
                @click="resetAvatar"
              >
                <span class="d-none d-sm-block">Reset</span>
                <VIcon
                  icon="tabler-refresh"
                  class="d-sm-none"
                />
              </VBtn>
            </div>

            <p class="text-body-1 mb-0">
              Allowed JPG, GIF or PNG. Max size of 800K
            </p>
          </form>
        </VCardText>

        <VDivider />

        <VCardText class="pt-2">
          <!-- 👉 Form -->
          <VForm
            ref="formRef"
            class="mt-6"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- 👉 First Name -->
              <VCol
                md="6"
                cols="12"
              >
                <AppTextField
                  v-model="accountDataLocal.firstName"
                  label="First Name"
                />
              </VCol>

              <!-- 👉 Last Name -->
              <VCol
                md="6"
                cols="12"
              >
                <AppTextField
                  v-model="accountDataLocal.lastName"
                  label="Last Name"
                />
              </VCol>

              <!-- 👉 Email -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.email"
                  label="E-mail"
                  type="email"
                  :disabled="emailDisabled"
                />
              </VCol>

              <!-- 👉 Bio -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextarea
                  v-model="accountDataLocal.bio"
                  label="Bio"
                  auto-grow
                />
              </VCol>

              <!-- 👉 Organization -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.organization"
                  label="Organization"
                />
              </VCol>

              <!-- 👉 Phone -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  id="phoneNumberEl"
                  ref="phoneNumberElRef"
                  v-model="accountDataLocal.phoneNumber"
                  label="Phone Number"
                />
              </VCol>

              <!-- 👉 Address -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.address"
                  label="Address"
                />
              </VCol>

              <!-- 👉 State -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.state"
                  label="State"
                />
              </VCol>

              <!-- 👉 Zip Code -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.zipcode"
                  label="Zip Code"
                />
              </VCol>

              <!-- 👉 Country -->
              <VCol
                cols="12"
                md="6"
              >
                <AppAutocomplete
                  v-model="accountDataLocal.country"
                  label="Country"
                  :items="countries"
                  :item-title="item => `${item.emoji} ${item.name}`"
                  item-value="name"
                />
              </VCol>

              <!-- 👉 Language -->
              <!--              <VCol -->
              <!--                cols="12" -->
              <!--                md="6" -->
              <!--              > -->
              <!--                <AppSelect -->
              <!--                  v-model="accountDataLocal.language" -->
              <!--                  label="Language" -->
              <!--                  :items="['English', 'Spanish', 'Arabic', 'Hindi', 'Urdu']" -->
              <!--                /> -->
              <!--              </VCol> -->

              <!-- 👉 Timezone -->
              <VCol
                cols="12"
                md="6"
              >
                <AppSelect
                  v-model="accountDataLocal.timezone"
                  label="Timezone"
                  :items="timezones"
                  :menu-props="{ maxHeight: 200 }"
                />
              </VCol>

              <!-- 👉 Currency -->
              <!--              <VCol -->
              <!--                cols="12" -->
              <!--                md="6" -->
              <!--              > -->
              <!--                <AppSelect -->
              <!--                  v-model="accountDataLocal.currency" -->
              <!--                  label="Currency" -->
              <!--                  :items="currencies" -->
              <!--                  :menu-props="{ maxHeight: 200 }" -->
              <!--                /> -->
              <!--              </VCol> -->

              <!-- 👉 Form Actions -->
              <VCol
                cols="12"
                class="d-flex flex-wrap gap-4"
              >
                <VBtn type="submit">
                  Save changes
                </VBtn>

                <VBtn
                  color="secondary"
                  variant="tonal"
                  type="reset"
                  @click.prevent="resetForm"
                >
                  Reset
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <!-- 👉 Delete Account -->
      <VCard title="Delete Account">
        <VCardText>
          <VAlert
            v-if="deactivated"
            type="error"
          >
            Your account has been deactivated successfully.
          </VAlert>
        </VCardText>
        <VCardText>
          <!-- 👉 Checkbox and Button  -->
          <div>
            <VCheckbox
              v-model="isAccountDeactivated"
              :disabled="deactivated"
              :rules="validateAccountDeactivation"
              label="I confirm my account deactivation"
            />
          </div>

          <VBtn
            :disabled="!isAccountDeactivated"
            color="error"
            class="mt-3"
            @click="isConfirmDialogOpen = true"
          >
            Deactivate Account
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- Confirm Dialog -->
  <ConfirmDialog
    v-model:isDialogVisible="isConfirmDialogOpen"
    confirmation-question="Are you sure you want to deactivate your account?"
    confirm-title="Deactivated!"
    confirm-msg="Your account has been deactivated successfully."
    cancel-title="Cancelled"
    cancel-msg="Account Deactivation Cancelled!"
    @confirm="handleConfirmation"
  />
</template>

