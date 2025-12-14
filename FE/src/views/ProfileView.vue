<template>
  <div class="profile">
    <h2>Profile</h2>

    <div v-if="!auth.me">Nejste v systému.</div>

    <div v-else>
      <!-- Profile Info -->
      <div class="profile-header">
        <div class="info-section" v-if="!isEditing">
          <p><strong>Name:</strong> {{ user.name || '—' }}</p>
          <p><strong>Email:</strong> {{ user.email || '—' }}</p>

          <div class="actions">
            <!-- Only show edit if it's your profile -->
            <button v-if="isMe" class="btn primary" @click="startEdit">Edit</button>
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
      <button class="btn" @click="toggleFollow(user)" v-if="!isMe">
        {{ user.is_following ? 'Unfollow' : 'Follow' }}
      </button>
      <!-- Albums Section -->
      <div class="albums-section">
        <h3>Albums</h3>

        <div class="visibility-filter" v-if="isMe">
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
          <!-- Inline Create Album, only for own profile -->
          <div v-if="creating && isMe" class="album-card create-card">
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

          <!-- Create button, only for own profile -->
          <button v-if="!creating && isMe" class="btn" @click="startCreate">New Album</button>

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
import { ref, reactive, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '../stores/auth'
import { useProfileAlbumsStore } from '../stores/profileAlbums'
import ProfileAlbumCard from '../components/ProfileAlbumCard.vue'
import api from '../utils/api'

const auth = useAuth()
const albumsStore = useProfileAlbumsStore()
const route = useRoute()

// Profile data
const user = ref(null)
const isEditing = ref(false)
const isSaving = ref(false)
const isMe = computed(() => user.value?.id === auth.me?.id)
const form = reactive({ name: '', email: '' })

// Albums
const creating = ref(false)
const newAlbumName = ref('')
const visibilityFilter = ref('')
const loading = ref(true)

const filteredAlbums = computed(() => {
  if (!user.value || !albumsStore.albums.length) return []
  // Only show public albums if it's another user's profile
  const visibility = isMe.value ? visibilityFilter.value || null : 'public'
  return albumsStore.visibleAlbums(user.value.id, visibility)
})

// Edit profile
function startEdit() {
  if (!isMe.value) return
  form.name = user.value.name || ''
  form.email = user.value.email || ''
  isEditing.value = true
}

function cancelEdit() { isEditing.value = false }

async function saveProfile() {
  if (!isMe.value || isSaving.value) return
  try {
    isSaving.value = true
    await auth.updateProfile({ name: form.name, email: form.email })
    user.value.name = form.name
    user.value.email = form.email
    isEditing.value = false
  } finally {
    isSaving.value = false
  }
}

// Album creation
function startCreate() {
  if (!isMe.value) return
  creating.value = true
  newAlbumName.value = ''
}

function cancelCreate() { creating.value = false }

async function confirmCreate() {
  if (!isMe.value || !newAlbumName.value.trim()) return
  await albumsStore.create(newAlbumName.value)
  creating.value = false
}

// Load user profile and albums
async function loadProfile(userId) {
  loading.value = true

  if (!auth.me) await auth.fetchMe()

  if (!userId || Number(userId) === auth.me.id) {
    user.value = auth.me
  } else {
    const { data } = await api.get(`/users/${userId}`)
    user.value = data
  }

  // Fetch albums (own or all)
  await albumsStore.fetchAll()

  loading.value = false
}

// Watch route change
watch(
  () => route.params.id,
  (newId) => loadProfile(newId),
  { immediate: true }
)

const following = ref(false)

async function checkFollow() {
  if (isMe.value) return
  try {
    const { data } = await api.get(`/users/${user.value.id}/is-following`)
    following.value = data.following
  } catch (err) {
    console.error(err)
  }
}

async function toggleFollow(user) {
  try {
    if (user.is_following) {
      await api.delete(`/users/${user.id}/unfollow`);
      user.is_following = false;
    } else {
      await api.post(`/users/${user.id}/follow`);
      user.is_following = true;
    }
  } catch (err) {
    console.error('Failed to follow/unfollow:', err);
  }
}

// After loading profile
watch(() => user.value, checkFollow, { immediate: true })
</script>

<style scoped>
.profile { max-width: 1200px; margin: 24px auto; padding: 12px; color: #fff; }
.profile-header { margin-bottom: 24px; }
.form-group label { display: block; margin-bottom: 4px; }
.form-group input { width: 100%; padding: 8px; border-radius: 4px; }
.btn.primary { background: #42b983; color: #fff; border: none; padding: 8px 16px; cursor: pointer; }
.albums-list { display: flex; flex-direction: column; gap: 1rem; }
.create-card { width: 100%; }
.profile-album-card { width: 100%; }
.visibility-filter { margin: 16px 0; }
select { padding: 4px 8px; border-radius: 4px; }
</style>
