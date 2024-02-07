<script setup>
const unansweredCall = ref('Voicemail')
const emailID = ref('')
const voiceMailMessage = ref('')
const voiceMailSetting = ref(true)

const onSubmit = () => {
  console.log('assignGroup :>> ')
}
</script>

<template>
  <VForm>
    <VRow>
      <VCol cols="12">
        <h4 class="text-h4 mb-3 _call_routing">
          IVR
        </h4>
      </VCol>

      <VCol
        cols="12"
        sm="6"
      >
        <div>
          <!-- 👉 In Unanswered, Froward calls to -->
          <AppSelect
            v-model="unansweredCall"
            label="In Unanswered, Froward calls to"
            :items="['Voicemail', 'Answered']"
          />
        </div>

        <div class="mt-4">
          <!-- 👉 Email ID -->
          <AppTextField
            v-model="emailID"
            label="Email ID"
            type="email"
            placeholder="Type email addres"
          />
        </div>
        <div class="mt-4">
          <!-- 👉 Voicemail message -->
          <AppTextarea
            v-model="voiceMailMessage"
            auto-grow
            label="Voicemail message"
            rows="2"
            row-height="20"
            placeholder="Write message"
          />
        </div>
        <div class="mt-4">
          <!-- 👉 Voicemail message -->
          <span class="_voice-mail-switch-span-lab">Voicemail transcription settings</span>
          <VSwitch
            v-model="voiceMailSetting"
            class="_voice-mail-switch-lab"
            label="Voicemail transcription"
          />
        </div>
      </VCol>
    </VRow>
  </VForm>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
