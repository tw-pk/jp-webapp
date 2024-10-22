<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  totalInput: {
    type: Number,
    required: false,
    default: 6,
  },
  default: {
    type: String,
    required: false,
    default: '',
  },
})

const emit = defineEmits(['updateOtp'])

const digits = ref([])
const refOtpComp = ref(null)

watch(() => props.default, newValue => {
  digits.value = newValue.split('')
})

digits.value = props.default.split('')

const defaultStyle = { style: 'max-width: 54px; text-align: center;' }

const handleKeyDown = (event, index) => {
  // Allow navigation keys (Tab, ArrowLeft, ArrowRight) and Ctrl + V (paste)
  if (['Tab', 'ArrowRight', 'ArrowLeft'].includes(event.code) || 
      (event.ctrlKey || event.metaKey) && event.code === 'KeyV') {
    return
  }

  event.preventDefault()

  if (event.code === 'Backspace') {
    digits.value[index - 1] = ''

    if (refOtpComp.value && index > 1) {
      const inputEl = refOtpComp.value.children[index - 2].querySelector('input')
      if (inputEl) inputEl.focus()
    }
    emit('updateOtp', digits.value.join(''))
    
    return
  }


  const numberRegExp = /^[0-9]$/
  if (numberRegExp.test(event.key)) {
    digits.value[index - 1] = event.key

    if (refOtpComp.value && index < props.totalInput) {
      const inputEl = refOtpComp.value.children[index].querySelector('input')
      if (inputEl) inputEl.focus()
    }
    emit('updateOtp', digits.value.join(''))
  }
}

const handlePaste = event => {
  const pastedData = (event.clipboardData || window.clipboardData).getData('text')

 
  if (pastedData.length === props.totalInput && /^\d+$/.test(pastedData)) {
    digits.value = pastedData.split('') 
    emit('updateOtp', digits.value.join('')) 

    if (refOtpComp.value) {
      const lastInput = refOtpComp.value.children[props.totalInput - 1].querySelector('input')
      if (lastInput) {
        lastInput.focus()
      }
    }
    event.preventDefault()
  }
}
</script>

<template>
  <div>
    <h6 class="text-h6 mb-3">
      Type your 6 digit security code
    </h6>
    <div
      ref="refOtpComp"
      class="d-flex align-center gap-4"
      @paste="handlePaste"
    >
      <AppTextField
        v-for="i in props.totalInput"
        :key="i"
        :model-value="digits[i - 1]"
        v-bind="defaultStyle"
        maxlength="1"
        @keydown="handleKeyDown($event, i)"
      />
    </div>
  </div>
</template>

<style lang="scss">
.v-field__field {
  input {
    padding: 0.5rem;
    font-size: 1.25rem;
    text-align: center;
  }
}
</style>
