<script setup>
import User from "@/apis/user"
import axiosIns from "@axios"
import avatar from '@images/avatars/avatar-0.png'
import { defineEmits, onMounted, ref } from "vue"


const emits = defineEmits(['update:isDialogVisible', 'confirmed'])

console.log('deactivateAccount123')
console.log(emits)

const confirmAction = () => {
  console.log('deactivateAccount12355555')
  emits('confirmed')
}


const handleConfirmation = () => {
  console.log('Account deactivation confirmed')

  // Perform further actions here, such as calling the deactivateAccount function
  deactivateAccount()
}

const deactivateAccount = async () => {
  console.log('deactivateAccount')
  try {
    console.log('deactivateAccount')

    // await axios.post('/api/deactivate-account');
    // this.isAccountDeactivated = true;
  } catch (error) {
    console.error('Error deactivating account:', error)
  }
}

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
        v-if="!apiResponse"
        class="my-3"
      >
        <VAlert
          density="compact"
          :color="isError ? 'error' : 'info'"
          variant="tonal"
          closable
        >
          {{ responseMessage }}
          Your registration form will be successfully saved, or your data will be prefilled and you can access the form by going to setting & Business profile.
        </VAlert>
      </div>
      <VCard>
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="formRef"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol
                cols="12"
                md="12"
              >
                <h2>Business Profile</h2>
              </VCol>
         
              <VCol
                cols="12"
                md="12"
              >
                <VDivider />
              </VCol>
              <VCol
                cols="12"
                md="12"
              >
                <h4>Your Business details will be used for registration with our upstream provider so that you can continue smooth calling and messaging experience.</h4>  
              </VCol>
              <!-- 👉 Checkbox Register Business -->
              <VCol
                md="12"
                cols="12"
              >
                <VCheckbox label="Register Business for Stir/Shaken" />
                <VCheckbox label="Register Business for 10DLC" />
                <VCheckbox label="Register Business for CNAM" />
                <VCheckbox label="Register Business for Toll-Free Numbers" />
              </VCol>

              <VCol
                cols="12"
                md="12"
              >
                <h2>Organization Information</h2>
              </VCol>


              <!-- 👉 Business Name* -->
              <VCol
                md="6"
                cols="6"
              >
                <AppTextField label="Business Name*" />
              </VCol>

              <!-- 👉 Address Line 1* -->
              <VCol
                cols="12"
                md="12"
              >
                <AppTextField label="Address Line 1*" />
              </VCol>

              <!-- 👉 Address Line 2* -->
              <VCol
                cols="12"
                md="12"
              >
                <AppTextField label="Address Line 2*" />
              </VCol>

              <!-- 👉 City* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField label="City*" />
              </VCol>

              <!-- 👉 Region/State/Province* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField label="Region/State/Province*" />
              </VCol>
              
              <!-- 👉 Country* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField label="Country*" />
              </VCol>

              <!-- 👉 Zip Code* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField label="Zip Code*" />
              </VCol>

              <!-- 👉 Business Type* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppAutocomplete
                  label="Business Type*"
                  :items="['test', 'test2']"
                  placeholder="Select Your Business Type"
                />
              </VCol>
              
              <!-- 👉 Business Industry* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppAutocomplete
                  label="Business Industry*"
                  :items="['test', 'test2']"
                  placeholder="Select Your Business Industry"
                />
              </VCol>
              
              <!-- 👉 Business Registration ID Type* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppAutocomplete
                  label="Business Registration ID Type*"
                  :items="['test', 'test2']"
                  placeholder="Select Your Business Registration ID Type"
                />
              </VCol>

              <!-- 👉 Business Registration Number* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppAutocomplete
                  label="Business Registration Number*"
                  :items="['test', 'test2']"
                  placeholder="Select Your Business Registration Number"
                />
              </VCol>

              <!-- 👉 Website Link* -->
              <VCol
                cols="12"
                md="12"
              >
                <AppTextField label="Website Link*" />
              </VCol>

              <!-- 👉 Checkbox Register Business -->
              <VCol
                md="12"
                cols="12"
              >
                <label>Region of Operations*</label>
              </VCol>
              <VCol
                md="2"
                cols="2"
              >
                <VCheckbox label="America" />
              </VCol>
              <VCol
                md="2"
                cols="2"
              >
                <VCheckbox label="Asia" />
              </VCol>
              <VCol
                md="2"
                cols="2"
              >
                <VCheckbox label="Europe" />
              </VCol>
              <VCol
                md="2"
                cols="2"
              >
                <VCheckbox label="Latin America" />
              </VCol>
              <VCol
                md="2"
                cols="2"
              >
                <VCheckbox label="USA & Canada" />
              </VCol>

              <!-- 👉 Company Status* -->
              <VCol
                cols="5"
                md="5"
              >
                <AppAutocomplete
                  label="Company Status*"
                  :items="['test', 'test2']"
                  placeholder="Select Company Status"
                />
              </VCol>

              <VCol
                cols="12"
                md="12"
              >
                <h2>Contact Information</h2>
              </VCol>
              <VCol
                cols="12"
                md="12"
              >
                <VDivider />
              </VCol>

              <!-- 👉 First Name* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField label="First Name*" />
              </VCol>

              <!-- 👉 Last Name* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField label="Last Name*" />
              </VCol>

              <!-- 👉 Job Position* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppAutocomplete
                  label="Job Position*"
                  :items="['test', 'test2']"
                  placeholder="Job Position"
                />
              </VCol>

              <!-- 👉 Email* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField
                  label="Email*"
                  type="email"
                />
              </VCol>

              <!-- 👉 Calling Code* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppAutocomplete
                  label="Calling Code*"
                  :items="['test', 'test2']"
                  placeholder="SELECT"
                />
              </VCol>

              <!-- 👉 Phone Number* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField label="Phone Number*" />
              </VCol>

              <!-- 👉 Business Title* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField label="Business Title*" />
              </VCol>

              <!-- 👉 Form Actions -->
              <VCol
                cols="12"
                class="d-flex justify-center flex-wrap"
              >
                <VBtn type="submit">
                  Submit
                </VBtn>
              </VCol>
              <VCol cols="12" />
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

