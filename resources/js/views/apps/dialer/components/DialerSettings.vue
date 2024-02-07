<script setup>
const props = defineProps({
  countries: {
    type: Array,
    required: true,
  },
  numbers: {
    type: Array,
    required: true,
  },
})

import DialerLoader from "@/pages/dialerLoader.vue"
import { useDialerStore } from "@/views/apps/dialer/useDialerStore"
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const audioOutputDevices = ref([])
const inputDevices = ref([])
const ringtoneDevices = ref([])
const currentOutputDevice = ref('')
const currentRingtoneDevice = ref('')
const currentInputDevice = ref('')
const dialerStore = useDialerStore()
const micTestActive = ref(false)
const isLoading = ref(false)
const loading = ref(false)
const countryOutboundCalls = ref('')
const numberOutboundCalls = ref('')
const numberOutboundSms = ref('')
const filteredNumbers = ref(structuredClone(toRaw(props.numbers)))
const countries = ref(structuredClone(toRaw(props.countries)))
const activeNoiseCancellation = ref(true)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')

async function getCurrentAudioOutputDevice() {
  try {
    // Get a list of available audio output devices
    const devices = await navigator.mediaDevices.enumerateDevices()

    const audioOutputDeviceId = localStorage.getItem('audioOutputDeviceId')

    if (audioOutputDeviceId) {
      // Find the currently selected audio output device
      const currentAudDevice = devices.find(device => device.kind === 'audiooutput' && device.deviceId === audioOutputDeviceId)

      if (currentAudDevice) {
        // The current audio output device is found
        console.log('Current Audio Output Device:', currentAudDevice.label)
        currentOutputDevice.value = currentAudDevice.deviceId
      } else {
        // No audio output device found
        console.log('No Audio Output Device Found')
        
        return null
      }
    }

  } catch (error) {
    console.error('Error getting audio output devices:', error)
    
    return null
  }
}

async function getCurrentInputDevice() {
  try {
    // Get a list of available audio output devices
    const devices = await navigator.mediaDevices.enumerateDevices()

    const audioInputDeviceId = localStorage.getItem('audioInputDeviceId')

    if (audioInputDeviceId) {
      // Find the currently selected audio output device
      const currentInpDevice = devices.find(device => device.kind === 'audioinput' && device.deviceId === audioInputDeviceId)
      if (currentInpDevice) {
        // The current audio output device is found
        console.log('Current Input Device:', currentInpDevice.label)
        currentInputDevice.value = currentInpDevice.deviceId
      } else {
        // No audio output device found
        console.log('No Input Device Found')
        
        return null
      }
    }

  } catch (error) {
    console.error('Error getting audio output devices:', error)
    
    return null
  }
}

async function testAudioOutputDevice(deviceId) {
  try {
    const device = dialerStore.twilioDevice


    // await device.audio.speakerDevices.set(deviceId)
    await device.audio.speakerDevices.test()
  } catch (error) {
    console.error('Error testing audio output:', error)
  }
}

async function getCurrentRingtoneDevice() {
  try {
    const device = dialerStore.twilioDevice
    const deviceSet = new Set(device.audio.ringtoneDevices.get())

    deviceSet.forEach(device => {
      currentRingtoneDevice.value = device.deviceId
    })
  } catch (error) {
    console.error('Error getting ringtone device:', error)
    
    return null
  }
}

async function testRingtoneDevices(){
  try {
    const device = dialerStore.twilioDevice

    await device.audio.ringtoneDevices.test()
  } catch (error) {
    console.error('Error testing ringtone device:', error)
    
    return null
  }
}

let isListening = true

async function testInputDevice(deviceId) {
  try {
    const constraints = {
      audio: { deviceId: deviceId },
    }

    const stream = await navigator.mediaDevices.getUserMedia(constraints)

    // Create a muted audio element to prevent audio output
    const audioElement = new Audio()

    audioElement.srcObject = stream
    audioElement.muted = true
    audioElement.play().catch(error => {
      console.error('Error playing muted audio:', error)
    })

    const audioContext = new AudioContext()

    // Create a GainNode to control volume
    const gainNode = audioContext.createGain()

    gainNode.gain.value = 3.0 // Increase the gain value for higher volume

    const analyser = audioContext.createAnalyser()
    const microphone = audioContext.createMediaStreamSource(stream)

    // Connect the microphone source to the gain node
    microphone.connect(gainNode)

    // Connect the gain node to the analyser
    gainNode.connect(analyser)

    analyser.connect(audioContext.destination)

    micTestActive.value = true
    analyser.fftSize = 256

    const bufferLength = analyser.frequencyBinCount
    const dataArray = new Uint8Array(bufferLength)

    const volumeBar = document.getElementById('volume-bar')

    function updateVolume() {
      analyser.getByteFrequencyData(dataArray)

      const volume = dataArray.reduce((acc, val) => acc + val, 0) / bufferLength
      const percent = (volume / 256) * 100

      volumeBar.style.width = percent + '%'

      // Add a condition to stop listening after a certain time (e.g., 10 seconds)
      if (!isListening) {
        micTestActive.value = false
        stream.getTracks().forEach(track => track.stop())
        audioContext.close()
      }

    }

    setInterval(updateVolume, 100)

    // Automatically stop listening after 10 seconds (adjust the time as needed)
    setTimeout(() => {
      isListening = false
    }, 20000) // Stop after 20 seconds

    isListening = true
  } catch (error) {
    console.error('Error testing audio output:', error)
  }
}

