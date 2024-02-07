<script setup>
import { useCallRoutingStore } from "@/views/apps/number/useCallRoutingStore"
import { defineProps, ref, watch } from 'vue'

const props = defineProps(['phoneNumber'])
const callRoutingStore = useCallRoutingStore()

const fwd_incoming_call = ref(null)
const ringOrder = ref(null)
const unanswered_fwd_call = ref(null)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const count = ref(0)
const selectedUser = ref(null)
const selectedUsers = ref([])
const assignUsers = ref([])
const errorEmailMsg = ref('')
const selectedUsersData = []

const incomingOption = [
  {
    name: 'Web & Mobile Apps',
    value: 'web_mobile_apps',
  },
  {
    name: 'Mobile/landline number',
    value: 'mobile_landline_number',
  },
  {
    name: 'Team Members',
    value: 'team_members',
  },
  {
    name: 'Voicemail',
    value: 'voicemail',
  },
]

const ringOrders = [
  {
    name: 'Voicemail',
    value: 'voicemail',
  },
  {
    name: 'External Number',
    value: 'external number',
  },
  {
    name: 'Dismiss',
    value: 'dismiss',
  },
]

const unansweredOption = [
  {
    name: 'Voicemail',
    value: 'voicemail',
  },
  {
    name: 'Dismiss Call',
    value: 'dismiss_call',
  },
  {
    name: 'External Number',
    value: 'external_number',
  },
]

// 👉 Fetching call rounting
const fetchCallRouting = () => {
  callRoutingStore.fetchCallRouting({
    phone_number: props.phoneNumber,
  }).then(response => {
    const data = response.data

    fwd_incoming_call.value = data.phoneSetting.fwd_incoming_call
    unanswered_fwd_call.value = data.phoneSetting.unanswered_fwd_call
    ringOrder.value = data.phoneSetting.ring_order
    if (data.phoneSetting.ring_order_value !== null && Array.isArray(data.phoneSetting.ring_order_value)) {
      selectedUsers.value = data.phoneSetting.ring_order_value
    } 
    assignUsers.value = data.assignUsers
  }).catch(error => {
    console.error(error)
  })
}

onMounted(() => {
  fetchCallRouting()

  setTimeout(() => {
    count.value = 1
  }, 1000)
})

watch([fwd_incoming_call, unanswered_fwd_call, ringOrder], ([newForward, newUnanswered, newRingOrder], [oldForward, oldUnanswered, oldRingOrder]) => {
  if ((newForward !== oldForward || newUnanswered !== oldUnanswered || newRingOrder !== oldRingOrder) && count.value === 1) {
    addCallRouting(newForward, newUnanswered, newRingOrder)
  }
})

const addCallRouting = (newForward, newUnanswered, newRingOrder) => {
  callRoutingStore.addCallRouting({
    phone_number: props.phoneNumber,
    fwd_incoming_call: newForward,
    unanswered_fwd_call: newUnanswered,
    ringOrder: newRingOrder,
    ringOrderValue: selectedUsersData,
  }).then(response => {
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = `success`
    isSnackbarVisible.value = true
  }).catch(error => {
    console.log(error)
    snackbarMessage.value = error.data.message
    snackbarActionColor.value = `error`
    isSnackbarVisible.value = true
  })
}

const handleCheckboxChange = (user, checkboxType) => {
  const userIndex = selectedUsersData.findIndex(u => u.invitationId === user.invitationId)
  if (!user.webDesktop && !user.mobileLandline) {
    if (userIndex !== -1) {
      selectedUsersData.splice(userIndex, 1)
    }
  } else {
    if (userIndex !== -1) {
      selectedUsersData[userIndex] = {
        invitationId: user.invitationId,
        fullname: user.fullname,
        webDesktop: user.webDesktop,
        mobileLandline: user.mobileLandline,
      }
    } else {
      selectedUsersData.push({
        invitationId: user.invitationId,
        fullname: user.fullname,
        webDesktop: user.webDesktop,
        mobileLandline: user.mobileLandline,
      })
    }
  }
  addCallRouting(fwd_incoming_call.value, unanswered_fwd_call.value, ringOrder.value)
}

watch(selectedUser, newValue => {
  const userExists = selectedUsers.value.some(user => user.invitationId === newValue)
  if (!userExists && newValue !== null) {
    const userToAdd = assignUsers.value.find(user => user.invitationId === newValue)
    if (userToAdd) {
      selectedUsers.value.push(userToAdd)
    }
  }

})
</script>

<template>
  <VForm>
    <VRow>
      <VCol cols="12">
        <h4 class="text-h4 mb-3">
          Call Forwarding
        </h4>
      </VCol>

      <VCol
        cols="12"
        sm="6"
      >
        <div>
          <!-- 👉 Forward Incoming Call To -->
          <AppSelect
            v-model="fwd_incoming_call"
            label="Forward Incoming Call To"
            :items="incomingOption"
            item-title="name"
            item-value="value"
          />
        </div>

        <div class="mt-4">
          <!-- 👉 Select Ring Order -->
          <AppSelect
            v-model="ringOrder"
            label="Select Ring Order"
            :items="ringOrders"
            item-title="name"
            item-value="value"
          />
        </div>
      </VCol>
    </VRow>

    <div
      v-for="(user, index) in selectedUsers"
      :key="user.invitationId"
    >
      <VRow class="border-b">
        <VCol
          cols="12"
          sm="6"
          class="mt-2"
        >
          <VBtn
            size="30"
            color="secondary"
            class=""
          >
            {{ index + 1 }}
          </VBtn>
          <span class="ml-2">{{ user.fullname }}</span>
        </VCol>
        <VCol
          cols="12"
          sm="3"
        >
          <VCheckbox
            v-model="user.webDesktop"
            label="Web & Desktop"
            @change="handleCheckboxChange(user)"
          />
        </VCol>
        <VCol
          cols="12"
          sm="3"
        >
          <VCheckbox
            v-model="user.mobileLandline"
            label="Mobile/Landline"
            @change="handleCheckboxChange(user)"
          />
        </VCol>
      </VRow>
    </div>
    <VRow>
      <VCol
        cols="12"
        sm="6"
        class="mt-7"
      >
        <VRow>
          <VBtn
            size="38"
            color="primary"
            class="ml-3"
          >
            <VIcon
              icon="tabler-plus"
              size="22"
            />
          </VBtn>
          <VText
            color="primary"
            class="ml-2 mr-2 mt-2"
          >
            Add
          </VText>
          <!-- 👉 If Unanswered, Forward calls to -->
          <AppSelect
            v-model="selectedUser"
            :items="assignUsers"
            item-title="fullname"
            item-value="invitationId"
          />
        </VRow>
      </VCol>
    </VRow>
    <VRow>
      <VCol
        cols="12"
        sm="6"
      >   
        <div>
          <!-- 👉 If Unanswered, Forward calls to -->
          <AppSelect
            v-model="unanswered_fwd_call"
            label="If Unanswered, Forward call to"
            :items="unansweredOption"
            item-title="name"
            item-value="value"
          />
        </div>
      </VCol>
      <VCol
        cols="12"
        sm="12"
      >
        <p class="border-b" />
      </VCol>
    </VRow>
    <!-- Snackbar -->
    <VSnackbar
      v-model="isSnackbarVisible"
      multi-line
    >
      {{ snackbarMessage }}

      <template #actions>
        <VBtn
          :color="snackbarActionColor"
          @click="isSnackbarVisible = false"
        >
          Close
        </VBtn>
      </template>
    </VSnackbar>
  </VForm>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>

