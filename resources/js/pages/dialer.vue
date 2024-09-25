<script setup>
import User from "@/apis/user"
import { useDialerStore } from "@/views/apps/dialer/useDialerStore"
import { isEmpty } from "@core/utils"
import defaultAvatar from "@images/avatars/avatar-0.png"
import defaultAudio from "@images/default-audio-3s.mp3"
import defaultRingtone from "@images/default-ringtone.mp3"
import "flag-icons/css/flag-icons.min.css"
import { parsePhoneNumberFromString } from 'libphonenumber-js'
import { onMounted, ref } from 'vue'
import { PerfectScrollbar } from "vue3-perfect-scrollbar"
import { useTheme } from 'vuetify'
import DialerLoader from "./dialerLoader.vue"

//import ChatLog from '@/views/apps/chat/ChatLog.vue'
import { useChatStore } from '@/views/apps/chat/useChatStore'
import DialerSettings from "@/views/apps/dialer/components/DialerSettings.vue"
import axiosIns from "@axios"
import { avatarText } from '@core/utils/formatters'

const incomingCallSound = ref(null)
const vuetifyTheme = useTheme()
const dialerStore = useDialerStore()
const showNotification = ref(false)
const showNotificationMenu = ref(false)
const callerId = ref("") // Replace with the actual caller ID
const calledId = ref("")
const twilioDevice = computed(() => dialerStore.twilioDevice)
const currentNumber = ref('')
const muted = ref(false)
const onPhone = ref(false)
const onForward = ref(false)
const selectedTeamMember = ref(null)
const teamMembers = ref([])
const log = ref('Connecting...')
const CallTimer = ref(0)
const connection = ref(null)
const connected = ref(false)
const dropdownContainer = ref(null)
const isHomeActive = ref(true)
const isLogsActive = ref(false)
const isContactsActive = ref(false)
const isMessagesActive = ref(false)
const isSettingsActive = ref(false)
const conversationIsOpened = ref(false)
const inboxIsOpened = ref(false)
const currentActiveNumber = ref('')
const desiredCountryCode = 'us' 
const countries = ref([])
const contacts = ref([])
const activeNoiseCancellation = ref(true)
const selectedAudioDevice = ref('')
const selectedInputDevice = ref('')
const selectedOutputDevice = ref('')
const testAudioBtn = ref('Test')
const testInputBtn = ref('Test')
const testOutputBtn = ref('Test')
const countryOutboundCalls = ref('')
const numberOutboundCalls = ref('')
const numberOutboundSms = ref('')
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const recent = ref(null)
const missed = ref(null)
const voicemail = ref(null)
const loading = ref(false)
const searchLogs = ref('')
const searchContact = ref('')
const activeTabName = ref('home')
const incomingCall = ref(null)
const callSid = ref('')
const childCallSid = ref('')
const userNumber  = ref('')
const phoneNumbers = ref('')
const dialog = ref(false)
const forwardNumber = ref('');

//hold track
const onHold = ref(false) 
const phoneNumbersMsg = ref(false);
const conferenceMsg = ref('');
const isConference = ref(false);

// Chat message
const msg = ref('')

const callStartTime = ref(null)
const currentTime = ref(null)
const timerInterval = ref(null)
const isCallAccepted = ref(false)

// file input
const refInputEl = ref()
const isLeftSidebarOpen = ref(true)

const store = useChatStore()

const outputDeviceError = ref({
  cls: '',
  msg: '',
  color: '',
})

const inputDeviceError = ref({
  cls: '',
  msg: '',
  color: '',
})

const inputDevices = ref([
  {
    name: 'Default - External Microphone (Realtek(R) Audio)',
    value: 'default',
  },
  {
    name: 'Communications - External Microphone (Realtek(R) Audio)',
    value: 'communications',
  },
  {
    name: 'Microphone Array (Intel® Smart Sound Technology (Intel® SST))',
    value: 'e1c448fa5f73f2142b9e4eb07c23ec01c370c4fac34294d6345172960873956e',
  },
  {
    name: 'External Microphone (Realtek(R) Audio)',
    value: 'b3b38198ceab85b25454027d76523fc3afc2119d9547ab25a131e720fe39540e',
  },
])

const testInputDevice = () => {
  if (selectedInputDevice.value) {
    testInputBtn.value = "Testing ..."
    inputDeviceError.value.cls = "success"
    inputDeviceError.value.color = "green"
    inputDeviceError.value.msg = "Microphone is playing. Input device is working."

    // default
    const audio = new Audio()
    const constraints = { audio: { deviceId: selectedInputDevice.value } }

    navigator.mediaDevices.getUserMedia(constraints)
      .then(stream => {
        audio.srcObject = stream
        audio.play()
        
        // Automatically stop testing after 3.5 seconds
        setTimeout(() => {
          audio.pause()
          audio.srcObject = null
          testInputBtn.value = "Test"
          inputDeviceError.value.cls = ""
          inputDeviceError.value.color = ""
          inputDeviceError.value.msg = ""

        }, 10000)
      })
      .catch(error => {
        console.error('Error testing input device:', error)
      
        setTimeout(() => {
          testInputBtn.value = "Test"
        }, 0)
      })
  } else {
    inputDeviceError.value.cls = "error"
    inputDeviceError.value.color = "red"
    inputDeviceError.value.msg = "Please select an input device before testing."
  }
}

const testOutputDevice = async () => {
  if (!selectedOutputDevice.value) {
    outputDeviceError.value.cls = "error"
    outputDeviceError.value.color = "red"
    outputDeviceError.value.msg = "Please select an ringtone device before testing."
    
    return
  }
     
  testOutputBtn.value = "Testing ..."

  const audio = new Audio(defaultAudio)
  try {
    await audio.setSinkId(selectedOutputDevice.value)

    await audio.play()


    outputDeviceError.value.cls = "success"
    outputDeviceError.value.color = "green"
    outputDeviceError.value.msg = `Audio is playing. Ringtone device is working.`

  } catch (error) {
    outputDeviceError.value.cls = "error"
    outputDeviceError.value.color = "red"
    outputDeviceError.value.msg = `Error playing audio on device : ${error}`
  } finally {
    // Pause for a short duration between tests
    await new Promise(resolve => setTimeout(resolve, 2000))
    audio.pause()
    audio.src = defaultAudio
  }

  testOutputBtn.value = "Test"
}

const audioDeviceError = ref({
  cls: '',
  msg: '',
  color: '',
})

const audioDevices = ref([
  {
    name: 'Default - Headphones (Realtek(R) Audio)',
    value: 'default',
  },
  {
    name: 'Communications - Headphones (Realtek(R) Audio)',
    value: 'communications',
  },
  {
    name: 'Headphones (Realtek(R) Audio)',
    value: '14711a350d186bd6a8158366369cc6d79cf1f2f0992ae3faef89f9679a97b3f2',
  },
  {
    name: 'Speakers (Realtek(R) Audio)',
    value: '1d3ecba85406b5abc6888b4cff4fab6663c116e356984c0a5e865fcbda660973',
  },
])
 
const testAudio = async () => {

  if (!selectedAudioDevice.value) {
    audioDeviceError.value.cls = "error"
    audioDeviceError.value.color = "red"
    audioDeviceError.value.msg = "Please select an audio output device before testing."
    
    return
  }

  testAudioBtn.value = "Testing ..."

  const audio = new Audio(defaultAudio)
  try {
    await audio.setSinkId(selectedAudioDevice.value)

    await audio.play()

    audioDeviceError.value.cls = "success"
    audioDeviceError.value.color = "green"
    audioDeviceError.value.msg = `Audio is playing. Output device is working.`

  } catch (error) {
    audioDeviceError.value.cls = "error"
    audioDeviceError.value.color = "red"
    audioDeviceError.value.msg = `Error playing audio on device : ${error}`
  } finally {
    // Pause for a short duration between tests
    await new Promise(resolve => setTimeout(resolve, 2000))
    audio.pause()
    audio.src = defaultAudio
  }

  testAudioBtn.value = "Test"
}

