<script setup>
import AccountSettingsAccount from '@/views/pages/account-settings/AccountSettingsAccount.vue'
import AccountSettingsBillingAndPlans from '@/views/pages/account-settings/AccountSettingsBillingAndPlans.vue'
import AccountSettingsNotification from '@/views/pages/account-settings/AccountSettingsNotification.vue'
import AccountSettingsSecurity from '@/views/pages/account-settings/AccountSettingsSecurity.vue'
import BusinessProfile from '@/views/pages/account-settings/BusinessProfile.vue'
import { can } from '@layouts/plugins/casl'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

// tabs
const tabs = [
  {
    title: 'Profile',
    icon: 'tabler-users',
    tab: 'account',
  },
  {
    title: 'Security',
    icon: 'tabler-lock',
    tab: 'security',
  },
  {
    title: 'Payment Methods',
    icon: 'tabler-file-text',
    tab: 'billing-plans',
  },
  {
    title: 'Notifications',
    icon: 'tabler-bell',
    tab: 'notification',
  },
  {
    title: 'Business Profile',
    icon: 'tabler-building-skyscraper',
    tab: 'business-profile',
  },
]

const filteredTabs = computed(() => {
  return tabs.filter(item => can('read', item.tab))
})

const activeTab = ref(route.params.tab)
</script>

<template>
  <div>
    <VTabs
      v-model="activeTab"
      class="v-tabs-pill"
    >
      <VTab
        v-for="item in filteredTabs"
        :key="item.icon"
        :value="item.tab"
        :to="{ name: 'pages-account-settings-tab', params: { tab: item.tab } }"
      >
        <VIcon
          size="20"
          start
          :icon="item.icon"
        />
        {{ item.title }}
      </VTab>
    </VTabs>

    <VWindow
      v-model="activeTab"
      class="mt-6 disable-tab-transition"
      :touch="false"
    >
      <!-- Account -->
      <VWindowItem value="account">
        <AccountSettingsAccount />
      </VWindowItem>

      <!-- Security -->
      <VWindowItem value="security">
        <AccountSettingsSecurity />
      </VWindowItem>

      <!-- Billing -->
      <VWindowItem value="billing-plans">
        <AccountSettingsBillingAndPlans />
      </VWindowItem>

      <!-- Notification -->
      <VWindowItem value="notification">
        <AccountSettingsNotification />
      </VWindowItem>

      <!-- Business Profile -->
      <VWindowItem value="business-profile">
        <BusinessProfile />
      </VWindowItem>

      <!-- Connections -->
      <!--      <VWindowItem value="connection"> -->
      <!--        <AccountSettingsConnections /> -->
      <!--      </VWindowItem> -->
    </VWindow>
  </div>
</template>

<route lang="yaml">
meta:
  navActiveLink: pages-account-settings-tab
  action: read
  subject: pages-account-settings-tab
</route>

