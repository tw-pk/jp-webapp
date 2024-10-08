<script setup>
import { initialAbility } from '@/plugins/casl/ability'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useRouter } from "vue-router"
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const router = useRouter()
const ability = useAppAbility()
const userData = ref(null)
const statusColor = ref('secondary')
const activityAt = ref()

const logout = () => {

  // Remove "userData" from localStorage
  localStorage.removeItem('userData')

  // Remove "accessToken" from localStorage
  localStorage.removeItem('accessToken')
  router.push('/login').then(() => {

    // Remove "userAbilities" from localStorage
    localStorage.removeItem('userAbilities')

    console.log(localStorage.getItem('userAbilities'))

    // Reset ability to initial ability
    ability.update(initialAbility)
  })
}

const userProfileList = [
  { type: 'divider' },

  // {
  //   type: 'navItem',
  //   icon: 'tabler-user',
  //   title: 'Business Profile',
  //   to: {
  //     name: 'apps-user-view-id',
  //     params: { id: 21 },
  //   },
  // },
  {
    type: 'navItem',
    icon: 'tabler-settings',
    title: 'Settings',
    to: {
      name: 'pages-account-settings-tab',
      params: { tab: 'account' },
    },
  },

  // {
  //   type: 'navItem',
  //   icon: 'tabler-credit-card',
  //   title: 'Billing',
  //   to: {
  //     name: 'pages-account-settings-tab',
  //     params: { tab: 'billing-plans' },
  //   },
  //   badgeProps: {
  //     color: 'error',
  //     content: '3',
  //   },
  // },
  // { type: 'divider' },
  // {
  //   type: 'navItem',
  //   icon: 'tabler-lifebuoy',
  //   title: 'Help',
  //   to: { name: 'pages-help-center' },
  // },
  // {
  //   type: 'navItem',
  //   icon: 'tabler-currency-dollar',
  //   title: 'Pricing',
  //   to: { name: 'pages-pricing' },
  // },
  // {
  //   type: 'navItem',
  //   icon: 'tabler-help',
  //   title: 'FAQ',
  //   to: { name: 'pages-faq' },
  // },
  { type: 'divider' },
  {
    type: 'navItem',
    icon: 'tabler-logout',
    title: 'Logout',
    onClick: logout,
  },
]

const updateUserStatus = () => {
  if (activityAt.value) {
    const activityTime = new Date(activityAt.value)
    const currentTime = new Date()
    const difference = Math.floor((currentTime - activityTime) / (1000 * 60))
    
    if (difference <= 30) {
      statusColor.value = 'success'
    } else if (difference > 30 && difference <= 60) {
      statusColor.value = 'warning'
    } else {
      statusColor.value = 'secondary'
    }
  } else {
    statusColor.value = 'secondary'
  }
}

onMounted(async () => {
  userData.value = JSON.parse(localStorage.getItem('userData') || 'null')
  
  activityAt.value = localStorage.getItem('activityAt')
  updateUserStatus()

  setInterval(updateUserStatus, 60000)
})
</script>

<template>
  <VBadge
    dot
    bordered
    location="bottom right"
    offset-x="3"
    offset-y="3"
    :color="statusColor"
  >
    <VAvatar
      class="cursor-pointer"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <VImg
        v-if="userData && userData.avatar"
        :src="userData.avatar"
        :lazy-src="userData.avatar"
      />
      <VIcon
        v-else
        icon="tabler-user"
      />

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="230"
        location="bottom end"
        offset="14px"
      >
        <VList>
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  :color="statusColor"
                  bordered
                >
                  <VAvatar
                    :color="!(userData && userData.avatar) ? 'primary' : undefined"
                    :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
                  >
                    <VImg
                      v-if="userData && userData.avatar"
                      :src="userData.avatar"
                    />
                    <VIcon
                      v-else
                      icon="tabler-user"
                    />
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-medium">
              {{ userData.firstname || userData.username }}
            </VListItemTitle>
            <VListItemSubtitle>{{ userData.role }}</VListItemSubtitle>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template
              v-for="item in userProfileList"
              :key="item.title"
            >
              <VListItem
                v-if="item.type === 'navItem'"
                :to="item.to"
                @click="item.onClick && item.onClick()"
              >
                <template #prepend>
                  <VIcon
                    class="me-2"
                    :icon="item.icon"
                    size="22"
                  />
                </template>

                <VListItemTitle>{{ item.title }}</VListItemTitle>

                <template
                  v-if="item.badgeProps"
                  #append
                >
                  <VBadge v-bind="item.badgeProps" />
                </template>
              </VListItem>

              <VDivider
                v-else
                class="my-2"
              />
            </template>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>
