<script setup>
import { paginationMeta } from '@/@fake-db/utils'
import { useAnalysisDashStore } from "@/views/apps/dashboard/useAnalysisDashStore"
import { ref } from "vue"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const analysisDashStore = useAnalysisDashStore()
const isProcessing = ref(false)
const searchQuery = ref('')
const numbers = ref([])
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
    title: 'Number',
    key: 'number',
    sortable: false,
  },
  {
    title: 'Outbound',
    key: 'outbound',
    sortable: false,
  },
  {
    title: 'Inbound',
    key: 'inbound',
    sortable: false,
  },
  {
    title: 'Missed',
    key: 'missed',
    sortable: false,
  },
]

// 👉 Fetching numbers analysis
const fetchNumbers = async() => {
  isProcessing.value = true

  try{
    const { data } = await analysisDashStore.fetchNumbers({ q: searchQuery.value, options: options.value })
    if(data.status){
      isProcessing.value = false
      numbers.value = data.numbers
      totalPage.value = data.totalPage
      totalRecord.value = data.totalRecord
      options.value.page = data.page
    }
  }catch (error){
    console.log(error.response)
    isProcessing.value = false
  }
}

watchEffect(fetchNumbers)
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
            Number Analysis
          </h5>
        </VCol>
        <VCol
          cols="6"
          md="6"
        >
          <AppTextField
            v-model="searchQuery"
            density="compact"
            placeholder="Search Phone Numbers"
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
          :items="numbers"
          :items-length="totalRecord"
          :headers="headers"
          class="text-no-wrap"
          show-select
        >
          <!-- number and profile -->
          <template #item.number="{ item }">
            <div class="d-flex align-center">
              <VAvatar
                rounded="lg"
                size="24"
                class="me-3"
              >
                <VImg
                  v-if="item.raw?.flag_url"
                  :src="item.raw?.flag_url"
                />
              </VAvatar>
              
              <div class="d-flex flex-column">
                <h5>
                  {{ item.raw?.number }}
                </h5>
                <span class="text-sm text-medium-emphasis">
                  <small>{{ item.raw?.friendly_name }}</small>
                </span>
              </div>
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

