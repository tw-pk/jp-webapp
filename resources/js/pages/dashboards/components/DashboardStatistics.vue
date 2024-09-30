<script setup>
import { useLiveCallsStore } from "@/views/apps/dashboard/useLiveCallsStore"

const liveCallsStore = useLiveCallsStore()

const statistics = ref([])

// 👉 Fetching Statistics
const fetchStatistics = () => {
  liveCallsStore.fetchStatistics()
    .then(response => {
      statistics.value = response?.data?.statistics
    })
    .catch(error => {
      console.error(error)
    })
}

onMounted(() => {
  fetchStatistics()
})

console.log('statistics statistics')
console.log(statistics)
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
