<script setup>
import { useLiveCallsStore } from "@/views/apps/dashboard/useLiveCallsStore"
import { getScatterChartConfig } from '@core/libs/apex-chart/apexCharConfig'
import { can } from "@layouts/plugins/casl"
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const liveCallsStore = useLiveCallsStore()
const vuetifyTheme = useTheme()
const memberId = ref('All Members')
const membersforChart = ref()
const xAxisCategories = ref([])
const apexChartReport = ref([])

// 👉 Fetching apex chart report
const fetchApexChartReport = () => {
  liveCallsStore.fetchApexChartReport({
    member: memberId.value,
  }).then(response => {
    xAxisCategories.value = response?.data?.yearMonths || []
    apexChartReport.value = response?.data?.series || []
  }).catch(error => {
    console.error(error)
  })
}

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
  fetchApexChartReport()
  fetchMembersforChart()
})

watch(memberId, newValue => {
  if (newValue) {
    fetchApexChartReport()
  }
})

const scatterChartConfig = computed(() => getScatterChartConfig(vuetifyTheme.current.value, xAxisCategories.value))
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
            v-model="memberId"
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
        :series="apexChartReport"
      />
    </VCardText>
  </VCard>
</template>
