<script setup>
import VueApexCharts from 'vue3-apexcharts'
import { useTheme } from 'vuetify'
import { getCallsChartConfig } from '@core/libs/apex-chart/apexCharConfig'

const vuetifyTheme = useTheme()
const expenseRationChartConfig = computed(() => getCallsChartConfig(vuetifyTheme.current.value))

const series = [
  85,
  16,
  50,
]
</script>

<template>
  <VueApexCharts
    type="donut"
    height="410"
    :options="expenseRationChartConfig"
    :series="series"
  />
</template>
