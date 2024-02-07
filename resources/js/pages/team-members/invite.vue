<script setup>
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()
const currentThemeName = vuetifyTheme.name.value


vuetifyTheme.themes.value[currentThemeName].colors.primary = '#38A6E3'
</script>

<template>
  <VRow no-gutters>
    <VCol
      cols="12"
      lg="12"
      class="auth-card-v2 d-flex align-center justify-center mt-6"
    >
      <DynamicFormGenerator />
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>

<route lang="yaml">
meta:
  layout: blank
</route>
