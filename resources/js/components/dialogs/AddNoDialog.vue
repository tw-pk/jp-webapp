<script setup>
import { defineEmits, defineProps } from 'vue'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'closeDialog'])

const dialogVisibleUpdate = val => {
  emit('update:isDialogVisible', val)
}

const handleCloseDialog = () => {
  emit('closeDialog')
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    fullscreen
    :scrim="false"
    transition="dialog-bottom-transition"
    @update:model-value="dialogVisibleUpdate"
  >
    <!-- Toolbar -->
    <div>
      <VToolbar color="primary">
        <VBtn
          icon
          variant="plain"
          @click="handleCloseDialog"
        >
          <VIcon
            color="white"
            icon="tabler-x"
          />
        </VBtn>
        <VToolbarTitle>Add Number</VToolbarTitle>

        <VSpacer />
      </VToolbar>
    </div>
    <VCard class="pricing-dialog pb-15">
      <VCardText>
        <AppPhoneNumber />
      </VCardText>
    </VCard>
  </VDialog>
</template>
