<script setup>
const statistics = [
  {
    title: 'Call Made',
    stats: '4',
    detailedStats: '18min 30sec',
    icon: 'tabler-chart-pie-2',
    color: 'primary',
    tonal: 'warning',
    background: '__light_primary',
  },
  {
    title: 'Call answered',
    stats: '2',
    detailedStats: '10min 30sec',
    icon: 'tabler-chart-pie-2',
    color: 'primary',
    tonal: 'info',
    background: '__light_primary',
  },
  {
    title: 'Avg answer time',
    stats: '9sec',
    icon: 'tabler-chart-pie-2',
    color: 'primary',
    tonal: 'success',
    background: '__light_primary',
  },
  {
    title: 'Call outside office hours',
    stats: '4',
    icon: 'tabler-chart-pie-2',
    color: 'primary',
    tonal: 'warning',
    background: '__light_primary',
  },
  {
    title: 'Call not picked by agent',
    stats: '2',
    icon: 'tabler-chart-pie-2',
    color: 'primary',
    tonal: 'error',
    background: '__light_primary',
  },
  {
    title: 'Calls abandoned before ringing',
    stats: '0',
    icon: 'tabler-chart-pie-2',
    color: 'primary',
    tonal: 'warning',
    background: '__light_primary',
  },
]
</script>

<template>
  <VCol
    v-for="item in statistics"
    :key="item.title"
    cols="12"
    sm="4"
    lg="4"
  >
    <VCard class="pa-0">
      <div class="d-flex flex-row align-center pa-6">
        <VAvatar
          variant="tonal"
          :color="item.tonal"
          size="42"
        >
          <VIcon
            :icon="item.icon"
            :color="item.color"
          />
        </VAvatar>

        <div class="d-flex flex-column justify-end w-100">
          <span class="text-h3 font-weight-bold text-end">{{ item.stats }}</span>
          <span class="text-sm text-end">
            {{ item.detailedStats }}
          </span>
          <span class="text-sm text-end">
            {{ item.title }}
          </span>
        </div>
      </div>
    </VCard>
  </VCol>
</template>
