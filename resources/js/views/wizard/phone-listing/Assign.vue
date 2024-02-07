<script setup>
import { useAssignStore } from "@/views/apps/number/useAssignStore"
import { defineProps, ref } from 'vue'

const props = defineProps(['phoneNumber'])
const assignStore = useAssignStore()

const member = ref(null)
const members = ref([])
const team = ref(null)
const teams = ref([])
const assignVForm = ref()
const isDisabled = ref(false)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const errorMemberMsg = ref('')
const errorTeamMsg = ref('')

// 👉 Fetching Members
const fetchMembers = () => {
  assignStore.fetchMembers().then(response => {
    members.value = response.data.inviteMembers
  }).catch(error => {
    console.error(error)
  })
}

// 👉 Fetching teams
const fetchTeams = () => {
  assignStore.fetchTeams().then(response => {
    teams.value = response.data.teams
  }).catch(error => {
    console.error(error)
  })
}

onMounted(() => {
  fetchMembers()
  fetchTeams()

})

const addAssignNumber = event => {
  isDisabled.value = true
  isLoading.value = true 

  event.preventDefault()
  if (member.value !== null || team.value !== null) {
    errorMemberMsg.value = ''
    errorTeamMsg.value = '' 
    
    assignStore.addAssignNumber({
      number: props.phoneNumber,
      member: member.value,
      team: team.value,
    }).then(response => {
      
      snackbarMessage.value = response.data.message
      snackbarActionColor.value = `success`
      isSnackbarVisible.value = true

      // Clear input fields
      member.value = null
      team.value = null
    })
      .catch(error => {
        console.log(error)
       
      })
  } else {
    errorMemberMsg.value = 'At least one field is required'
    errorTeamMsg.value = 'At least one field is required'
  }
  isDisabled.value = false
  isLoading.value = false
}
</script>

<template>
  <div>
    <VForm
      ref="assignVForm"
      @submit.prevent="addAssignNumber"
    >
      <VRow>
        <VCol cols="12">
          <h4 class="text-h4 mb-3">
            Assign Number
          </h4>
        </VCol>

        <VCol
          cols="12"
          sm="6"
        >
          <div>
            <!-- 👉 Assign team member -->
            <AppSelect
              v-model="member"
              label="Assign team member"
              :items="members"
              item-title="fullname"
              item-value="id"
              density="compact"
              clearable
              :error-messages="errorMemberMsg"
            />
          </div>

          <div class="mt-4">
            <!-- 👉 Or, Assign To Group -->
            <AppSelect
              v-model="team"
              label="Or, Assign To Group"
              :items="teams"
              item-title="name"
              item-value="id"
              density="compact"
              clearable
              :error-messages="errorTeamMsg"
            />
          </div>
        </VCol>
        <VCol cols="12">
          <div class="d-flex justify-end mt-8">
            <VBtn
              color="success"
              type="submit"
              :disabled="isDisabled"
              :loading="isDisabled"
            >
              Assign
            </VBtn>
          </div>
        </VCol>
      </VRow>
    </VForm>
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
  </div>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
