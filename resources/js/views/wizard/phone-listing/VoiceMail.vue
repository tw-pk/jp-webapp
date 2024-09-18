<script setup>
import { useVoiceMailStore } from "@/views/apps/number/useVoiceMailStore"
import { defineProps, ref } from 'vue'

const props = defineProps(['phoneNumber'])
const voiceMailStore = useVoiceMailStore()

const transcription = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const count = ref(0)

// 👉 Fetching voice mail
const fetchVoiceMail = () => {
  voiceMailStore.fetchVoiceMail({
    phone_number: props.phoneNumber,
  }).then(response => {
    const data = response.data.phoneSetting
    
    transcription.value = data[0].transcription==1? true:false
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

watch([transcription], ([newTranscription], [oldTranscription]) => {

  if ((newTranscription !== oldTranscription) && count.value === 1) {
    voiceMailStore.addVoiceMail({
      phone_number: props.phoneNumber, 
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
</script>

<template>
  <VForm>
    <VRow>
      <VCol cols="12">
        <h4 class="text-h4 mb-3">
          Voicemail
        </h4>
      </VCol>

      <VCol
        cols="12"
        sm="6"
      >
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
