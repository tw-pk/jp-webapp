<script setup>
import { paginationMeta } from '@/@fake-db/utils'
import { useRecentCallsStore } from "@/views/apps/recent-calls/useRecentCallsStore"
import { requiredValidator } from '@validators'
import { ref } from "vue"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const recentCallsStore = useRecentCallsStore()
const searchQuery = ref('')
const isDisabled = ref(false)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const isProcessing = ref(false)
const isProcessingNote = ref(false)
const call_sid = ref()
const error = ref(false)
const errorMessage = ref('')
const editDialog = ref(false)
const notes = ref()
const form = ref()
const calls = ref([])
const totalPage = ref(1)
const totalRecord = ref(0)
const items = ['Default', 'Outbound', 'Inbound', 'Missed', 'Voicemail']
const selectedItem = ref(items[0])
const callTrait = ref(null)
const callTraits = ['Queued', 'ringing', 'in-progress', 'canceled', 'completed', 'failed', 'busy', 'no-answer']
const dateRange = ref([new Date().toISOString(), new Date().toISOString()])
const members = ref([])
const member = ref(null)
const requestData = ref(null)


// headers
const headers = [
  {
    title: 'JOTPHONE NUMBER',
    key: 'teamdialer_number',
  },
  {
    title: 'NAME/NUMBER',
    key: 'number',
  },
  {
    title: 'CALL TRAITS',
    key: 'status',
    sortable: false,
  },
  {
    title: 'DATE & TIME',
    key: 'date',
  },
  {
    title: "DURATION",
    key: "duration",
  },
  {
    title: "NOTES",
    key: "notes",
  },
  {
    title: "RATING",
    key: "rating",
  },
  {
    title: "DISPOSITION",
    key: "disposition",
  },
  {
    title: "RECORD",
    key: "record",
  },
]

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

const fetchMemberList = async () => {
  try {
    const res = await recentCallsStore.fetchMemberList()
    if (res.data.status) {
      members.value = res.data.members
    } else {
      error.value = true
      errorMessage.value = res.data.message
      isProcessing.value = false
    }
  } catch (error) {
    error.value = true
    errorMessage.value = error.response?.data?.message || error.message
    isProcessing.value = false
  }
}

fetchMemberList()

const fetchRecentCalls = () => {
  isProcessing.value = true
  recentCallsStore.fetchRecentCalls({
    selectedItem: selectedItem.value,
    callTrait: callTrait.value,
    dateRange: dateRange.value,
    member: member.value,
    q: searchQuery.value,
    options: options.value,
  })
    .then(res => {
      if(res.data.status){
        error.value = false
        isProcessing.value = false
        calls.value = res.data.calls

        totalPage.value = res.data.totalPage
        totalRecord.value = res.data.totalRecord
        options.value.page = res.data.page        
      }
    })
    .catch(error => {
      error.value = true
      errorMessage.value = error.response.data.message
      isProcessing.value = false
    })
}

watchEffect(fetchRecentCalls)

const resolveUserRoleVariant = direction => {
  if (direction === 'outbound-api')
    return {
      color: 'info',
      icon: 'tabler-phone-call',
    }
  if (direction === 'inbound')
    return {
      color: 'error',
      icon: 'tabler-phone-off',
    }
  
  return {
    color: 'error',
    icon: 'tabler-phone-off',
  }
}

const addNote = () => {
  isDisabled.value = true
  isLoading.value = true
  recentCallsStore.addNote({
    call_sid: call_sid.value,
    notes: notes.value,
  }).then(response => {
    editDialog.value = false
    isDisabled.value = false
    isLoading.value = false
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = `success`
    isSnackbarVisible.value = true
  })
    .catch(error =>{
      isDisabled.value = false
      isLoading.value = false
      let errorMsg = ''

      // Error: Display validation errors
      if (error.response && error.response.data && error.response.data.errors) {
        const validationErrors = error.response.data.errors

        errorMsg = Object.values(validationErrors).flat().join('\n')
      } else {
        console.log('An error occurred:', error.message)
        errorMsg = error.message
      }
      snackbarMessage.value = errorMsg
      snackbarActionColor.value = 'error'
      isSnackbarVisible.value = true
    })
}

const submitNotes = () => {
  form.value.validate().then(isValid => {
    if(isValid.valid === true) {
      form.value.resetValidation()
      addNote()
    }
  })
}

// 👉 Fetching note
const fetchNote = callSid => {
  isProcessingNote.value = true
  call_sid.value = callSid,
  recentCallsStore.fetchNote({
    sid: callSid,
  }).then(response => {
    notes.value = response.data.note
    isProcessingNote.value = false
  }).catch(error => {
    isProcessingNote.value = false
    console.log(error)
  })
}

const editItem = callSid => {
  fetchNote(callSid)
  editDialog.value = true
}
</script>

