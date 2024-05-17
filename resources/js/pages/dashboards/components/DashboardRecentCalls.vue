<script setup>
import { paginationMeta } from '@/@fake-db/utils'
import { useRecentCallsDashStore } from "@/views/apps/recent-calls/useRecentCallsDashStore"
import { requiredValidator } from '@validators'
import { ref } from "vue"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const recentCallsDashStore = useRecentCallsDashStore()
const currentTab = ref(0)
const route = useRoute()
const router = useRouter()
const searchQuery = ref('')
const error = ref(false)
const errorMessage = ref('')
const isProcessing = ref(false)
const isProcessingNote = ref(false)
const calls = ref([])
const totalPage = ref(1)
const totalRecord = ref(0)
const editDialog = ref(false)
const notes = ref()
const isDisabled = ref(false)
const isLoading = ref(false)
const call_sid = ref()
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const form = ref()

//const members = ['All members']
const members = ref([])
const member = ref(null)

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

// headers
const headers = [
  {
    title: 'JOTPHONE NUMBER',
    key: 'teamdialer_number',
    sortable: false,
  },
  {
    title: 'NAME/NUMBER',
    key: 'number',
    sortable: false,
  },
  {
    title: 'CALL TRAITS',
    key: 'status',
    sortable: false,
  },
  {
    title: 'DATE & TIME',
    key: 'date',
    sortable: false,
  },
  {
    title: "DURATION",
    key: "duration",
    sortable: false,
  },
  {
    title: "NOTES",
    key: "notes",
    sortable: false,
  },
  {
    title: "RATING",
    key: "rating",
    sortable: false,
  },
  {
    title: "DISPOSITION",
    key: "disposition",
    sortable: false,
  },
  {
    title: "RECORD",
    key: "record",
    sortable: false,
  },
]

const tabTitles = ['all', 'outbound-dial', 'inbound', 'missed', 'voicemail']

const resolveUserRoleVariant = call => {
  if (call.direction === 'outbound-api' || call.direction === 'outbound-dial')
    return {
      color: 'success',
      icon: 'tabler-phone-outgoing',
    }
  if (call.direction === 'inbound')
    return {
      color: 'error',
      icon: 'tabler-phone-incoming',
    }
}

// 👉 Fetching recent calls
const fetchRecentCalls = () => {
  isProcessing.value = true

  const selectedTabTitle = tabTitles[currentTab.value]

  recentCallsDashStore.fetchRecentCalls({
    callType: selectedTabTitle,
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
    .catch(errors => {
      error.value = true
      errorMessage.value = errors.response.data.message ? errors.response.data.message : errors.message
      isProcessing.value = false
    })
}

watchEffect(fetchRecentCalls)

watch(options, value => {
  console.log(value)
})

const addNote = () => {
  isDisabled.value = true
  isLoading.value = true
  recentCallsDashStore.addNote({
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
  recentCallsDashStore.fetchNote({
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

onMounted(async () => {
  await recentCallsDashStore.fetchMemberList()
    .then(res => {
      if(res.data.status){
        members.value = res.data.members
      }else{
        console.log(res.data.message)
      }
    })
})
</script>

<template>
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
  <VCard>
    <div class="__dashboard__recent-calls-header pa-4 __border-bottom-light">
      <div class="__dashboard__header-title">
        <h5 class="font-weight-bold text-h5">
          Recent Calls
        </h5>
      </div>
      
      <div class="__dashboard__header-tabs">
        <VTabs
          v-model="currentTab"
          class="v-tabs-pill"
        >
          <VTab>All</VTab>
          <VTab>Outbound</VTab>
          <VTab>Inbound</VTab>
          <VTab>Missed</VTab>
          <VTab>Voicemail</VTab>
        </VTabs>
      </div>
      <VCol
        cols="2"
        md="2"
      >
        <VAutocomplete
          v-model="member"
          label="Select members"
          :items="members"
          item-title="fullname"
          item-value="fullname"
        />
      </VCol>
      <div class="__dashboard__header-search">
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
      </div>
    </div>
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
    >
      <!-- JotPhone Number -->
      <template #item.teamdialer_number="{ item }">
        <div class="d-flex align-center gap-4">
          <VIcon
            :size="20"
            :color="resolveUserRoleVariant(item.raw)?.color || 'default'"
            :icon="resolveUserRoleVariant(item.raw)?.icon || 'default-icon'"
          />
          <span class="text-capitalize">{{ item.raw?.teamdialer_number || 'N/A' }}</span>
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

        <div class="d-flex align-center justify-center justify-sm-space-between flex-wrap gap-3 pa-5 pt-3">
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
  </VCard>
</template>

<style scoped lang="css">
@use "@core-scss/template/pages/page-auth.scss";

.v-tabs-pill {
  font-size: 0.8125rem;
  padding-block: 0.2rem;
  padding-inline: 0.5rem;
}

.v-tab {
  block-size: 30px;
  line-height: 1.1;
  padding-block: 0.1rem;
  padding-inline: 0.3rem;
}

.v-tabs-pill .v-tab__indicator {
  block-size: 1px;
}
</style>
