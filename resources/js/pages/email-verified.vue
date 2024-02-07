<script setup>
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import miscMaskDark from '@images/pages/misc-mask-dark.png'
import miscMaskLight from '@images/pages/misc-mask-light.png'
import pages401 from "@images/pages/401.png"

const authThemeMask = useGenerateImageVariant(miscMaskLight, miscMaskDark)
</script>

<template>
<div class="misc-wrapper">
    <ErrorHeader
      error-title="Email Verified!"
      error-description="You can now proceed to dashboard "
    />
    <VBtn
      to="/"
      class="mb-12"
    >
      Go to Dashboard
    </VBtn>

    <!-- 👉 Image -->
    <div class="misc-avatar w-100 text-center">
      <VImg
        :src="pages401"
        alt="Coming Soon"
        :max-width="170"
        class="mx-auto"
      />
    </div>

    <VImg
      :src="authThemeMask"
      class="misc-footer-img d-none d-md-block"
    />
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/misc.scss";
</style>

<route lang="yaml">
meta:
  layout: blank
  action: read
  subject: Auth
  redirectEmailVerified: true
</route>
