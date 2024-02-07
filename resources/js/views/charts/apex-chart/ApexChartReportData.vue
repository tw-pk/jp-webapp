<script setup>
import { getScatterChartConfig } from '@core/libs/apex-chart/apexCharConfig'
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()
const scatterChartConfig = computed(() => getScatterChartConfig(vuetifyTheme.current.value, xAxisCategories))

const callData = {
  missed: [0, 0, 1, 2, 3, 4, 2],
  outbound: [0, 4, 1, 2, 3, 2, 1],
  inbound: [0, 1, 2, 2, 2, 3, 3],
}

const series = [
  {
    name: 'Missed',
    data: callData.missed,
  },
  {
    name: 'Outbound',
    data: callData.outbound,
  },
  {
    name: 'Inbound',
    data: callData.inbound,
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
</script>

//area
<template>
  <VueApexCharts
    type="line"
    height="400"
    :options="scatterChartConfig"
    :series="series"
  />
</template>
