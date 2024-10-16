<script setup>
import DashboardNAnalysis from "@/pages/dashboards/components/DashboardNAnalysis.vue"
import DashboardRecentCalls from "@/pages/dashboards/components/DashboardRecentCalls.vue"
import DashboardStatistics from "@/pages/dashboards/components/DashboardStatistics.vue"
import DashboardTMAnalysis from "@/pages/dashboards/components/DashboardTMAnalysis.vue"
import { useLiveCallsStore } from "@/views/apps/dashboard/useLiveCallsStore"
import ApexChartReportData from '@/views/charts/apex-chart/ApexChartReportData.vue'
import { getCallsChartConfig } from '@core/libs/apex-chart/apexCharConfig'
import { can } from "@layouts/plugins/casl"
import { computed, onMounted, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const liveCallsStore = useLiveCallsStore()
const vuetifyTheme = useTheme()
const currentTheme = vuetifyTheme.current.value.colors
const expenseRationChartConfig = computed(() => getCallsChartConfig(vuetifyTheme.current.value))
const totalLiveCalls = ref(0)
const totalMissed = ref(0)
const totalOutboundCalls = ref(0)
const totalInboundCalls = ref(0)
const totalCompletedCalls = ref(0)
const series = ref([])
const userHaveNumber = ref(false)

const moreList = [
  {
    title: 'Refresh',
    value: 'refresh',
  },
  {
    title: 'Download',
    value: 'Download',
  },
  {
    title: 'View All',
    value: 'View All',
  },
]

// headers
const headers = [
  {
    title: 'Call Sid',
    key: 'callSid',
    sortable: false,
  },
  {
    title: 'From',
    key: 'from',
    sortable: false,
  },
  {
    title: 'To',
    key: 'to',
    sortable: false,
  },
  {
    title: 'Call Time',
    key: 'callTime',
    sortable: false,
  },
]

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

const fetchLiveCalls = async () => {
  try {
    const liveCallsResponse = await liveCallsStore.fetchLiveCalls()
    if (liveCallsResponse.data) {
      totalLiveCalls.value = liveCallsResponse.data.totalLiveCalls
      totalOutboundCalls.value = liveCallsResponse.data.totalOutboundCalls
      totalInboundCalls.value = liveCallsResponse.data.totalInboundCalls
      totalCompletedCalls.value = liveCallsResponse.data.totalCompletedCalls
      totalMissed.value = liveCallsResponse.data.totalMissed
      userHaveNumber.value = liveCallsResponse.data.userHaveNumber

      series.value = [
        totalOutboundCalls.value,
        totalInboundCalls.value,
        totalMissed.value,
      ]
    }
  } catch (error) {
    console.error('Error fetching data:', error)
  }
}

onMounted(fetchLiveCalls)
      
//watchEffect(fetchLiveCalls)

const userData = JSON.parse(localStorage.getItem('userData') || '{}')
const userRole = (userData && userData.role) ? userData.role : null
</script>

<template>
  <div class="">
    <div class="pb-4 font-weight-bold text-h4">
      Dashboard
    </div>
    <VAlert
      v-if="userHaveNumber && userRole=='InactiveMember'"
      border="start"
      color="primary"
      variant="tonal"
      class="mb-5"
    >
      Success! Thank you very much for using our system. You have been made an admin, please logout and login again get admin dashboard.
    </VAlert>
    <VRow class="match-height">
      <DashboardStatistics class="h-100" />

      <VCol cols="12">
        <ApexChartReportData class="h-100" />
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VCard
          title="Live Calls"
          max-height="515"
        >
          <template #append>
            <div class="me-n2">
              <MoreBtn :menu-list="moreList" />
            </div>
          </template>
          <VCardText>
            <VRow justify="center">
              <h5 class="text-h1 text-center">
                {{ totalLiveCalls }}
              </h5>
            </VRow>
            <VRow
              justify="center"
              class="pt-3"
            >
              <VCol
                cols="6"
                class="__light_primary text-center"
              >
                <span class="text-md-h6 __text-primary text-center">Outbound</span>
              </VCol>
              <VCol
                cols="6"
                class="__light_success text-center"
              >
                <span class="text-md-h6 __text-success text-center">Answered</span>
              </VCol>
            </VRow>
            <VRow
              justify="center"
              class="pl-3 pr-3 h-50"
            >
              <VCol
                cols="6"
                class="text-center __border-right"
              >
                <h5 class="text-h1 text-center">
                  {{ totalOutboundCalls }}
                </h5>
              </VCol>
              <VCol
                cols="6"
                class=" text-center"
              >
                <h5 class="text-h1 text-center">
                  {{ totalCompletedCalls }}
                </h5>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="6"
      >
        <VCard max-height="515">
          <VCardText>
            <VueApexCharts
              v-if="series.length > 0"
              type="donut"
              height="410"
              :options="expenseRationChartConfig"
              :series="series"
            />
          </VCardText>
        </VCard>
      </VCol>
 
      <VCol
        cols="12"
        :sm="can('manage', 'all') ? 6 : 12"
        :lg="can('manage', 'all') ? 6 : 12"
      >
        <DashboardNAnalysis class="h-100" />
      </VCol>
      
      <VCol
        v-if="can('manage', 'all')"
        cols="12" 
        sm="6"
        lg="6"
      >
        <DashboardTMAnalysis class="h-100" />
      </VCol>
      
      <VCol cols="12">
        <DashboardRecentCalls class="h-100" />
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
@use "@core-scss/template/pages/page-auth.scss";

#apex-chart-wrapper {
  .v-card-item__append {
    padding-inline-start: 0;
  }
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: dashboard-analytics
</route>
