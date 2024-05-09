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

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

const fetchRecentCalls = () => {
  isProcessing.value = true
  recentCallsStore.fetchRecentCalls({
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
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VRow>
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
        <VCol cols="12">
          <VCard title="Call Logs">
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

</style>
