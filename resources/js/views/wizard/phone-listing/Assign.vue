<script setup>
import { useAssignStore } from "@/views/apps/number/useAssignStore"
import { defineProps, ref, watch } from 'vue'

const props = defineProps(['phoneNumber'])
const assignStore = useAssignStore()

const member = ref(null)
const memberList = ref([])
const team = ref(null)
const teamList = ref([])
const assignVForm = ref()
const isDisabled = ref(false)
const isLoading = ref(false)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const errorMemberMsg = ref('')
const errorTeamMsg = ref('')
const isMemberDisabled = ref(false)
const isTeamDisabled = ref(false)

watch(member, newValue => {
  if (newValue && newValue.length > 0) {
    team.value = null
    isTeamDisabled.value = true
  }else{
    isTeamDisabled.value = false
  }
})

watch(team, newValue => {
  if (newValue && newValue.length > 0) {
    member.value = null
    isMemberDisabled.value = true
  }else{
    isMemberDisabled.value = false
  }
})

// 👉 Fetching Members And Teams
const fetchMembersTeams = () => {
  assignStore.fetchMembersTeams().then(response => {
    memberList.value = response.data.inviteMembers
    teamList.value = response.data.teams
  }).catch(error => {
    console.error(error)
  })
}



// 👉 Fetching assing number
const fetchAssignNumber = () => {
  assignStore.fetchAssignNumber({
    number: props.phoneNumber,
  }).then(response => {
    member.value = response.data.assigned
      .filter(item => item.invitation_id != null)
      .map(item => {
        const matchingMember = memberList.value.find(member => member.id === item.invitation_id)
        
        return matchingMember ? matchingMember : null
      })

    if (response.data.assigned.every(item => !item.invitation_id)) {
      team.value = response.data.assigned.map(item => {
        let matchingTeam = teamList.value.find(team => team.id === item.team_id)
        
        return matchingTeam ? matchingTeam : null
      })
    }
    
  }).catch(error => {
    console.error(error)
  })
}

onMounted(() => {
  fetchMembersTeams()
  fetchAssignNumber()
})

const addAssignNumber = event => {
  isDisabled.value = true
  isLoading.value = true 

  event.preventDefault()
  if (member.value !== null || team.value !== null) {
    errorMemberMsg.value = ''
    errorTeamMsg.value = '' 
    
    let memberIds = member.value !== null ? member.value.map(item => item.id) : []
    let teamIds = team.value !== null ? team.value.map(item => item.id) : []

    assignStore.addAssignNumber({
      number: props.phoneNumber,
      member: memberIds,
      team: teamIds,
    }).then(response => {
      
      snackbarMessage.value = response.data.message
      snackbarActionColor.value = `success`
      isSnackbarVisible.value = true

      // // Clear input fields
      // member.value = null
      // team.value = null
    })
      .catch(error => {
        console.log(error)
        snackbarMessage.value = error.message
        snackbarActionColor.value = `error`
        isSnackbarVisible.value = true
       
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
            <VCombobox
              v-model="member"
              multiple
              :items="memberList"
              item-title="fullname"
              item-value="id"
              clearable
              :error-messages="errorMemberMsg"
              label="Assign to member"
              placeholder="Assign to member"
              variant="outlined"
              :disabled="isMemberDisabled"
            />
          </div>

          <div class="mt-4">
            <!-- 👉 Or, Assign To Group -->
            <VCombobox
              v-model="team"
              multiple
              :items="teamList"
              item-title="name"
              item-value="id"
              clearable
              :error-messages="errorTeamMsg"
              label="Or, Assign To Team"
              placeholder="Or, Assign To Team"
              variant="outlined"
              :disabled="isTeamDisabled"
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
