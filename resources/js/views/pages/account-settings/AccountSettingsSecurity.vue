<script setup>
import twoFactor from "@/apis/twoFactor"
import User from "@/apis/user"
import laptopGirl from '@images/illustrations/laptop-girl.png'
import { confirmedValidator, passwordValidator, requiredValidator } from "@validators"
import { VDataTable } from 'vuetify/labs/VDataTable'

const isCurrentPasswordVisible = ref(false)
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const isError = ref(false)

const apiResponse = ref(false)

const responseMessage = ref('')

const errors = ref({
  currentPassword: undefined,
  newPassword: undefined,
  confirmPassword: undefined,
})

const passwordRequirements = [
  'Minimum 8 characters long - the more, the better',
  'At least one lowercase character',
  'At least one number, symbol, or whitespace character',
]

const serverKeys = [
]

const recentDevicesHeaders = [
  {
    title: 'BROWSER',
    key: 'browser',
  },
  {
    title: 'DEVICE',
    key: 'device',
  },
  {
    title: 'LOCATION',
    key: 'location',
  },
  {
    title: 'RECENT ACTIVITY',
    key: 'recentActivity',
  },
]

const recentDevices = ref([])

const isOneTimePasswordDialogVisible = ref(false)

const updatePassword = async() => {
  await User.updatePassword({
    "currentPassword": currentPassword.value,
    "newPassword": newPassword.value,
    "confirmPassword": confirmPassword.value,
  })
    .then(res => {
      if(res.data.status){
        currentPassword.value = ''
        newPassword.value = ''
        confirmPassword.value = ''
        isError.value = false
        apiResponse.value = true
        responseMessage.value = res.data.message
      }
    })
    .catch(error => {
      currentPassword.value = ''
      newPassword.value = ''
      confirmPassword.value = ''
      errors.value = error.response.data.errors
      apiResponse.value = true
      isError.value = true
      responseMessage.value = error.response.data.message
    })
}

const twoFactorEnabled = ref(false)
const defaultMessage2FA = ref('Two factor authentication is not enabled yet.')

const disableTwoFactorAuth = async () => {
  await twoFactor.disableTwoFactor()
    .then(res => {
      if(res.data.status){
        twoFactorEnabled.value = false
        defaultMessage2FA.value = 'Two factor authentication is not enabled yet.'
      }else{
        twoFactorEnabled.value = true
      }
    })
    .catch(error => {
      console.log(error)
    })
}

onMounted(async() => {
  await twoFactor.isEnabled()
    .then(res => {
      console.log('twoFactor')
      console.log(res.data.recentDevices)
      console.log(res.data.recentDevices)

      recentDevices.value = res.data.recentDevices
      if(res.data.status){
        twoFactorEnabled.value = true
        defaultMessage2FA.value = res.data.message
      }else{
        twoFactorEnabled.value = false
        defaultMessage2FA.value = res.data.message
      }
    })
    .catch(error => {
      console.log(error)
    })
})
</script>

