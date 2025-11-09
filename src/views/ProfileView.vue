<template>
  <div class="profile">
    <h2>Profile</h2>
    <div v-if="!auth.me">Nejste v systemu. </div>
    <div v-else>
      <div class="profile-header">
        
        <div class="info-section" v-if="!isEditing">
          <p><strong>Name:</strong> {{ auth.me.name || '—' }}</p>
          <p><strong>Email:</strong> {{ auth.me.email || '—' }}</p>
          <div class="actions">
            <button class="btn primary" @click="startEdit">Edit</button>
            <button class="btn" @click="logout">Logout</button>
          </div>
        </div>

        <form v-else class="edit-form" @submit.prevent="saveProfile">
          <div class="form-group">
            <label>Name:</label>
            <input v-model="form.name" type="text" required>
          </div>

          <div class="form-group">
            <label>Email:</label>
            <input v-model="form.email" type="email" required>
          </div>

          <div class="actions">
            <button type="submit" class="btn primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : 'Save' }}
            </button>
            <button type="button" class="btn" @click="cancelEdit" :disabled="isSaving">Отмена</button>
          </div>
        </form>
      </div>

      <!-- Albums Section -->
      <div class="albums-section">
        <h3>My Albums</h3>
        <div v-if="loading" class="loading">Loading Albums...</div>
        <div v-else-if="albums.length === 0" class="no-albums">
          Nejsou zadne alba.
        </div>
        <div v-else class="albums-grid">
          <router-link 
            v-for="album in albums" 
            :key="album.id" 
            :to="{ name: 'album', params: { id: album.id }}"
            class="album-card"
          >
            <div class="album-info">
              <span class="album-title">{{ album.title }}</span>
              <span class="album-count" v-if="album.photos_count">{{ album.photos_count }} фото</span>
            </div>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'
import api from '../utils/api'
import AlbumCard from '../components/AlbumCard.vue'

const auth = useAuth()
const router = useRouter()
const isEditing = ref(false)
const isSaving = ref(false)
const loading = ref(false)
const albums = ref([])

const form = reactive({
  name: '',
  email: ''
})

function startEdit() {
  form.name = auth.me.name || ''
  form.email = auth.me.email || ''
  isEditing.value = true
}

function cancelEdit() {
  isEditing.value = false
}

async function saveProfile() {
  if (isSaving.value) return
  
  try {
    isSaving.value = true
    const { data } = await api.post('/auth/me', {
      name: form.name,
      email: form.email
    })
    
    // Обновляем данные в сторе
    Object.assign(auth.me, data)
    isEditing.value = false
  } catch (error) {
    console.error('Chyba pri ulozeni alba:', error)
    alert('Neni mozne ulozit zmeny')
  } finally {
    isSaving.value = false
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/albums')
    albums.value = data?.data || data
  } catch (error) {
    console.error('Chyba pri nacitani alba', error)
  } finally {
    loading.value = false
  }
}

async function renameAlbum({ id, title }) {
  await api.patch(`/albums/${id}`, { title })
  await load()
}

async function deleteAlbum(id) {
  await api.delete(`/albums/${id}`)
  await load()
}

function openAlbum(id) { 
  router.push({ name: 'album', params: { id }})
}

function logout(){
  auth.logout()
  router.push('/albums')
}

onMounted(load)
</script>

<style scoped>
.profile { 
  max-width: 1200px; 
  margin: 24px auto; 
  padding: 12px; 
}

.profile-card {
  margin-bottom: 32px;
  background: #f8f9fa;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.section {
  background: #f8f9fa;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.row {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
}

.space {
  justify-content: space-between;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 16px;
}

.actions { 
  margin-top: 12px;
  display: flex;
  gap: 8px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 4px;
  font-weight: 500;
}

.form-group input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
}

.btn:hover {
  background: #f5f5f5;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn.primary {
  background: #42b983;
  color: white;
  border-color: #42b983;
}

.btn.primary:hover {
  background: #3aa876;
}

.empty {
  text-align: center;
  padding: 32px;
  color: #666;
}
</style>
