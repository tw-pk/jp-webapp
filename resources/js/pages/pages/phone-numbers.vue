<script setup>
import { paginationMeta } from '@/@fake-db/utils'
import { usePhoneNumberStore } from "@/views/apps/number/usePhoneNumberStore"
import { avatarText } from "@core/utils/formatters"
import { ref } from "vue"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const phoneNumberStore = usePhoneNumberStore()
const isAddNoDialogVisible = ref(false)
const searchQuery = ref('')
const numbers = ref([])
const totalPage = ref(1)
const totalRecord = ref(0)
const isProcessing = ref(false)

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

// headers
const headers = [
  {
    title: 'PHONE NUMBER',
    key: 'number',
  },
  {
    title: 'COUNTRY',
    key: 'country',
  },
  {
    title: 'NUMBER OWNER',
    key: 'owner',
  },
  {
    title: 'SHARED WITH',
    key: 'shared',
  },
]

const resolveStatusVariant = status => {
  if (status === 1)
    return {
      color: 'primary',
      text: 'Current',
    }
  else if (status === 2)
    return {
      color: 'success',
      text: 'Professional',
    }
  else if (status === 3)
    return {
      color: 'error',
      text: 'Rejected',
    }
  else if (status === 4)
    return {
      color: 'warning',
      text: 'Resigned',
    }
  else
    return {
      color: 'info',
      text: 'Applied',
    }
}

// 👉 Fetching users
const fetchPhoneNumbers = () => {
  isProcessing.value = true
  phoneNumberStore.fetchPhoneNumbers({
    q: searchQuery.value,
    options: options.value,
  }).then(response => {
    numbers.value = response.data.numbers.data
    totalPage.value = response.data.totalPage
    totalRecord.value = response.data.totalRecord
    options.value.page = response.data.page
    
    isProcessing.value = false

  }).catch(error => {
    isProcessing.value = false
    console.log(error)
  })
}

const resolveStatusColor = {
  'Online': 'success',
  'Away': 'warning',
  'Offline': 'secondary',
  'In Meeting': 'error',
}

const formatePhoneNumber = phoneNumber => {
  const cleaned = ('' + phoneNumber).replace(/\D/g, '')
  const match = cleaned.match(/^1?(\d{3})(\d{3})(\d{4})$/)
  if (match) {
    return '(' + match[1] + ') ' + match[2] + '-' + match[3]
  }
  
  return phoneNumber
}

const closePhoneNumberDialog = () => {
  fetchPhoneNumbers()
  isAddNoDialogVisible.value = false
}

const debounceInput = (fn, delay) => {
  let timeoutId
  
  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      fn(...args)
    }, delay)
  }
}

// Create a debounced version of the performSearch function
const debouncedPerformSearch  = debounceInput(fetchPhoneNumbers, 500) // Adjust the debounce delay as needed

onMounted(fetchPhoneNumbers)
</script>