<template>
  <div class="">
    <div class="pb-4 font-weight-bold text-h4">
      Recent Calls
    </div>

    <div
      v-if="errorMessage"
      class="my-3"
    >
      <VAlert
        density="compact"
        color="error"
        variant="tonal"
        closable
      >
        {{ errorMessage }}
      </VAlert>
    </div>

    <VRow class="match-height">
      <VCol cols="12">
        <VCard>
          <VRow
            justify="space-between"
            class="__align-items-center pa-4 __border-bottom-light"
          >
            <VCol
              cols="12"
              md="3"
            >
              <AppTextField
                v-model="searchQuery"
                placeholder="Search Number"
                density="compact"
                append-inner-icon="tabler-search"
                single-line
                hide-details
                dense
                outlined
              />
            </VCol>
            <VCol
              cols="12"
              md="9"
              class="d-flex flex-col flex-wrap"
            >
              <VRow class="__align-items-center justify-md-end mx-1 flex-md-nowrap flex-wrap">
                <VCol
                  cols="2"
                  md="2"
                >
                  <AppCombobox
                    v-model="selectedItem"
                    :items="items"
                  />
                </VCol>
                <VCol
                  cols="3"
                  md="3"
                >
                  <!-- 👉 outlined variant -->
                  <VAutocomplete
                    v-model="callTrait"
                    label="Select a call trait"
                    :items="callTraits"
                    multiple
                  />
                </VCol>
                <VCol
                  cols="3"
                  md="3"
                >
                  <!-- 👉 outlined variant -->
                  <AppDateTimePicker
                    v-model="dateRange"
                    :config="{ mode: 'range', dateFormat: 'F j, Y' }"
                  />
                </VCol>
                <VCol
                  cols="3"
                  md="3"
                >
                  <!-- 👉 outlined variant -->
                  <VAutocomplete
                    v-model="member"
                    label="Select members"
                    :items="members"
                    item-title="fullname"
                    item-value="fullname"
                  />
                </VCol>
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
              height="3"
              bg-color="primary"
              color="primary"
              :rounded="false"
            />
          </template>
  
          <VDataTableServer
            v-model:items-per-page="options.itemsPerPage"
            v-model:page="options.page"
            :items="calls"
            :items-length="totalRecord"
            :headers="headers"
            class="text-no-wrap"
            show-select
            @update:options="options = $event"
          >
            <!-- JotPhone Number -->
            <template #item.teamdialer_number="{ item }">
              <div class="d-flex align-center gap-4">
                <VIcon
                  :size="20"
                  :color="resolveUserRoleVariant(item.raw.direction).color"
                  :icon="resolveUserRoleVariant(item.raw.direction).icon"
                />
                <span class="text-capitalize">{{ item.raw.teamdialer_number }}</span>
              </div>
            </template>
    
            <!-- Add Notes -->
            <template #item.notes="{ item }">
              <div class="d-flex align-center gap-4">
                <VBtn
                  variant="plain"
                  @click="editItem(item.raw.call_sid)"
                >
                  + Add Note
                </VBtn>
              </div>
            </template>

            <!-- To status -->
            <template #item.status="{ item }">
              <VChip
                label
                color="success"
              >
                {{ item.raw.status }}
              </VChip>
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
                  :total-visible="$vuetify.display.xs ? 1 : 5"
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
      
      <!-- 👉 Edit Dialog  -->
      <VDialog
        v-model="editDialog"
        max-width="600px"
      >
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="editDialog = !editDialog" />
        <!-- Dialog Content -->
        <VCard>
          <VForm
            ref="form"
            lazy-validation
          >
            <VCardText>
              <VRow justify="center">
                <h3 class="font-weight-bold text-h3">
                  Add Note
                </h3>
              </VRow>
              <VContainer>
                <VRow>
                  <!-- full_name -->
                  <VCol cols="12">
                    <AppTextarea
                      v-model="notes"
                      label="Write Notes"
                      :rules="[requiredValidator]"
                      required
                    />
                    <!-- Show the loader -->
                    <template v-if="isProcessingNote">
                      <VProgressLinear
                        indeterminate
                        height="3"
                        bg-color="primary"
                        color="primary"
                        :rounded="false"
                      />
                    </template>
                  </VCol>
                </VRow>
              </VContainer>
            </VCardText>

            <VCardText class="d-flex justify-center flex-wrap gap-3">
              <VBtn
                :disabled="isDisabled"
                :loading="isLoading"
                @click.prevent="submitNotes"
              >
                Save
              </VBtn>
              <VBtn
                variant="tonal"
                color="secondary"
                @click="editDialog = false"
              >
                Close
              </VBtn>
            </VCardText>
          </VForm>
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
.flex-col {
  flex-direction: column;
}
</style>
