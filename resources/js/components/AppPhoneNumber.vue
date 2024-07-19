<script setup>
import { paginationMeta } from "@/@fake-db/utils"
import { useCountriesStore } from "@/views/apps/fetch-countries/useCountriesStore"
import { useNumberSelectionStore } from "@/views/apps/number-selection/useNumberSelectionStore"
import { isEmpty } from "@core/utils"
import { onMounted, ref } from 'vue'
import { useTheme } from 'vuetify'
import { VDataTable } from 'vuetify/labs/VDataTable'

const vuetifyTheme = useTheme()
const currentThemeName = vuetifyTheme.name.value

vuetifyTheme.themes.value[currentThemeName].colors.primary = '#38A6E3'

// Router
const route = useRoute()
const router = useRouter()

const isProcessing = ref(false)

const countries = ref([
  {
    country: '',
    country_code: '',
    flag_url: '',
  },
])

const country_value = ref(null)
const selectCountryName = ref('')
const selectCountryFlag = ref('')
const isProcessingCountry = ref(true)

countries.value.isDisabled = true

const CountriesStore = useCountriesStore()
const numberSelectionStore = useNumberSelectionStore()

onMounted(async () => {
  try {
    const response = await CountriesStore.fetchTwilioCountryList()

    countries.value = response.twilioCountry

  } catch (error) {
    console.error(error)
  } finally {
    isProcessingCountry.value = false
    countries.value.isDisabled = false
  }
  if (countries.value.length > 0) {
    country_value.value = countries.value[0].country_code
    
  }
})

const type = [
  {
    name: 'Number',
    key: 'local',
  },
]

const matchNumber = ref('Anywhere')
const address_requirements = ref('Any')
const types = ref('All')
const type_value = ref(type[0].name)
const searchNumber = ref('')
const callCheckbox = ref(false)
const smsCheckbox = ref(false)
const mmsCheckbox = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('')

const rules = [v => v.length <= 12 || 'Max 12 characters']
const userData = localStorage.getItem('userData')

const projectTableHeaders = [
  {
    title: 'Numbers',
    key: 'numbers',
  },
  {
    title: 'Type',
    key: 'type',
  },
  {
    title: 'Capabilities',
    key: 'capabilities',
  },
  {
    title: 'Address Req.',
    key: 'addressRequirements',
  },
  {
    title: 'PICK A NUMBER',
    key: 'button',
  },
]

const search = ref('')

const options = ref({
  itemsPerPage: 12,
  page: 1,
})

const numbers = ref([])

const resolveUserProgressVariant = progress => {
  if (progress <= 25)
    return 'error'
  if (progress > 25 && progress <= 50)
    return 'warning'
  if (progress > 50 && progress <= 75)
    return 'primary'
  if (progress > 75 && progress <= 100)
    return 'success'

  return 'secondary'
}


const addNumber = item => {
  item.raw.isLoading = true
  item.raw.isDisabled = true

  numberSelectionStore.purchaseNumber({
    phoneNumber: item.raw.numbers,
    state: item.raw.region,
    city: item.raw.city,
  }).then(response => {
    // Handle the successful purchase response
    const number = response.data.number
    if(number){
      snackbarMessage.value = response.data.message

      //snackbarColor.value = `success`
      isSnackbarVisible.value = true
      item.raw.isLoading = false
      item.raw.isDisabled = true
    }else{
      snackbarMessage.value = response.data.message

      //snackbarColor.value = `primary`
      isSnackbarVisible.value = true
      item.raw.isLoading = false
      item.raw.isDisabled = false
    }

    //router.push("/")
  })
    .catch(error => {
      item.raw.isLoading = false
      item.raw.isDisabled = false
      snackbarMessage.value = error.data.message
      snackbarColor.value = `error`
      isSnackbarVisible.value = true
    })
}

const getButtonTitle = item => {
  return item.raw.isDisabled ? 'Purchased' : 'Add Number'
}

