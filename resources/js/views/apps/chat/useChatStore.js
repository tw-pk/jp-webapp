import axiosIns from "@axios"

export const useChatStore = defineStore('chat', {
  // ℹ️ arrow function recommended for full type inference
  state: () => ({
    contacts: [],
    chatsContacts: [],
    profileUser: undefined,
    activeChat: null,
  }),
  actions: {
    async fetchChatsAndContacts(q) {
      const { data } = await axiosIns.get('/api/auth/chat/chats-and-contacts', {
        params: { q },
      })

      const { chatsContacts, contacts, profileUser } = data.data

      this.chatsContacts = chatsContacts
      this.contacts = contacts
      this.profileUser = profileUser
    },
    async getChat(contactId) {
      const { data } = await axiosIns.get(`/api/auth/chats/${contactId}`)

      this.activeChat = data.data
    },
    async sendMsg(message) {
      const senderId = this.profileUser?.id

      const requestData = {
        contactId: this.activeChat?.contact.id,
        message,
        senderId,
      }

      const { data } = await axiosIns.post(`/api/auth/chats/send/message`, requestData)
      const { msg, chat } = data

      // ? If it's not undefined => New chat is created (Contact is not in list of chats)
      if (chat !== undefined) {
        const activeChat = this.activeChat

        this.chatsContacts.push({
          ...activeChat.contact,
          chat: {
            id: chat.id,
            lastMessage: [],
            unseenMsgs: 0,
            messages: [msg],
          },
        })
        if (this.activeChat) {
          this.activeChat.chat = {
            id: chat.id,
            messages: [msg],
            unseenMsgs: 0,
            userId: this.activeChat?.contact.id,
          }
        }
      } else {
        this.activeChat?.chat?.messages.push(msg)
      }

      // Set Last Message for active contact
      const contact = this.chatsContacts.find(c => {
        if (this.activeChat)
          return c.id === this.activeChat.contact.id

        return false
      })

      contact.chat.lastMessage = msg
    },
  },
})
