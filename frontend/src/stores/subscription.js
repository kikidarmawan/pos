import { defineStore } from "pinia";
import api from "@/utils/axios";

export const useSubscriptionStore = defineStore("subscription", {
  state: () => ({
    hasActive: null, // null = belum dicek, true/false = hasil cek
    subscription: null,
    showExpiredModal: false,
  }),

  getters: {
    isExpired: (state) => state.hasActive === false,
    needsCheck: (state) => state.hasActive === null,
    /** True jika langganan aktif dan masa berlaku habis dalam 7 hari ke depan */
    isExpiringSoon(state) {
      if (state.hasActive !== true || !state.subscription?.expires_at) return false
      const expiresAt = new Date(state.subscription.expires_at)
      const now = new Date()
      if (expiresAt <= now) return false
      const sevenDaysFromNow = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)
      return expiresAt <= sevenDaysFromNow
    },
    /** Sisa hari sampai langganan habis (untuk tampilan), null jika tidak berlaku */
    daysLeft(state) {
      if (state.hasActive !== true || !state.subscription?.expires_at) return null
      const expiresAt = new Date(state.subscription.expires_at)
      const now = new Date()
      if (expiresAt <= now) return 0
      return Math.ceil((expiresAt - now) / (24 * 60 * 60 * 1000))
    },
  },

  actions: {
    setFromResponse(data) {
      this.hasActive = data?.has_active ?? false;
      this.subscription = data?.subscription ?? null;
    },

    setExpired() {
      this.hasActive = false;
      this.subscription = null;
      this.showExpiredModal = true;
    },

    openPayModal() {
      this.showExpiredModal = true;
    },

    closePayModal() {
      this.showExpiredModal = false;
    },

    async fetchCurrent() {
      try {
        const res = await api.get("/subscription/current");
        this.setFromResponse(res.data);
        return res.data;
      } catch (e) {
        this.hasActive = false;
        this.subscription = null;
        return null;
      }
    },
  },
});
