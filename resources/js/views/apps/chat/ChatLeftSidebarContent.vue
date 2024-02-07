<script setup>
import ChatContact from '@/views/apps/chat/ChatContact.vue'
import { useChatStore } from '@/views/apps/chat/useChatStore'
import { useContactStore } from "@/views/apps/contact/useContactStore"
import { emailValidator, requiredValidator } from "@validators"
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useChat } from './useChat'

const props = defineProps({
  search: {
    type: String,
    required: true,
  },
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },

  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'openChatOfContact',
  'showUserProfile',
  'close',
  'isDialogVisible',
])

const contactStore = useContactStore()
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarActionColor = ref(' ')
const { resolveAvatarBadgeVariant } = useChat()
const search = useVModel(props, 'search')
const isDialogVisible = useVModel(props, 'isDialogVisible')
const chatStore = useChatStore()
const firstname = ref('')
const lastname = ref('')
const email = ref('')
const phone = ref('')
const address_home = ref('')
const address_office = ref('')
const isDisabled = ref(false)
const isLoading = ref(false)

const errors = ref({
  firstname: undefined,
  lastname: undefined,
  email: undefined,
  phone: undefined,
  address_home: undefined,
})

const addContact = () => {
  isDisabled.value = true
  isLoading.value = true

  const reqData = {
    firstname: firstname.value,
    lastname: lastname.value,
    email: email.value,
    phone: phone.value,
    address_home: address_home.value,
    address_office: address_office.value,
  }

  contactStore.addContact(reqData)
    .then(async res => {
      isDisabled.value = false
      isLoading.value = false
      isDialogVisible.value = false
      await useChatStore().fetchChatsAndContacts("")
      snackbarMessage.value = res.data.message
      snackbarActionColor.value = 'success'
      isSnackbarVisible.value = true
    })
    .catch(error => {
      isDisabled.value = false
      isLoading.value = false
      errors.value = error.response.data.errors
      snackbarMessage.value = error.message
      snackbarActionColor.value = 'error'
      isSnackbarVisible.value = true
    })
}

onMounted(() => {
  console.log('onmounted')
})
</script>

<template>
  <!-- 👉 Chat list header -->
  <div
    v-if="chatStore.profileUser"
    class="chat-list-header"
  >
    <!--
      <VBadge
      dot
      location="bottom right"
      offset-x="3"
      offset-y="3"
      :color="resolveAvatarBadgeVariant(chatStore.profileUser.status)"
      bordered
      >
      <VAvatar
      size="38"
      class="cursor-pointer"
      @click="$emit('showUserProfile')"
      >
      <VImg
      :src="chatStore.profileUser.avatar"
      alt="John Doe"
      />
      </VAvatar>
      </VBadge> 
    -->

   
    <AppTextField
      v-model="search"
      placeholder="Search..."
      class=" me-1 chat-list-search"
    >
      <template #prepend-inner>
        <VIcon
          size="22"
          icon="tabler-search"
        />
      </template>
    </AppTextField>

    <VDialog
      v-model="isDialogVisible"
      persistent
      class="v-dialog-sm"
    >
      <!-- Dialog Activator -->
      <template #activator="{ props }">
        <VBtn
          size="38"
          color="primary"
          class="me-1"
          v-bind="props"
        >
          <VTooltip
            location="top"
            activator="parent"
          >
            Add New Contact
          </VTooltip>
          <VIcon
            icon="tabler-plus"
            size="22"
          />
        </VBtn>
      </template>

      <!-- Dialog close btn -->
      <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />

      <!-- Dialog Content -->
      <VCard title="Add New Contact">
        <VCardText>
          <VForm
            ref="form"
            lazy-validation
          >
            <VCardText>
              <VRow>
                <VCol
                  cols="12"
                  sm="6"
                  md="6"
                >
                  <AppTextField
                    v-model="firstname"
                    label="First Name"
                    :rules="[requiredValidator]"
                    :error-messages="errors.firstname"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                  md="6"
                >
                  <AppTextField
                    v-model="lastname"
                    label="Last Name"
                    :rules="[requiredValidator]"
                    :error-messages="errors.lastname"
                  />
                </VCol>
              </VRow>
              <VRow>
                <VCol
                  cols="12"
                  sm="6"
                  md="6"
                >
                  <AppTextField
                    v-model="email"
                    label="Email Address"
                    :rules="[emailValidator, requiredValidator]"
                    :error-messages="errors.email"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                  md="6"
                >
                  <AppTextField
                    v-model="phone"
                    label="Phone Number"
                    :rules="[requiredValidator]"
                    :error-messages="errors.phone"
                  />
                </VCol>
              </VRow>
              <VRow>
                <VCol
                  cols="12"
                  sm="12"
                  md="12"
                >
                  <AppTextField
                    v-model="address_home"
                    label="Address Home"
                    :rules="[requiredValidator]"
                    :error-messages="errors.address_home"
                  />
                </VCol>
              </VRow>
              <VRow>
                <VCol
                  cols="12"
                  sm="12"
                  md="12"
                >
                  <AppTextField
                    v-model="address_office"
                    label="Address Office"
                  />
                </VCol>
              </VRow>
            </VCardText>
            <VCardText class="d-flex justify-center flex-wrap gap-3">
              <VBtn
                :disabled="isDisabled"
                :loading="isLoading"
                @click="addContact"
              >
                Add Contact
              </VBtn>
              <VBtn
                variant="tonal"
                color="secondary"
                @click="isDialogVisible = !isDialogVisible"
              >
                Cancel
              </VBtn>
            </VCardText>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>

    <IconBtn
      v-if="$vuetify.display.smAndDown"
      @click="$emit('close')"
    >
      <VIcon
        icon="tabler-x"
        class="text-medium-emphasis"
      />
    </IconBtn>
  </div>
  <VDivider />

  <PerfectScrollbar
    tag="ul"
    class="d-flex flex-column gap-y-1 chat-contacts-list px-3 list-none"
    :options="{ wheelPropagation: false }"
  >
    <li>
      <span class="chat-contact-header d-block text-primary text-xl font-weight-medium">Chats</span>
    </li>

    <ChatContact
      v-for="contact in chatStore.chatsContacts"
      :key="`chat-${contact.id}`"
      :user="contact"
      is-chat-contact
      @click="$emit('openChatOfContact', contact.id)"
    />

    <span
      v-show="!chatStore.chatsContacts.length"
      class="no-chat-items-text text-disabled"
    >No chats found</span>

    <li> 
      <span class="chat-contact-header d-block text-primary text-xl font-weight-medium">Contacts</span> 
    </li> 

    <ChatContact 
      v-for="contact in chatStore.contacts" 
      :key="`chat-${contact.id}`" 
      :user="contact" 
      @click="$emit('openChatOfContact', contact.id)" 
    /> 

    <span 
      v-show="!chatStore.contacts.length" 
      class="no-chat-items-text text-disabled" 
    >No contacts found</span> 
  </PerfectScrollbar>

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
</template>

<style lang="scss">
.chat-contacts-list {
  --chat-content-spacing-x: 16px;

  padding-block-end: 0.75rem;

  .chat-contact-header {
    margin-block-end: 0.625rem;
    margin-block-start: 1.25rem;
  }

  .chat-contact-header,
  .no-chat-items-text {
    margin-inline: var(--chat-content-spacing-x);
  }
}

.chat-list-search {
  .v-field--focused {
    box-shadow: none !important;
  }
}
</style>
