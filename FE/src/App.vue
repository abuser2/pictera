<template>
  <div class="app">
    <header class="topbar">
      <h1>Pictera</h1>
      <div class="spacer"/>
      <button v-if="!auth.me" class="btn" @click="openLogin">Login</button>
      <div v-else class="user">
        <span>{{ auth.me.name || 'User' }}</span>
        <button class="btn ghost" @click="auth.logout()">Logout</button>
      </div>
    </header>

    <div class="layout">
      <aside class="side">

        <router-link to="/home">Home</router-link>
        <router-link to="/profile">Profile</router-link>
        <router-link to="/albums">Albums</router-link>
        
        <div v-if="isAlbumsRoute && albums.length" class="sub-menu">
          <router-link 
            v-for="a in albums" 
            :key="a.id" 
            :to="`/albums/${a.id}`"
            class="sub-link"
          >
            {{ a.name }}
          </router-link>
        </div>

        <router-link to="/shares">Shared Albums</router-link>
        
        <div v-if="isSharesRoute && sharedAlbums.length" class="sub-menu">
          <router-link 
            v-for="a in sharedAlbums" 
            :key="a.id" 
            :to="`/shares/${a.id}`"
            class="sub-link"
          >
            {{ a.name }}
          </router-link>
        </div>

        <router-link to="/bookings">Bookings</router-link>

      </aside>
      <main class="content">
        <router-view />
      </main>
    </div>

    <LoginModal v-if="showLogin" @close="showLogin=false"/>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from './stores/auth'
import api from './utils/api'
import LoginModal from './components/LoginModal.vue'

const auth = useAuth()
const route = useRoute()
const showLogin = ref(false)
const albums = ref([])
const sharedAlbums = ref([])

function openLogin(){ showLogin.value = true }

const isAlbumsRoute = computed(() => route.path.startsWith('/albums'))
const isSharesRoute = computed(() => route.path.startsWith('/shares'))

async function refreshSidebar() {
  if (!auth.me) return
  
  if (isAlbumsRoute.value) {
    try {
      const { data } = await api.get('/albums?type=owned')
      albums.value = data?.data || data
    } catch (e) { console.error(e) }
  } else if (isSharesRoute.value) {
    try {
      const { data } = await api.get('/albums?type=shared')
      sharedAlbums.value = data?.data || data
    } catch (e) {
      console.error(e)
    }
  }
}

watch([isAlbumsRoute, isSharesRoute, () => auth.me], refreshSidebar, { immediate: true })

onMounted(() => {
  auth.fetchMe().catch(()=>{})
  window.addEventListener('need-login', () => showLogin.value = true)
  window.addEventListener('albums-updated', refreshSidebar)
})

onUnmounted(() => {
  window.removeEventListener('albums-updated', refreshSidebar)
})
</script>

<style scoped>
.sub-menu { display:flex; flex-direction:column; gap:2px; margin-left:16px; margin-top:-8px; margin-bottom:8px; border-left:1px solid #333; padding-left:8px; }
.sub-link { font-size:0.9rem; color:#888; padding:4px 8px; border-radius:6px; transition:color 0.2s; }
.sub-link:hover { color:#fff; background:transparent; }
/* Переопределяем глобальные стили активной ссылки для подменю */
.sub-link.router-link-active { color:#d0813b; background:transparent !important; font-weight:normal; }
</style>
