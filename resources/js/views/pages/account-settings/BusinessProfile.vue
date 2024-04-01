<script setup>
import { useProfileStore } from "@/views/apps/business-profile/useProfileStore"
import { emailValidator, requiredValidator } from '@validators'
import { ref } from "vue"

const profileStore = useProfileStore()
const friendlyName = ref()
const registerBusiness = ref()
const firstName = ref()
const lastName = ref()
const businessName = ref()
const socialMediaProfileUrls = ref()
const websiteLink = ref()
const regionOfOperations = ref(null)
const companyStatus = ref()
const businessType = ref()
const businessRegistrationId = ref()
const businessIdentity = ref()
const businessIndustry = ref()
const businessRegistrationNumber = ref()
const jobPosition = ref()
const phoneNumber = ref()
const email = ref()
const businessTitle = ref()
const addressLine1 = ref()
const addressLine2 = ref()
const city = ref()
const regionState = ref()
const country = ref()
const zipcode = ref()
const isDisabled = ref(false)
const isLoading = ref(false)
const isError = ref(false)
const apiResponse = ref(false)
const responseMessage = ref('')
const form = ref()
const countries = ref([])

const busTypeOpt = [
  'Partnership', 
  'Limited Liability Corporation', 
  'Co-operative', 
  'Non-profit Corporation',
  'Corporation',
]


const onMountedFunction = async () => {
  profileStore.fetchCountries()
    .then(res => {
      countries.value = res.countries
    })
    .catch(error => {
      console.log(error)
    })
}

onMountedFunction()


const busIndustryOpt = [
  'AUTOMOTIVE',
  'AGRICULTURE',
  'BANKING',
  'CONSTRUCTION',
  'CONSUMER',
  'EDUCATION',
  'ENGINEERING',
  'ENERGY',
  'OIL_AND_GAS',
  'FAST_MOVING_CONSUMER_GOODS',
  'FINANCIAL',
  'FINTECH',
  'FOOD_AND_BEVERAGE',
  'GOVERNMENT',
  'HEALTHCARE',
  'HOSPITALITY',
  'INSURANCE',
  'LEGAL',
  'MANUFACTURING',
  'MEDIA',
  'ONLINE',
  'PROFESSIONAL_SERVICES',
  'RAW_MATERIALS',
  'REAL_ESTATE',
  'RELIGION',
  'RETAIL',
  'JEWELRY',
  'TECHNOLOGY',
  'TELECOMMUNICATIONS',
  'TRANSPORTATION',
  'TRAVEL',
  'ELECTRONICS',
  'NOT_FOR_PROFIT',
]

const busRegistrationIdOpt = [
  {
    title: 'USA: Employer Identification Number (EIN)',
    value: 'EIN',
  },
  {
    title: 'DUNS Number (Non-US Businesses Only)',
    value: 'DUNS',
  },
  {
    title: 'Canada: Canadian Business Number (CBN)',
    value: 'CBN',
  },
  {
    title: 'CN Great Britain: Company Number',
    value: 'CN',
  },
  {
    title: 'Australia: Company Number from ASIC (ACN)',
    value: 'ACN',
  },
  {
    title: 'CIN India: Corporate Identity Number',
    value: 'CIN',
  },
  {
    title: 'VAT Estonia: VAT Number',
    value: 'VAT',
  },
  {
    title: 'VATRN Romania: VAT Registration Number',
    value: 'VATRN',
  },
  {
    title: 'RN Israel: Registration Number',
    value: 'RN',
  },
  {
    title: 'Other',
    value: 'Other',
  },
]

const busIdentityOpt = [
  {
    title: 'Direct Customer',
    value: 'direct_customer',
  },
]

const regions = [
  { 
    label: 'Africa', 
    value: 'AFRICA', 
  },
  { 
    label: 'Asia', 
    value: 'ASIA', 
  },
  { 
    label: 'Europe',
    value: 'EUROPE', 
  },
  { 
    label: 'Latin America', 
    value: 'LATIN_AMERICA', 
  },
  { 
    label: 'USA And Canada', 
    value: 'USA_AND_CANADA', 
  },
]

