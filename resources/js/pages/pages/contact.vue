<script setup>
import { paginationMeta } from '@/@fake-db/utils'
import { useContactStore } from "@/views/apps/contact/useContactStore"
import { avatarText } from "@core/utils/formatters"
import avatar1 from '@images/avatars/avatar-0.png'
import { emailValidator, requiredValidator } from '@validators'
import { ref } from "vue"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const contactStore = useContactStore()
const isDialogVisible = ref(false)
const searchQuery = ref('')
const isDisabled = ref(false)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const defaultTitle = ref('Add New Contact')
const defaultButton = ref('Add To Contact')
const firstname = ref('')
const lastname = ref('')
const email = ref('')
const phone = ref('')
const company_name = ref('')
const form = ref()
const contacts = ref([])
const avatarInput = ref(null)
const isProcessing = ref(false)
const totalPage = ref(1)
const totalRecord = ref(0)
const editedIndex = ref(-1)
const deleteId = ref('')
const id = ref('')
const deleteDialog = ref(false)

// headers
const headers = [
  {
    title: 'Name',
    key: 'fullname',
  },
  {
    title: 'Email',
    key: 'email',
  },
  {
    title: 'Phone Number',
    key: 'phone',
  },
  {
    title: 'ACTION',
    key: 'actions',
    sortable: false,
  },
]

