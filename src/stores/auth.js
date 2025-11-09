import { defineStore } from 'pinia'
import api from '../utils/api'

export const useAuth = defineStore('auth', {
  state: () => ({ token: localStorage.getItem('token') || null, me: null }),
  actions: {
    setToken(t) { this.token = t; localStorage.setItem('token', t) },
    async login(email, password) {
      // POST /api/auth/login  -> {token, user}  :contentReference[oaicite:1]{index=1}
      const { data } = await api.post('/auth/login', { email, password })
      this.setToken(data.token || data?.data?.token || data?.access_token)
      await this.fetchMe()
    },
    async register(name, email, password) {
      await api.post('/auth/register', { name, email, password }) // :contentReference[oaicite:2]{index=2}
      return this.login(email, password)
    },
    async fetchMe() {
      if (!this.token) return
      const { data } = await api.get('/auth/me') // Bearer {{token}}  :contentReference[oaicite:3]{index=3}
      this.me = data
    },
    async updateProfile(profileData) {
      const { data } = await api.post('/profile', profileData)
      this.me = { ...this.me, ...data }
      return data
    },
    async logout() {
      try { await api.post('/auth/logout') } catch {}
      this.token = null; this.me = null; localStorage.removeItem('token')
    }
  }
})
