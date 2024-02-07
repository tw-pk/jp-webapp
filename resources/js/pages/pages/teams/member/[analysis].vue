<script setup>
import { ref } from 'vue'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()
const currentThemeName = vuetifyTheme.name.value
const route = useRoute()

const analysisID = ref('')

analysisID.value = route.params.analysis
console.log('aaaaaaaa')
console.log(analysisID)

vuetifyTheme.themes.value[currentThemeName].colors.primary = '#38A6E3'
</script>

<template>
  <div>
    <div class="pb-4 font-weight-bold text-h4">
      Member Analysis
    </div>

    <VRow class="match-height">
      <VCol
        cols="12"
        md="6"
      >
        <VCard>
          <VRow class="__align-items-center pa-4 " />
          <p>Comming Soon</p>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="6"
      >
        <VCard>
          <VRow class="__align-items-center pa-4 " />
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";

</style>
