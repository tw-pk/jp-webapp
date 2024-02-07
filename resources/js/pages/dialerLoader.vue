<script setup>
const props = defineProps(['isProcessing'])
</script>

<template>
  <VCol
    v-if="isProcessing"
    cols="12"
  >
    <!-- Show the loader -->
    <VProgressLinear
      indeterminate
      height="2"
      bg-color="primary"
      color="primary"
      :rounded="false"
    />
  </VCol>
</template>
