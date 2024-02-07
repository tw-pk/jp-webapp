import { defineStore } from 'pinia'
import axiosIns from "@axios"

export const useInvoiceStore = defineStore('InvoiceStore', {
  actions: {
    // 👉 Fetch all Invoices
    fetchInvoices(params) {
      return axiosIns.get('/api/auth/invoices', { params })
    },

    // 👉 Fetch single invoice
    fetchInvoice(id) {
      return axiosIns.get(`/apps/invoices/${id}`)
    },

    // 👉 Fetch Clients
    fetchClients() {
      return axiosIns.get('/apps/invoice/clients')
    },

    // 👉 Delete Invoice
    deleteInvoice(id) {
      return axiosIns.delete(`/apps/invoices/${id}`)
    },
  },
})