watch(currentOutputDevice, value => {
  localStorage.setItem('audioOutputDeviceId', value)
  currentOutputDevice.value = value

  const device = dialerStore.twilioDevice


  // Use the setSinkId method to set the audio output device for Twilio
  device.audio.speakerDevices.set(value)
    .then(() => {
      console.log('Output device updated successfully')
    })
    .catch(error => {
      console.error('Error setting audio output device:', error)
    })
})

watch(currentInputDevice, value => {
  localStorage.setItem('audioInputDeviceId', value)
  currentInputDevice.value = value

  const device = dialerStore.twilioDevice


  // Use the setSinkId method to set the audio output device for Twilio
  device.audio.setInputDevice(value)
    .then(() => {
      console.log('Input device updated successfully')
    })
    .catch(error => {
      console.error('Error setting audio input device:', error)
    })
})

watch(currentRingtoneDevice, value => {
  const device = dialerStore.twilioDevice

  device.audio.ringtoneDevices.set(value)
  currentRingtoneDevice.value = value
})

// 👉 Watching props change
watch(props, () => {
  countries.value = structuredClone(toRaw(props.countries))
  filteredNumbers.value = structuredClone(toRaw(props.numbers))
})

const settingSave = type => {
  if(type =="country_outbound_calls" && countryOutboundCalls.value ==""){
    snackbarMessage.value = `Please select default country for outbound calls and texts.`
    snackbarActionColor.value = `error`
    isSnackbarVisible.value = true

    return
  }
  if(type =="number_outbound_calls" && numberOutboundCalls.value ==""){
    snackbarMessage.value = `Please select default number for outbound calls.`
    snackbarActionColor.value = `error`
    isSnackbarVisible.value = true

    return
  }
  if(type =="number_outbound_sms" && numberOutboundSms.value ==""){
    snackbarMessage.value = `Please select default number for outbound SMS/MMS.`
    snackbarActionColor.value = `error`
    isSnackbarVisible.value = true

    return
  }

  dialerStore.settingSave({
    type: type,
    country_outbound_calls: countryOutboundCalls.value,
    number_outbound_calls: numberOutboundCalls.value,
    number_outbound_sms: numberOutboundSms.value,
    active_noise_cancellation: activeNoiseCancellation.value ===true ? false: true,
  })
    .then(res => {
      snackbarMessage.value = res.data.message
      snackbarActionColor.value = `success`
      isSnackbarVisible.value = true
    })
    .catch(error => {
      snackbarMessage.value = error
      snackbarActionColor.value = `error`
      isSnackbarVisible.value = true
    })
}

const fetchSetting = () => {
  loading.value = true
  dialerStore.fetchSetting()
    .then(res => {
      if(res.setting){
        countryOutboundCalls.value = res?.setting?.country_outbound_calls
        numberOutboundCalls.value = res?.setting?.number_outbound_calls
        numberOutboundSms.value = res?.setting?.number_outbound_sms
        activeNoiseCancellation.value = res?.setting?.active_noise_cancellation ===1 ? true : false
        loading.value = false
      }else{
        loading.value = false
      }
    })
    .catch(error => {
      loading.value = false
      console.log(error)
    })
}

onMounted(async () => {
  countries.value = props.countries

  const device = dialerStore.twilioDevice

  device.audio.availableOutputDevices.forEach((device, id) => {
    audioOutputDevices.value.push({
      deviceId: id,
      label: device.label,
    })
  })
  device.audio.availableInputDevices.forEach((device, id) => {
    inputDevices.value.push({
      deviceId: id,
      label: device.label,
    })
  })

  device.audio.availableOutputDevices.forEach((device, id) => {
    ringtoneDevices.value.push({
      deviceId: id,
      label: device.label,
    })
  })

  await getCurrentAudioOutputDevice()
  await getCurrentInputDevice()
  await getCurrentRingtoneDevice()
  await fetchSetting()
})
</script>