const addProfile = () => {
  isDisabled.value = true
  isLoading.value = true 
  
  profileStore.addProfile({
    friendlyName: friendlyName.value,
    email: email.value,
    firstName: firstName.value,
    lastName: lastName.value,
    businessName: businessName.value,
    socialMediaProfileUrls: socialMediaProfileUrls.value,
    websiteLink: websiteLink.value,
    regionOfOperations: regionOfOperations.value,
    businessType: businessType.value,
    businessRegistrationIdentifer: businessRegistrationId.value,
    businessIdentity: businessIdentity.value,
    businessIndustry: businessIndustry.value,
    businessRegistrationNumber: businessRegistrationNumber.value,
    jobPosition: jobPosition.value,
    phoneNumber: phoneNumber.value,
    businessTitle: businessTitle.value,
    addressLine1: addressLine1.value,
    addressLine2: addressLine2.value,
    city: city.value,
    regionState: regionState.value,
    zipcode: zipcode.value,
    country: country.value,
    registerBusiness: registerBusiness.value,
    companyStatus: companyStatus.value,

  }).then(response => {
    responseMessage.value = response.data.message
    isError.value = false
    apiResponse.value = true
    
    isDisabled.value = false
    isLoading.value = false
    
    // Clear input fields
    form.value.reset()
  })
    .catch(error => {
      isDisabled.value = false
      isLoading.value = false
      let errorMsg = ''
      if (error.response && error.response.data && error.response.data.message) {
        errorMsg = error.response.data.message
      } else {
        errorMsg = error.message
      }
      apiResponse.value = true
      isError.value = true
      responseMessage.value = errorMsg
      form.value.validate().then(isValid => {
        if(isValid.valid === true) {
          form.value.resetValidation()
        }
      })
    })
}

