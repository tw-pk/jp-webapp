<script setup>
import axiosIns from "@axios"
import avatar from "@images/avatars/avatar-0.png"
import UserProfileHeaderBg from '@images/pages/user-profile-header-bg.png'
import { useRoute } from "vue-router"
import About from "./About.vue"

const props = defineProps({
  contactData: {
    type: Object,
    required: true,
  },
})

const profileHeaderData = ref()
const route = useRoute()

const fetchHeaderData = () => {
  axiosIns.post('/api/auth/contact/details', {
    contact_id: route.params.id,
  }).then(response => {
    profileHeaderData.value = response.data.contactData
    profileHeaderData.value.coverImg = UserProfileHeaderBg
  })
}

fetchHeaderData()
</script>

<template>
  <VCard v-if="profileHeaderData">
    <VImg
      :src="profileHeaderData.coverImg"
      min-height="125"
      max-height="250"
      cover
    />
    
    <VCardText class="d-flex align-bottom flex-sm-row flex-column justify-center gap-x-5">
      <div class="d-flex h-0">
        <VAvatar
          rounded
          size="120"
          :image="profileHeaderData.avatar ?? avatar"
          class="user-profile-avatar mx-auto"
        />
      </div>

      <div class="user-profile-info w-100 mt-16 pt-6 pt-sm-0 mt-sm-0">
        <h6 class="text-h6 text-center text-sm-start font-weight-medium mb-3">
          {{ profileHeaderData.fullName }}
        </h6>

        <div class="d-flex align-center justify-center justify-sm-space-between flex-wrap gap-4">
          <div class="d-flex flex-wrap justify-center justify-sm-start flex-grow-1 gap-2">
            <span class="d-flex">
              <VIcon
                size="20"
                icon="tabler-address-book"
                class="me-1"
              />
              
              <span class="text-body-1">
                Contact
              </span>
            </span>

            <!--
              <span class="d-flex align-center">
              <VIcon
              size="20"
              icon="tabler-map-pin"
              class="me-2"
              />
              <span class="text-body-1">
              {{ profileHeaderData?.address_home }}
              </span>
              </span>
            -->

            <span class="d-flex align-center">
              <VIcon
                size="20"
                icon="tabler-calendar"
                class="me-2"
              />
              <span class="text-body-1">
                {{ profileHeaderData?.joinedAt }}
              </span>
            </span>
          </div>
        </div>
      </div>
    </VCardText>
    <VCardText class="d-flex flex-sm-row flex-column justify-end align-top about-container">
      <div class="about-scroll-container">
        <About :data="contactData" />
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss">
.user-profile-avatar {
  border: 5px solid rgb(var(--v-theme-surface));
  background-color: rgb(var(--v-theme-surface)) !important;
  inset-block-start: -3rem;

  .v-img__img {
    border-radius: 0.125rem;
  }
}

.about-container {
  position: absolute;
  z-index: 1;
  inset-block-start: 0.875rem;
  inset-inline-end: 0.5625rem;

  @media (min-width: 576px) {
    inset-block-start: 0.875rem;
    inset-inline-end: 0.5625;
  }

  @media (min-width: 768px) {
    inset-block-start: 0.875rem;
    inset-inline-end: 0.5625;
  }

  @media (min-width: 992px) {
    inset-block-start: 0.875rem;
    inset-inline-end: 0.5625rem;
  }
}

.about-scroll-container {
  max-block-size: 350px;
  overflow-x: auto;
  overflow-y: auto;
  white-space: nowrap;

  @media (max-width: 576px) {
    max-block-size: 200px;
  }

  @media (max-width: 768px) {
    max-block-size: 200px;
  }

  @media (max-width: 992px) {
    max-block-size: 200px;
  }
}
</style>
