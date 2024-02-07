<script setup>
const props = defineProps({
  formData: {
    type: null,
    required: true,
  },
})

const emit = defineEmits(['update:formData'])

const formData = ref(props.formData)

watch(formData, () => {
  emit('update:formData', formData.value)
})
</script>

<template>
  <VForm>
    <VRow>
      <VCol
        cols="12"
        sm="12"
      >
        <!-- 👉 Furnished Status -->
        <AppSelect
          v-model="formData.furnishedStatus"
          label="Furnished Status"
          :items="['Fully Furnished', 'Furnished', 'Semi-Furnished', 'Unfurnished']"
        />
      </VCol>
      <VCol cols="12">
        <!-- 👉 Furnishing Details -->
        <AppSelect
          v-model="formData.furnishingDetails"
          label="Furnishing Details"
          multiple
          chips
          :items="['TV', 'AC', 'RO', 'Bed', 'Fridge', 'Wifi', 'Sofa', 'Cupboard', 'Microwave', 'Dining Table', 'Washing Machine']"
        />
      </VCol>
      <VCol
        cols="12"
        sm="6"
      >
        <!-- 👉 xCommon Area? -->
        <VRadioGroup v-model="formData.isCommonArea1">
          <template #label>
            <div class="text-high-emphasis">
              Is There Any Common Area?
            </div>
          </template>
          <VRadio
            label="Yes"
            value="true"
            class="ms-2"
          />
          <VRadio
            label="No"
            value="false"
            class="ms-2"
          />
        </VRadioGroup>
      </VCol>
      <VCol
        cols="12"
        sm="6"
      >
        <!-- 👉 Common Area? -->
        <VRadioGroup v-model="formData.isCommonArea2">
          <template #label>
            <div class="text-high-emphasis">
              Is There Any Common Area?
            </div>
          </template>
          <VRadio
            label="Yes"
            value="true"
            class="ms-2"
          />
          <VRadio
            label="No"
            value="false"
            class="ms-2"
          />
        </VRadioGroup>
      </VCol>
    </VRow>
  </VForm>
</template>