const fetchCountries = () => {
  dialerStore.fetchCountries()
    .then(res => {
      countries.value = res.countries
    })
    .catch(error => {
      console.log(error)
    })
}

const fetchTeamMembers = async () => {
  try {
    const res = await dialerStore.fetchMemberList()

    console.log(res, 'here is the response of invite memeber')
    teamMembers.value = res.map(member => ({
      title: member.fullname,
      value: member.id,
    }))
  } catch (error) {
    console.error('Failed to fetch team members:', error)
  }
}

    
const currentTab = ref('tab-phone')
const filteredNumbers = ref([])
const userNumbers = ref(null)
const from = ref('')
const flag = ref('')
const call = ref()
    
const validPhone = computed(() => {
  return /^(\+\d{2})[0-9#*]+$/.test(currentNumber.value.replace(/[-()\s]/g, ''))
})
    
const handleSuccessfulRegistration = device => {
  console.log(device, 'here is device values')
  log.value = 'Connected'
  connected.value = true
  console.log('The device is ready to receive incoming calls.')
      
  // setInputDevice()
}
    
const toggleMute = () => {
  muted.value = !muted.value

  const device = dialerStore.twilioDevice

  device.mute(muted.value)
}


const forwardCall = () => {  
  onForward.value = true;
}    

const toggleConference = () => {
  isConference.value = true;
}

// Function to toggle hold and resume
const toggleHold = () => {
  if (onHold.value) {
    resumeCall()
  } else {
    holdCall()
  }
}

// Function to hold the call
const holdCall = async () => {
  const user = await User.auth()      
  const userId = user.data.id

  axiosIns.post('/twiml/place-on-hold', {
    callSid: callSid.value,
    to: userNumber.value,
    From: from.value,
  })
    .then(response => {
      onHold.value = true                    
      childCallSid.value = response.data.childCallSid
      console.log(response, 'here is response')
          
      console.log(childCallSid.value, 'here is childCallSid')
                                    
    })
    .catch(error => {
      console.error(error.response.data.error)
    })
}


// Function to resume the call
const resumeCall = () => {      
  console.log('inside resume function => ', childCallSid.value)
      
  axiosIns.post('/twiml/resume-from-hold', { 
    callSid: callSid.value,
    childCallSid: childCallSid.value,
    to: userNumber.value,
    From: from.value,
  })
    .then(response => {
      onHold.value = false         
      console.log(response)
           
    })
    .catch(error => {
      console.error("Error resuming the call:", error)
    })
}


  const transferCall = () => {      
    axiosIns.post('/twiml/transfer-call', { 
      callSid: callSid.value,      
      to: userNumber.value,
      From: from.value,
      forwardNumber: forwardNumber.value
    })
    .then(response => {        
      console.log(response)
          
    })
    .catch(error => {
      console.error("Error resuming the call:", error)
    })    
      
  }


  const connectForwardCall = () => {      
    axiosIns.post('/twiml/connect-transfer-call', { 
      callSid: callSid.value,      
      to: userNumber.value,
      From: from.value,
      forwardNumber: forwardNumber.value
    })
    .then(response => {        
      console.log(response)
          
    })
    .catch(error => {
      console.error("Error resuming the call:", error)
    })    
      
  }



const  openConferenceDialog = () => {
  dialog.value = true
} 

  const createConference = () => {      
      const numbers = (phoneNumbers.value).split(',').map(number => number.trim());
      axiosIns.post('/twiml/create-conference', { 
        numbers: JSON.stringify(numbers),
        from :from.value,
        callSid: callSid.value,
        to: userNumber.value,
        childCallSid: childCallSid.value

      })
      .then(response => {                  
          console.log(response);           
        })
        .catch(error => {
          console.error("Error resuming the call:", error.response.data.errors.message);
          conferenceMsg.value = error.response.data.errors.message;

    })
}

const callDuration = computed(() => {
  if (!callStartTime.value || !currentTime.value) return '00:00:00'
  
  const diff = Math.floor((currentTime.value - callStartTime.value) / 1000)
  const hours = Math.floor(diff / 3600).toString().padStart(2, '0')
  const minutes = Math.floor((diff % 3600) / 60).toString().padStart(2, '0')
  const seconds = (diff % 60).toString().padStart(2, '0')
  
  return `${hours}:${minutes}:${seconds}`
})

const startTimer = () => {
  callStartTime.value = Date.now()
  currentTime.value = Date.now()
  timerInterval.value = setInterval(() => {
    currentTime.value = Date.now()
  }, 1000)
}

const stopTimer = () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
  callStartTime.value = null
  currentTime.value = null
  isCallAccepted.value = false
}



const toggleCall = async event => {
  event.preventDefault()

  // Code for making outbound call...
  const user = await User.auth()
  const device = dialerStore.twilioDevice
  const userId = user.data.id

  // Function to check the user's credit balance
  const checkBalance = async userId => {
    try {
      const response = await fetch(`/api/auth/check-balance/${JSON.stringify(userId)}`)            
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
            
      const contentType = response.headers.get('content-type')
      if (!contentType || !contentType.includes('application/json')) {
        throw new TypeError("Received non-JSON response")
      }
      
      return await response.json()
    } catch (error) {
      console.error('Error checking balance:', error)
      
      return null
    }
  }

  if (!onPhone.value) {
    muted.value = false
    onPhone.value = true

    // Check user's balance
    const balanceResult = await checkBalance(userId)
    if (balanceResult && balanceResult.lowBalance) {
      // Show low balance message        
      connected.value = false
      log.value = 'Your balance is currently low. Please contact your team lead.'
      onPhone.value = false
      muted.value = true      
    } else {
      // Make outbound call with current number
      userNumber.value  = '+' + currentNumber.value.replace(/\D/g, '')      

      try {                

        const call = await device.connect({
          params: {
            To: userNumber.value,
            agent: JSON.stringify(userId),
            From: from.value,
          },
        })      


        call.on('ringing', () => {                                 
          log.value = 'Ringing'
          isCallAccepted.value = true          
        })         
        


        call.on('accept', () => {                                                                   
          log.value = 'Progress'
          if (call.parameters && call.parameters.CallSid) {
            callSid.value = call.parameters.CallSid    
            console.log(callSid.value, 'here is dialer CallSid ')
                                                   
          }          

          isCallAccepted.value = true
          startTimer()

          // check Call status
          axiosIns.post('/get-call-info', {
            callSid: callSid.value,
            to: userNumber.value,
            From: from.value,
          })
          .then(response => {            
            console.log(response, 'here is call info response')                                                          
          })
          .catch(error => {
            console.error(error.response.data.error)
          })


        })                                    


        call.on('disconnect', () => {       
          //call Discconnect status to fetch details
          axiosIns.post('/call-disconnected', {
            callSid: callSid.value, 
            childCallSid: childCallSid.value,
            to: userNumber.value,
            From: from.value,
            ForwardNumber: forwardNumber.value,
            PhoneNumbers: phoneNumbers

          })
          .then(response => {            
            console.log(response, 'here is call disconnected response')                                                          
          })
          .catch(error => {
            console.error(error.response.data.error)
          })
          
          onPhone.value = false
          connected.value = false
          callSid.value = null
          log.value = 'Call has ended'
          userNumber.value = null
          isCallAccepted.value = false;         
          isConference.value = false;
          onForward.value   = false; 
          stopTimer();          
          muted.value = true;
          setTimeout(() => {
            connected.value = true
            log.value = 'Connected'
            callSid.value = ''
          }, 5000)
        })        

        device.disconnect(() => {
            console.log('here is  call disconnected');            
        });
        
      } catch (error) {
        console.error('Error connecting:', error)
      }


    }
  } else {    
    log.value = 'Hanging Up'
    device.disconnectAll()
    log.value = 'Connected'
    muted.value = true
    onPhone.value = false
  }
}



