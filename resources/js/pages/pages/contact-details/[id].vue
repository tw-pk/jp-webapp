<script setup>
import Profile from "@/pages/pages/contact-details/components/Profile.vue"
import ProfileHeader from "@/pages/pages/contact-details/components/ProfileHeader.vue"
import { useContactStore } from "@/views/apps/contact/useContactStore"
import { useRoute } from "vue-router"

const contactStore = useContactStore()
const route = useRoute()
const router = useRouter()
const contactData = ref(null)

onMounted(async () => {
  if(route.params.id){
    await contactStore.getContactDetails({
      contact_id: route.params.id,
    })
      .then(res => {
        if(res.data.status){
          contactData.value = res.data.contactData
        }else{
          router.replace({ name: 'pages-misc-not-found' })
        }
      })
  }else{
    await router.replace({ name: 'pages-misc-not-found' })
  }
})
</script>

<template>
  <div v-if="contactData">
    <ProfileHeader class="mb-5" />
    <Profile :contact-data="contactData" />
  </div>
</template>

<route lang="yaml">
meta:
  action: read
  subject: contact-details
</route>

