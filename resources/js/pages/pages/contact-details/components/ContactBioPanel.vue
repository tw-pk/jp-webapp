<script setup>
import {
  avatarText,
  kFormatter,
} from '@core/utils/formatters'

const props = defineProps({
  contactData: {
    type: Object,
    required: true,
  },
})


const isUserInfoEditDialogVisible = ref(false)
const isUpgradePlanDialogVisible = ref(false)

const resolveUserStatusVariant = stat => {
  if (stat === 'pending')
    return 'warning'
  if (stat === 'active')
    return 'success'
  if (stat === 'inactive')
    return 'secondary'

  return 'primary'
}

const resolveUserRoleVariant = role => {
  if (role === 'subscriber')
    return {
      color: 'warning',
      icon: 'tabler-user',
    }
  if (role === 'author')
    return {
      color: 'success',
      icon: 'tabler-circle-check',
    }
  if (role === 'maintainer')
    return {
      color: 'primary',
      icon: 'tabler-chart-pie-2',
    }
  if (role === 'editor')
    return {
      color: 'info',
      icon: 'tabler-pencil',
    }
  if (role === 'admin')
    return {
      color: 'secondary',
      icon: 'tabler-server-2',
    }

  return {
    color: 'primary',
    icon: 'tabler-user',
  }
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.contactData">
        <VCardText class="text-center pt-15">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="100"
            :color="!props.contactData.avatar ? 'primary' : undefined"
            :variant="!props.contactData.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="props.contactData.avatar"
              :src="props.contactData.avatar"
            />
            <span
              v-else
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(props.contactData.fullName) }}
            </span>
          </VAvatar>

          <!-- 👉 User fullName -->
          <h6 class="text-h4 mt-4">
            {{ props.contactData.fullName }}
          </h6>

          <!-- 👉 Role chip -->
          <VChip
            label
            :color="resolveUserRoleVariant('contact').color"
            size="small"
            class="text-capitalize mt-3"
          >
            Contact
          </VChip>
        </VCardText>

        <VDivider />

        <!-- 👉 Details -->
        <VCardText>
          <p class="text-sm text-uppercase text-disabled">
            Details
          </p>

          <!-- 👉 User Details list -->
          <VList class="card-list mt-2">
            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Username:
                  <span class="text-body-1">
                    {{ props.contactData.fullName }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Email:
                  <span class="text-body-1">{{ props.contactData.email }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

<!--            <VListItem>-->
<!--              <VListItemTitle>-->
<!--                <h6 class="text-h6">-->
<!--                  Status:-->

<!--                  <VChip-->
<!--                    label-->
<!--                    size="small"-->
<!--                    :color="resolveUserStatusVariant(props.contactData.status)"-->
<!--                    class="text-capitalize"-->
<!--                  >-->
<!--                    {{ props.contactData.status }}-->
<!--                  </VChip>-->
<!--                </h6>-->
<!--              </VListItemTitle>-->
<!--            </VListItem>-->

<!--            <VListItem>-->
<!--              <VListItemTitle>-->
<!--                <h6 class="text-h6">-->
<!--                  Role:-->
<!--                  <span class="text-capitalize text-body-1">{{ props.contactData.role }}</span>-->
<!--                </h6>-->
<!--              </VListItemTitle>-->
<!--            </VListItem>-->

<!--            <VListItem>-->
<!--              <VListItemTitle>-->
<!--                <h6 class="text-h6">-->
<!--                  Tax ID:-->
<!--                  <span class="text-body-1">-->
<!--                    {{ props.contactData.taxId }}-->
<!--                  </span>-->
<!--                </h6>-->
<!--              </VListItemTitle>-->
<!--            </VListItem>-->

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  Contact:
                  <span class="text-body-1">{{ props.contactData.phone }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

<!--            <VListItem>-->
<!--              <VListItemTitle>-->
<!--                <h6 class="text-h6">-->
<!--                  Language:-->
<!--                  <span class="text-body-1">{{ props.contactData.language }}</span>-->
<!--                </h6>-->
<!--              </VListItemTitle>-->
<!--            </VListItem>-->

<!--            <VListItem>-->
<!--              <VListItemTitle>-->
<!--                <h6 class="text-h6">-->
<!--                  Country:-->
<!--                  <span class="text-body-1">{{ props.contactData.country }}</span>-->
<!--                </h6>-->
<!--              </VListItemTitle>-->
<!--            </VListItem>-->
          </VList>
        </VCardText>

        <!-- 👉 Edit and Suspend button -->
        <VCardText class="d-flex justify-center">
          <VBtn
            variant="elevated"
            class="me-4"
            @click="isUserInfoEditDialogVisible = true"
          >
            Edit
          </VBtn>

          <VBtn
            variant="tonal"
            color="error"
          >
            Suspend
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <!-- SECTION Current Plan -->
<!--    <VCol cols="12">-->
<!--      <VCard>-->
<!--        <VCardText class="d-flex">-->
<!--          &lt;!&ndash; 👉 Standard Chip &ndash;&gt;-->
<!--          <VChip-->
<!--            label-->
<!--            color="primary"-->
<!--            size="small"-->
<!--            class="font-weight-medium"-->
<!--          >-->
<!--            Popular-->
<!--          </VChip>-->

<!--          <VSpacer />-->

<!--          &lt;!&ndash; 👉 Current Price  &ndash;&gt;-->
<!--          <div class="d-flex align-center">-->
<!--            <sup class="text-primary text-sm font-weight-regular">$</sup>-->
<!--            <h3 class="text-h3 text-primary">-->
<!--              99-->
<!--            </h3>-->
<!--            <sub class="mt-3"><h6 class="text-sm font-weight-regular text-disabled">/ month</h6></sub>-->
<!--          </div>-->
<!--        </VCardText>-->

<!--        <VCardText>-->
<!--          &lt;!&ndash; 👉 Price Benefits &ndash;&gt;-->
<!--          <VList class="card-list">-->
<!--            <VListItem-->
<!--              v-for="benefit in standardPlan.benefits"-->
<!--              :key="benefit"-->
<!--            >-->
<!--              <VIcon-->
<!--                size="12"-->
<!--                color="#A8AAAE"-->
<!--                class="me-2"-->
<!--                icon="tabler-circle"-->
<!--              />-->
<!--              <span>{{ benefit }}</span>-->
<!--            </VListItem>-->
<!--          </VList>-->

<!--          &lt;!&ndash; 👉 Days &ndash;&gt;-->
<!--          <div class="my-6">-->
<!--            <div class="d-flex mt-3 mb-2">-->
<!--              <h6 class="text-base font-weight-medium">-->
<!--                Days-->
<!--              </h6>-->
<!--              <VSpacer />-->
<!--              <h6 class="text-base font-weight-medium">-->
<!--                26 of 30 Days-->
<!--              </h6>-->
<!--            </div>-->

<!--            &lt;!&ndash; 👉 Progress &ndash;&gt;-->
<!--            <VProgressLinear-->
<!--              rounded-->
<!--              rounded-bar-->
<!--              :model-value="65"-->
<!--              height="10"-->
<!--              color="primary"-->
<!--            />-->

<!--            <p class="mt-2">-->
<!--              4 days remaining-->
<!--            </p>-->
<!--          </div>-->

<!--          &lt;!&ndash; 👉 Upgrade Plan &ndash;&gt;-->
<!--          <div class="d-flex gap-4">-->
<!--            <VBtn @click="isUpgradePlanDialogVisible = true">-->
<!--              Upgrade Plan-->
<!--            </VBtn>-->
<!--            <VBtn-->
<!--              variant="tonal"-->
<!--              color="default"-->
<!--            >-->
<!--              cancel-->
<!--            </VBtn>-->
<!--          </div>-->
<!--        </VCardText>-->
<!--      </VCard>-->
<!--    </VCol>-->
    <!-- !SECTION -->
  </VRow>

  <!-- 👉 Edit user info dialog -->
  <UserInfoEditDialog
    v-model:isDialogVisible="isUserInfoEditDialogVisible"
    :user-data="props.contactData"
  />
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.75rem;
}

.text-capitalize {
  text-transform: capitalize !important;
}
</style>
