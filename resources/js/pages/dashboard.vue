<script setup>
import DashboardRecentCalls from "@/pages/dashboards/components/DashboardRecentCalls.vue"
import DashboardStatistics from "@/pages/dashboards/components/DashboardStatistics.vue"


import ApexChartExpenseRatio from "@/views/charts/apex-chart/ApexChartExpenseRatio.vue"
import ApexChartReportData from '@/views/charts/apex-chart/ApexChartReportData.vue'
import { useTheme } from 'vuetify'

import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'

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

const select_report = ref(['John Doe'])

const vuetifyTheme = useTheme()
const currentTheme = vuetifyTheme.current.value.colors

const statisticsVertical = {
  title: 'Revenue Generated',
  color: 'success',
  icon: 'tabler-credit-card',
  stats: '97.5k',
  height: 97,
  series: [{
    data: [
      300,
      350,
      330,
      380,
      340,
      400,
      380,
    ],
  }],
  chartOptions: {
    chart: {
      height: 110,
      type: 'area',
      parentHeightOffset: 0,
      toolbar: { show: false },
      sparkline: { enabled: true },
    },
    tooltip: { enabled: false },
    markers: {
      colors: 'transparent',
      strokeColors: 'transparent',
    },
    grid: { show: false },
    colors: [currentTheme.success],
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 0.8,
        opacityFrom: 0.6,
        opacityTo: 0.1,
      },
    },
    dataLabels: { enabled: false },
    stroke: {
      width: 2,
      curve: 'smooth',
    },
    xaxis: {
      show: true,
      lines: { show: false },
      labels: { show: false },
      stroke: { width: 0 },
      axisBorder: { show: false },
    },
    yaxis: {
      stroke: { width: 0 },
      show: false,
    },
  },
}

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
            <ApexChartExpenseRatio />
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
                1
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
                class=" text-center __border-right"
              >
                <h5 class="text-h1 text-center">
                  2
                </h5>
              </VCol>
              <VCol
                cols="6"
                class=" text-center"
              >
                <h5 class="text-h1 text-center">
                  3
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
              <VTabs class="w-75">
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
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="6">
        <!--        <DashboardNAnalysis class="h-100" /> -->
      </VCol>

      <VCol cols="6">
        <!--        <DashboardTMAnalysis class="h-100" /> -->
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
  title: Team Dialer - Dashboard
</route>
