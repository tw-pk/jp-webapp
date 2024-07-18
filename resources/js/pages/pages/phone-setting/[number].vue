<script setup>
import Assign from '@/views/wizard/phone-listing/Assign.vue'
import CallRouting from '@/views/wizard/phone-listing/CallRouting.vue'
import CallerId from '@/views/wizard/phone-listing/CallerId.vue'
import VoiceMail from '@/views/wizard/phone-listing/VoiceMail.vue'

// import Greetings from '@/views/wizard/phone-listing/Greetings.vue'
// import OtherSettings from '@/views/wizard/phone-listing/OtherSettings.vue'

import { ref } from 'vue'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()
const currentThemeName = vuetifyTheme.name.value

vuetifyTheme.themes.value[currentThemeName].colors.primary = '#38A6E3'

const route = useRoute()

const activeTab = ref('assign')
const phoneNumber = ref('')
const dPhoneNumber = ref('')
const phoneSetting = ref(null)

const formatePhoneNumber = phoneNumber => {
  const cleaned = ('' + phoneNumber).replace(/\D/g, '')
  const match = cleaned.match(/^1?(\d{3})(\d{3})(\d{4})$/)
  if (match) {
    return '(' + match[1] + ') ' + match[2] + '-' + match[3]
  }
  
  return phoneNumber
}

phoneNumber.value = route.params.number

dPhoneNumber.value = formatePhoneNumber(phoneNumber.value)

const phoneListingSteps = [
  {
    title: 'Assign',
    component: Assign,
    key: 'assign',
  },
  {
    title: 'Call Forwarding',
    component: CallRouting,
    key: 'callRoutingDetails',
  },
  {
    title: 'Caller ID',
    component: CallerId,
    key: 'callerId',
  },
  {
    title: 'Voicemail',
    component: VoiceMail,
    key: 'voiceMail',
  },

  // {
  //   title: 'Greetings',
  //   component: Greetings,
  //   key: 'greetings',
  // },
  // {
  //   title: 'Other Settings',
  //   component: OtherSettings,
  //   key: 'otherSettings',
  // },
  
]

const handleUpdatePhoneSetting = data => {
  phoneSetting.value = data
}
</script>

<template>
  <div class="">
    <VCol cols="12">
      <div class="pb-4 font-weight-bold text-h4">
        {{ dPhoneNumber }}
      </div>
    </VCol>

    <VCard>
      <VRow no-gutters>
        <VCol
          v-show="phoneListingSteps.length"
          cols="12"
          sm="4"
          lg="3"
          class="position-relative"
          :class="$vuetify.display.smAndDown ? 'border-b' : 'border-e'"
        >
          <VCardText>
            <VTabs
              v-model="activeTab"
              direction="vertical"
              class="v-tabs-pill"
              grow
            >
              <VTab
                v-for="(phonelistStep, index) in phoneListingSteps"
                :key="index"
                :value="phonelistStep.key"
                class="text-high-emphasis"
              >
                {{ phonelistStep.title }}
              </VTab>
            </VTabs>
          </VCardText>
        </VCol>

        <VCol
          cols="12"
          md="9"
        >
          <VCardText>
            <VWindow
              v-model="activeTab"
              class="faq-v-window disable-tab-transition"
            >
              <VWindowItem
                v-for="phonelistStep in phoneListingSteps"
                :key="phonelistStep.title"
                :value="phonelistStep.key"
              >
                <template v-if="activeTab === phonelistStep.key">
                  <Component
                    :is="phonelistStep.component"
                    v-model:form-data="phonelistStep.key"
                    :phone-number="phoneNumber"
                    @updatePhoneSetting="handleUpdatePhoneSetting"
                  />
                </template>
              </VWindowItem>
            </VWindow>
          </VCardText>
        </VCol>
      </VRow>
    </VCard>

    
    <VCard
      v-if="activeTab === 'callRoutingDetails' && phoneSetting"
      class="mt-5"
    >
      <VRow no-gutters>
        <VCol cols="12">
          <VTable>
            <thead>
              <tr>
                <th>
                  Forward Incoming Call To
                </th>
                <th>
                  If Unanswered, Forward call to
                </th>
                <th>
                  Phone Number
                </th>
                <th>
                  Members
                </th>
                <th>
                  Web & Desktop
                </th>
                <th>
                  Mobile Number
                </th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="item in phoneSetting"
                :key="item?.key"
              >
                <td>
                  {{ item?.fwd_incoming_call }}
                </td>
                <td>
                  {{ item?.unanswered_fwd_call }}
                </td>
                <td>
                  {{ item?.external_phone_number }}
                </td>
                <td>
                  {{ item?.ring_order_value?.fullname }}
                </td>
                <td>
                  <VIcon
                    :icon="item?.ring_order_value?.webDesktop?.icon"
                    :color="item?.ring_order_value?.webDesktop?.color"
                    :size="30"
                  />
                </td>
                <td>
                  <VIcon
                    :icon="item?.ring_order_value?.mobileLandline?.icon"
                    :color="item?.ring_order_value?.mobileLandline?.color"
                    :size="30"
                  />
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCol>
      </VRow>
    </VCard>
  </div>
</template>


<style scoped lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
