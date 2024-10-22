<script setup>
import { paginationMeta } from '@/@fake-db/utils'
import { useInvoiceStore } from '@/views/apps/invoice/useInvoiceStore'
import { useRouter } from "vue-router"
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const invoiceListStore = useInvoiceStore()
const searchQuery = ref('')
const dateRange = ref('')
const selectedStatus = ref()
const totalInvoices = ref(0)
const invoices = ref([])
const selectedRows = ref([])
const router = useRouter()

const options = ref({
  page: 1,
  itemsPerPage: 10,
  sortBy: [],
  groupBy: [],
  search: undefined,
})

const isLoading = ref(false)

// 👉 headers
const headers = [
  {
    title: '#ID',
    key: 'id',
  },
  {
    title: 'Trending',
    key: 'trending',
    sortable: false,
  },

  {
    title: 'Amount',
    key: 'total',
  },
  {
    title: 'Date',
    key: 'date',
  },
  {
    title: 'Status',
    key: 'status',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

// 👉 Fetch Invoices
const fetchInvoices = (query, currentStatus, firstDate, lastDate, option) => {
  isLoading.value = true
  invoiceListStore.fetchInvoices({
    q: query,
    status: currentStatus,
    startDate: firstDate,
    endDate: lastDate,
    options: option,
  }).then(response => {
    invoices.value = response.data.invoices
    totalInvoices.value = response.data.totalInvoices
    options.value.page = response.data.page
  }).catch(error => {
    console.log(error)
  })
  isLoading.value = false
}

// 👉 Invoice balance variant resolver
const resolveInvoiceBalanceVariant = status => {
  

  if (status === 'succeeded')

    return {
      status: 'Succeeded',
      chip: { color: 'success' },
    }
  if (status === 'failed')
    return {
      status: 'Failed',
      chip: { color: 'success' },
    }
  
  return {
    status: status,
    chip: { variant: 'text' },
  }
}

const resolveInvoiceStatusVariantAndIcon = status => {
  if (status === 'Partial Payment')
    return {
      variant: 'warning',
      icon: 'tabler-circle-half-2',
    }
  if (status === 'succeeded' || status === 'Paid' || status === 'paid')
    return {
      variant: 'success',
      icon: 'tabler-circle-check',
    }
  if (status === 'Downloaded' || status === 'downloaded')
    return {
      variant: 'info',
      icon: 'tabler-download',
    }
  if (status === 'Draft' || status === 'draft')
    return {
      variant: 'secondary',
      icon: 'tabler-device-floppy',
    }
  if (status === 'Sent' || status === 'sent')
    return {
      variant: 'primary',
      icon: 'tabler-mail',
    }
  if (status === 'Past Due' || status === 'past due')
    return {
      variant: 'error',
      icon: 'tabler-alert-circle',
    }
  
  return {
    variant: 'secondary',
    icon: 'tabler-x',
  }
}

const computedMoreList = computed(() => {
  return paramId => [
    {
      title: 'Download',
      value: 'download',
      prependIcon: 'tabler-download',
    },
    {
      title: 'Edit',
      value: 'edit',
      prependIcon: 'tabler-pencil',
      to: {
        name: 'apps-invoice-edit-id',
        params: { id: paramId },
      },
    },
    {
      title: 'Duplicate',
      value: 'duplicate',
      prependIcon: 'tabler-layers-intersect',
    },
  ]
})

const deleteInvoice = id => {
  invoiceListStore.deleteInvoice(id).then(() => {
    fetchInvoices(searchQuery.value, selectedStatus.value, dateRange.value?.split('to')[0], dateRange.value?.split('to')[1], options.value)
  }).catch(error => {
    console.log(error)
  })
}

// 👉 watch for data table options like itemsPerPage,page,searchQuery,sortBy etc...
watchEffect(() => {
  const [start, end] = dateRange.value ? dateRange.value.split('to') : ''

  fetchInvoices(searchQuery.value, selectedStatus.value, start, end, options.value)
})

const openInvoice = url => {
  window.location.href = url
}
</script>

<template>
  <VCard
    v-if="invoices"
    id="invoice-list"
  >
    <VCardText class="d-flex align-center flex-wrap gap-3">
      <!-- 👉 Create invoice -->
      <!--      <VBtn -->
      <!--        prepend-icon="tabler-plus" -->
      <!--        :to="{ name: 'apps-invoice-add' }" -->
      <!--        class="me-3" -->
      <!--      > -->
      <!--        Create invoice -->
      <!--      </VBtn> -->

      <VSpacer />

      <div class="d-flex align-end flex-wrap gap-3">
        <!-- 👉 Search  -->
        <div class="invoice-list-search">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Invoice"
            density="compact"
            class="me-3"
          />
        </div>
        <div class="invoice-list-status">
          <AppSelect
            v-model="selectedStatus"
            density="compact"
            label="Select Status"
            clearable
            clear-icon="tabler-x"
            :items="['Downloaded', 'Draft', 'Sent', 'Paid', 'Partial Payment', 'Past Due']"
            style="inline-size: 12rem;"
          />
        </div>
      </div>
    </VCardText>

    <VDivider />

    <!-- SECTION Datatable -->
    <VDataTableServer
      v-model="selectedRows"
      v-model:items-per-page="options.itemsPerPage"
      v-model:page="options.page"
      :loading="isLoading"
      show-select
      :items-length="totalInvoices"
      :headers="headers"
      :items="invoices"
      class="text-no-wrap"
      @update:options="options = $event"
    >
      <!-- Trending Header -->
      <template #column.trending>
        <VIcon
          size="22"
          icon="tabler-trending-up"
        />
      </template>

      <!-- id -->
      <template #item.id="{ item }">
        {{ item.raw.id }}
      </template>

      <!-- trending -->
      <template #item.trending="{ item }">
        <VTooltip>
          <template #activator="{ props }">
            <VAvatar
              :size="30"
              v-bind="props"
              :color="resolveInvoiceStatusVariantAndIcon(item.raw.status).variant"
              variant="tonal"
            >
              <VIcon
                :size="20"
                :icon="resolveInvoiceStatusVariantAndIcon(item.raw.status).icon"
              />
            </VAvatar>
          </template>
          <p class="mb-0">
            {{ item.raw.status }}
          </p>
          <p class="mb-0">
            Balance: {{ item.raw.balance }}
          </p>
          <p class="mb-0">
            Due date: {{ item.raw.date }}
          </p>
        </VTooltip>
      </template>

      <!-- Total -->
      <template #item.total="{ item }">
        ${{ item.raw.total }}
      </template>

      <!-- Issued Date -->
      <template #item.date="{ item }">
        {{ item.raw.createdAt }}
      </template>

      <!-- Balance -->
      <template #item.status="{ item }">
        <VChip
          
          :color="resolveInvoiceBalanceVariant(item.raw.status).chip.color"
          label
        >
          {{ (resolveInvoiceBalanceVariant(item.raw.status).status) }}
        </VChip>
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <IconBtn @click="openInvoice(item.raw.invoice_url)">
          <VIcon icon="tabler-eye" />
        </IconBtn>

        <!--
          <IconBtn @click="deleteInvoice(item.raw.id)">
          <VIcon icon="tabler-trash" />
          </IconBtn>
          <MoreBtn
          :menu-list="computedMoreList(item.raw.id)"
          item-props
          color="undefined"
          />
        -->
      </template>

      <template #bottom>
        <VDivider />
        <div class="d-flex align-center justify-sm-space-between justify-center flex-wrap gap-3 pa-5 pt-3">
          <p class="text-sm text-disabled mb-0">
            {{ paginationMeta(options, totalInvoices) }}
          </p>

          <VPagination
            v-model="options.page"
            :length="Math.ceil(totalInvoices / options.itemsPerPage)"
            :total-visible="$vuetify.display.xs ? 1 : Math.ceil(totalInvoices / options.itemsPerPage)"
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
    <!-- !SECTION -->
    <VDivider />
  </VCard>
</template>

<style lang="scss">
#invoice-list {
  .invoice-list-actions {
    inline-size: 8rem;
  }

  .invoice-list-search {
    inline-size: 12rem;
  }
}
</style>
