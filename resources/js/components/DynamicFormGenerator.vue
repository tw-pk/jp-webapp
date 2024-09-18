<script setup>
import { useInviteMemberStore } from "@/views/apps/invite-members/useInviteMemberStore"
import { useNumberSelectionStore } from "@/views/apps/number-selection/useNumberSelectionStore"
import axiosIns from "@axios"
import { VNodeRenderer } from "@layouts/components/VNodeRenderer"
import { themeConfig } from '@themeConfig'
import { emailValidator, requiredValidator } from "@validators"
import { ref, watch } from 'vue'
import { useTheme } from 'vuetify'
import { isEmpty } from "../@core/utils"

const numberSelectionStore = useNumberSelectionStore()
const inviteMemberStore = useInviteMemberStore()

const vuetifyTheme = useTheme()
const currentThemeName = vuetifyTheme.name.value

// const selectOption = ref()
// const selectOptionSearch = ref()

//const existingNumberOptionSelected = ref(false)

const inviteUsers = ref([{
  firstName: '',
  lastName: '',
  emailAddress: '',
  phoneNumber: '',
  existingNumber: '',
  allowNewNumber: '',
  panel: 0,
  selectOption: '',
  selectOptionSearch: '',
  existingNumberOptionSelected: false,
}])

const errors = ref({
  email: undefined,
  firstname: undefined,
  lastname: undefined,
  phone_number: undefined,
  my_phone_number: undefined,
  allowNewNumber: '',
})

const newNumberItems = ref([
  'Yes',
  'No',
])

const isDisabled = ref(false)
const refVForm = ref(null)

vuetifyTheme.themes.value[currentThemeName].colors.primary = '#38A6E3'

const addMore = () => {

  inviteUsers.value.push({
    firstName: '',
    lastName: '',
    emailAddress: '',
    phoneNumber: '',
    existingNumber: '',
    allowNewNumber: '',
    panel: 0,
    selectOption: '',
    selectOptionSearch: '',
    existingNumberOptionSelected: false,
  })
}


const remove = index => {
  inviteUsers.value.splice(index, 1)
}

const loading = ref(false)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const search = ref()
const select = ref(null)
const existingNumbers = ref([])
const items = ref([])
const checkoutUrl = ref()

const assignNumberOptions = ref([
  { optionValue: 'Existing Number' },
  { optionValue: 'Allow user to get a new number' },
])


numberSelectionStore.fetchExistingNumber().then(res => {
  if (res.data.status) {
    existingNumbers.value = res.data.numbers
    items.value = existingNumbers.value.map(numbers => numbers.phone_number)
  }
}).catch(error => {
  console.log(error)
})

const querySelections = query => {
  loading.value = true

  // Simulated ajax query
  setTimeout(() => {
    items.value = existingNumbers.value.filter(numbers => (numbers.phone_number || '').toLowerCase().includes((query || '').toLowerCase()))[0] ? [existingNumbers.value.filter(numbers => (numbers.phone_number || '').toLowerCase().includes((query || '').toLowerCase()))[0]?.phone_number] : []
    loading.value = false
  }, 500)
}

watch(search, query => {
  query && query !== select.value && querySelections(query)
})


const watchSelectOptionChanges = () => {
  inviteUsers.value.forEach((inviteUser, index) => {
    watch(() => inviteUser.selectOption, newValue => {
    
      inviteUser.existingNumberOptionSelected = newValue && newValue === "Existing Number"
      if(!inviteUser.existingNumberOptionSelected){
        inviteUser.existingNumber = null
      }
      
      // Update allowNewNumber based on selectOption
      inviteUser.allowNewNumber = newValue === 'Existing Number' ? false : true
    })
  })
}

// Initial setup
watchSelectOptionChanges()

watch(inviteUsers, () => {
  watchSelectOptionChanges()
}, { deep: true })

const createStripeSession = () => {
  
  axiosIns.post('/api/auth/create-session-details')
    .then(res => {
      if (res.data){
        window.location.href = res.data.checkout_url
      }else{
        isLoading.value = false
        isDisabled.value = false
      }
    })
    .catch(error => {
      console.log(error)
    })
}

const inviteMembers = () => {
  refVForm.value.validate().then(isValid => {
    if(isValid.valid === true) {
      refVForm.value.resetValidation()

      isLoading.value = true
      isDisabled.value = true
      
      inviteMemberStore.createInvitations({
        members: inviteUsers.value,
      })
        .then(res => {
          isLoading.value = false
          isDisabled.value = false
          if (res.data.status) {
            createStripeSession()
          }else{
            snackbarMessage.value = res
            snackbarActionColor.value = 'error'
            isSnackbarVisible.value = true
          }
        })
        .catch(error => {
          isLoading.value = false
          isDisabled.value = false
          let errorMsg = ''
          if (error.response && error.response.data && error.response.data.errors) {
            const errorMessages = error.response.data.errors
            
            const errorFields = Object.keys(errorMessages)
            for (const field of errorFields) {
              const fieldErrors = errorMessages[field]

              errorMsg += `${fieldErrors.join('\n')}\n`
            }
          } else {
            errorMsg = error.message
          }
          snackbarMessage.value = errorMsg
          snackbarActionColor.value = 'error'
          isSnackbarVisible.value = true
        })

    }
  })
}

const cancelInviteMembers = () => {
  createStripeSession()
}
</script>