const playIncomingCallSound = connection => {
  incomingCallSound.value = new Audio(defaultRingtone)
  incomingCallSound.value.play()
}
    
const stopIncomingCallSound = () => {
  if (incomingCallSound.value) {
    incomingCallSound.value.pause()
    incomingCallSound.value = null
  }
}
    
const sendDigit = digit => {
  if(!isEmpty(connection.value)){
    connection.value.sendDigits(digit)
  }else{
    if(isEmpty(currentNumber.value)){
      currentNumber.value = '+' + currentNumber.value + digit
    }else{
      currentNumber.value = currentNumber.value + digit
    }
  }
}
    
const setInputDevice = async () => {
  const inputDeviceId = localStorage.getItem('audioInputDeviceId')
  const device = dialerStore.twilioDevice

  await device.audio.ringtoneDevices.set(inputDeviceId)
}
    
const handleIncomingCall = incomingCall => {
 
  const device = dialerStore.twilioDevice

  console.log('incomingCall')
  console.log(device, 'here is device')
  try {
    calledId.value = incomingCall.From
    callerId.value = incomingCall.To
    call.value = incomingCall

    // Play incoming call sound
    playIncomingCallSound(device)
    triggerIncomingCall()
    
    // Event listener for call answered
    device.on('connect', connection => {
      log.value = 'Call connected.'
    })

    // Event listener for call disconnect
    device.on('disconnect', () => {
      stopIncomingCallSound()
      log.value = 'Call disconnected.'
      callerId.value = ''
    })

    // // Event listener for call reject
    device.on('reject', () => {
      // Stop the incoming call sound when the call is rejected
      stopIncomingCallSound()
      log.value = 'Call rejected.'
      callerId.value = ''
    })
    
  } catch (error) {
    console.log(error, 'here is the twilio error')
  }
}
    
const initializeTwilio = async () => {

  // Initialize Twilio Device
  await dialerStore.initializeTwilioDevice()
  
  // Get Twilio Device from Store (Pinia)
  const device = dialerStore.twilioDevice
  
  device.on("ready", function (device) {
    log("Twilio.Device Ready!", device)
  })
    
  // add event Listener to check if device is registered and ready
  device.on('registered', handleSuccessfulRegistration)

  if (device._state !== 'registered') {
    device.register()
    console.log('Device registration initiated')
  }
  
  device.on('incoming', conn => {
    alert('incoming')
    handleIncomingCall(conn)
  })

}
    
const triggerIncomingCall = () => {
  // Simulate an incoming call
  showNotification.value = true
}
    
const acceptCall = () => {
  
  stopIncomingCallSound()
  showNotification.value = false
  showNotificationMenu.value = true

  // Handle call acceptance logic here  
  //call.value.accept()
  
  const device = dialerStore.twilioDevice

  if (device && call) {
    try {
      // Connect the call using Twilio Voice SDK
      device.connect()

      //const connection = device.connect({ CallSid: call.value.CallSid })

      //device.connect({ parameters: { CallSid: incomingCall.CallSid } });
      console.log('Call connected')
    } catch (error) {
      console.error('Error accepting call:', error)
    }
  } else {
    console.error('Device or call not available')
  }
}
    
const rejectCall = () => {

  stopIncomingCallSound()
  showNotification.value = false
  showNotificationMenu.value = false
  
  // Instead of immediately hiding the notification, you can add a fade-out effect

  const device = dialerStore.twilioDevice

  console.log('rejectedCall')
  console.log(device.audio.incoming.stream)
  console.log(call.value.CallSid)
  
  device.disconnectAll()
}
    
initializeTwilio()

watch(from, value => {
  // Parse the phone number
  const parsedPhoneNumber = parsePhoneNumberFromString(value)
    
  flag.value = `fi fi-${parsedPhoneNumber.country.toLowerCase()} mr-1`
})
    
const selectedCountryCode = ref('+1')
const phoneNumber = ref('')
    
const clearInput = () => {
  currentNumber.value = currentNumber.value.slice(0, -1);
  phoneNumbers.value = phoneNumbers.value.slice(0, -1);
  forwardNumber.value = forwardNumber.value.slice(0, -1);

}
   
onMounted(() => {  
  fetchTeamMembers()
  window.Echo.channel('incoming-calls')
    .listen('IncomingCallEvent', event => {      
      if(event.CallStatus =='ringing'){       
        handleIncomingCall(event)
      }      
    })
})

watch(currentNumber, number => {
  for (const country of countries.value) {
    const cleanedNumber = number.replace(/^\+/, '')
    if (cleanedNumber.startsWith(`${country.phone_code}`)) {
      selectedCountry.value = country
      break
    }
  }
})
    
watch(selectedCountryCode, newCode => {
  phoneNumber.value = newCode + phoneNumber.value
})
 
const isDropdownOpen = ref(false)
    
const selectedCountry = ref({
  name: 'United States',
  code_2: 'us',
  phone_code: '1',
})
    
const isOpen = ref(false)
    
const selectCountry = country => {
  selectedCountry.value = country
  currentNumber.value = '+'+country.phone_code
  setTimeout(() => {
    isOpen.value = false
  }, 600)
}
    
const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const activeTab = ref('Dialer - JotPhone')
    
const onMountedFunction = async () => { 
  fetchCountries()

  //currentNumber.value = '+' + selectedCountry.value.phone_code
  
  const userDefaultNumber = await dialerStore.fetchSetting()
    .then(res => {    
      return res.setting.number_outbound_calls
    })
    .catch(error => {
      console.error(error)
    })

  dialerStore.fetchUserOwnerNumbers()
    .then(res => {
      userNumbers.value = res.data
      filteredNumbers.value = userNumbers.value.filter(item => item.number)

      if(userDefaultNumber){
        from.value = userDefaultNumber
      }else{
        const activeNumber = filteredNumbers.value.map(item => item.active ? item.number : '')

        from.value = activeNumber[0]
      }

    })
    .catch(error => {
      console.log(error, 'No here is we are')
    })
    
  // Ask the user for microphone permission
  try {
    // Ask the user for microphone permission
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })

  } catch (error) {
    console.error('Error accessing microphone:', error)
    
    // Handle any errors, such as permission denied
  }
}

onMountedFunction()
if (document.visibilityState) {
  // Add event listener for visibility change
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
      // Page is now visible (tab is active)
      if(activeTabName.value =='home'){
        onMountedFunction()
      }else{
        openTab(activeTabName.value)
      }
    }
  })
}

const getButtonValue = (row, col) => {
  const dialerGrid = [
    ['1', '2', '3'],
    ['4', '5', '6'],
    ['7', '8', '9'],
    ['*', '0', '#'],
  ]
    
  return dialerGrid[row - 1][col - 1]
}
  
watch(searchContact, (newVal, oldVal) => {
  if (newVal.length >= 3 || (newVal.length === 0 && oldVal.length > 0)) {
    setTimeout(() => {
      fetchContacts()
    }, 1800)
  }
})

