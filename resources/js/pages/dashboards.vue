<script setup>
//import DashboardNAnalysis from "@/pages/dashboards/components/DashboardNAnalysis.vue"
import DashboardRecentCalls from "@/pages/dashboards/components/DashboardRecentCalls.vue"
import DashboardStatistics from "@/pages/dashboards/components/DashboardStatistics.vue"

//import DashboardTMAnalysis from "@/pages/dashboards/components/DashboardTMAnalysis.vue"
import { useLiveCallsStore } from "@/views/apps/dashboard/useLiveCallsStore"
import ApexChartReportData from '@/views/charts/apex-chart/ApexChartReportData.vue'
import { getCallsChartConfig } from '@core/libs/apex-chart/apexCharConfig'
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'
import { computed, onMounted, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const liveCallsStore = useLiveCallsStore()
const select_report = ref(['John Doe'])
const vuetifyTheme = useTheme()
const currentTheme = vuetifyTheme.current.value.colors
const expenseRationChartConfig = computed(() => getCallsChartConfig(vuetifyTheme.current.value))
const totalLiveCalls = ref(0)
const totalMissed = ref(0)
const totalOutboundCalls = ref(0)
const totalInboundCalls = ref(0)
const currentTab = ref(0)
const liveCallsPast = ref([])
const totalPage = ref(1)
const totalRecord = ref(0)
const series = ref([])

const items = [
  {
    name: 'John Doe',
    avatar: avatar1,
  },
  {
    name: 'Ali Connors',
    avatar: avatar2,
  },
  {
    name: 'Trevor Hansen',
    avatar: avatar3,
  },
]

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

const tabTitles = ['live', 'recent', 'queue']

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  groupBy: [],
  search: undefined,
})


const fetchData = async () => {
  try {
    const [liveCallsResponse, liveCallsPastResponse] = await Promise.all([
      liveCallsStore.fetchLiveCalls(),
      liveCallsStore.fetchLiveCallsPast({
        callType: tabTitles[currentTab.value],
        options: options.value,
      }),
    ])

    // liveCallsResponse
    if (liveCallsResponse.data) {
      totalLiveCalls.value = liveCallsResponse.data.totalLiveCalls
      totalOutboundCalls.value = liveCallsResponse.data.totalOutboundCalls
      totalInboundCalls.value = liveCallsResponse.data.totalInboundCalls
      totalMissed.value = liveCallsResponse.data.totalMissed

      series.value = [
        totalOutboundCalls.value, // outbound
        totalInboundCalls.value,  // inbound
        totalMissed.value,        // missed
      ]
    }

    //liveCallsPastResponse
    if (liveCallsPastResponse.data && liveCallsPastResponse.data.status) {
      liveCallsPast.value = liveCallsPastResponse.data.liveCallsPast
      totalPage.value = liveCallsPastResponse.data.totalPage
      totalRecord.value = liveCallsPastResponse.data.totalRecord
      options.value.page = liveCallsPastResponse.data.page
    }
  } catch (error) {
    console.error('Error fetching data:', error)
  }
}

onMounted(fetchData)

//watchEffect(fetchData)
</script>

<template>
  <div class="">
    <div class="pb-4 font-weight-bold text-h4">
      Dashboard
    </div>
    <VRow class="match-height">
      <VCol
        cols="12"
        md="12"
      >
        <DashboardStatistics class="h-100" />
      </VCol>

      <VCol
        cols="12"
        md="4"
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

      <VCol cols="8">
        <VCard>
          <VCardItem class="d-flex flex-wrap justify-space-between gap-4">
            <template #append>
              <div class="d-flex align-center">
                <AppSelect
                  v-model="select_report"
                  :items="items"
                  item-title="name"
                  item-value="name"
                  class="mr-5"
                >
                  <template #selection="{ item }">
                    <VChip>
                      <VAvatar
                        start
                        :image="item.raw.avatar"
                      />
                      <span>{{ item.title }}</span>
                    </VChip>
                  </template>
                </AppSelect>
                <VBtn>
                  Download Report
                </VBtn>
              </div>
            </template>
          </VCardItem>

          <VCardText>
            <ApexChartReportData />
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="4"
      >
        <VCard
          title="Live Calls"
          max-height="300"
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
              class="pl-3 pr-3 pt-3"
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
                  {{ totalInboundCalls }}
                </h5>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="8">
        <VCard>
          <VCardText class="d-flex flex-column gap-4">
            <!-- Default -->
            <div class="d-flex flex-row justify-space-between">
              <VTabs
                v-model="currentTab"
                class="w-75"
              >
                <VTab>Live Calls</VTab>
                <VTab>Recent</VTab>
                <VTab>Calls in Queue</VTab>
              </VTabs>
              <VBtn
                color="secondary"
                class="justify-end"
              >
                Past 30 Minutes
              </VBtn>
            </div>
            <VDataTableServer
              v-model:items-per-page="options.itemsPerPage"
              v-model:page="options.page"
              :items="liveCallsPast"
              :items-length="totalRecord"
              :headers="headers"
              class="text-no-wrap"
              show-select
            />
          </VCardText>
        </VCard>
      </VCol>
      
      <!--
        <VCol cols="6">
        <DashboardNAnalysis class="h-100" />
        </VCol>
      -->
      
      <!--
        <VCol cols="6">
        <DashboardTMAnalysis class="h-100" />
        </VCol>
      -->
      
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
