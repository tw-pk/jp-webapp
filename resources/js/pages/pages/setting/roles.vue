<script setup>
import { useSettingRoleStore } from "@/views/apps/call/useSettingRoleStore"
import { avatarText } from "@core/utils/formatters"
import { requiredValidator } from '@validators'
import { ref } from "vue"

const settingRoleStore = useSettingRoleStore()
const isDialogVisible = ref(false)
const searchQuery = ref('')
const isDisabled = ref(false)
const isLoading = ref(false)
const checkedAll = ref(false)
const role = ref()
const roles = ref([])
const isProcessing = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const defaultTitle = ref('Create New Role')
const defaultButton = ref('Submit')
const deleteId = ref('')
const id = ref('')
const deleteDialog = ref(false)
const form = ref()
const editedIndex = ref(-1)

// 👉 Fetching roles
const fetchRoles = () => {
  isProcessing.value = true
  settingRoleStore.fetchRoles({
    q: searchQuery.value,
  }).then(response => {
    roles.value = response.data.roles
    isProcessing.value = false
  }).catch(error => {
    isProcessing.value = false
    console.log(error)
  })
}

watchEffect(fetchRoles)

watch(checkedAll, newValue => {
  roles.value.forEach(item => {
    item.checked.value = newValue
  })
})

const addRole = () => {
  isDisabled.value = true
  isLoading.value = true
  settingRoleStore.addRole({
    id: id.value,
    role: role.value,
  }).then(response => {
    isDialogVisible.value = false
    isDisabled.value = false
    isLoading.value = false
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = `success`
    isSnackbarVisible.value = true
    
    // Clear input fields
    form.value.reset()

    // re-fetch Members
    fetchRoles()
  })
    .catch(error =>{
      isDisabled.value = false
      isLoading.value = false
      let errorMsg = ''
      if (error.response && error.response.data && error.response.data.errors) {
        const errorMessages = error.response.data.errors

        errorMsg = Object.values(errorMessages).flat().join('\n')
      } else {
        console.log('An error occurred:', error.message)
        errorMsg = error.message
      }
      snackbarMessage.value = errorMsg
      snackbarActionColor.value = 'error'
      isSnackbarVisible.value = true
    })
}

const submitRole = () => {
  form.value.validate().then(isValid => {
    if(isValid.valid === true) {
      form.value.resetValidation()
      addRole()
    }
  })
}

const editItem = item => {
  defaultTitle.value = "Update Role"
  defaultButton.value = "Update"
  id.value = item.id,
  role.value = item.name,
  isDialogVisible.value = true
}

const deleteRole = id => {
  console.log('deleteRole id')
  console.log(id)
  settingRoleStore.deleteRole(id)
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

  //re-fetch roles
  fetchRoles()
}

const deleteItem = item => {
  editedIndex.value = roles.value.indexOf(item)
  deleteId.value = item.id
  deleteDialog.value = true
}

const deleteItemConfirm = () => {
  roles.value.splice(editedIndex.value, 1)
  deleteRole(deleteId.value)
  closeDelete()
}

const closeDelete = () => {
  deleteDialog.value = false
  editedIndex.value = -1
}

const onCloseDialog = () => {
  form.value.reset()
  isDialogVisible.value = false
  defaultTitle.value = 'Create New Role'
  defaultButton.value = 'Submit'
}

const resolveUserRoleVariant = status => {
  if (status === 1)
    return {
      color: 'error',
      text: 'Admin',
    }
  else if (status === 2)
    return {
      color: 'success',
      text: 'Owner',
    }
  else
    return {
      color: 'primary',
      text: 'Team Member',
    }
}
</script>

<template>
  <div>
    <div class="pb-4 font-weight-bold text-h4">
      Settings
    </div>
    <VRow class="match-height">
      <VCol cols="12">
        <VCard>
          <VRow
            justify="space-between"
            class="__align-items-center pa-4 __border-bottom-light"
          >
            <VCol cols="12">
              <div class="pb-4 text-h4">
                User Role
              </div>
            </VCol>
            <VCol cols="3">
              <AppTextField
                v-model="searchQuery"
                placeholder="Search Role Here"
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
                <span class="mr-3">
                  1 of 2 roles created
                </span>
                <VDialog
                  v-model="isDialogVisible"
                  max-width="600"
                >
                  <!-- Dialog Activator -->
                  <template #activator="{ props }">
                    <VBtn
                      v-bind="props"
                      class="mr-2"
                    >
                      Create Role
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
                            md="12"
                          >
                            <AppTextField
                              v-model="role"
                              label="Role"
                              :rules="[requiredValidator]"
                              required
                            />
                          </VCol>
                        </VRow>
                      </VCardText>

                      <VCardText class="d-flex justify-center flex-wrap gap-3">
                        <VBtn
                          :disabled="isDisabled"
                          :loading="isLoading"
                          @click.prevent="submitRole"
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
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-uppercase">
                  <VCheckbox
                    v-model="checkedAll"
                    label="Role"
                  />
                </th>
                <th class="text-uppercase text-center">
                  Users
                </th>
                <th class="text-uppercase text-center">
                  Last Updated
                </th>
                <th class="text-uppercase text-center">
                  Created By
                </th>
                <th class="text-uppercase text-center">
                  Actions
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="item in roles"
                :key="item.id"
              >
                <td>
                  <VCheckbox
                    v-model="item.checked"
                    :label="item.name"
                  />
                </td>
                <td class="text-center">
                  {{ item.user_count }}
                </td>
                <td class="text-center">
                  <small>{{ item.last_updated }}</small>
                </td>
                <td class="text-center">
                  <VAvatar
                    size="32"
                    :color="item.created_by ? resolveUserRoleVariant(item.id).color : primary"
                    class="v-avatar-light-bg primary-text"
                    variant="tonal"
                  >
                    {{ avatarText(item.created_by) }}
                  </VAvatar>
                  {{ item.created_by }}
                </td>
                <td class="text-center">
                  <!-- Actions -->
                  
                  <IconBtn @click="editItem(item)">
                    <VIcon icon="tabler-edit" />
                  </IconBtn>
                    
                  <IconBtn @click="deleteItem(item)">
                    <VIcon icon="tabler-trash" />
                  </IconBtn>

                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    color="medium-emphasis"
                  >
                    <VIcon
                      size="24"
                      icon="tabler-dots-vertical"
                    />

                    <VMenu activator="parent">
                      <VList>
                        <VListItem :to="{ name: 'apps-user-view-id', params: { id: item.id } }">
                          <template #prepend>
                            <VIcon icon="tabler-eye" />
                          </template>
                          <VListItemTitle>View</VListItemTitle>
                        </VListItem>
                      </VList>
                    </VMenu>
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
    </VRow>
    <!-- 👉 Delete Dialog  -->
    <VDialog
      v-model="deleteDialog"
      max-width="500px"
    >
      <VCard>
        <VCardTitle>
          Are you sure you want to delete this role?
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
  </div>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";

</style>
