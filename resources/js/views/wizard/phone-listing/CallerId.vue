<script setup>
import { useCallerIdStore } from "@/views/apps/number/useCallerIdStore"
import { defineProps, ref } from 'vue'

const props = defineProps(['phoneNumber'])
const callerIdStore = useCallerIdStore()
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const count = ref(0)

const callerIds = ref([
  {
    incomingCaller: 'default',
    outboundCaller: 'default',
  },

])

const incomingOption = [
  {
    name: 'Default',
    value: 'default',
  },
  {
    name: 'JustCall Number',
    value: 'justcall_number',
  },
]

const outboundOption = [
  {
    name: 'Default',
    value: 'default',
  },
]


// 👉 Fetching caller ids
const fetchCallerIds = () => {
  callerIdStore.fetchCallerIds({
    phone_number: props.phoneNumber,
  }).then(response => {
    const data = response.data.phoneSetting
    
    callerIds.value[0].incomingCaller = data.incoming_caller_id

    callerIds.value[0].outboundCaller = data.outbound_caller_id
  }).catch(error => {
    console.error(error)
  })
}

onMounted(() => {
  fetchCallerIds()

  setTimeout(() => {
    count.value = 1
  }, 1000)
})


callerIds.value.forEach(callerId => {
  watch(callerId, (newCallerId, oldCallerId) => {
    if (count.value === 1) {
      callerIdStore.addCallerId({
        phone_number: props.phoneNumber,
        incoming_caller_id: newCallerId.incomingCaller,
        outbound_caller_id: newCallerId.outboundCaller,
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

})

const addCallerId = () => {
  callerIds.value.push({
    incomingCaller: '',
    outboundCaller: '',
  })
}

const remove = index => {
  callerIds.value.splice(index, 1)
}
</script>

<template>
  <VForm>
    <VRow>
      <VCol cols="12">
        <h4 class="text-h4 mb-3">
          Caller ID
        </h4>
      </VCol>

      <VCol
        v-for="(callerId, index) in callerIds"
        :key="index"
        cols="12"
      >
        <VRow>
          <VCol
            v-if="index > 0"
            cols="12"
          >
            <VDivider />
          </VCol>
          <VCol
            cols="12"
            sm="6"
          >
            <!-- 👉 Incoming Caller ID -->
            <AppSelect
              v-model="callerId.incomingCaller"
              label="Incoming Caller ID"
              :items="incomingOption"
              item-title="name"
              item-value="value"
            />
            
            <!-- 👉 Outbound Caller ID -->
            <AppSelect
              v-model="callerId.outboundCaller"
              label="Outbound Caller ID"
              :items="outboundOption"
              item-title="name"
              item-value="value"
              class="mt-4"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
          >
            <div
              v-if="index > 0"
              class="d-flex align-center mt-7"
            >
              <VBtn
                v-if="index > 0"
                size="30"
                color="secondary"
                variant="tonal"
                @click.prevent="remove(index)"
              >
                <VIcon
                  icon="tabler-x"
                  size="22"
                />
              </VBtn>
            </div>
            <div
              v-if="index <= 0"
              class="d-flex justify-end mt-15"
            />
            <!--
              <div
              v-if="index === callerIds.length - 1"
              class="d-flex mt-12"
              >
              <VBtn
              color="success"
              prepend-icon="tabler-plus"
              @click="addCallerId"
              >
              Add Caller ID
              </VBtn>
              </div>
            -->
          </VCol>
        </VRow>
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
