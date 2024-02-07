<script setup>
import { useVoiceMailStore } from "@/views/apps/number/useVoiceMailStore"
import { defineProps, ref } from 'vue'

const props = defineProps(['phoneNumber'])
const voiceMailStore = useVoiceMailStore()

const vunanswered_fwd_call = ref('Voicemail')
const vemail_id = ref('')
const voice_message = ref('')
const transcription = ref(true)
const errorEmailMsg = ref('')
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const count = ref(0)

const unansweredOption = [
  {
    name: 'Voicemail',
    value: 'voicemail',
  },
  {
    name: 'Queue',
    value: 'queue',
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

// 👉 Fetching voice mail
const fetchVoiceMail = () => {
  voiceMailStore.fetchVoiceMail({
    phone_number: props.phoneNumber,
  }).then(response => {
    const data = response.data.phoneSetting

    vunanswered_fwd_call.value = data.vunanswered_fwd_call
    vemail_id.value = data.vemail_id
    voice_message.value = data.voice_message
    transcription.value = data.transcription==1? true:false
  }).catch(error => {
    console.error(error)
  })
}

onMounted(() => {
  fetchVoiceMail()

  setTimeout(() => {
    count.value = 1
  }, 1000)
})

watch([vunanswered_fwd_call, voice_message, transcription], ([newVunanswered, newVoice_message, newTranscription], [oldVunanswered, oldVoice_message, oldTranscription]) => {

  if ((newVunanswered !== oldVunanswered || newVoice_message !== oldVoice_message || newTranscription !== oldTranscription) && count.value === 1) {
    voiceMailStore.addVoiceMail({
      phone_number: props.phoneNumber,
      vunanswered_fwd_call: newVunanswered,
      vemail_id: vemail_id.value,
      voice_message: newVoice_message,
      transcription: newTranscription,
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
})

const emailsArray = () => {
  const inputValue = vemail_id.value
  if (!inputValue) {
    return []
  }
  
  return inputValue.split(',').map(email => email.trim())
}

const isEmailValid = email => {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  
  return emailPattern.test(email)
}

watch(vemail_id,  (newVemail_id, oldVemail_id) => {
  const emailsSplit = emailsArray()
  const invalidEmails = emailsSplit.filter(email => !isEmailValid(email))
  if (invalidEmails.length != 0) {
    errorEmailMsg.value = 'The Email field must be a valid email.'
  }

  if (invalidEmails.length === 0 && count.value === 1 && newVemail_id !='') {
    errorEmailMsg.value =''
    voiceMailStore.addVoiceMail({
      phone_number: props.phoneNumber,
      vunanswered_fwd_call: vunanswered_fwd_call.value,
      vemail_id: newVemail_id,
      voice_message: voice_message.value,
      transcription: transcription.value,
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
})
</script>

<template>
  <VForm>
    <VRow>
      <VCol cols="12">
        <h4 class="text-h4 mb-3">
          Voice Mail
        </h4>
      </VCol>

      <VCol
        cols="12"
        sm="6"
      >
        <div>
          <!-- 👉 In Unanswered, Froward calls to -->
          <AppSelect
            v-model="vunanswered_fwd_call"
            label="In Unanswered, Froward calls to"
            :items="unansweredOption"
            item-title="name"
            item-value="value"
          />
        </div>

        <div class="mt-4">
          <!-- 👉 Email ID -->
          <AppTextField
            v-model="vemail_id"
            label="Email ID"
            type="email"
            placeholder="Type email addres"
            :error-messages="errorEmailMsg"
          />
        </div>
        <div class="mt-4">
          <!-- 👉 Voicemail message -->
          <AppTextarea
            v-model="voice_message"
            auto-grow
            label="Voicemail message"
            rows="2"
            row-height="20"
            placeholder="Write message"
          />
        </div>
        <div class="mt-4">
          <!-- 👉 Voicemail message -->
          <span>Voicemail transcription settings</span>
          <VSwitch
            v-model="transcription"
            class="_voice-mail-switch-lab"
            label="Voicemail transcription"
          />
        </div>
      </VCol>
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
    </VRow>
  </VForm>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