<template>
  <div>
    <div class="d-flex flex-row w-100 h-30 pa-3 border__bottom_shadow">
      <h5 class="text-h5">
        Settings
      </h5>
    </div>
    <DialerLoader :is-processing="loading" />
    <VExpansionPanels
      class="d-block mt-4"
      multiple
    >
      <PerfectScrollbar
        :options="{ wheelPropagation: false }"
        style="max-block-size: 34rem;"
      >
        <VExpansionPanel style="width: 100%;">
          <div class="d-flex align-items-center justify-content-between">
            <VCol
              cols="8"
              md="9"
              sm="9"
            >
              <AppAutocomplete
                v-model="countryOutboundCalls"
                label="Default country for outbound calls and texts"
                :items="countries"
                item-title="name"
                item-vale="name"
                placeholder="Select Country"
              />
              <span class="text-sm"><small>This allows you to dial numbers without typing in the area code.</small></span>
            </VCol>
            <VCol
              cols="4"
              md="3"
              sm="3"
            >
              <VBtn
                color="primary"
                class="mt-6 ml-6"
                @click="settingSave('country_outbound_calls')"
              >
                Save
              </VBtn>
            </VCol>
          </div>
          <p class="border-b" />

          <div class="d-flex align-items-center justify-content-between">
            <VCol
              cols="8"
              md="9"
              sm="9"
            >
              <AppAutocomplete
                v-model="numberOutboundCalls"
                label="Default number for outbound calls"
                :items="filteredNumbers"
                item-title="number"
                item-value="number"
                placeholder="Select Number"
              />
              <span class="text-sm"><small>This will be the default number for making outbound calls.</small></span>
            </VCol>
            <VCol
              cols="4"
              md="3"
              sm="3"
            >
              <VBtn
                color="primary"
                class="mt-6 ml-6"
                @click="settingSave('number_outbound_calls')"
              >
                Save
              </VBtn>
            </VCol>
          </div>
          <p class="border-b" />

          <div class="d-flex align-items-center justify-content-between">
            <VCol
              cols="9"
              md="9"
              ms="9"
            >
              <AppAutocomplete
                v-model="numberOutboundSms"
                label="Default number for outbound SMS/MMS"
                :items="filteredNumbers"
                item-title="number"
                item-value="number"
                placeholder="Select Number SMS/MMS"
              />
              <span class="text-sm"><small>This will be the default number for sending SMS/MMS.</small></span>
            </VCol>
            <VCol
              cols="4"
              md="3"
              sm="3"
            >
              <VBtn
                color="primary"
                class="mt-6 ml-6"
                @click="settingSave('number_outbound_sms')"
              >
                Save
              </VBtn>
            </VCol>
          </div>
          <p class="border-b" />

          <VRow
            class="pa-3 pr-0 mr-0 pt-1"
            align="center"
            justify="space-between"
          >
            <VCol
              cols="9"
              md="9"
              sm="9"
            >
              <h5>Active Noise Cancellation</h5>
              <span class="text-sm"><small>Filters out sound from your mic that isn't speech.</small></span>
            </VCol>
            <VCol
              cols="3"
              md="3"
              sm="3"
            >
              <div class="d-flex flex-row justify-center">
                <VSwitch
                  v-model="activeNoiseCancellation"
                  @click="settingSave('active_noise_cancellation')"
                />
              </div>
            </VCol>
          </VRow>
          <p class="border-b" />

          <VExpansionPanelTitle>
            <VIcon
              icon="tabler-volume"
              class="mr-2"
            />
            Audio Settings
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <VCol cols="12">
              <AppAutocomplete
                v-model="currentOutputDevice"
                label="Select Output Devices"
                :items="audioOutputDevices"
                item-title="label"
                item-value="deviceId"
              />
              <VBtn
                size="small"
                class="mt-2"
                @click="testAudioOutputDevice(currentOutputDevice)"
              >
                Test
                <VIcon
                  end
                  icon="tabler-volume"
                />
              </VBtn>

              <VDivider class="mt-3" />

              <AppAutocomplete
                v-model="currentInputDevice"
                class="mt-5"
                label="Select Input Devices"
                :items="inputDevices"
                item-title="label"
                item-value="deviceId"
              />

              <div :class="!micTestActive ? 'microphone-bar d-none' : 'microphone-bar'">
                <div
                  id="volume-bar"
                  class="bar"
                />
              </div>

              <VBtn
                size="small"
                class="mt-2"
                @click="testInputDevice(currentInputDevice)"
              >
                Test
                <VIcon
                  end
                  icon="tabler-volume"
                />
              </VBtn>

              <AppAutocomplete
                v-model="currentRingtoneDevice"
                class="mt-5"
                label="Select Ringtone Devices"
                :items="ringtoneDevices"
                item-title="label"
                item-value="deviceId"
              />

              <VBtn
                size="small"
                class="mt-2"
                @click="testRingtoneDevices(currentRingtoneDevice)"
              >
                Test
                <VIcon
                  end
                  icon="tabler-volume"
                />
              </VBtn>
            </VCol>
          </VExpansionPanelText>
        </VExpansionPanel>
      </PerfectScrollbar>
    </VExpansionPanels>

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

<style lang="css">
.h-30 {
  block-size: 45px;
}

.border__bottom_shadow {
  border-radius: inherit;
  box-shadow: 0 2px 6px rgba(15, 20, 34, 14%), 0 0 transparent, 0 0 transparent;
}

.microphone-bar {
  position: relative;
  background-color: #eee;
  block-size: 10px;
  inline-size: 200px;
  margin-block-start: 5px;
  transition: 0.1s ease-in-out;
}

.bar {
  background-color: rgba(56, 166, 227, 100%);
  block-size: 100%;
  inline-size: 0;
}
</style>