watch(searchNumber,  (newValue, oldValue) => {
  if(!isEmpty(newValue)){
    if (newValue.length > 3) {
      isProcessing.value = true
      numbers.value = ''
      numberSelectionStore.fetchNumbers({
        searchNumber: newValue,
        country: country_value.value,
        address_requirements: address_requirements.value,
        type: types.value,
      }).then(response => {
        callCheckbox.value = true
        smsCheckbox.value =  true
        mmsCheckbox.value =  true
        numbers.value = response.data.numbers
        isProcessing.value = false
      }).catch(error => {
        isProcessing.value = false
      })
        
    }
  }
})

watch(matchNumber,  (newValue, oldValue) => {
  isProcessing.value = true
  numbers.value = ''
  numberSelectionStore.fetchNumbers({
    matchNumber: newValue,
    searchNumber: searchNumber.value,
    country: country_value.value,
    address_requirements: address_requirements.value,
    type: types.value,
  }).then(response => {
    callCheckbox.value = true
    smsCheckbox.value =  true
    mmsCheckbox.value =  true
    numbers.value = response.data.numbers
    isProcessing.value = false
  }).catch(error => {
    isProcessing.value = false
  })
    
})

watch(country_value,  (newValue, oldValue) => {
  isProcessing.value = true
  numbers.value = ''
 
  const selectedCountry = countries.value.find(country => country.country_code === newValue)
  if (selectedCountry) {
    selectCountryName.value = selectedCountry.country
    selectCountryFlag.value = selectedCountry.flag_url
  }
  
    
  numberSelectionStore.fetchNumbers({
    country: newValue,
    matchNumber: matchNumber.value,
    searchNumber: searchNumber.value,
    address_requirements: address_requirements.value,
    type: types.value,
  }).then(response => {
    callCheckbox.value = true
    smsCheckbox.value =  true
    mmsCheckbox.value =  true
    numbers.value = response.data.numbers
    isProcessing.value = false
  }).catch(error => {
    isProcessing.value = false
  })
    
})

watch(types,  (newValue, oldValue) => {
  isProcessing.value = true
  numbers.value = ''
  numberSelectionStore.fetchNumbers({
    type: newValue,
    matchNumber: matchNumber.value,
    searchNumber: searchNumber.value,
    country: country_value.value,
    address_requirements: address_requirements.value,
  }).then(response => {
    callCheckbox.value = true
    smsCheckbox.value =  true
    mmsCheckbox.value =  true
    numbers.value = response.data.numbers
    isProcessing.value = false
  }).catch(error => {
    isProcessing.value = false
  })
})

watch(address_requirements,  (newValue, oldValue) => {
  isProcessing.value = true
  numbers.value = ''
  numberSelectionStore.fetchNumbers({
    address_requirements: newValue,
    matchNumber: matchNumber.value,
    searchNumber: searchNumber.value,
    country: country_value.value,
    type: types.value,
  }).then(response => {
    callCheckbox.value = true
    smsCheckbox.value =  true
    mmsCheckbox.value =  true
    numbers.value = response.data.numbers
    isProcessing.value = false
  }).catch(error => {
    isProcessing.value = false
  })
    
})
</script>

