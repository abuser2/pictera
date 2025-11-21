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
import { onMounted, ref } from 'vue'
import { useAuth } from './stores/auth'
import LoginModal from './components/LoginModal.vue'

const auth = useAuth()
const showLogin = ref(false)
function openLogin(){ showLogin.value = true }

onMounted(() => {
  auth.fetchMe().catch(()=>{})
  window.addEventListener('need-login', () => showLogin.value = true)
})
</script>
