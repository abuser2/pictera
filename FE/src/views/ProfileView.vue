<template>
  <div class="profile">
    <h2>Profile</h2>

    <div v-if="!auth.me">Nejste v systému.</div>
    <div v-else>
      <!-- Profile Info -->
      <div class="profile-header">
        <div class="info-section" v-if="!isEditing">
          <p><strong>Name:</strong> {{ auth.me.name || '—' }}</p>
          <p><strong>Email:</strong> {{ auth.me.email || '—' }}</p>
          <div class="actions">
            <button class="btn primary" @click="startEdit">Edit</button>
          </div>
        </div>

        <form v-else class="edit-form" @submit.prevent="saveProfile">
          <div class="form-group">
            <label>Name:</label>
            <input v-model="form.name" type="text" required />
          </div>
          <div class="form-group">
            <label>Email:</label>
            <input v-model="form.email" type="email" required />
          </div>
          <div class="actions">
            <button type="submit" class="btn primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : 'Save' }}
            </button>
            <button type="button" class="btn" @click="cancelEdit" :disabled="isSaving">
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Albums Section -->
      <div class="albums-section">
        <h3>Albums</h3>

        <!-- Filter Dropdown -->
        <div class="visibility-filter">
          <label>Filter by visibility:</label>
          <select v-model="visibilityFilter">
            <option value="">All</option>
            <option value="public">Public</option>
            <option value="private">Private</option>
          </select>
        </div>

        <div v-if="loading" class="load">Loading Albums...</div>
        <div v-else-if="filteredAlbums.length === 0" class="no-albums">
          No albums available.
        </div>

        <div class="albums-list">
          <!-- Inline Create Album -->
          <div v-if="creating" class="album-card create-card">
            <input
              v-model="newAlbumName"
              placeholder="Album name"
              @keyup.enter="confirmCreate"
              @keyup.esc="cancelCreate"
              autofocus
            />
            <div class="row gap">
              <button class="btn small" @click="confirmCreate">Create</button>
              <button class="btn ghost small" @click="cancelCreate">Cancel</button>
            </div>
          </div>

          <button v-if="!creating" class="btn" @click="startCreate">New Album</button>

          <!-- Album Cards -->
          <ProfileAlbumCard
            v-for="album in filteredAlbums"
            :key="album.id"
            :album="album"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuth } from '../stores/auth'
import { useProfileAlbumsStore } from '../stores/profileAlbums'
import ProfileAlbumCard from '../components/ProfileAlbumCard.vue'

// Stores
const auth = useAuth()
const albumsStore = useProfileAlbumsStore()

// Profile Edit
const isEditing = ref(false)
const isSaving = ref(false)
const form = reactive({ name: '', email: '' })

function startEdit() {
  form.name = auth.me?.name || ''
  form.email = auth.me?.email || ''
  isEditing.value = true
}

function cancelEdit() { isEditing.value = false }

async function saveProfile() {
  if (isSaving.value) return
  try {
    isSaving.value = true
    await auth.updateProfile({ name: form.name, email: form.email })
    isEditing.value = false
  } finally {
    isSaving.value = false
  }
}

// Album Management
const creating = ref(false)
const newAlbumName = ref('')
const visibilityFilter = ref('')
const loading = ref(true)

const filteredAlbums = computed(() => {
  if (!auth.me || !albumsStore.albums.length) return []
  return albumsStore.visibleAlbums(auth.me.id, visibilityFilter.value || null)
})

function startCreate() {
  creating.value = true
  newAlbumName.value = ''
}

function cancelCreate() {
  creating.value = false
}

async function confirmCreate() {
  if (!newAlbumName.value.trim()) return
  await albumsStore.create(newAlbumName.value)
  creating.value = false
}

// Fetch albums and user
onMounted(async () => {
  if (!auth.me) await auth.fetchMe()
  if (!albumsStore.albums.length) await albumsStore.fetchAll()
  loading.value = false
})
</script>

<style scoped>
.profile { max-width: 1200px; margin: 24px auto; padding: 12px; color: #fff; }
.profile-header { margin-bottom: 24px; }
.form-group label { display: block; margin-bottom: 4px; }
.form-group input { width: 100%; padding: 8px; border-radius: 4px; }
.btn.primary { background: #42b983; color: #fff; border: none; padding: 8px 16px; cursor: pointer; }

.albums-list {
  display: flex;
  flex-direction: column;
  gap: 1rem; /* space between albums */
}

.create-card {
  width: 100%;
}
.profile-album-card {
  width: 100%;
}

.visibility-filter {
  margin: 16px 0;
}
select {
  padding: 4px 8px;
  border-radius: 4px;
}
</style>