const fetchContacts = () => {
  loading.value = true
  dialerStore.fetchContacts({
    q: searchContact.value,
  })
    .then(res => {
      contacts.value = res.data.contacts
      loading.value = false
    })
    .catch(error => {
      loading.value = false
      console.log(error, 'here is contacts')
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
      console.log(error, 'here is setting')
    })
}

watch(searchLogs, (newVal, oldVal) => {
  if (newVal.length >= 3 || (newVal.length === 0 && oldVal.length > 0)) {
    setTimeout(() => {
      fetchCallLogs(currentTab.value)
    }, 1800)
  }
})

const fetchCallLogs = curTab => {
  loading.value = true
  dialerStore.fetchCallLogs({
    current_tab: curTab,
    q: searchLogs.value,
  })
    .then(res => {
      recent.value = res.data.recent
      missed.value = res.data.missed
      voicemail.value = res.data.voicemail
      loading.value = false
    })
    .catch(error => {
      loading.value = false
      console.log(error)
    })
}

const homeScreen = phone => {
  activeTab.value = 'Dialer - JotPhone'
  isHomeActive.value = true
  isLogsActive.value = false
  isContactsActive.value = false
  isMessagesActive.value = false
  isSettingsActive.value = false
  currentNumber.value = phone
}

const openTab = tabName => {
  activeTabName.value = tabName
  switch (tabName){
  case 'home':
    activeTab.value = 'Dialer - JotPhone'
    isHomeActive.value = true
    isLogsActive.value = false
    isContactsActive.value = false
    isMessagesActive.value = false
    isSettingsActive.value = false
    break
  case 'logs':
    activeTab.value = 'Call Logs'
    isLogsActive.value = true
    isContactsActive.value = false
    isMessagesActive.value = false
    isSettingsActive.value = false
    isHomeActive.value = false
    fetchCallLogs(currentTab.value)
    break
  case 'contacts':
    activeTab.value = 'Contacts'
    isContactsActive.value = true
    isLogsActive.value = false
    isHomeActive.value = false
    isMessagesActive.value = false
    isSettingsActive.value = false
    fetchContacts()
    break
  case 'messages':
    activeTab.value = 'Messages'
    isMessagesActive.value = true
    isContactsActive.value = false
    isLogsActive.value = false
    isHomeActive.value = false
    isSettingsActive.value = false
    break
  case 'settings':
    activeTab.value = 'Settings'
    isSettingsActive.value = true
    isMessagesActive.value = false
    isContactsActive.value = false
    isLogsActive.value = false
    isHomeActive.value = false
    fetchSetting()
    break
  default:
    isHomeActive.value = true
    isLogsActive.value = false
    isContactsActive.value = false
    isMessagesActive.value = false
    isSettingsActive.value = false
    break
  }
}
    
const handleButtonClick = (row, col) => {
  const buttonValue = getButtonValue(row, col)
    
  // Handle button click, e.g., emit an event or perform some action
  sendDigit(buttonValue)
}

const openConversation = (conversationId = null) => {
  
  conversationIsOpened.value = true
}

const closeConversation = () => {
  conversationIsOpened.value = false
}

const openInbox = () => {
  inboxIsOpened.value = true
}

const closeInbox = () => {
  inboxIsOpened.value = false
}

const sendMessage = async () => {
  if (!msg.value)
    return
  await store.sendMsg(msg.value)

  // Reset message input
  msg.value = ''

  // Scroll to bottom
  // nextTick(() => {
  //   scrollToBottomInChatLog()
  // })
}

const moreList = [
  {
    title: 'View Contact',
    value: 'View Contact',
  },
  {
    title: 'Mute Notifications',
    value: 'Mute Notifications',
  },
  {
    title: 'Block Contact',
    value: 'Block Contact',
  },
  {
    title: 'Clear Chat',
    value: 'Clear Chat',
  },
  {
    title: 'Report',
    value: 'Report',
  },
]
</script>
    
