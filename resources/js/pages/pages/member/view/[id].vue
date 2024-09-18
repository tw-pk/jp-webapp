<script setup>
import Profile from "@/pages/pages/member/view/components/Profile.vue"
import ProfileHeader from "@/pages/pages/member/view/components/ProfileHeader.vue"
import { useMemberListStore } from '@/views/apps/member/useMemberListStore'
import { useRoute } from "vue-router"

const memberListStore = useMemberListStore()
const route = useRoute()
const router = useRouter()
const memberDetail = ref(null)

onMounted(async () => {
  let member_id = Number(route.params.id)
  if(member_id){
    await memberListStore.fetchMemberDetail({
      member_id: member_id,
    })
      .then(res => {
        if(res.data.status){
          memberDetail.value = res.data.memberDetail
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
  <div v-if="memberDetail">
    <ProfileHeader
      class="mb-5"
      :member-detail="memberDetail"
    />
    <Profile />
  </div>
</template>