<template>
  <VRow>
    <!-- SECTION: Change Password -->
    <VCol cols="12">
      <div
        v-if="apiResponse"
        class="my-3"
      >
        <VAlert
          density="compact"
          :color="isError ? 'error' : 'success'"
          variant="tonal"
          closable
        >
          {{ responseMessage }}
        </VAlert>
      </div>
      <VCard title="Change Password">
        <VForm @submit.prevent="updatePassword">
          <VCardText class="pt-0">
            <!-- 👉 Current Password -->
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <!-- 👉 current password -->
                <AppTextField
                  v-model="currentPassword"
                  :rules="[requiredValidator]"
                  :error-messages="errors.currentPassword"
                  :type="isCurrentPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isCurrentPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="Current Password"
                  @click:append-inner="isCurrentPasswordVisible = !isCurrentPasswordVisible"
                />
              </VCol>
            </VRow>

            <!-- 👉 New Password -->
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <!-- 👉 new password -->
                <AppTextField
                  v-model="newPassword"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :rules="[requiredValidator, passwordValidator]"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="New Password"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <!-- 👉 confirm password -->
                <AppTextField
                  v-model="confirmPassword"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :rules="[requiredValidator, passwordValidator, confirmedValidator(confirmPassword, newPassword)]"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="Confirm New Password"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>
            </VRow>
          </VCardText>

          <!-- 👉 Password Requirements -->
          <VCardText>
            <h6 class="text-base font-weight-medium mb-3">
              Password Requirements:
            </h6>

            <VList class="card-list">
              <VListItem
                v-for="item in passwordRequirements"
                :key="item"
                :title="item"
                class="text-medium-emphasis"
              >
                <template #prepend>
                  <VIcon
                    size="8"
                    icon="tabler-circle"
                    class="me-3"
                  />
                </template>
              </VListItem>
            </VList>
          </VCardText>

          <!-- 👉 Action Buttons -->
          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn type="submit">
              Save changes
            </VBtn>

            <VBtn
              type="reset"
              color="secondary"
              variant="tonal"
            >
              Reset
            </VBtn>
          </VCardText>
        </VForm>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <!-- SECTION Two-steps verification -->
    <VCol cols="12">
      <VCard title="Two-steps verification">
        <VCardText>
          <h6
            v-if="!twoFactorEnabled"
            class="text-base font-weight-medium mb-3"
          >
            {{ defaultMessage2FA }}
          </h6>
          <h6
            v-else
            class="text-base font-weight-medium mb-3"
          >
            {{ defaultMessage2FA }}
          </h6>
          <p>
            Two-factor authentication adds an additional layer of security to your account by
            <br>
            requiring more than just a password to log in.
            <a
              href="javascript:void(0)"
              class="text-decoration-none"
            >Learn more.</a>
          </p>
          <VRow no-gutters>
            <VCol
              cols="3"
              md="3"
            >
              <VBtn @click="isOneTimePasswordDialogVisible = true">
                Enable 2FA
              </VBtn>
            </VCol>

            <VCol
              v-if="twoFactorEnabled"
              cols="3"
              md="3"
            >
              <VBtn @click="disableTwoFactorAuth">
                Disable 2FA
              </VBtn>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

    <VCol cols="12">
      <!-- SECTION: Create an API key -->
      <VCard title="Create an API key">
        <VRow no-gutters>
          <!-- 👉 Choose API Key -->
          <VCol
            cols="12"
            md="5"
            order-md="0"
            order="1"
          >
            <VCardText>
              <VForm @submit.prevent="() => { }">
                <VRow>
                  <!-- 👉 Choose API Key -->
                  <VCol cols="12">
                    <AppSelect
                      label="Choose the API key type you want to create"
                      :items="['Full Control', 'Modify', 'Read & Execute', 'List Folder Contents', 'Read Only', 'Read & Write']"
                    />
                  </VCol>

                  <!-- 👉 Name the API Key -->
                  <VCol cols="12">
                    <AppTextField label="Name the API key" />
                  </VCol>

                  <!-- 👉 Create Key Button -->
                  <VCol cols="12">
                    <VBtn
                      type="submit"
                      block
                    >
                      Create Key
                    </VBtn>
                  </VCol>
                </VRow>
              </VForm>
            </VCardText>
          </VCol>

          <!-- 👉 Lady image -->
          <VCol
            cols="12"
            md="7"
            order="0"
            order-md="1"
            class="d-flex flex-column justify-center align-center"
          >
            <VImg
              :src="laptopGirl"
              :width="200"
              :style="$vuetify.display.smAndDown ? '' : 'position: absolute; bottom: 0;'"
            />
          </VCol>
        </VRow>
      </VCard>
      <!-- !SECTION -->
    </VCol>

    <VCol cols="12">
      <!-- SECTION: API Keys List -->
      <VCard title="API Key List &amp; Access">
        <VCardText>
          An API key is a simple encrypted string that identifies an application without any principal. They are useful
          for accessing public data anonymously, and are used to associate API requests with your project for quota and
          billing.
        </VCardText>

        <!-- 👉 Server Status -->
        <VCardText class="d-flex flex-column gap-y-4">
          <VCard
            v-for="serverKey in serverKeys"
            :key="serverKey.key"
            flat
            variant="tonal"
            class="pa-4"
          >
            <MoreBtn
              :menu-list="[
                { prependIcon: 'tabler-pencil', title: 'Edit', value: 'Edit' },
                { prependIcon: 'tabler-trash', title: 'Delete', value: 'Delete' },
              ]"
              item-props
              class="position-absolute server-close-btn"
            />

            <div class="d-flex align-center flex-wrap mb-3">
              <h6 class="text-h6 me-3">
                {{ serverKey.name }}
              </h6>

              <VChip
                label
                color="primary"
                size="small"
              >
                {{ serverKey.permission }}
              </VChip>
            </div>

            <div class="d-flex align-center text-base font-weight-medium mb-2">
              <h6 class="text-base me-3">
                {{ serverKey.key }}
              </h6>

              <div class="cursor-pointer">
                <VIcon
                  icon="tabler-copy"
                  class="text-disabled"
                />
              </div>
            </div>

            <span>Created on {{ serverKey.createdOn }}</span>
          </VCard>
        </VCardText>
      </VCard>
      <!-- !SECTION -->
    </VCol>

    <!-- SECTION Recent Devices -->
    <VCol cols="12">
      <!-- 👉 Table -->
      <VCard title="Recent Devices">
        <VDataTable
          :headers="recentDevicesHeaders"
          :items="recentDevices"
          hide-default-footer
        >
          <template #item.browser="{ item }">
            <div class="d-flex">
              <VIcon
                start
                :icon="item.raw.deviceIcon.icon"
                :color="item.raw.deviceIcon.color"
              />
              <span>
                {{ item.raw.browser }}
              </span>
            </div>
          </template>
          <!-- TODO Refactor this after vuetify provides proper solution for removing default footer -->
          <template #bottom />
        </VDataTable>
      </VCard>
    </VCol>
    <!-- !SECTION -->
  </VRow>

  <!-- SECTION Enable One time password -->
  <TwoFactorAuthDialog v-model:isDialogVisible="isOneTimePasswordDialogVisible" />
  <!-- !SECTION -->
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 5px;
}

.server-close-btn {
  inset-inline-end: 0.5rem;
}
</style>