const submitProfile = () => {
  form.value.validate().then(isValid => {
    if(isValid.valid === true) {
      form.value.resetValidation()
      addProfile()
    }
  })
}
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
          :color="isError ? 'error' : 'primary'"
          variant="tonal"
          closable
        >
          {{ responseMessage }}
        </VAlert>
      </div>
      <VCard>
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="form"
            lazy-validation
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
                <VCheckbox
                  v-model="registerBusiness"
                  value="stirShaken"
                  label="Register Business for Stir/Shaken"
                  :rules="[requiredValidator]"
                  required
                />
                <VCheckbox
                  v-model="registerBusiness"
                  value="tenDlc"
                  label="Register Business for 10DLC"
                  :rules="[requiredValidator]"
                  required
                />
                <VCheckbox
                  v-model="registerBusiness"
                  value="cnam"
                  label="Register Business for CNAM"
                  :rules="[requiredValidator]"
                  required
                />
                <VCheckbox
                  v-model="registerBusiness"
                  value="tollFreeNumbers"
                  label="Register Business for Toll-Free Numbers"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <VCol
                cols="12"
                md="12"
              >
                <h2>Organization Information</h2>
              </VCol>

              <!-- 👉 Friendly Name* -->
              <VCol
                md="6"
                cols="6"
              >
                <AppTextField
                  v-model="friendlyName"
                  label="Friendly Name*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Business Name* -->
              <VCol
                md="6"
                cols="6"
              >
                <AppTextField
                  v-model="businessName"
                  label="Business Name*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Address Line 1* -->
              <VCol
                cols="12"
                md="12"
              >
                <AppTextField 
                  v-model="addressLine1"
                  label="Address Line 1*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Address Line 2* -->
              <VCol
                cols="12"
                md="12"
              >
                <AppTextField 
                  v-model="addressLine2"
                  label="Address Line 2*"
                />
              </VCol>

              <!-- 👉 City* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField 
                  v-model="city"
                  label="City*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Region/State/Province* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField 
                  v-model="regionState"
                  label="Region/State/Province*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>
              
              <!-- 👉 Country* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppAutocomplete 
                  v-model="country"
                  label="Country*"
                  :items="countries"
                  :item-title="item => `${item.emoji} ${item.name}`"
                  item-value="code_2"
                  placeholder="Select your country"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Zip Code* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField 
                  v-model="zipcode"
                  label="Zip Code*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Business Type* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppAutocomplete
                  v-model="businessType"
                  label="Business Type*"
                  :items="busTypeOpt"
                  placeholder="Select your business type"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>
              
              <!-- 👉 Business Industry* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppAutocomplete
                  v-model="businessIndustry"
                  label="Business Industry*"
                  :items="busIndustryOpt"
                  placeholder="Select your business industry"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>
              
              <!-- 👉 Business Registration Identifier* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppAutocomplete
                  v-model="businessRegistrationId"
                  label="Business Registration Identifier*"
                  :items="busRegistrationIdOpt"
                  item-title="title"
                  item-value="value"
                  placeholder="Select your business registration identifier"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Business Registration Number* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppTextField
                  v-model="businessRegistrationNumber"
                  label="Business Registration Number*"
                  placeholder="Enter your business registration number"
                  :rules="[requiredValidator]"
                  required
                />
                <small>e.g.: US EIN: [xx-xxxxxxx] (NUMERICAL) US DUNS: [xx-xxx-xxxx] (NUMERICAL) CA CBN: [xxxxxxxxx] (NUMERICAL)</small>
              </VCol>

              <!-- 👉 Business Identity* -->
              <VCol
                cols="8"
                md="8"
              >
                <AppAutocomplete
                  v-model="businessIdentity"
                  label="Business Identity*"
                  :items="busIdentityOpt"
                  item-title="title"
                  item-value="value"
                  placeholder="Select your business identity"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Website Link* -->
              <VCol
                cols="12"
                md="12"
              >
                <AppTextField
                  v-model="websiteLink"
                  type="url"
                  label="Website Link*"
                  placeholder="https://www.acme.com"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Social Media Profile Url* -->
              <VCol
                cols="12"
                md="12"
              >
                <AppTextField
                  v-model="socialMediaProfileUrls"
                  label="Social Media Profile*"
                  placeholder="@acme_biz"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Checkbox Register Business -->
              <VCol
                md="12"
                cols="12"
              >
                <label>Region of Operations*</label>
              </VCol>
              <VCol
                v-for="region in regions"
                :key="region.value"
                md="2"
                cols="2"
              >
                <VCheckbox
                  v-model="regionOfOperations"
                  :label="region.label"
                  :value="region.value"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>
              
              <!-- 👉 Company Status* -->
              <VCol
                cols="5"
                md="5"
              >
                <AppAutocomplete
                  v-model="companyStatus"
                  label="Company Status*"
                  :items="['PRIVATE', 'PUBLIC']"
                  placeholder="Select Company Status"
                  :rules="[requiredValidator]"
                  required
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
                <AppTextField 
                  v-model="firstName"
                  label="First Name*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Last Name* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField
                  v-model="lastName"
                  label="Last Name*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Job Position* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppAutocomplete
                  v-model="jobPosition"
                  label="Job Position*"
                  :items="['Director','GM','VP','CEO','CFO','General Counsel','Other']"
                  placeholder="Job Position"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Email* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField
                  v-model="email"
                  label="Email*"
                  type="email"
                  :rules="[emailValidator, requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Phone Number* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField 
                  v-model="phoneNumber"
                  type="tel"
                  label="Phone Number*"
                  placeholder="+11234567890"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Business Title* -->
              <VCol
                cols="6"
                md="6"
              >
                <AppTextField 
                  v-model="businessTitle"
                  label="Business Title*"
                  :rules="[requiredValidator]"
                  required
                />
              </VCol>

              <!-- 👉 Form Actions -->
              <VCol
                cols="12"
                class="d-flex justify-center flex-wrap"
              >
                <VBtn
                  :disabled="isDisabled"
                  :loading="isLoading"
                  @click="submitProfile"
                >
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

