<script setup>
import { paginationMeta } from '@/@fake-db/utils'
import { useAnalysisDashStore } from "@/views/apps/dashboard/useAnalysisDashStore"
import { avatarText } from "@core/utils/formatters"
import { ref } from "vue"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const analysisDashStore = useAnalysisDashStore()
const isProcessing = ref(false)
const searchQuery = ref('')
const members = ref([])
const totalPage = ref(1)
const totalRecord = ref(0)

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
    title: 'Name',
    key: 'fullName',
    sortable: false,
  },
  {
    title: 'Last Login',
    key: 'last_login_at',
    sortable: false,
  },
  {
    title: 'Outbound',
    key: 'outboundCalls',
    sortable: false,
  },
  {
    title: 'Inbound',
    key: 'inboundCalls',
    sortable: false,
  },
]

// 👉 Fetching members analysis
const fetchMembers = async () => {
  isProcessing.value = true
  try{
    const { data } = await analysisDashStore.fetchMembers({
      q: searchQuery.value,
      options: options.value,
    })

    if(data.status){
      isProcessing.value = false
      members.value = data.teamMembers
      totalPage.value = data.totalPage
      totalRecord.value = data.totalRecord
      options.value.page = data.page
    }
  }catch (error){
    //console.log(error.response)
    isProcessing.value = false
  }
}

watchEffect(fetchMembers)

const resolveStatusColor = {
  'Online': 'success',
  'Away': 'warning',
  'Offline': 'secondary',
  'In Meeting': 'error',
}
</script>

<template>
  <VCard>
    <VCardText>
      <VRow
        justify="space-between"
        class="__align-items-center"
      >
        <VCol cols="6">
          <h5 class="font-weight-bold text-h5">
            Team Member Analysis
          </h5>
        </VCol>
        <VCol
          cols="6"
          md="6"
        >
          <AppTextField
            v-model="searchQuery"
            density="compact"
            placeholder="Search Team Member"
            append-inner-icon="tabler-search"
            single-line
            hide-details
            dense
            outlined
          />
        </VCol>
      </VRow>
      
      <VCol
        cols="12"
        md="12"
        class="pa-0 pt-3"
      >
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
          :items="members"
          :items-length="totalRecord"
          :headers="headers"
          class="text-no-wrap"
          show-select
        >
          <!-- number and profile -->
          <template #item.fullName="{ item }">
            <div class="d-flex align-center">
              <VAvatar
                size="28"
                :color="!item.raw?.avatar ? 'primary' : undefined"
                :class="item.raw?.avatar ? '' : 'v-avatar-light-bg primary--text'"
                :variant="!item.raw?.avatar ? 'tonal' : undefined"
                class="me-3"
              >
                <VImg
                  v-if="item.raw?.avatar"
                  :src="item.raw?.avatar"
                />
                <span v-else>{{ avatarText(item.raw?.fullName) }}</span>
              </VAvatar>
              <div class="d-flex flex-column">
                <h5>
                  {{ item.raw?.fullName }}
                </h5>
                <span class="text-sm text-medium-emphasis text-success">
                  <VBtn
                    color="success"
                    variant="tonal"
                    size="x-small"
                  >
                    <small>Available</small>
                  </VBtn>
                </span>
              </div>
            </div>
          </template>

          <template #item.last_login_at="{ item }">
            <VBadge
              dot
              location="start center"
              offset-x="2"
              :color="resolveStatusColor['Online']"
              class="me-3"
            >
              <h6 class="ms-4">
                Online
              </h6>
            </VBadge>
            <div class="d-flex flex-column">
              <span class="text-sm">{{ item.raw?.last_login_at }}</span>
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
      </VCol>
    </VCardText>
  </VCard>
</template>

