<script setup>
import Profile from "@/pages/pages/contact-details/components/Profile.vue"
import ProfileHeader from "@/pages/pages/contact-details/components/ProfileHeader.vue"
import { useMemberListStore } from '@/views/apps/member/useMemberListStore'
import { useRoute } from "vue-router"

const memberListStore = useMemberListStore()
const route = useRoute()
const router = useRouter()
const memberDetail = ref(null)

onMounted(async () => {
  if(route.params.id){
    console.log('member member member')
    await memberListStore.fetchMemberDetail({
      member_id: route.params.id,
    })
      .then(res => {
        console.log('member member member')
        console.log(res)

        // if(res.data.status){
        //   memberDetail.value = res.data.member
        // }else{
        //   router.replace({ name: 'pages-misc-not-found' })
        // }
      })
  }else{
    //await router.replace({ name: 'pages-misc-not-found' })
    console.log('pages-misc-not-found')
  }
})
</script>

<template>
  <div v-if="memberDetail">
    <ProfileHeader
      class="mb-5"
      :member-detail="memberDetail"
    />
    <Profile />
  </div>
</template>