const options = ref({
  page: 1,
  itemsPerPage: 5,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

const accountData = {
  avatarImg: avatar1,
}

const accountDataLocal = ref(structuredClone(accountData))

const changeAvatar = file => {
  const fileReader = new FileReader()
  const { files } = file.target
  if (files && files.length) {
    fileReader.readAsDataURL(files[0])
    fileReader.onload = () => {
      if (typeof fileReader.result === 'string')
        accountDataLocal.value.avatarImg = fileReader.result
    }
  }
}

// reset avatar image
const resetAvatar = () => {
  accountDataLocal.value.avatarImg = accountData.avatarImg
}

// 👉 Fetching Contact
const fetchContacts = () => {
  isProcessing.value = true
  contactStore.fetchContacts({
    q: searchQuery.value,
    options: options.value,
  }).then(response => {
    
    contacts.value = response.data.contacts.data
    totalPage.value = response.data.totalPage
    totalRecord.value = response.data.totalRecord
    options.value.page = response.data.page
    isProcessing.value = false

  }).catch(error => {
    isProcessing.value = false
    console.log(error)
  })
}

const deleteContact = id => {
  contactStore.deleteContact(id)
    .then(response => {
      snackbarMessage.value = response.data.message
      snackbarActionColor.value = `success`
      isSnackbarVisible.value = true
    })
    .catch(error => {
      snackbarMessage.value = error.data.message
      snackbarActionColor.value = `error`
      isSnackbarVisible.value = true
    })

  //re-fetch contacts
  fetchContacts()
}

watchEffect(fetchContacts)

const addContact = () => {
  isDisabled.value = true
  isLoading.value = true

  const formData = new FormData()

  formData.append('id', id.value)
  formData.append('avatar', avatarInput.value.files[0])
  formData.append('firstname', firstname.value)
  formData.append('lastname', lastname.value)
  formData.append('email', email.value)
  formData.append('phone', phone.value)
  formData.append('company_name', company_name.value)
  contactStore.addContact(formData).then(response => {
    isDialogVisible.value = false
    isDisabled.value = false
    isLoading.value = false
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = `success`
    isSnackbarVisible.value = true

    // Clear input fields
    form.value.reset()

    // refetch Team
    //if(!avatarInput.value){
    fetchContacts()

    //}
  })
    .catch(error => {
      isDisabled.value = false
      isLoading.value = false
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

const submitContact = () => {
  form.value.validate().then(isValid => {
    if(isValid.valid === true) {
      form.value.resetValidation()
      addContact()
    }
  })
}

const editItem = item => {
  defaultTitle.value = "Update Contact"
  defaultButton.value = "Update To Contact"
  
  id.value = item.id,
  accountDataLocal.value.avatarImg = item.avatarPath || accountData.avatarImg
  firstname.value = item.firstname,
  lastname.value = item.lastname,
  email.value = item.email,
  phone.value = item.phone,
  company_name.value = item.company_name,
  
  isDialogVisible.value = true
}

const deleteItem = item => {
  editedIndex.value = contacts.value.indexOf(item)
  deleteId.value = item.id
  deleteDialog.value = true
}

const deleteItemConfirm = () => {
  contacts.value.splice(editedIndex.value, 1)
  deleteContact(deleteId.value)
  closeDelete()
}

const closeDelete = () => {
  deleteDialog.value = false
  editedIndex.value = -1
}

const onCloseDialog = () => {
  form.value.reset()
  isDialogVisible.value = false
  defaultTitle.value = 'Add New Contact'
  defaultButton.value = 'Add To Contact'
 
}
</script>

<template>
  <div class="">
    <div class="pb-4 font-weight-bold text-h4">
      Contacts
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
                placeholder="Search Contact"
                density="compact"
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
                      Add New Contact
                    </VBtn>
                  </template>
                  <!-- Dialog close btn -->
                  <DialogCloseBtn @click="onCloseDialog" />
                  <!-- Dialog Content -->
                  <VCard class="__dialog-padding">
                    <VForm
                      ref="form"
                      lazy-validation
                    >
                      <VCardText>
                        <VRow justify="center">
                          <h3 class="font-weight-bold text-h3">
                            {{ defaultTitle }}
                          </h3>
                        </VRow>
                        <VRow>
                          <VCol
                            cols="12"
                            sm="12"
                            md="12"
                            class="d-flex"
                          >
                            <!-- 👉 Avatar -->
                            <VAvatar
                              size="x-large"
                              class="me-3 mt-3"
                              :image="accountDataLocal.avatarImg"
                            />
                            <!-- 👉 Upload Photo -->
                            <div class="d-flex flex-wrap gap-2 mt-5">
                              <VBtn
                                color="primary"
                                @click="avatarInput?.click()"
                              >
                                <VIcon
                                  icon="tabler-cloud-upload"
                                  class="d-sm-none"
                                />
                                <span class="d-sm-block">Upload Image</span>
                              </VBtn>
                              <input
                                ref="avatarInput"
                                type="file"
                                name="file"
                                accept=".jpeg,.png,.jpg,GIF"
                                hidden
                                @input="changeAvatar"
                              >
                              <VBtn
                                type="reset"
                                color="secondary"
                                variant="tonal"
                                @click="resetAvatar"
                              >
                                <span class="d-sm-block">Delete</span>
                                <VIcon
                                  icon="tabler-refresh"
                                  class="d-sm-none"
                                />
                              </VBtn>
                            </div>
                          </VCol>
                        </VRow>
                        <VRow>
                          <VCol
                            cols="12"
                            sm="6"
                            md="6"
                          >
                            <AppTextField
                              v-model="firstname"
                              label="First Name"
                              :rules="[requiredValidator]"
                              required
                            />
                          </VCol>
                          <VCol
                            cols="12"
                            sm="6"
                            md="6"
                          >
                            <AppTextField
                              v-model="lastname"
                              label="Last Name"
                              :rules="[requiredValidator]"
                              required
                            />
                          </VCol>
                        </VRow>
                        <VRow>
                          <VCol
                            cols="12"
                            sm="6"
                            md="6"
                          >
                            <AppTextField
                              v-model="email "
                              label="Email Address"
                              :rules="[emailValidator, requiredValidator]"
                              required
                            />
                          </VCol>
                          <VCol
                            cols="12"
                            sm="6"
                            md="6"
                          >
                            <AppTextField
                              v-model="phone"
                              label="Phone Number"
                              :rules="[requiredValidator]"
                              required
                            />
                          </VCol>
                        </VRow>
                        <VRow>
                          <VCol
                            cols="12"
                            sm="12"
                            md="12"
                          >
                            <AppTextField
                              v-model="company_name"
                              label="Company Name"
                              :rules="[requiredValidator]"
                              required
                            />
                            <input
                              v-model="id"
                              type="hidden"
                            >
                          </VCol>
                        </VRow>
                      </VCardText>
                      <VCardText class="d-flex justify-center flex-wrap gap-3">
                        <VBtn
                          :disabled="isDisabled"
                          :loading="isLoading"
                          @click="submitContact"
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
                    </VForm>
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
          <VDataTableServer
            v-model:items-per-page="options.itemsPerPage"
            v-model:page="options.page"
            :items="contacts"
            :items-length="totalRecord"
            :headers="headers"
            class="text-no-wrap"
            show-select
            @update:options="options = $event"
          >
            <!-- full name -->
            <template #item.fullname="{ item }">
              <div class="d-flex align-center">
                <VAvatar
                  size="32"
                  :color="!item.raw.avatarPath ? 'primary' : undefined"
                  :class="item.raw.avatarPath ? '' : 'v-avatar-light-bg primary--text'"
                  :variant="!item.raw.avatarPath ? 'tonal' : undefined"
                >
                  <VImg
                    v-if="item.raw.avatarPath"
                    :src="item.raw.avatarPath"
                  />
                  <span v-else>{{ avatarText(item.raw.fullname) }}</span>
                </VAvatar>
                <div class="d-flex flex-column ms-3">
                  <RouterLink
                    :to="{ name: 'pages-contact-details-id', params: { id: item.raw.contactId } }"
                    class="font-weight-medium user-list-name"
                  >
                    <span class="d-block font-weight-medium text--primary text-truncate">{{ item.raw.fullname }}</span>
                    {{ item.raw.fullName }}
                  </RouterLink>
                </div>
              </div>
            </template>
        
            <template #item.actions="{ item }">
              <VBtn
                variant="outlined"
                size="small"
                color="medium-emphasis"
                class="v-btn--outlined"
              >
                Action
                <VIcon
                  size="16"
                  icon="mdi-chevron-down"
                />
                <VMenu activator="parent">
                  <VList>
                    <VListItem @click="editItem(item.raw)">
                      <template #prepend>
                        <VIcon icon="tabler-pencil" />
                      </template>
                      <VListItemTitle>Edit</VListItemTitle>
                    </VListItem>
                    <VListItem @click="deleteItem(item.raw)">
                      <template #prepend>
                        <VIcon icon="tabler-trash" />
                      </template>
                      <VListItemTitle>Delete</VListItemTitle>
                    </VListItem>
                  </VList>
                </VMenu>
              </VBtn>
            </template>
            <!-- pagination -->
            <template #bottom>
              <VDivider />
              <div class="d-flex align-center justify-sm-space-between justify-center flex-wrap gap-3 pa-5 pt-3">
                <p class="text-sm text-disabled mb-0">
                  {{ paginationMeta(options, totalRecord) }}
                </p>
                <VPagination
                  v-model="options.page"
                  :length="Math.ceil(totalRecord / options.itemsPerPage)"
                  :total-visible="$vuetify.display.xs ? 1 : Math.ceil(totalRecord / options.itemsPerPage)"
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
        </VCard>
      </VCol>
      <!-- 👉 Delete Dialog  -->
      <VDialog
        v-model="deleteDialog"
        max-width="500px"
      >
        <VCard>
          <VCardTitle>
            Are you sure you want to delete this contact?
          </VCardTitle>
          
      
          <VCardActions>
            <VSpacer />
             
            <VBtn
              color="error"
              variant="outlined"
              @click="closeDelete"
            >
              Cancel
            </VBtn>
            <VBtn
              color="success"
              variant="elevated"
              @click="deleteItemConfirm"
            >
              OK
            </VBtn>
            <VSpacer />
          </VCardActions>
        </VCard>
      </VDialog>
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
    </VRow>
  </div>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