<template>
  <VCard
    :max-width="700"
    :min-width="375"
    class="fixed-width-vcard mt-12 mt-sm-0 __p_card"
  >
    <VCardText class="pa-3">
      <VRow
        justify="center"
        class="mb-1"
      >
        <VNodeRenderer
          :nodes="themeConfig.app.teams_logo"
          class=""
        />
      </VRow>
      <VRow justify="center">
        <h3
          class="text-h3"
          style="font-weight: 700;"
        >
          Invite Team Members
        </h3>
      </VRow>
    </VCardText>

    <VForm
      ref="refVForm"
      class="__login_form pt-6"
      lazy-validation
    >
      <VCol
        cols="12"
        class="pa-0"
      >
        <VExpansionPanels
          v-for="(inviteUser, index) in inviteUsers"
          :key="index"
          class="mb-5"
          variant="default"
          :model-value="inviteUser.panel"
        >
          <VCol
            cols="12"
            class="pa-0"
          >
            <!-- SECTION Delivery Address -->
            <VExpansionPanel :model-value="inviteUser.panel">
              <VExpansionPanelTitle class="font-weight-bold">
                {{ isEmpty(inviteUser.emailAddress) ? `Invite User ${index + 1}` : inviteUser.emailAddress }}
              </VExpansionPanelTitle>

              <VExpansionPanelText>
                <VRow class="custom_padding_expansion_panel">
                  <!-- 👉 Full Name -->
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <AppTextField
                      :ref="inviteUser.firstName"
                      v-model="inviteUser.firstName"
                      label="First Name"
                      :rules="[requiredValidator]"
                      required
                    />
                  </VCol>

                  <!-- 👉 Phone No -->
                  <VCol
                    cols="12"
                    md="6"
                  >
                    <AppTextField
                      :ref="inviteUser.lastName"
                      v-model="inviteUser.lastName"
                      label="Last Name"
                      type="text"
                    />
                  </VCol>

                  <!-- 👉 Address -->
                  <VCol cols="12">
                    <AppTextField
                      :ref="inviteUser.emailAddress"
                      v-model="inviteUser.emailAddress"
                      :rules="[requiredValidator, emailValidator]"
                      required
                      label="Email Address"
                      type="email"
                    />
                  </VCol>
                  <!-- 👉 Number Selection Options -->

                  <VCol
                    cols="12"
                    md="12"
                  >
                    <AppAutocomplete
                      v-model="inviteUser.selectOption"
                      v-model:search="inviteUser.selectOptionSearch"
                      :loading="loading"
                      label="Assign Number"
                      :items="assignNumberOptions"
                      item-title="optionValue"
                      :menu-props="{ maxHeight: '200px' }"
                      :rules="[requiredValidator]"
                      required
                    />
                  </VCol>

                  <VCol
                    v-if="inviteUser.existingNumberOptionSelected"
                    cols="12"
                    md="12"
                  >
                    <AppAutocomplete
                      v-model="inviteUser.existingNumber"
                      v-model:search="search"
                      :loading="loading"
                      label="Assign Existing Number"
                      :items="items"
                      :menu-props="{ maxHeight: '200px' }"
                    />
                  </VCol>

                  <!-- 👉 Number Selection Options -->
                  <!--                  -->
                  <!--                  <VCol -->
                  <!--                    cols="12" -->
                  <!--                    md="12" -->
                  <!--                  > -->
                  <!--                    <AppAutocomplete -->
                  <!--                      v-model="inviteUser.allowNewNumber" -->
                  <!--                      label="Allow user to get new number" -->
                  <!--                      :items="newNumberItems" -->
                  <!--                      :menu-props="{ maxHeight: '200px' }" -->
                  <!--                    /> -->

                  <!--                  </VCol> -->

                  <VCol
                    v-if="index"
                    cols="12"
                    class="my-5"
                  >
                    <VDivider />
                  </VCol>

                  <VRow
                    v-if="index > 0"
                    class="pa-6"
                    justify="end"
                  >
                    <div style="justify-content: end;">
                      <VBtn
                        color="secondary"
                        variant="tonal"
                        @click.prevent="remove(index)"
                      >
                        Remove Invitation
                        <VIcon
                          icon="tabler-x"
                          class="ml-2"
                        />
                      </VBtn>
                    </div>
                  </VRow>
                </VRow>
              </VExpansionPanelText>
            </VExpansionPanel>
            <!--          </VCol> -->
            <!--          <VCol -->
            <!--            cols="2" -->
            <!--            class="pa-0 pl-3" -->
            <!--          > -->
            <!--            <VBtn -->
            <!--              v-if="index > 0" -->
            <!--              size="30" -->
            <!--              color="secondary" -->
            <!--              variant="tonal" -->
            <!--              @click.prevent="remove(index)" -->
            <!--            > -->
            <!--              <VIcon -->
            <!--                icon="tabler-x" -->
            <!--                size="22" -->
            <!--              /> -->
            <!--            </VBtn> -->
          </VCol>
          <!-- !SECTION Delivery Address -->
        </VExpansionPanels>


        <VCol
          cols="12"
          class="mt-3"
        >
          <VRow
            justify="end"
            class="mb-3"
          >
            <VBtn
              class="text-end"
              type="button"
              :disabled="isDisabled"
              :loading="isDisabled"
              variant="text"
              @click="addMore"
            >
              + Add more
            </VBtn>
          </VRow>
          <VRow class="d-flex flex-wrap justify-end gap-4 mt-4">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="cancelInviteMembers"
            >
              Cancel
            </VBtn>
            <VBtn
              :disabled="isDisabled"
              :loading="isLoading"
              @click.prevent="inviteMembers"
            >
              Invite Members
            </VBtn>
          </VRow>
        </VCol>
      </VCol>
    </VForm>
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
  </VCard>
</template>

<style scoped>
.fixed-width-vcard {
  inline-size: 700px; /* Set your desired fixed width */
}
</style>