<template>
  <!-- Show the incoming call notification when `showNotification` is true -->
  <Transition name="slide-fade">
    <VCol
      v-if="showNotification"
      key="tabler-phone-incoming"
      class="incoming-call-notification"
      sm="6"
      md="4"
      lg="3"
      style="z-index: 2;"
    >
      <VCard class="bg-surface">
        <VCardItem>
          <template #prepend>
            <VIcon
              size="1.9rem"
              color="white"
              icon="tabler-phone-incoming"
            />
          </template>
          <div class="status-container">
            <div class="d-flex flex-row justify-center">
              <VCardTitle class="mr-7">
                {{ calledId }}
              </VCardTitle>
            </div>
          </div>
        </VCardItem>
        
        <VCardText class="d-flex justify-space-between align-center flex-wrap">
          <IconBtn
            icon="tabler-phone"
            color="success"
            variant="tonal"
            size="x-large"
            @click="acceptCall"
          />

          <IconBtn
            icon="tabler-phone-x"
            color="error"
            variant="tonal"
            size="x-large"
            @click="rejectCall"
          />
        </VCardText>
      </VCard>
    </VCol>
  </Transition>
  <!-- The notification menu will show when the notification is ture -->
  <Transition name="slide-fade">
    <VRow
      v-if="showNotificationMenu"
      key="tabler-phone-incoming"
      class="incoming-call-notification-menu dialer-notification"
      justify="center"
      style="z-index: 1;"
    >
      <VCard class="dialer-container bg-surface">
        <div class="dialer-header">
          <p class="mb-auto mt-auto">
            {{ activeTab }}
          </p>
        </div>

        <div class="status-container mt-10">
          <div class="d-flex flex-row justify-center">
            <h2>
              Usman Ghani
            </h2>
          </div>
          <div class="d-flex flex-row justify-center mt-2">
            <h3>
              Calling via (230) 768-6032 - NYC
            </h3>
          </div>
  
          <div class="d-flex flex-row justify-center mt-2">
            <h3 class="text-success">
              Connecting...
            </h3>
          </div>
        </div>

        <div class="input-container-time d-flex flex-row justify-center mt-10">
          It’s 10:30 PM there
        </div>

        <div class="dialer-grid mt-12">
          <div class="dialer-row">
            <div class="dialer-button-notification">
              <IconBtn
                variant="tonal"
                size="4.5rem"
              >
                <VIcon
                  icon="tabler-microphone-off"
                  size="30"
                />
              </IconBtn>
              <small class="btn-name-notification">Mute</small>
            </div>
            <div class="dialer-button-notification">
              <IconBtn
                variant="tonal"
                size="4.5rem"
              >
                <VIcon
                  icon="tabler-dialpad-filled"
                  size="30"
                />
              </IconBtn>
              <small class="btn-name-notification">Keypad</small>
            </div>
            <div class="dialer-button-notification">
              <IconBtn
                variant="tonal"
                size="4.5rem"
              >
                <VIcon
                  icon="tabler-volume"
                  size="30"
                />
              </IconBtn>
              <small class="btn-name-notification">Speaker</small>
            </div>
          </div>
          <div class="dialer-row-second">
            <div class="dialer-button-notification">
              <IconBtn
                variant="tonal"
                size="4.5rem"
              >
                <VIcon
                  icon="tabler-arrow-forward-up"
                  size="30"
                />
              </IconBtn>
              <small class="btn-name-notification">Transfer</small>
            </div>
            <div class="dialer-button-notification">
              <IconBtn
                variant="tonal"
                size="4.5rem"
              >
                <VIcon
                  icon="tabler-player-pause-filled"
                  size="30"
                />
              </IconBtn>
              <small class="btn-name-notification">Hold</small>
            </div>
          </div>
        </div>

        <div class="dialer-grid mt-15">
          <div class="dialer-row mt-10">
            <div class="dialer-button-notification">
              <IconBtn
                variant="tonal"
                size="4.5rem"
              >
                <VIcon
                  icon="tabler-note"
                  size="30"
                  color="warning"
                />
              </IconBtn>
            </div>
            <div class="dialer-button-notification">
              <IconBtn
                variant="tonal"
                size="4.5rem"
                color="error"
                @click="rejectCall"
              >
                <VIcon
                  icon="tabler-phone-off"
                  size="30"
                />
              </IconBtn>
            </div>
            <div class="dialer-button-notification">
              <IconBtn
                variant="tonal"
                size="4.5rem"
              >
                <VIcon
                  icon="tabler-record-mail"
                  size="30"
                  color="error"
                />
              </IconBtn>
            </div>
          </div>
        </div>
      </VCard>
    </VRow>
  </Transition>

  <VRow
    justify="center"
    class="ma-0"
  >
    <div class="dialer-container">
      <div class="dialer-header">
        <p class="mb-auto mt-auto">
          {{ activeTab }}
        </p>
      </div>

      <div
        v-if="isHomeActive"
        class="home"
      >
        <div class="input-container mt-5">
          <div
            class="custom-dropdown"
            @click="toggleDropdown"
          >
            <div
              v-if="!isEmpty(selectedCountry)"
              class="selected-item border-none"
            >
              <span
                class="w-15 h-15 fi"
                :class="{ ['fi-' + selectedCountry.code_2.toLowerCase()]: true }"
              />
              <VIcon
                class="text-black ml-2"
                :icon="isOpen ? 'tabler-chevron-up' : 'tabler-chevron-down'"
                size="15px"
              />
            </div>
            <div
              v-else
              class=""
            >
              +
            </div>
            <div class="dropdown-container">
              <Transition
                :duration="{ enter: 500, leave: 800 }"
                name="fade"
                type="transition"
              >
                <div
                  v-if="isOpen"
                  class="dropdown-items rounded"
                >
                  <PerfectScrollbar
                    :options="{ wheelPropagation: false }"
                    style="max-block-size: 20rem;"
                  >
                    <div
                      v-for="(country, index) in countries"
                      :key="index"
                      class="dropdown-item"
                      @click="selectCountry(country)"
                    >
                      <p class="mb-0 text-black">
                        +{{ country.phone_code }} {{ country.name }}
                      </p> <span
                        class="fi"
                        :class="{ ['fi-' + country.name.toLowerCase()]: true }"
                      />
                    </div>
                  </PerfectScrollbar>
                </div>
              </Transition>
            </div>
          </div>

          <input
            v-model="currentNumber"
            type="tel"
            placeholder="Enter Phone No +1XXXXXXXXXXX"
            class="phone-input"
          >
          <button
            v-if="currentNumber"
            class="clear-button"
            @click="clearInput"
          >
            <VIcon
              icon="tabler-backspace"
              class="text-black"
              size="20px"
            />
          </button>
        </div>

        

        <div class="d-flex flex-row justify-center status-container mt-4">
          <VChip
            label
            :color="connected ? 'success' : 'error'"
            class="ml-1"
          >
            <VIcon
              start
              :color="connected ? 'success' : 'error'"
              icon="tabler-point-filled"
              class="mr-1"
              size="18px"
            />
            {{ log }}
          </VChip>
        </div>

        <div
          v-if="isCallAccepted"
          class="d-flex flex-row justify-center status-container mt-4"
        >          
          <VChip
            label
            :color="isCallAccepted ? 'success' : 'error'"
            class="ml-1"
          >
            <VIcon
              start
              :color="isCallAccepted ? 'success' : 'error'"
              icon="tabler-point-filled"
              class="mr-1"
              size="18px"
            />
            {{ callDuration }}          
          </VChip>            
        </div>



        <label v-if="isConference" class="text-info d-flex justify-center pt-2">Enter Numbers separated by commas and use country code</label>
        <div v-if="isConference" class="p-2 d-flex justify-center mt-5">          
          <div class="conference-container">
            <input        
              v-model="phoneNumbers"
              type="tel"
              placeholder="Enter Numbers separated by commas and use country code"
              class="conference-input"
            >
            
            <button
              v-if="phoneNumbers"
              class="clear-button"
              @click="clearInput"
            >
              <VIcon
                icon="tabler-backspace"
                class="text-black"  
                size="20px"
              />
            </button>
          </div>          
          <VBtn
              :icon="onPhone ? 'tabler-phone-calling' : 'tabler-phone'"
              size="small"
              :color="onPhone ? 'success' : 'error'"              
              @click.prevent="createConference"
            />          
        </div>                


        <div
          v-if="onForward"
          class="d-flex flex-row justify-center align-items-center status-container mt-4 mr-4 ml-4 gap-4"
        >                        

            <input        
              v-model="forwardNumber"
              type="tel"
              placeholder="Enter Number here to connect"
              class="phone-input"
            >

            <button
              v-if="forwardNumber"
              class="clear-button"
              @click="clearInput"
            >
              <VIcon
                icon="tabler-backspace"
                class="text-black"  
                size="20px"
              />
            </button>
            

          <!-- <VSelect
            v-model="selectedTeamMember"
            label="Select Team Member"
            :items="teamMembers"
            item-value="value" 
            item-title="title" 
            density="compact"              
            clearable
            clear-icon="tabler-x"
            style="width: 10rem;"
          />      -->

          <VBtn              
            size="small"
            color="success"
            icon="tabler-letter-t"
            @click.prevent="transferCall"
          />  
          
          <VBtn              
            size="small"
            color="success"
            icon="tabler-letter-c"
            @click.prevent="connectForwardCall"
          />  
                   
        </div>  
        

        <div class="dialer-grid mt-10">
          <div
            v-for="row in 4"
            :key="row"
            class="dialer-row"
          >
            <div
              v-for="col in 3"
              :key="col"
              class="dialer-button"
              @click="handleButtonClick(row, col)"
            >
              {{ getButtonValue(row, col) }}
            </div>
          </div>
        </div>

        <div class="d-flex flex-row justify-center">
          <div class="controls mt-3 mb-2">
            <VBtn
              :icon="onPhone ? 'tabler-phone-calling' : 'tabler-phone'"
              size="large"
              :color="onPhone ? 'error' : 'success'"
              :disabled="!validPhone"
              @click.prevent="toggleCall"
            />

            

            <VBtn
              v-if="onPhone"
              :icon="muted ? 'tabler-microphone-off' : 'tabler-microphone'"
              size="large"
              class="ml-3"
              @click="toggleMute"
            />

            <VBtn
              v-if="onPhone"
              class="ml-3"
              icon="tabler-arrow-forward"
              title="Forward Call"
              @click="forwardCall"
            />                  

            <VBtn              
              v-if="onPhone"
              class="ml-3"
              :icon= "isConference  ? 'tabler-letter-d' : 'tabler-letter-c'"
              title="Conference"
              @click="toggleConference"          
            />
            

            <VBtn
              v-if="onPhone"
              class="ml-3"
              :icon="onHold ? 'tabler-letter-r' : 'tabler-letter-h'"
              title="Hold"
              @click="toggleHold"
            />
          </div>
        </div>

        <div class="d-flex flex-row justify-center mt-3 mb-3 ml-auto mr-auto align-center">
          <!-- Dialer input -->
          <small class="mr-2">Call via</small>
          <span :class="[flag]" />
          <VCol
            lg="6"
            md="6"
            sm="9"
          >
            <AppAutocomplete
              v-model="from"
              class="text-black"
              :items="filteredNumbers"
              item-title="number"
              item-value="number"
            />
          </VCol>
        </div>
        
      </div>

      <div
        v-if="isLogsActive"
        class="logs"
      >
        <VTabs
          v-model="currentTab"
          grow
          height="45px"
          @click="fetchCallLogs(currentTab)"
        >
          <VTab value="tab-phone">
            Recent
          </VTab>
          <VTab value="tab-missed">
            Missed
          </VTab>
          <VTab value="tab-voicemail">
            Voicemail
          </VTab>
        </VTabs>

        <VWindow
          v-model="currentTab"
          style="height: 596px;"
        >
          <VWindowItem value="tab-phone">
            <VCol cols="12">
              <AppTextField
                v-model="searchLogs"
                aria-placeholder="Search"
                placeholder="Search"
                prepend-inner-icon="tabler-search"
              />
            </VCol>
            <!-- Show the loader -->
            <DialerLoader :is-processing="loading" />
          
            <PerfectScrollbar
              :options="{ wheelPropagation: false }"
              style="max-block-size: 37rem;"
            >
              <div
                v-for="(rec, index) in recent"
                :key="index"
                class="call-log"
              >
                <div class="d-flex align-center gap-4">
                  <VAvatar
                    color="info"
                    variant="tonal"
                    size="small"
                  >
                    <VIcon
                      icon="tabler-phone-call"
                      size="20"
                    />
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <h6 class="text-base">
                      {{ rec.to }}
                    </h6>
                  </div>
                </div>
                <div class="d-flex align-center gap-4">
                  <div class="d-flex flex-column">
                    <div class="text-base text-right">
                      {{ rec.formatted_date_time }}
                    </div>
                    <div class="text-base text-right">
                      {{ rec.duration }}
                    </div>
                  </div>
                </div>
              </div>
            </PerfectScrollbar>
          </VWindowItem>
          <VWindowItem value="tab-missed">
            <VCol cols="12">
              <AppTextField
                v-model="searchLogs"
                aria-placeholder="Search"
                placeholder="Search"
                prepend-inner-icon="tabler-search"
              />
            </VCol>
            <!-- Show the loader -->
            <DialerLoader :is-processing="loading" />
            <PerfectScrollbar
              :options="{ wheelPropagation: false }"
              style="max-block-size: 37rem;"
            >
              <div
                v-for="(mis, index) in missed"
                :key="index"
                class="call-log"
              >
                <div class="d-flex align-center gap-4">
                  <VAvatar
                    color="error"
                    variant="tonal"
                    size="small"
                  >
                    <VIcon
                      icon="tabler-phone-off"
                      size="20"
                    />
                  </VAvatar>

                  <div class="d-flex flex-column">
                    <h6 class="text-base">
                      {{ mis.from }}
                    </h6>
                  </div>
                </div>
                <div class="d-flex align-center gap-4">
                  <div class="d-flex flex-column">
                    <div class="text-base text-right">
                      {{ mis.formatted_date_time }}
                    </div>
                  </div>
                </div>
              </div>
            </PerfectScrollbar>
          </VWindowItem>
          <VWindowItem value="tab-voicemail">
            <VCol cols="12">
              <AppTextField
                v-model="searchLogs"
                aria-placeholder="Search"
                placeholder="Search"
                prepend-inner-icon="tabler-search"
              />
            </VCol>
            <!-- Show the loader -->
            <DialerLoader :is-processing="loading" />
            <PerfectScrollbar
              :options="{ wheelPropagation: false }"
              style="max-block-size: 37rem;"
            />
          </VWindowItem>
        </VWindow>
      </div>

      <div
        v-if="isContactsActive"
        class="logs"
      >
        <VCol cols="12">
          <AppTextField
            v-model="searchContact"
            aria-placeholder="Search"
            placeholder="Search"
            prepend-inner-icon="tabler-search"
          />
        </VCol>
        <!-- Show the loader -->
        <DialerLoader :is-processing="loading" />
        <PerfectScrollbar
          :options="{ wheelPropagation: false }"
          style="max-block-size: 36rem;"
        >
          <div
            v-for="(contact, index) in contacts"
            :key="index"
            class="contact"
          >
            <div class="d-flex align-center gap-4">
              <VAvatar
                :image="contact.avatar_url"
                size="large"
                lazy
              />

              <div class="d-flex flex-column">
                <h6 class="text-base text-primary">
                  {{ contact.fullName }}
                </h6>
                <p class="text-base mb-0">
                  {{ contact.email }}
                </p>
                <p class="text-base mb-0">
                  {{ contact.phone }}
                </p>
              </div>
            </div>
            <div class="d-flex align-center gap-4 actions">
              <div class="d-flex flex-row gap-2">
                <VBtn
                  color="white"
                  rounded="xl"
                  size="45"
                  @click="homeScreen(contact.phone)"
                >
                  <VIcon
                    icon="tabler-phone"
                    color="primary"
                    size="23"
                  />
                </VBtn>

                <VBtn
                  color="white"
                  rounded="xl"
                  size="45"
                >
                  <VIcon
                    icon="tabler-message"
                    color="warning"
                    size="23"
                  />
                </VBtn>
              </div>
            </div>
          </div>
        </PerfectScrollbar>
      </div>

      <div
        v-if="isMessagesActive"
        class="logs"
      >
        <div v-if="!conversationIsOpened && !inboxIsOpened">
          <VCol
            cols="12"
            class="pa-7 pb-1"
          >
            <VRow>
              <AppTextField
                aria-placeholder="Search"
                placeholder="Search"
                prepend-inner-icon="tabler-search"
                class="mr-3"
              />
              <VBtn
                color="primary"
                @click="openInbox"
              >
                <VIcon icon="tabler-message-plus" />
              </VBtn>
            </VRow>
          </VCol>
          <!-- Show the loader -->
          <DialerLoader :is-processing="loading" />
          <VCol cols="12">
            <p class="mb-0 text-center">
              Messages - JotPhone
            </p>
          </VCol>
          <PerfectScrollbar
            :options="{ wheelPropagation: false }"
            style="max-block-size: 36rem;"
          >
            <div
              class="contact"
              @click="openConversation(1)"
            >
              <div class="d-flex align-center gap-4">
                <VAvatar
                  size="large"
                  variant="tonal"
                  color="success"
                  class="cursor-pointer"
                  lazy
                >
                  <VImg
                    v-if="defaultAvatar"
                    :src="defaultAvatar"
                    alt="User"
                  />
                  <span v-else>{{ avatarText('Usman Ghani1') }}</span>
                </VAvatar>

                <div class="d-flex flex-column">
                  <h6 class="text-base text-primary">
                    Usman Ghani
                  </h6>
                  <p class="text-base mb-0">
                    I will purchase it for sure. 👍
                  </p>
                </div>
              </div>
              <div class="d-flex align-center gap-4">
                <div class="d-flex flex-row gap-2">
                  02:30 PM
                </div>
              </div>
            </div>
          </PerfectScrollbar>
        </div>
        <div v-if="conversationIsOpened && !inboxIsOpened">
          <VLayout class="chat-app-layout pa-4">
            <!-- 👉 Chat content -->
            <VMain class="chat-content-container">
              <!-- 👉 Right content: Active Chat -->
              <div class="d-flex flex-column h-100">
                <!-- 👉 Active chat header -->
                <div class="active-chat-header d-flex align-center text-medium-emphasis bg-surface">
                  <!-- Sidebar toggler -->
                  <div class="">
                    <VBtn
                      color="primary"
                      size="small"
                      @click="closeConversation"
                    >
                      <VIcon
                        class="mr-1"
                        icon="tabler-chevron-left"
                      />
                      Back
                    </VBtn>
                  </div>
                  <!-- avatar -->
                  <div class="d-flex align-center cursor-pointer ml-4">
                    <VBadge
                      dot
                      location="bottom right"
                      offset-x="3"
                      offset-y="0"
                      color="success"
                      bordered
                    >
                      <VAvatar
                        size="32"
                        variant="tonal"
                        color="success"
                        class="cursor-pointer"
                        lazy
                      >
                        <VImg
                          v-if="defaultAvatar"
                          :src="defaultAvatar"
                          alt="User"
                        />
                        <span v-else>{{ avatarText('Usman Ghani1') }}</span>
                      </VAvatar>
                    </VBadge>

                    <div class="flex-grow-1 ms-4 overflow-hidden">
                      <p class="text-h6 mb-0">
                        Usman Ghani
                      </p>
                      <p class="text-truncate mb-0 text-disabled">
                        Software Engineer
                      </p>
                    </div>
                  </div>
                  <VSpacer />
                  <!-- Header right content -->
                  <div class="d-sm-flex align-center d-none">
                    <IconBtn>
                      <VIcon icon="tabler-phone-call" />
                    </IconBtn>
                    <IconBtn>
                      <VIcon icon="tabler-video" />
                    </IconBtn>
                    <IconBtn>
                      <VIcon icon="tabler-search" />
                    </IconBtn>
                  </div>
                  <MoreBtn
                    :menu-list="moreList"
                    density="comfortable"
                    color="undefined"
                  />
                </div>

                <VDivider color="whitesmoke" />
                <!-- Chat log -->
                <PerfectScrollbar
                  ref="chatLogPS"
                  tag="ul"
                  :options="{ wheelPropagation: false }"
                  class="flex-grow-1"
                  style="max-block-size: 32rem;"
                >
                  <div class="chat-log pa-5">
                    <div class="chat-group d-flex align-start flex-row-reverse mb-4">
                      <div class="chat-avatar ms-4">
                        <VAvatar
                          size="32"
                          variant="tonal"
                          color="success"
                          class="cursor-pointer"
                          lazy
                        >
                          <VImg
                            v-if="!defaultAvatar"
                            :src="defaultAvatar"
                            alt="User"
                          />
                          <span v-else>{{ avatarText('Usman Ghani1') }}</span>
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-end">
                        <p
                          class="chat-content py-2 px-4 elevation-1 bg-primary text-white chat-right mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          How can we help? We're here for you!
                        </p>
                        <div class="text-right">
                          <VIcon
            
                            size="18"
                            color="success"
                          >
                            tabler-checks
                          </VIcon>
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                    <div class="chat-group d-flex align-start mb-4">
                      <div class="chat-avatar me-4">
                        <VAvatar size="32">
                          <VImg
                            v-if="defaultAvatar"
                            :src="defaultAvatar"
                          />
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-start">
                        <p
                          class="chat-content py-2 px-4 elevation-1 chat-left mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          Hey John, I am looking for the best admin template. Could you please help me to find it out?
                        </p>
                        <div class="text-right">
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                    <div class="chat-group d-flex align-start mb-4">
                      <div class="chat-avatar me-4">
                        <VAvatar size="32">
                          <VImg
                            v-if="defaultAvatar"
                            :src="defaultAvatar"
                          />
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-start">
                        <p
                          class="chat-content py-2 px-4 elevation-1 chat-left mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          It should use nice Framework.
                        </p>
                        <div class="text-right">
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                    <div class="chat-group d-flex align-start flex-row-reverse mb-4">
                      <div class="chat-avatar ms-4">
                        <VAvatar
                          size="32"
                          variant="tonal"
                          color="success"
                          class="cursor-pointer"
                          lazy
                        >
                          <VImg
                            v-if="!defaultAvatar"
                            :src="defaultAvatar"
                            alt="User"
                          />
                          <span v-else>{{ avatarText('Usman Ghani') }}</span>
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-end">
                        <p
                          class="chat-content py-2 px-4 elevation-1 bg-primary text-white chat-right mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          Absolutely!
                        </p>
                        <div class="text-right">
                          <VIcon
            
                            size="18"
                            color="success"
                          >
                            tabler-checks
                          </VIcon>
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                    <div class="chat-group d-flex align-start flex-row-reverse mb-4">
                      <div class="chat-avatar ms-4">
                        <VAvatar
                          size="32"
                          variant="tonal"
                          color="success"
                          class="cursor-pointer"
                          lazy
                        >
                          <VImg
                            v-if="!defaultAvatar"
                            :src="defaultAvatar"
                            alt="User"
                          />
                          <span v-else>{{ avatarText('Usman Ghani') }}</span>
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-end">
                        <p
                          class="chat-content py-2 px-4 elevation-1 bg-primary text-white chat-right mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          Our admin is the responsive admin template.!
                        </p>
                        <div class="text-right">
                          <VIcon
            
                            size="18"
                            color="success"
                          >
                            tabler-checks
                          </VIcon>
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                    <div class="chat-group d-flex align-start mb-4">
                      <div class="chat-avatar me-4">
                        <VAvatar size="32">
                          <VImg
                            v-if="defaultAvatar"
                            :src="defaultAvatar"
                          />
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-start">
                        <p
                          class="chat-content py-2 px-4 elevation-1 chat-left mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          Looks clean and fresh UI. 😍
                        </p>
                        <div class="text-right">
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                    <div class="chat-group d-flex align-start flex-row-reverse mb-4">
                      <div class="chat-avatar ms-4">
                        <VAvatar
                          size="32"
                          variant="tonal"
                          color="success"
                          class="cursor-pointer"
                          lazy
                        >
                          <VImg
                            v-if="!defaultAvatar"
                            :src="defaultAvatar"
                            alt="User"
                          />
                          <span v-else>{{ avatarText('Usman Ghani1') }}</span>
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-end">
                        <p
                          class="chat-content py-2 px-4 elevation-1 bg-primary text-white chat-right mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          Can I get details of my last transaction I made last month? 🤔
                        </p>
                        <div class="text-right">
                          <VIcon
            
                            size="18"
                            color="success"
                          >
                            tabler-checks
                          </VIcon>
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                    <div class="chat-group d-flex align-start flex-row-reverse mb-4">
                      <div class="chat-avatar ms-4">
                        <VAvatar
                          size="32"
                          variant="tonal"
                          color="success"
                          class="cursor-pointer"
                          lazy
                        >
                          <VImg
                            v-if="!defaultAvatar"
                            :src="defaultAvatar"
                            alt="User"
                          />
                          <span v-else>{{ avatarText('Usman Ghani1') }}</span>
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-end">
                        <p
                          class="chat-content py-2 px-4 elevation-1 bg-primary text-white chat-right mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          Thanks, From our official site 😇
                        </p>
                        <div class="text-right">
                          <VIcon
            
                            size="18"
                            color="success"
                          >
                            tabler-checks
                          </VIcon>
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                    <div class="chat-group d-flex align-start mb-4">
                      <div class="chat-avatar me-4">
                        <VAvatar size="32">
                          <VImg
                            v-if="defaultAvatar"
                            :src="defaultAvatar"
                          />
                        </VAvatar>
                      </div>
                      <div class="chat-body d-inline-flex flex-column align-start">
                        <p
                          class="chat-content py-2 px-4 elevation-1 chat-left mb-1"
                          style="background-color: rgb(var(--v-theme-surface));"
                        >
                          I will purchase it for sure. 👍
                        </p>
                        <div class="text-right">
                          <span class="text-sm ms-1 text-disabled">12:46 PM</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </PerfectScrollbar>
                
                <!-- Message form -->
                <VForm
                  class="chat-log-message-form "
                  style="margin-top: -1.5rem;"
                  @submit.prevent="sendMessage"
                >
                  <VTextField
                    :key="store?.activeChat?.contac?.id"
                    v-model="msg"
                    variant="solo"
                    class="chat-message-input"
                    placeholder="Type your message..."
                    density="default"
                    autofocus
                  >
                    <template #append-inner>
                      <IconBtn>
                        <VIcon icon="tabler-microphone" />
                      </IconBtn>

                      <IconBtn
                        class="mr-2"
                        @click="refInputEl?.click()"
                      >
                        <VIcon icon="tabler-photo" />
                      </IconBtn>

                      <VBtn
                        class="mb-4"
                        @click="sendMessage"
                      >
                        Send
                      </VBtn>
                    </template>
                  </VTextField>
                  <input
                    ref="refInputEl"
                    type="file"
                    name="file"
                    accept=".jpeg,.png,.jpg,GIF"
                    hidden
                  >
                </VForm>
              </div>
            </VMain>
          </VLayout>
        </div>
        <div v-if="inboxIsOpened">
          <VLayout class="chat-app-layout pa-4">
            <!-- 👉 Chat content -->
            <VMain class="chat-content-container">
              <!-- 👉 Right content: Active Chat -->
              <div class="d-flex flex-column h-100">
                <!-- 👉 Active chat header -->
                <div class="active-chat-header d-flex align-center text-medium-emphasis bg-surface">
                  <!-- Sidebar toggler -->
                  <div class="">
                    <VBtn
                      color="primary"
                      size="small"
                      @click="closeInbox"
                    >
                      <VIcon
                        class="mr-1"
                        icon="tabler-chevron-left"
                      />
                      Back
                    </VBtn>
                  </div>
                </div>

                <VDivider color="whitesmoke" />
                
                <!-- Message form -->
                <VForm @submit.prevent="sendMessage">
                  <VRow class="mt-5">
                    <VCol
                      cols="4"
                      md="3"
                      sm="3"
                    >
                      From<span style="color: red;">*</span>
                    </VCol>
                    <VCol
                      cols="8"
                      md="9"
                      sm="9"
                    >
                      <AppAutocomplete
                        :items="filteredNumbers"
                        item-title="number"
                        item-value="number"
                        placeholder="Select Number"
                      />
                    </VCol>
                  </VRow>
                  <VRow class="mt-5">
                    <VCol
                      cols="4"
                      md="3"
                      sm="3"
                    >
                      To<span style="color: red;">*</span>
                    </VCol>
                    <VCol
                      cols="8"
                      md="9"
                      sm="9"
                    >
                      <AppTextField
                        type="tel"
                        placeholder="Enter your phone number"
                        persistent-placeholder
                      />
                    </VCol>
                  </VRow>
                  <VRow class="mt-5">
                    <VCol cols="12">
                      <AppTextarea
                    
                        variant="solo"
                        density="default"
                        autofocus
                        placeholder="Compose your message*"
                      />
                    </VCol>
                  </VRow>
                  <VRow class="mt-5">
                    <VCol
                      cols="6"
                      md="6"
                    >
                      <VIcon
                        icon="tabler-bookmark"
                        class="mr-2"
                      />
                      <VIcon
                        icon="tabler-clock-hour-1"
                        class="mr-2"
                      />
                      <VIcon icon="tabler-camera" />
                    </VCol>
                    <VCol
                      cols="6"
                      md="6"
                      class="d-flex justify-end"
                    >
                      <VBtn
                        color="primary"
                        size="small"
                      >
                        Send
                      </VBtn>
                    </VCol>
                  </VRow>
                </VForm>
              </div>
            </VMain>
          </VLayout>
        </div>
      </div>

      <div
        v-if="isSettingsActive"
        class="logs"
      >
        <DialerSettings
          :countries="countries"
          :numbers="userNumbers"
        />        
        <span
          v-if="inputDeviceError"
          class="error"
          :style="{ color: inputDeviceError.color || 'red' }"
        >{{ inputDeviceError.msg }}</span>     
      </div>

      <div class="d-flex flex-row align-center bottom__0">
        <div
          :class="isHomeActive ? 'tab-button active h-auto' : 'tab-button h-auto'"
          @click="openTab('home')"
        >
          <VIcon
            icon="tabler-phone"
            class="text-white"
          />
          <p class="mb-0 text-white mt-2">
            Phone
          </p>
        </div>
    
        <div
          :class="isLogsActive ? 'tab-button active h-auto' : 'tab-button h-auto'"
          @click="openTab('logs')"
        >
          <VIcon
            icon="tabler-clock-hour-4"
            class="text-white"
          />
          <p class="mb-0 text-white mt-2">
            Logs
          </p>
        </div>
    
        <div
          :class="isContactsActive ? 'tab-button active h-auto' : 'tab-button h-auto'"
          @click="openTab('contacts')"
        >
          <VIcon
            icon="tabler-user-search"
            class="text-white"
          />
          <p class="mb-0 text-white mt-2">
            Contacts
          </p>
        </div>
    
        <div
          :class="isMessagesActive ? 'tab-button active h-auto' : 'tab-button h-auto'"
          @click="openTab('messages')"
        >
          <VIcon
            icon="tabler-message-2"
            class="text-white"
          />
          <p class="mb-0 text-white mt-2">
            Messages
          </p>
        </div>
    
        <div
          :class="isSettingsActive ? 'tab-button active h-auto' : 'tab-button h-auto'"
          @click="openTab('settings')"
        >
          <VIcon
            icon="tabler-settings"
            class="text-white"
          />
          <p class="mb-0 text-white mt-2">
            Settings
          </p>
        </div>
      </div>
    </div>
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
</template>
    
