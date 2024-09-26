<script setup>
import { useLiveCallsStore } from "@/views/apps/dashboard/useLiveCallsStore"
import { getScatterChartConfig } from '@core/libs/apex-chart/apexCharConfig'
import { can } from "@layouts/plugins/casl"
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const liveCallsStore = useLiveCallsStore()
const vuetifyTheme = useTheme()
const scatterChartConfig = computed(() => getScatterChartConfig(vuetifyTheme.current.value, xAxisCategories))

const select_report = ref(['All Members'])
const membersforChart = ref()

const callData = {
  missed: [0, 0, 1, 2, 3, 4, 2],
  outbound: [0, 4, 1, 2, 3, 2, 1],
  inbound: [0, 1, 2, 2, 2, 3, 3],
}

const series = [
  {
    name: 'Outbound',
    data: callData.outbound,
  },
  {
    name: 'Inbound',
    data: callData.inbound,
  },
  {
    name: 'Missed',
    data: callData.missed,
  },
  
]

const xAxisCategories = [
  '2023-0',
  '2023-01',
  '2023-02',
  '2023-03',
  '2023-04',
  '2023-05',
  '2023-06',

  // Add more categories here based on your data
]

// 👉 Fetching Members for chart
const fetchMembersforChart = () => {
  liveCallsStore.fetchMembersforChart().then(response => {
    if(response.data.status){
      membersforChart.value = response.data.members
    }else{
      console.error(response?.data.message)
    }
  }).catch(error => {
    console.error(error)
  })
}

onMounted(() => {
  fetchMembersforChart()

})
</script>

//area
<template>
  <VCard>
    <VCardItem
      v-if="can('manage', 'all')"
      class="d-flex flex-wrap justify-space-between gap-4"
    >
      <template #append>
        <div class="d-flex align-center">
          <AppSelect
            v-model="select_report"
            :items="membersforChart"
            item-title="fullname"
            item-value="id"
            class="mr-5"
          >
            <template #selection="{ item }">
              <VChip>
                <VAvatar
                  start
                  :image="item.raw.avatar_url"
                />
                <span>{{ item.title }}</span>
              </VChip>
            </template>
          </AppSelect>
        </div>
      </template>
    </VCardItem>

    <VCardText>
      <VueApexCharts
        type="line"
        height="400"
        :options="scatterChartConfig"
        :series="series"
      />
    </VCardText>
  </VCard>
</template>
