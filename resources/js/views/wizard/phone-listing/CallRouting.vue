<script setup>
import { useCallForwardingStore } from "@/views/apps/number/useCallForwardingStore"
import { defineProps, ref, watch } from 'vue'

const props = defineProps(['phoneNumber'])
const emit = defineEmits(['updatePhoneSetting'])
const callForwardingStore = useCallForwardingStore()

const fwd_incoming_call = ref(null)
const ringOrder = ref(null)
const unanswered_fwd_call = ref(null)
const unansweredFwdCallValue = ref('dismiss_call')
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const count = ref(0)
const selectedUser = ref(null)
const selectedUsers = ref([])
const assignUsers = ref([])
const errorExtPhone = ref('')
const externalPhoneNumber = ref(null)
const phoneNumberBlock = ref(false)
const selectedUsersData = []
const selectedUsersDataValue = []
const callForwardForm = ref()
const webMobileBlock = ref(false)
const unansweredFwdCallBlock = ref(false)

const incomingOption = [
  {
    name: 'Web & Desktop Apps',
    value: 'web_desktop_apps',
  },
  {
    name: 'Mobile Number',
    value: 'mobile_number',
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
    name: '1 Ring',
    value: '1_ring',
  },
  {
    name: '2 Ring',
    value: '2_ring',
  },
  {
    name: '3 Ring',
    value: '3_ring',
  },
  {
    name: '4 Ring',
    value: '4_ring',
  },
  {
    name: '5 Ring',
    value: '5_ring',
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
const fetchCallForwarding = () => {
  callForwardingStore.fetchCallForwarding({
    phone_number: props.phoneNumber,
  }).then(response => {
    const data = response.data
    if(data.phoneSetting){
      emit('updatePhoneSetting', data.phoneSetting)
    }
    assignUsers.value = data?.assignUsers
  }).catch(error => {
    console.error(error)
  })
}

onMounted(() => {
  fetchCallForwarding()

  setTimeout(() => {
    count.value = 1
  }, 1000)
})

watch(fwd_incoming_call, newValue => {
  if(newValue=='web_desktop_apps') {
    unansweredFwdCallBlock.value = true
    webMobileBlock.value = false
    phoneNumberBlock.value = false
    unanswered_fwd_call.value = null
    ringOrder.value = null
    selectedUsers.value = []
    selectedUsersDataValue.value = []
  }else if(newValue=='mobile_number'){
    webMobileBlock.value = false
    unansweredFwdCallBlock.value = false
    phoneNumberBlock.value = true
    
    unanswered_fwd_call.value = null
    ringOrder.value = null
    selectedUsers.value = []
    selectedUsersDataValue.value = []
  }else{
    phoneNumberBlock.value = false
    webMobileBlock.value = true
    unansweredFwdCallBlock.value = true
   
    unanswered_fwd_call.value = null
    ringOrder.value = null
    selectedUsers.value = []
    selectedUsersDataValue.value = []
  }
})

watch(unanswered_fwd_call, newValue => {

  externalPhoneNumber.value = null
  if(fwd_incoming_call.value=='mobile_number'){
    phoneNumberBlock.value = true
  }else{
    phoneNumberBlock.value = false
  }
  if(newValue=='external_number') {
    phoneNumberBlock.value = true
  }
  
  unansweredFwdCallValue.value = newValue
})

const addCallForwarding = () => {
  if (((fwd_incoming_call.value === 'web_desktop_apps' && unansweredFwdCallValue.value == 'external_number') || fwd_incoming_call.value === 'mobile_number') && !externalPhoneNumber.value) {
    snackbarMessage.value = 'Phone number is required.'
    snackbarActionColor.value = 'error'
    isSnackbarVisible.value = true

    return 
  }
  callForwardingStore.addCallForwarding({
    phone_number: props.phoneNumber,
    fwd_incoming_call: fwd_incoming_call.value,
    unanswered_fwd_call: unansweredFwdCallValue.value,
    externalPhoneNumber: externalPhoneNumber.value,
    ringOrder: ringOrder.value,
    ringOrderValue: selectedUsersDataValue.value,
  }).then(response => {
    snackbarMessage.value = response.data.message
    snackbarActionColor.value = `success`
    isSnackbarVisible.value = true

    fetchCallForwarding()
  }).catch(error => {
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
      selectedUsersDataValue.value = selectedUsersData
    }
  } else {
    if (userIndex !== -1) {
      selectedUsersData[userIndex] = {
        invitationId: user.invitationId,
        fullname: user.fullname,
        webDesktop: user.webDesktop,
        mobileLandline: user.mobileLandline,
      }
      selectedUsersDataValue.value = selectedUsersData
    } else {
      selectedUsersData.push({
        invitationId: user.invitationId,
        fullname: user.fullname,
        webDesktop: user.webDesktop,
        mobileLandline: user.mobileLandline,
      })
      selectedUsersDataValue.value = selectedUsersData
    }
  }

  //addCallForwarding(fwd_incoming_call.value, unanswered_fwd_call.value, ringOrder.value)
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
  <VForm
    ref="callForwardForm"
    @submit.prevent="addCallForwarding"
  >
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

        <div
          v-if="webMobileBlock"
          class="mt-4"
        >
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
            label="Mobile Number"
            @change="handleCheckboxChange(user)"
          />
        </VCol>
      </VRow>
    </div>
    
    <VRow v-if="webMobileBlock">
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
    <VRow v-if="unansweredFwdCallBlock">
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
    </VRow>
    <VRow>
      <VCol
        v-if="phoneNumberBlock"
        cols="12"
        sm="6"
      >   
        <div>
          <!-- 👉 External Phone Number -->
          <AppTextField
            v-model="externalPhoneNumber"
            label="Phone Number"
            placeholder="+1XXXXXXXXXXX"
          />
        </div>
      </VCol>
      <VCol
        cols="12"
        class="d-flex justify-end mt-8"
      >
        <VBtn
          color="success"
          type="submit"
          :disabled="isDisabled"
          :loading="isDisabled"
        >
          Save
        </VBtn>
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

