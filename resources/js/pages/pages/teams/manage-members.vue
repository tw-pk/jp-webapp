<script setup>
import { paginationMeta } from "@/@fake-db/utils"
import User from "@/apis/user"
import { useMemberListStore } from "@/views/apps/member/useMemberListStore"
import { avatarText } from "@core/utils/formatters"
import { VDataTableServer } from "vuetify/labs/VDataTable"

const memberListStore = useMemberListStore()
const isDialogVisible = ref(false)
const id = ref('')
const firstName = ref('')
const lastName = ref('')
const search = ref('')
const email = ref('')
const role = ref('')
const number = ref('')
const numbers = ref([])
const roles = ref([])
const isDisabled = ref(false)
const isLoading = ref(false)
const isProcessing = ref(false)
const searchQuery = ref('')
const totalPage = ref(1)
const totalUsers = ref(0)
const members = ref([])
const validationErrors = ref({})
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const defaultTitle = ref('Add New Member')
const defaultButton = ref('Submit')
const assignNumber = ref()
const existingNumberOptionSelected = ref(false)

const options = ref({
  page: 1,
  itemsPerPage: 5,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

// Headers
const headers = [
  {
    title: 'NAME',
    key: 'name',
  },
  {
    title: 'EXTENSION',
    key: 'extension',
  },
  {
    title: 'ROLE',
    key: 'role',
  },

  {
    title: 'ACTION',
    key: 'action',
    sortable: false,
  },
]

// 👉 Fetching phone numbers
const fetchNumbers = () => {
  memberListStore.fetchNumbers().then(response => {
    numbers.value = response.data.userNumber
  }).catch(error => {
    console.error(error)
  })
}

// 👉 Fetching roles
const fetchRoles = () => {
  memberListStore.fetchRoles().then(response => {
    roles.value = response.data.roles
  }).catch(error => {
    console.error(error)
  })
}

// 👉 Fetching users
const fetchMembers = () => {
  isProcessing.value = true
  memberListStore.fetchMembers({
    q: searchQuery.value,
    options: options.value,
  }).then(response => {
    members.value = response.data.members.data
    totalPage.value = response.data.totalPage
    totalUsers.value = response.data.totalUsers
    options.value.page = response.data.page
    isProcessing.value = false
  }).catch(error => {
    isProcessing.value = false
    console.error(error)
  })
}


onMounted(() => {
  fetchNumbers()
  fetchRoles()
 
  //fetchMembers()
 
})

watchEffect(fetchMembers)

const status = [
  {
    title: 'Pending',
    value: 'pending',
  },
  {
    title: 'Active',
    value: 'active',
  },
  {
    title: 'Inactive',
    value: 'inactive',
  },
]

const resolveUserRoleVariant = status => {
  if (status === 1)
    return {
      color: 'success',
      text: 'Admin',
    }
  else if (status === 2)
    return {
      color: 'primary',
      text: 'Owner',
    }
  else if (status === 3)
    return {
      color: 'error',
      text: 'Team Member',
    }
  else if (status === 4)
    return {
      color: 'warning',
      text: 'Maintainer',
    }
  else if (status === 5 || status === 6 )
    return {
      color: 'secondary',
      text: 'Subscrib',
    }
  else
    return {
      color: 'info',
      text: 'Subscriber',
    }
}


// const deleteUser = id => {
//   memberListStore.deleteMember(id)

//   // re-fetch Members
//   fetchMembers()
// }

const inviteUser = () => {
  isDisabled.value = true
  isLoading.value = true
  User.inviteMember({
    id: id.value,
    firstName: firstName.value,
    lastName: lastName.value,
    email: email.value,
    assignNumber: assignNumber.value,
    number: number.value,
    role: role.value,
  }).then(response => {
    isDialogVisible.value = false
    isDisabled.value = false
    isLoading.value = false
    validationErrors.value = {}
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = `success`
    isSnackbarVisible.value = true
    
    // Clear input fields
    id.value = ''
    firstName.value = ''
    lastName.value = ''
    email.value = ''
    assignNumber.value = ''
    number.value = ''
    role.value = ''

    // re-fetch Members
    fetchMembers()
  })
    .catch(error =>{
      isDisabled.value = false
      isLoading.value = false

      // Error: Display validation errors
      if (error.response && error.response.data && error.response.data.errors) {
        validationErrors.value = error.response.data.errors
      } else {
        console.log('An error occurred:', error.message)
      }
    })
}

const editItem = item => {

  defaultTitle.value = "Update Member"
  defaultButton.value = "Update"
  id.value = item.id,
  firstName.value = item.firstname,
  lastName.value = item.lastname,
  email.value = item.email,
  assignNumber.value = item.can_have_new_number ==1 ? 'Allow user to get a new number' : 'Existing Number' 
  number.value = item.number,
  role.value = item.role,
  isDialogVisible.value = true
}

const onCloseDialog = () => {
  id.value = ''
  firstName.value = ''
  lastName.value = ''
  email.value = '',
  assignNumber.value = ''
  number.value = '',
  role.value = '',
  validationErrors.value = {}
  defaultTitle.value = 'Add New Member'
  defaultButton.value = 'Submit'
  isDialogVisible.value = false
}

const assignNumberOptions = ref([
  { optionValue: 'Existing Number' },
  { optionValue: 'Allow user to get a new number' },
])

watch(assignNumber, value => {
  if (value && value === "Existing Number") {
    existingNumberOptionSelected.value = value
  }else {
    existingNumberOptionSelected.value = false
    number.value = ''
  }
})
</script>

<template>
  <div>
    <div class="pb-4 font-weight-bold text-h4">
      Manage Members
    </div>

    <VRow class="match-height">
      <VCol cols="12">
        <VCard>
          <VRow
            justify="space-between"
            class="__align-items-center pa-4 __border-bottom-light"
          >
            <VCol cols="3">
              <AppTextField
                v-model="searchQuery"
                density="compact"
                placeholder="Search Name or Email"
                append-inner-icon="tabler-search"
                single-line
                hide-details
                dense
                outlined
              />
            </VCol>
            <VCol cols="9">
              <VRow class="__align-items-center justify-end mx-1">
                <VDialog
                  v-model="isDialogVisible"
                  max-width="610"
                >
                  <!-- Dialog Activator -->
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      class="mr-2"
                    >
                      Add Team Member
                    </VBtn>
                  </template>

                  <!-- Dialog close btn -->
                  <DialogCloseBtn @click="onCloseDialog" />

                  <!-- Dialog Content -->
                  <VCard class="__dialog-padding">
                    <VCardText>
                      <VRow justify="center">
                        <h3 class="font-weight-bold text-h3">
                          {{ defaultTitle }}
                        </h3>
                      </VRow>
                      <VRow
                        justify="center"
                        class="pt-2 pb-2"
                      >
                        <h6 class=" text-h6">
                          Updating user details will receive a privacy audit.
                        </h6>
                      </VRow>
                      <VRow>
                        <VCol
                          cols="12"
                          sm="12"
                          md="6"
                        >
                          <AppTextField
                            v-model="firstName"
                            label="First Name"
                          />
                          <span
                            v-if="validationErrors.firstName"
                            class="error"
                            style="color: red;"
                          >{{ validationErrors.firstName[0] }}</span>
                        </VCol>
                        <VCol
                          cols="12"
                          sm="12"
                          md="6"
                        >
                          <AppTextField
                            v-model="lastName"
                            label="Last Name"
                          />
                        </VCol>
                        <VCol cols="12">
                          <AppTextField
                            v-model="email"
                            label="Email Address"
                          />
                          <span
                            v-if="validationErrors.email"
                            class="error"
                            style="color: red;"
                          >{{ validationErrors.email[0] }}</span>
                        </VCol>
                        <VCol cols="12">
                          <AppAutocomplete
                            v-model="role"
                            label="Assign Role"
                            :items="roles"
                            item-title="name"
                            item-value="id"
                            clearable
                          />
                          <span
                            v-if="validationErrors.role"
                            class="error"
                            style="color: red;"
                          >{{ validationErrors.role[0] }}</span>
                        </VCol>
                        <VCol
                          cols="12"
                          md="12"
                        >
                          <AppAutocomplete
                            v-model="assignNumber"
                            label="Assign Number"
                            :items="assignNumberOptions"
                            item-title="optionValue"
                            clearable
                          />
                          <span
                            v-if="validationErrors.assignNumber"
                            class="error"
                            style="color: red;"
                          >{{ validationErrors.assignNumber[0] }}</span>
                        </VCol>
                        
                        <VCol
                          v-if="existingNumberOptionSelected"
                          cols="12"
                          sm="12"
                        >
                          <AppAutocomplete
                            v-model="number"
                            label="Assign Existing Number"
                            :items="numbers"
                            item-title="phone_number"
                            item-value="phone_number"
                            clearable
                          />
                          <span
                            v-if="validationErrors.number"
                            class="error"
                            style="color: red;"
                          >{{ validationErrors.number[0] }}</span>
                        </VCol>
                        <input
                          v-model="id"
                          type="hidden"
                        >
                      </VRow>
                    </VCardText>

                    <VCardText class="d-flex justify-center flex-wrap gap-3">
                      <VBtn
                        :disabled="isDisabled"
                        :loading="isLoading"
                        @click.prevent="inviteUser"
                      >
                        {{ defaultButton }}
                      </VBtn>
                      <VBtn
                        variant="tonal"
                        color="secondary"
                        @click="onCloseDialog"
                      >
                        Cancel
                      </VBtn>
                    </VCardText>
                  </VCard>
                </VDialog>
                <IconBtn @click.prevent="">
                  <VIcon icon="tabler-download" />
                </IconBtn>
              </VRow>
            </VCol>
          </VRow>
          <VDivider />

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
          <!-- SECTION datatable -->
          <VDataTableServer
            v-model:items-per-page="options.itemsPerPage"
            v-model:page="options.page"
            :headers="headers"
            :items="members"
            :items-length="totalUsers"
            class="text-no-wrap"
            show-select
            @update:options="options = $event"
          >
            <!-- Members -->
            <template #item.name="{ item }">
              <div class="d-flex align-center">
                <VAvatar
                  size="38"
                  :variant="!item.raw.invitation_accept?.profile?.avatar ? 'tonal' : undefined"
                  :color="!item.raw.invitation_accept?.profile?.avatar ? resolveUserRoleVariant(item.raw.role).color : undefined"
                  class="me-3"
                >
                  <VImg
                    v-if="item.raw.invitation_accept?.profile?.avatar"
                    :src="'./../../storage/avatars/' + item.raw.invitation_accept?.profile?.avatar"
                  />
                  <span v-else>{{ avatarText(item.raw.firstname) }}</span>
                </VAvatar>

                <div class="d-flex flex-column">
                  <h6 class="text-base">
                    <RouterLink
                      :to="{ name: 'apps-user-view-id', params: { id: item.raw.id } }"
                      class="font-weight-medium user-list-name"
                    >
                      {{ item.raw.firstname }} {{ item.raw.lastname }}
                    </RouterLink>
                  </h6>

                  <span class="text-sm text-medium-emphasis">{{ item.raw.email }}</span>
                </div>
              </div>
            </template>

            <!-- 👉 Extension -->
            <template #item.extension="{ item }">
              <VChip
                label
                color="success"
                size="small"
              >
                Ex 101
              </VChip>
            </template>
            
            <!-- 👉 Role -->
            <template #item.role="{ item }">
              <div class="d-flex align-center gap-4">
                <span
                  v-if="item.raw.role_info?.name"
                  class="text-capitalize"
                >{{ item.raw.role_info.name }}</span>
              </div>
            </template>

            <!-- Actions -->
            <template #item.action="{ item }">
              <IconBtn
                class=" user-list-name"
                @click="editItem(item.raw)"
              >
                Edit
              </IconBtn>
            
              <VIcon icon="tabler-minus-vertical" />
              <RouterLink
                :to="{ name: 'pages-teams-member-analysis', params: { analysis: item.raw.id } }"
                class="user-list-name"
              >
                Analysis
              </RouterLink>
            </template>
            
            <!-- pagination -->
            <template #bottom>
              <VDivider />
              <div class="d-flex align-center justify-sm-space-between justify-center flex-wrap gap-3 pa-5 pt-3">
                <p class="text-sm text-disabled mb-0">
                  {{ paginationMeta(options, totalUsers) }}
                </p>

                <VPagination
                  v-model="options.page"
                  :length="Math.ceil(totalUsers / options.itemsPerPage)"
                  :total-visible="$vuetify.display.xs ? 1 : Math.ceil(totalUsers / options.itemsPerPage)"
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
          </VDataTableServer>
          <!-- SECTION -->
        </VCard>
      </VCol>
    </VRow>
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
  </div>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";

.text-capitalize {
  text-transform: capitalize;
}

.user-list-name:not(:hover) {
  color: rgba(var(--v-theme-on-background), var(--v-medium-emphasis-opacity));
}
</style>

