<script setup>
const props = defineProps({
  data: {
    type: null,
    required: true,
  },
})

const dataProperties = ref({})

onMounted(() => {
  dataProperties.value = {
    about: [
      { property: 'Full Name', value: props.data.fullName, icon: 'tabler-user' },
      { property: 'Status', value: 'active', icon: 'tabler-check' },
      { property: 'Role', value: 'Contact', icon: 'tabler-star' },
      { property: 'Country', value: props.data.country ?? 'None', icon: 'tabler-flag' },
      { property: 'Language', value: 'English', icon: 'tabler-language' },
    ],
    contacts: [
      { property: 'Contact', value: props.data.phone, icon: 'tabler-phone' },
      { property: 'Email', value: props.data.email, icon: 'tabler-mail' },
    ],
    teams: [
      { property: 'Backend Developer', value: '(126 Members)', icon: 'tabler-brand-angular', color: 'error' },
      { property: 'VueJS Developer', value: '(98 Members)', icon: 'tabler-brand-vue', color: 'success' },
    ],
    overview: [
      { property: 'Task Compiled', value: '13.5k', icon: 'tabler-check' },
      { property: 'Connections', value: '897', icon: 'tabler-user' },
      { property: 'Projects Compiled', value: '146', icon: 'tabler-layout-grid-add' },
    ],
  }

  console.log(dataProperties.value)
})
</script>

<template>
  <VCard class="mb-4">
    <VCardText>
      <p class="text-xs">
        ABOUT
      </p>

      <VList class="card-list text-medium-emphasis">
        <VListItem
          v-for="item in dataProperties['about']"
          :key="item.property"
        >
          <template #prepend>
            <VIcon
              :icon="item.icon"
              size="20"
              class="me-2"
            />
          </template>
          <VListItemTitle>
            <span class="font-weight-medium me-1">{{ item.property }}:</span>
            <span>{{ item.value }}</span>
          </VListItemTitle>
        </VListItem>
      </VList>

      <p class="text-xs mt-5">
        CONTACTS
      </p>

      <VList class="card-list text-medium-emphasis">
        <VListItem
          v-for="item in dataProperties['contacts']"
          :key="item.property"
        >
          <template #prepend>
            <VIcon
              :icon="item.icon"
              size="20"
              class="me-2"
            />
          </template>
          <VListItemTitle>
            <span class="font-weight-medium me-1">{{ item.property }}:</span>
            <span>{{ item.value }}</span>
          </VListItemTitle>
        </VListItem>
      </VList>

      <p class="text-xs mt-5">
        TEAMS
      </p>

      <VList class="card-list text-medium-emphasis">
        <VListItem
          v-for="item in dataProperties['teams']"
          :key="item.property"
        >
          <template #prepend>
            <VIcon
              :icon="item.icon"
              size="20"
              :color="item.color"
              class="me-2"
            />
          </template>
          <VListItemTitle>
            <span class="font-weight-medium me-1">{{ item.property }}:</span>
            <span>{{ item.value }}</span>
          </VListItemTitle>
        </VListItem>
      </VList>
    </VCardText>
  </VCard>

  <VCard>
    <VCardText>
      <p class="text-xs">
        OVERVIEW
      </p>

      <VList class="card-list text-medium-emphasis">
        <VListItem
          v-for="item in dataProperties['overview']"
          :key="item.property"
        >
          <template #prepend>
            <VIcon
              :icon="item.icon"
              size="20"
              class="me-2"
            />
          </template>
          <VListItemTitle>
            <span class="font-weight-medium me-1">{{ item.property }}:</span>
            <span>{{ item.value }}</span>
          </VListItemTitle>
        </VListItem>
      </VList>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}
</style>