<template>
  <div class="">
    <div class="pb-4 font-weight-bold text-h4">
      Phone Numbers
    </div>
    <VRow class="match-height">
      <VCol cols="12">
        <VCard>
          <VRow
            justify="space-between"
            class="__align-items-center pa-4 __border-bottom-light"
          >
            <VCol cols="3">
              <AppTextField
                v-model="searchQuery"
                placeholder="Search Number"
                density="compact"
                append-inner-icon="tabler-search"
                single-line
                hide-details
                dense
                outlined
                @keyup="debouncedPerformSearch"
              />
            </VCol>
            <VCol cols="9">
              <VRow class="__align-items-center justify-end mx-1">
                <VBtn
                  class="mr-2"
                  @click="isAddNoDialogVisible = !isAddNoDialogVisible"
                >
                  Add Number
                </VBtn>
                <IconBtn @click.prevent="">
                  <VIcon icon="tabler-download" />
                </IconBtn>
              </VRow>
            </VCol>
          </VRow>

          <!-- 👉Add Phone dialog -->
          <AddNoDialog
            v-model:is-dialog-visible="isAddNoDialogVisible"
            @closeDialog="closePhoneNumberDialog"
          />
          <VDivider />
          
          <!-- Show the loader -->
          <template v-if="isProcessing">
            <VProgressLinear
              indeterminate
              height="5"
              bg-color="primary"
              color="primary"
              :rounded="false"
            />
          </template>
          <VDataTableServer
            v-model:items-per-page="options.itemsPerPage"
            v-model:page="options.page"
            :items="numbers"
            :items-length="totalRecord"
            :headers="headers"
            class="text-no-wrap"
            show-select
            @update:options="options = $event"
          >
            <!-- Phone Number -->
            <template #item.number="{ item }">
              <div class="d-flex flex-column">
                <VBadge
                  dot
                  location="start center"
                  offset-x="2"
                  :color="resolveStatusColor['Online']"
                  class="me-3"
                >
                  <RouterLink
                    v-if="item.raw.isShared == 'personal'"
                    :to="{ name: 'pages-phone-setting-number', params: { number: item.raw.phone_number } }"
                    class="font-weight-medium user-list-name"
                  >
                    <span class="ms-4">{{ formatePhoneNumber(item.raw.phone_number) }}</span>
                  </RouterLink>
                  <span
                    v-else
                    class="font-weight-medium user-list-name ms-4"
                  >{{ formatePhoneNumber(item.raw.phone_number) }}</span>
                </VBadge>
                <span class="text-sm"><small>{{ formatePhoneNumber(item.raw.phone_number) }}-GP-Sny</small></span>
              </div>
            </template>

            <!-- Number owner -->
            <template #item.owner="{ item }">
              <div class="d-flex flex-column">
                {{ item.raw?.ownerFullName }}
              </div>
            </template>

            <!-- Shared column -->
            <template #item.shared="{ item }">
              <div class="v-avatar-group">
                <template v-if="item.raw.shared.length === 0">
                  <VAvatar
                    size="32"
                    color="#f6f6f7"
                  >
                    <span>N/A</span>
                  </VAvatar>
                </template>
                <template
                  v-for="(share, index) in item.raw.shared"
                  :key="index"
                >
                  <VAvatar
                    v-if="index < 3 || item.raw.shared.length === 4"
                    size="32"
                    :variant="!item.raw.avatar ? 'tonal' : undefined"
                  >
                    <VImg
                      v-if="share.avatar_url"
                      :src="share.avatar_url"
                    />
                    <span v-else>{{ avatarText(share?.firstname) }}</span>
                  </VAvatar>
                </template>
                <VAvatar
                  v-if="item.raw.shared.length > 4"
                  :color="$vuetify.theme.current.dark ? '#4A5072' : '#f6f6f7'"
                >
                  <span>+{{ item.raw.shared.length - 3 }}</span>
                </VAvatar>
              </div>
            </template>


            <!-- pagination -->
            <template #bottom>
              <VDivider />
              <div class="d-flex align-center justify-sm-space-between justify-center flex-wrap gap-3 pa-5 pt-3">
                <p class="text-sm text-disabled mb-0">
                  {{ paginationMeta(options, totalRecord) }}
                </p>

                <VPagination
                  v-model="options.page"
                  :length="Math.ceil(totalRecord / options.itemsPerPage)"
                  :total-visible="$vuetify.display.xs ? 1 : Math.ceil(totalRecord / options.itemsPerPage)"
                >
                  <template #prev="slotProps">
                    <VBtn
                      variant="tonal"
                      color="default"
                      v-bind="slotProps"
                      :icon="false"
                    >
                      Previous
                    </VBtn>
                  </template>

                  <template #next="slotProps">
                    <VBtn
                      variant="tonal"
                      color="default"
                      v-bind="slotProps"
                      :icon="false"
                    >
                      Next
                    </VBtn>
                  </template>
                </VPagination>
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>

<route lang="yaml">
meta:
  action: read
  subject: phone-numbers
</route>