<template>
  <div class="">
    <VAlert
      type="info"
      border="start"
      color="primary"
      variant="tonal"
      closable
      class="mb-3"
    >
      First! select the country and then search for the number of the country you want to add.
    </VAlert>
    <VRow class="match-height">
      <VCol
        cols="12"
        md="4"
        lg="4"
      >
        <VRow class="match-height">
          <VCol
            cols="12"
            class="match-height"
          >
            <VCard
              class="auth-card-v2 match-height"
              title="Filter"
            >
              <VRow class="pa-5">
                <VCol
                  cols="12"
                  md="6"
                  lg="6"
                >
                  <AppAutocomplete
                    v-model="country_value"
                    :items="countries"
                    item-title="country"
                    item-value="country_code"
                    :disabled="isProcessingCountry"
                    :loading="countries.isDisabled"
                  >
                    <template #selection="{ props, item }">
                      <VChip
                        v-bind="props"
                        :prepend-avatar="item.raw.flag_url"
                        :text="item.raw.country"
                        closable
                      />
                    </template>

                    <template #item="{ props, item }">
                      <VListItem
                        v-bind="props"
                        :prepend-avatar="item.raw.flag_url"
                        :title="item?.raw?.country"
                        class="small-avatar-item"
                      />
                    </template>
                  </AppAutocomplete>
                </VCol>

                <VCol
                  cols="12"
                  md="6"
                  lg="6"
                >
                  <AppAutocomplete
                    v-model="type_value"
                    :items="type"
                    item-title="name"
                    item-value="name"
                  />
                </VCol>

                <VCol
                  cols="12"
                  md="12s"
                  lg="12"
                >
                  <AppTextField
                    v-model="searchNumber"
                    :rules="rules"
                    clearable
                    placeholder="Search Number"
                  />
                </VCol>
              </VRow>
              <VDivider />
              <VRow class="pt-2 pl-2">
                <VCardText class="mb-0 pl-7 pr-7">
                  <h6 class="text-h6 font-weight-bold">
                    Match to Number
                    <VIcon
                      small
                      class="ml-2 icon-border-blue"
                      color="primary"
                    >
                      mdi-information-outline
                    </VIcon>
                  </h6>
                </VCardText>
              </VRow>
              <VCol
                cols="12"
                class="pt-3"
              >
                <VRow class="pl-2">
                  <VRadioGroup v-model="matchNumber">
                    <VRow class="pa-3">
                      <VCol cols="5">
                        <VRadio value="Anywhere">
                          <template #label>
                            <div>
                              Anywhere
                            </div>
                          </template>
                        </VRadio>
                      </VCol>

                      <VCol cols="5">
                        <VRadio value="StartsWith">
                          <template #label>
                            <div>
                              First Part
                            </div>
                          </template>
                        </VRadio>
                      </VCol>

                      <VCol cols="5">
                        <VRadio value="EndsWith">
                          <template #label>
                            <div>
                              Last Part
                            </div>
                          </template>
                        </VRadio>
                      </VCol>
                    </VRow>
                  </VRadioGroup>
                </VRow>
              </VCol>
              <VDivider />
              <VRow class="pt-2 pl-2">
                <VCardText class="mb-0 pl-7 pr-7">
                  <h6 class="text-h6 font-weight-bold">
                    Type
                    <VIcon
                      small
                      class="ml-2 icon-border-blue"
                      color="primary"
                    >
                      mdi-information-outline
                    </VIcon>
                  </h6>
                </VCardText>
              </VRow>
              <VCol
                cols="12"
                class="pt-3"
              >
                <VRow class="pl-2">
                  <VRadioGroup v-model="types">
                    <VRow class="pa-3">
                      <VCol cols="5">
                        <VRadio value="All">
                          <template #label>
                            <div>
                              All
                            </div>
                          </template>
                        </VRadio>
                      </VCol>

                      <VCol cols="5">
                        <VRadio value="local">
                          <template #label>
                            <div>
                              Local Number
                            </div>
                          </template>
                        </VRadio>
                      </VCol>

                      <VCol cols="5">
                        <VRadio value="mobile">
                          <template #label>
                            <div>
                              Mobile Number
                            </div>
                          </template>
                        </VRadio>
                      </VCol>

                      <VCol cols="6">
                        <VRadio value="tollFree">
                          <template #label>
                            <div>
                              Toll Free Number
                            </div>
                          </template>
                        </VRadio>
                      </VCol>
                    </VRow>
                  </VRadioGroup>
                </VRow>
              </VCol>
              <VDivider />
              <VRow class="pt-2 pl-2">
                <VCardText class="mb-0 pl-7 pr-7">
                  <h6 class="text-h6 font-weight-bold">
                    Capabilities
                    <VIcon
                      small
                      class="ml-2 icon-border-blue"
                      color="primary"
                    >
                      mdi-information-outline
                    </VIcon>
                  </h6>
                </VCardText>
              </VRow>
              <VCol
                cols="12"
                class=""
              >
                <VRow class="pl-2">
                  <VCol cols="5">
                    <VCheckbox
                      v-model="callCheckbox"
                      label="Call"
                    />
                  </VCol>

                  <VCol cols="5">
                    <VCheckbox
                      v-model="smsCheckbox"
                      label="SMS"
                    />
                  </VCol>

                  <VCol cols="5">
                    <VCheckbox
                      v-model="mmsCheckbox"
                      label="MMS"
                    />
                  </VCol>
                </VRow>
              </VCol>
              <VDivider />
              <VRow class="pt-2 pl-2">
                <VCardText class="mb-0 pl-7 pr-7">
                  <h6 class="text-h6 font-weight-bold">
                    Address Requirement
                    <VIcon
                      small
                      class="ml-2 icon-border-blue"
                      color="primary"
                    >
                      mdi-information-outline
                    </VIcon>
                  </h6>
                </VCardText>
              </VRow>
              <VCol
                cols="12"
                class="pt-3"
              >
                <VRow class="pl-2">
                  <VRadioGroup v-model="address_requirements">
                    <VRow class="pa-3">
                      <VCol cols="5">
                        <VRadio value="Any">
                          <template #label>
                            <div>
                              Any
                            </div>
                          </template>
                        </VRadio>
                      </VCol>

                      <VCol cols="5">
                        <VRadio value="None">
                          <template #label>
                            <div>
                              None
                            </div>
                          </template>
                        </VRadio>
                      </VCol>

                      <VCol cols="5">
                        <VRadio value="Exclude Local">
                          <template #label>
                            <div>
                              Exclude Local
                            </div>
                          </template>
                        </VRadio>
                      </VCol>

                      <VCol cols="6">
                        <VRadio value="Exclude Foreign">
                          <template #label>
                            <div>
                              Exclude Foreign
                            </div>
                          </template>
                        </VRadio>
                      </VCol>
                    </VRow>
                  </VRadioGroup>
                </VRow>
              </VCol>
            </VCard>
          </VCol>
        </VRow>
      </VCol>

      <VCol
        cols="12"
        md="8"
        lg="8"
      >
        <VRow>
          <VCol cols="12">
            <VCard>
              <template #title>
                <div
                  v-if="selectCountryName && selectCountryFlag"
                  class="card-title-wrapper"
                >
                  Phone Numbers: {{ selectCountryName }}
                  <img
                    :src="selectCountryFlag"
                    class="small-avatar"
                    alt="Flag"
                  >
                </div>
              </template>
              <VCardText>
                <div class="d-flex">
                  <AppSelect
                    :model-value="options.itemsPerPage"
                    :items="[
                      { value: 5, title: '5' },
                      { value: 12, title: '12' },
                      { value: 50, title: '50' },
                      { value: 100, title: '100' },
                      { value: -1, title: 'All' },
                    ]"
                    style="width: 6.25rem;"
                    @update:model-value="options.itemsPerPage = parseInt($event, 10)"
                  />
                  <div class="flex-grow-1" />
                  <AppTextField v-model="search" />
                </div>
              </VCardText>
              <VDivider />
              <!-- 👉 User Project List Table -->
              <!-- Show the loader -->
              <template v-if="isProcessing">
                <VProgressLinear
                  indeterminate
                  height="5"
                  bg-color="primary"
                  color="primary"
                  :rounded="false"
                />
              </template>
              <!-- SECTION Datatable -->
              <VDataTable
                v-model:page="options.page"
                :headers="projectTableHeaders"
                :items="numbers"
                :search="search"
                :items-per-page="options.itemsPerPage"
                @update:options="options = $event"
              >
                <!-- projects -->
                <template #item.name="{ item }">
                  <div class="d-flex">
                    <div>
                      <p class="font-weight-medium mb-0">
                        {{ item.raw.numbers }}
                      </p>
                      <p class="text-xs text-medium-emphasis mb-0">
                        {{ item.raw.type }}
                      </p>
                    </div>
                  </div>
                </template>

                <!-- Progress -->
                <template #item.capabilities="{ item }">
                  <span
                    v-for="(value, key) in item.raw.capabilities"
                    :key="key"
                    class="chip-container"
                    draggable="false"
                  >
                    <small><VChip
                      label
                      color="primary"
                      class="small-chip"
                    >
                      {{ key }}
                    </VChip>
                    </small>
                  </span>
                </template>

                <template #item.address_requirements="{ item }">
                  <div class="d-flex">
                    <div>
                      <p class="text-xs text-medium-emphasis mb-0">
                        {{ item.raw.address_requirements }}
                      </p>
                    </div>
                  </div>
                </template>

                <template #item.button="{ item }">
                  <div class="d-flex">
                    <div>
                      <VBtn
                        :key="item.raw.number"
                        border
                        :loading="item.raw.isLoading"
                        :disabled="item.raw.isDisabled"
                        @click.prevent="addNumber(item)"
                      >
                        <small>{{ getButtonTitle(item) }}</small>
                      </VBtn>
                    </div>
                  </div>
                </template>
              
                <!-- pagination -->
                <template #bottom>
                  <VDivider />
                  <div class="d-flex align-center justify-sm-space-between justify-center flex-wrap gap-3 pa-5 pt-3">
                    <p class="text-sm text-disabled mb-0">
                      {{ paginationMeta(options, numbers.length) }}
                    </p>

                    <VPagination
                      v-model="options.page"
                      :length="Math.ceil(numbers.length / options.itemsPerPage)"
                      :total-visible="Math.ceil(numbers.length / options.itemsPerPage)"
                    >
                      <template #prev="slotProps">
                        <VBtn
                          variant="tonal"
                          color="default"
                          v-bind="slotProps"
                          :icon="false"
                        >
                          Previous
                        </VBtn>
                      </template>

                      <template #next="slotProps">
                        <VBtn
                          variant="tonal"
                          color="default"
                          v-bind="slotProps"
                          :icon="false"
                        >
                          Next
                        </VBtn>
                      </template>
                    </VPagination>
                  </div>
                </template>
              </VDataTable>
              <!-- !SECTION -->
            </VCard>
          </VCol>
        </VRow>
      </VCol>
    </VRow>
  </div>
  <template>
    <!-- Snackbar -->
    <VSnackbar
      v-model="isSnackbarVisible"
      :color="snackbarColor"
    >
      {{ snackbarMessage }}
    </VSnackbar>
  </template>
</template>

<style>
.card-title-wrapper {
  display: flex;
  align-items: center;
}

.small-avatar {
  block-size: 20px; /* Adjust the height as needed */
  inline-size: 20px; /* Adjust the width as needed */
  margin-inline-start: 10px; /* Adjust the margin as needed */
}

.chip-container {
  margin-inline-end: 5px; /* Adjust the margin as needed */
}

.small-chip {
  font-size: 10px; /* Adjust the font size as needed */
  padding-block: 2px;
  padding-inline: 6px; /* Adjust padding as needed */
}

.icon-border-blue {
  padding: 2px;
  border-radius: 50%;
  color: blue;
}

.small-avatar-item .v-avatar {
  block-size: 20px; /* Adjust the height as needed */
  inline-size: 20px; /* Adjust the width as needed */
}

/* Additional styling for the small avatar */
.small-avatar-item .v-list-item__avatar {
  margin-inline-start: 5px; /* Adjust the margin as needed */
}
</style>

<route lang="yaml">
meta:
  layout: blank
  action: view
  subject: Auth
  requiresEmailVerification: true
  redirectIfNumbersSelected: true
</route>
