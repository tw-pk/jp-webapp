<script setup>
import { paginationMeta } from "@/@fake-db/utils"
import { useRecentCallsStore } from "@/views/apps/recent-calls/useRecentCallsStore"
import { ref } from "vue"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const router = useRoute()
const isProcessing = ref(false)
const recentCallsStore = useRecentCallsStore()
const error = ref(false)
const errorMessage = ref('')
const searchQuery = ref('')
const isDisabled = ref(false)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const totalPage = ref(1)
const totalRecord = ref(0)
const form = ref()
const calls = ref([])
const currentTab = ref(0)

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
    title: 'DATE & TIME',
    key: 'date',
  },
  {
    title: "DURATION",
    key: "duration",
  },
]

const tabTitles = [
  {
    icon: 'tabler-phone-call',
    title: 'All',
    
  },
  {
    icon: 'tabler-phone-outgoing',
    title: 'Outbound',
    
  },
  {
    icon: 'tabler-phone-incoming',
    title: 'Inbound',
    
  },
  {
    icon: 'tabler-phone-pause',
    title: 'Missed',
    
  },
  {
    icon: 'tabler-record-mail',
    title: 'Voicemail',
    
  },
]

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

const fetchRecentCallsContact = () => {
  isProcessing.value = true

  const selectedTabTitle = tabTitles[currentTab.value]

  recentCallsStore.fetchRecentCallsContact({
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
    .catch(error => {
      error.value = true
      errorMessage.value = error.response.data.message
      isProcessing.value = false
    })
}

watchEffect(fetchRecentCallsContact)

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
</script>

<template>
  <VRow>
    <VCol cols="12">
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
      <VRow>
        <VCol cols="12">
          <VCard>
            <div class="__dashboard__recent-calls-header pa-4 __border-bottom-light">
              <div class="__dashboard__header-title">
                <h5 class="font-weight-bold text-h5">
                  Call Logs
                </h5>
              </div>
      
              <div class="__dashboard__header-tabs">
                <VTabs
                  v-model="currentTab"
                  class="v-tabs-pill"
                >
                  <VTab
                    v-for="tab in tabTitles"
                    :key="tab.icon"
                    class="me-5"
                  >
                    <VIcon
                      :size="18"
                      :icon="tab.icon"
                      class="me-1"
                    />
                    <span>{{ tab.title }}</span>
                  </VTab>
                </VTabs>
              </div>
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
      </VRow>
    </VCol>
  </VRow>
</template>


<style scoped lang="scss">
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
