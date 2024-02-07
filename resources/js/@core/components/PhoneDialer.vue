<script setup>
import { useDialerConfig } from "@core/composable/useDialerConfig"

const props = defineProps({
  icons: {
    type: Array,
    required: true,
  },
})

const { dialer } = useDialerConfig()

console.log('dialer.value')
console.log(dialer.value)

const {
  state: currentDialerName,
  next: getNextDialerName,
  index: currentDialerIndex,
} = useCycleList(props.icons.map(t => t.name), { initialValue: dialer.value })

const changeDialerName = () => {
  dialer.value = getNextDialerName()
  window.open(`${import.meta.env.VITE_APP_URL}`+'dialer')
}

watch(dialer, val => {
  currentDialerName.value = val
})
</script>

<template>
  <IconBtn @click="changeDialerName">
    <VIcon
      size="26"
      :icon="props.icons[currentDialerIndex].icon"
    />
    <VTooltip
      activator="parent"
      open-delay="1000"
      scroll-strategy="close"
    >
      <span class="text-capitalize">{{ props.icons[currentDialerIndex].name }}</span>
    </VTooltip>
  </IconBtn>
</template>
