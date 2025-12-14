<template>
  <div class="center-screen">
    <div v-if="loading" style="color: #fff;">Loading...</div>
    <div v-else class="share-card">
      <h2 class="album-name">{{ album.title || album.name || 'Shared Album' }}</h2>
      <p class="album-info">{{ photos.length }} photos</p>
      
      <div class="actions">
        <button v-if="auth.me" class="btn" @click="importAlbum">Add to my albums</button>
        <div v-else class="login-block">
          <p>Login to add this album</p>
          <button class="btn" @click="triggerLogin">Login</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'
import api from '../utils/api'

const route = useRoute()
const router = useRouter()
const auth = useAuth()

const loading = ref(true)
const error = ref('')
const album = ref({})
const photos = ref([])

async function load() {
  loading.value = true
  try {
    // Запрашиваем данные по токену
    const { data } = await api.get(`/share/${route.params.token}`)
    if (data.type === 'album') {
      album.value = data.data
      photos.value = data.data.photos
    } else {
      error.value = 'This link is not for an album'
    }
  } catch (e) {
    error.value = 'Link expired or invalid'
  } finally {
    loading.value = false
  }
}

function triggerLogin() {
  window.dispatchEvent(new Event('need-login'))
}

async function importAlbum() {
  try {
    const { data } = await api.post(`/share/${route.params.token}/import`)
    alert('Album imported successfully!')
    window.dispatchEvent(new Event('albums-updated'))
    router.push(`/albums/${data.id}`)
  } catch (e) {
    alert('Import failed')
  }
}

onMounted(load)
</script>

<style scoped>

.btn:hover { background: #b46b2f; }
.center-screen {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 80vh;
  width: 100%;
}
.share-card {
  background: #1c1c1e;
  border: 1px solid #2c2c2e;
  border-radius: 16px;
  padding: 40px;
  text-align: center;
  max-width: 400px;
  width: 100%;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.album-name { color: #fff; margin: 0 0 10px; font-size: 1.8rem; }
.album-info { color: #888; margin: 0 0 30px; }
.actions { display: flex; flex-direction: column; gap: 15px; align-items: center; }
.login-block { display: flex; flex-direction: column; gap: 10px; align-items: center; color: #aaa; }
.login-block p { margin: 0; font-size: 0.9rem; }
.btn { background: #d0813b; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; }
</style>
