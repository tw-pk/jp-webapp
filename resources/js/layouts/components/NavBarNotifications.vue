<script setup>
import { useNotificationStore } from "@/views/apps/notification/useNotificationStore"
import { onMounted, ref } from 'vue'

const notificationStore = useNotificationStore()
const notifications = ref([])

// Function to fetch initial notifications
const fetchNotifications = () => {
  notificationStore.fetchNotifications()
    .then(response => {
      notifications.value = response.data.notifications
    }).catch(error => {
      console.error(error)
    })
}

const addNotification = notification => {
  notifications.value.unshift(notification)
}

const removeNotification = notificationId => {
  notifications.value.forEach((item, index) => {
    if (notificationId === item.id)
      notifications.value.splice(index, 1)
  })
}

const markRead = notificationId => {
  notifications.value.forEach(item => {
    notificationId.forEach(id => {
      if (id === item.id)
        item.isSeen = true
    })
  })
}

const markUnRead = notificationId => {
  notifications.value.forEach(item => {
    notificationId.forEach(id => {
      if (id === item.id)
        item.isSeen = false
    })
  })
}

const handleNotificationClick = notification => {
  if (!notification.isSeen)
    markRead([notification.id])
}


onMounted(async () => {

  const userData = localStorage.getItem('userData')

  console.log(`${JSON.parse(userData).id}`)

  window.Echo
    .private(`User.${JSON.parse(userData).id}`)
    .listen('.TestHello', e => {
      console.log('Private channel')
      console.log(e)

      // addNotification(e)
    })

  fetchNotifications()

})
</script>

<template>
  <Notifications
    :notifications="notifications"
    @remove="removeNotification"
    @read="markRead"
    @unread="markUnRead"
    @click:notification="handleNotificationClick"
  />
</template>
