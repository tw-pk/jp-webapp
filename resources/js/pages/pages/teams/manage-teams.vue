<script setup>
import { paginationMeta } from '@/@fake-db/utils'
import { useTeamListStore } from "@/views/apps/team/useTeamListStore"
import { avatarText } from "@core/utils/formatters"
import { can } from "@layouts/plugins/casl"
import { onMounted, ref } from "vue"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const teamListStore = useTeamListStore()
const isDialogVisible = ref(false)
const searchQuery = ref('')
const id = ref('')
const deleteId = ref('')
const teams = ref([])
const teamName = ref('')
const description = ref('')
const inviteMembers = ref('')
const members = ref([])
const isDisabled = ref(false)
const isLoading = ref(false)
const validationErrors = ref({})
const totalPage = ref(1)
const totalUsers = ref(0)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const defaultTitle = ref('Create New Team')
const defaultButton = ref('Submit')
const deleteDialog = ref(false)
const editedIndex = ref(-1)
const memberIds = ref('')
const isProcessing = ref(false)

const options = ref({
  page: 1,
  itemsPerPage: 5,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

// headers
const headers = [
  {
    title: 'Team NAME',
    key: 'name',
    tab: 'Member',
  },
  {
    title: 'PHONE',
    key: 'phone_number',
    tab: 'Member',
  },
  {
    title: 'MEMBERS',
    key: 'members',
    tab: 'Member',
  },
  {
    title: 'ACTION',
    key: 'actions',
    sortable: false,
  },
]

const filteredHeaders = computed(() => {
  return headers.filter(item => can('read', item.tab))
})

const resolveStatusVariant = status => {
  if (status === 1)
    return {
      color: 'primary',
      text: 'Current',
    }
  else if (status === 2)
    return {
      color: 'success',
      text: 'Professional',
    }
  else if (status === 3)
    return {
      color: 'error',
      text: 'Rejected',
    }
  else if (status === 4)
    return {
      color: 'warning',
      text: 'Resigned',
    }
  else
    return {
      color: 'info',
      text: 'Applied',
    }
}

// 👉 Fetching Members
const fetchMembers = () => {
  teamListStore.fetchMemberList().then(response => {
    inviteMembers.value = response.data.inviteMembers
  }).catch(error => {
    console.error(error)
  })
}

// 👉 Fetching teams
const fetchTeams = () => {
  isProcessing.value = true
  teamListStore.fetchTeams({
    q: searchQuery.value,
    options: options.value,
  }).then(response => {
    teams.value = response.data.teams.data
    totalPage.value = response.data.totalPage
    totalUsers.value = response.data.totalUsers
    options.value.page = response.data.page
    isProcessing.value = false
  }).catch(error => {
    isProcessing.value = false
    console.log(error)
  })
}

const deleteTeam = id => {
  teamListStore.deleteTeam(id)
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

  //re-fetch teams
  fetchTeams()
}

onMounted(() => {
  fetchMembers()

  //fetchTeams()
})

watchEffect(fetchTeams)

const submitTeam = () => {
  isDisabled.value = true
  isLoading.value = true 
  
  teamListStore.addTeam({
    id: id.value,
    teamName: teamName.value,
    description: description.value,
    members: members.value,
  }).then(response => {
    isDialogVisible.value = false
    
    isDisabled.value = false
    isLoading.value = false
    validationErrors.value = {}
      
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = `success`
    isSnackbarVisible.value = true

    // Clear input fields
    teamName.value = ''
    description.value = ''
    members.value = []

    // refetch Team
    fetchTeams()
  })
    .catch(error => {
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
  defaultTitle.value = "Update Team"
  defaultButton.value = "Update"
  id.value = item.id,
  teamName.value = item.name,
  description.value = item.description,
  members.value = item.members.map(member => member.id),
  isDialogVisible.value = true
}

const deleteItem = item => {
  editedIndex.value = teams.value.indexOf(item)
  deleteId.value = item.id
  deleteDialog.value = true
}

const deleteItemConfirm = () => {
  teams.value.splice(editedIndex.value, 1)
  deleteTeam(deleteId.value)
  closeDelete()
}

const closeDelete = () => {
  deleteDialog.value = false
  editedIndex.value = -1
}

const onCloseDialog = () => {
  id.value = ''
  teamName.value = ''
  description.value = ''
  members.value = [],
  validationErrors.value = {}
  defaultTitle.value = 'Create New Team'
  defaultButton.value = 'Submit'
  isDialogVisible.value = false
}
</script>

<template>
  <div class="">
    <div class="pb-4 font-weight-bold text-h4">
      Manage Teams
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
                placeholder="Search Name"
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
                      v-if="can('manage', 'all')"
                      v-bind="props"
                      class="mr-2"
                    >
                      Create Team
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
                          md="12"
                        >
                          <AppTextField
                            v-model="teamName"
                            label="Team Name"
                          />
                          <span
                            v-if="validationErrors.teamName"
                            class="error"
                            style="color: red;"
                          >{{ validationErrors.teamName[0] }}</span>
                        </VCol>
                        <VCol
                          cols="12"
                          sm="12"
                          md="12"
                        >
                          <AppTextarea
                            v-model="description"
                            label="Description"
                            rows="2"
                            auto-grow
                          />
                          <span
                            v-if="validationErrors.description"
                            class="error"
                            style="color: red;"
                          >{{ validationErrors.description[0] }}</span>
                        </VCol>
                        <VCol cols="12">
                          <AppAutocomplete
                            v-model="members"
                            density="compact"
                            chips
                            closable-chips
                            multiple
                            :items="inviteMembers"
                            item-title="fullname"
                            item-value="id"
                            dense
                            label="Members"
                          >
                            <template #chip="{ props, item }">
                              <VChip
                                v-bind="props"
                                :prepend-avatar="item.raw.avatar_url"
                                :text="item.raw.fullname"
                              />
                            </template>

                            <template #item="{ props, item }">
                              <VListItem
                                v-bind="props"
                                :prepend-avatar="item?.raw?.avatar_url"
                                :title="item?.raw?.fullname"
                              />
                            </template>
                          </AppAutocomplete>
                          <span
                            v-if="validationErrors.members"
                            class="error"
                            style="color: red;"
                          >{{ validationErrors.members[0] }}</span>
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
                        @click="submitTeam"
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
          <VDataTableServer
            v-model:items-per-page="options.itemsPerPage"
            v-model:page="options.page"
            :items="teams"
            :items-length="totalUsers"
            :headers="filteredHeaders"
            class="text-no-wrap"
            show-select
            @update:options="options = $event"
          >
            <!-- team name -->
            <template #item.name="{ item }">
              <div class="d-flex align-center">
                <VAvatar
                  size="32"
                  :color="!item.raw.avatar ? resolveStatusVariant(item.raw.status).color : undefined"
                  :class="item.raw.avatar ? '' : 'v-avatar-light-bg primary--text'"
                  :variant="!item.raw.avatar ? 'tonal' : undefined"
                >
                  <VImg
                    v-if="item.raw.avatar"
                    :src="'./../../' + item.raw.avatar"
                  />
                  <span v-else>{{ avatarText(item.raw.name) }}</span>
                </VAvatar>
                <div class="d-flex flex-column ms-3">
                  <span class="d-block font-weight-medium text--primary text-truncate">{{ item.raw.name }}</span>
                </div>
              </div>
            </template>

            <!-- Members column -->
            <template #item.members="{ item }">
              <div class="v-avatar-group">
                <template
                  v-for="(member, index) in item.raw.members"
                  :key="index"
                >
                  <VAvatar
                    v-if="index < 3 || item.raw.members.length === 4"
                    size="32"
                    :variant="!member.avatar_url ? 'tonal' : undefined"
                  >
                    <VImg
                      v-if="member.avatar_url"
                      :src="member.avatar_url"
                    />
                    <span v-else>{{ avatarText(member.full_name) }}</span>
                  </VAvatar>
                </template>
                <VAvatar
                  v-if="item.raw.members.length > 4"
                  :color="$vuetify.theme.current.dark ? '#4A5072' : '#f6f6f7'"
                >
                  <span>+{{ item.raw.members.length - 3 }}</span>
                </VAvatar>
              </div>
            </template>

            <!-- Actions -->
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
                    <VListItem :to="{ name: 'apps-user-view-id', params: { id: item.raw.id } }">
                      <template #prepend>
                        <VIcon icon="tabler-eye" />
                      </template>

                      <VListItemTitle>View</VListItemTitle>
                    </VListItem>

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
          Are you sure you want to delete this item?
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

<route lang="yaml">
meta:
  action: read
  subject: teams
</route>
