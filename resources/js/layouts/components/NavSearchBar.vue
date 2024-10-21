<script setup>
import axios from '@axios'
import { useThemeConfig } from '@core/composable/useThemeConfig'
import Shepherd from 'shepherd.js'

const { appContentLayoutNav } = useThemeConfig()

defineOptions({ inheritAttrs: false })

// 👉 Is App Search Bar Visible
const isAppSearchBarVisible = ref(false)

const userData = JSON.parse(localStorage.getItem('userData') || '{}')
const userRole = (userData && userData.role) ? userData.role : null

console.log('userRole:', userRole)

// 👉 Common Dashboard Suggestions
const commonDashboard = [
  {
    icon: 'tabler-layout-dashboard',
    title: 'Dashboard',
    url: { name: 'dashboard' },
  },
  {
    icon: 'tabler-users',
    title: 'Manage Members',
    url: { name: 'pages-teams-manage-members' },
  },
  {
    icon: 'tabler-users',
    title: 'Manage Team',
    url: { name: 'pages-teams-manage-teams' },
  },
  {
    icon: 'tabler-user-circle',
    title: 'Contact',
    url: { name: 'pages-contact' },
  },
  {
    icon: 'tabler-phone-call',
    title: 'Recent Calls',
    url: { name: 'pages-recent-calls' },
  },
]

// 👉 Common Settings Suggestions
const commonSettings = [
  {
    icon: 'tabler-users',
    title: 'Profile',
    url: {
      name: 'pages-account-settings-tab',
      params: { tab: 'account' },
    },
  },
  {
    icon: 'tabler-lock',
    title: 'Security',
    url: {
      name: 'pages-account-settings-tab',
      params: { tab: 'security' },
    },
  },
]

// 👉 Phone Numbers Menu
const phoneNumbers = [
  {
    icon: 'tabler-phone',
    title: 'Phone Numbers',
    url: { name: 'pages-phone-numbers' },
  },
]

// 👉 Admin Specific Suggestions
const adminSuggestions = [
  {
    icon: 'tabler-message-circle-2',
    title: 'SMS & MMS',
    url: { name: 'messages-inbox' },
  },
  {
    icon: 'tabler-transfer-in',
    title: 'Top Up Credit',
    url: { name: 'pages-top-up-credit' },
  },
  {
    icon: 'tabler-file-text',
    title: 'Payment Methods',
    url: {
      name: 'pages-account-settings-tab',
      params: { tab: 'billing-plans' },
    },
  },
  {
    icon: 'tabler-bell',
    title: 'Notifications',
    url: {
      name: 'pages-account-settings-tab',
      params: { tab: 'notification' },
    },
  },
  {
    icon: 'tabler-building-skyscraper',
    title: 'Business Profile',
    url: {
      name: 'pages-account-settings-tab',
      params: { tab: 'business-profile' },
    },
  },
]

// 👉 Inactive Member Specific Suggestions
const inactiveMemberSuggestions = [
  {
    icon: 'tabler-layout-dashboard',
    title: 'Dashboard',
    url: { name: 'dashboard' },
  },
]

let suggestionGroups = []

if (userRole === "Admin") {
  suggestionGroups = [
    { title: 'Dashboard', content: [...commonDashboard, ...adminSuggestions] },
    { title: 'Settings', content: [...commonSettings] },
    { title: 'Phone Numbers', content: [...phoneNumbers] },
  ]
} else if (userRole === "InactiveMember") {
  suggestionGroups = [
    { title: 'Dashboard', content: [...inactiveMemberSuggestions] },
    { title: 'Settings', content: [...commonSettings] },
    { title: 'Phone Numbers', content: [...phoneNumbers] },
  ]
} else {
  // Default role suggestions
  suggestionGroups = [
    { title: 'Dashboard', content: [...commonDashboard] },
    { title: 'Settings', content: [...commonSettings] },
    { title: 'Phone Numbers', content: [...phoneNumbers] },
  ]
}


// 👉 No Data suggestion
const noDataSuggestions = [
  {
    title: 'Analytics Dashboard',
    icon: 'tabler-shopping-cart',
    url: { name: 'dashboard' },
  },
  {
    title: 'Account Settings',
    icon: 'tabler-user',
    url: {
      name: 'pages-account-settings-tab',
      params: { tab: 'account' },
    },
  },
  {
    title: 'Pricing Page',
    icon: 'tabler-cash',
    url: { name: 'pages-pricing' },
  },
]

const searchQuery = ref('')
const searchResult = ref([])
const router = useRouter()

// 👉 fetch search result API
watchEffect(() => {
  axios.get('/app-bar/search', { params: { q: searchQuery.value } }).then(response => {
    searchResult.value = response.data
  })
})

const redirectToSuggestedOrSearchedPage = selected => {
  router.push(selected.url)
  isAppSearchBarVisible.value = false
  searchQuery.value = ''
}

const LazyAppBarSearch = defineAsyncComponent(() => import('@core/components/AppBarSearch.vue'))
</script>

<template>
  <div
    class="d-flex align-center cursor-pointer"
    v-bind="$attrs"
    style="user-select: none;"
    @click="isAppSearchBarVisible = !isAppSearchBarVisible"
  >
    <!-- 👉 Search Trigger button -->
    <!-- close active tour while opening search bar using icon -->
    <IconBtn
      class="me-1"
      @click="Shepherd.activeTour?.cancel()"
    >
      <VIcon
        size="26"
        icon="tabler-search"
      />
    </IconBtn>

    <span
      v-if="appContentLayoutNav === 'vertical'"
      class="d-none d-md-flex align-center text-disabled"
      @click="Shepherd.activeTour?.cancel()"
    >
      <span class="me-3">Search</span>
      <span class="meta-key">&#8984;K</span>
    </span>
  </div>

  <!-- 👉 App Bar Search -->
  <LazyAppBarSearch
    v-model:isDialogVisible="isAppSearchBarVisible"
    v-model:search-query="searchQuery"
    :search-results="searchResult"
    :suggestions="suggestionGroups"
    :no-data-suggestion="noDataSuggestions"
    @item-selected="redirectToSuggestedOrSearchedPage"
  >
    <!--
      <template #suggestions>
      use this slot if you want to override default suggestions
      </template>
    -->

    <!--
      <template #noData>
      use this slot to change the view of no data section
      </template>
    -->

    <!--
      <template #searchResult="{ item }">
      use this slot to change the search item
      </template>
    -->
  </LazyAppBarSearch>
</template>

<style lang="scss" scoped>
@use "@styles/variables/_vuetify.scss";

.meta-key {
  border: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  block-size: 1.5625rem;
  line-height: 1.3125rem;
  padding-block: 0.125rem;
  padding-inline: 0.25rem;
}
</style>