<style scoped lang="scss">
@use "@styles/variables/_vuetify.scss";
@use "@core-scss/base/_mixins.scss";
@use "@layouts/styles/mixins" as layoutsMixins;
@use "@styles/variables/_dialer.scss";

// Variables
$chat-app-header-height: 62px;

// Placeholders
%chat-header {
  display: flex;
  align-items: center;
  min-block-size: $chat-app-header-height;
  padding-inline: 1rem;
}

.chat-app-layout {
  border-radius: vuetify.$card-border-radius;

  @include mixins.elevation(vuetify.$card-elevation);

  $sel-chat-app-layout: &;

  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-chat-app-layout);
    }
  }

  .active-chat-user-profile-sidebar,
  .user-profile-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }

  .chat-list-header,
  .active-chat-header {
    @extend %chat-header;
  }

  .chat-list-search {
    .v-field__outline__start {
      flex-basis: 20px !important;
      border-radius: 28px 0 0 28px !important;
    }

    .v-field__outline__end {
      border-radius: 0 28px 28px 0 !important;
    }

    @include layoutsMixins.rtl {
      .v-field__outline__start {
        flex-basis: 20px !important;
        border-radius: 0 28px 28px 0 !important;
      }

      .v-field__outline__end {
        border-radius: 28px 0 0 28px !important;
      }
    }
  }

  .chat-list-sidebar {
    .v-navigation-drawer__content {
      display: flex;
      flex-direction: column;
    }
  }
}
</style>

<route lang="yaml">
meta:
  layout: Auth
  action: read
  subject: dialer
  layoutWrapperClasses: layout-content-height-fixed
</route>
