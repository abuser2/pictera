/**
 * ITU 2025
 *
 * @author Radim Dvořák
 * @file ProfileAlbumCard.vue
 * @brief View for Album in profile
 */

<template>
  <div class="profile">
    <h2>Profile</h2>

    <div v-if="!auth.me">You are not logged in.</div>

    <div v-else>
      <!-- Profile Header -->
      <div class="profile-header">
        <div class="avatar-section">
          <img
            class="avatar"
            :src="user.avatar_url || defaultAvatar"
            alt="Avatar"
            @click="isEditing && selectAvatar()"
            :class="{ editable: isEditing }"
          />
        </div>

        <div class="info-section" v-if="!isEditing">
          <p><strong>Name:</strong> {{ user.name || '—' }}</p>
          <p><strong>Email:</strong> {{ user.email || '—' }}</p>
          <p><strong>Price:</strong> {{ user.price || '-'}} €</p>

          <p><strong>Tag:</strong> {{ user.tag || '-'}} </p>

          <div class="actions">
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
          <div class="form-group">
            <label>Price:</label>
            <input v-model="form.price" type="number" optional />
          </div>
          <div class="form-group">
            <label>Tag:</label>
            <select v-model="form.tag" optional>
              <option disabled value="">Select tags</option>
              <option value="portrait">Portrait</option>
              <option value="landscape">Landscape</option>
              <option value="street">Street</option>
              <option value="travel">Travel</option>
              <option value="wildlife">Wildlife</option>
            </select>
          </div>

          <div class="actions">
            <button type="submit" class="btn primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : 'Save' }}
            </button>
            <button type="button" class="btn" @click="cancelEdit" :disabled="isSaving">Cancel</button>
          </div>
        </form>

        <input type="file" ref="avatarInput" @change="uploadAvatar" style="display:none" accept="image/*">
      </div>

      <button class="btn" @click="toggleFollow(user)" v-if="!isMe">
        {{ user.is_following ? 'Unfollow' : 'Follow' }}
      </button>

      <!-- Albums -->
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
        <div v-else-if="filteredAlbums.length === 0" class="no-albums">No albums available.</div>

        <div class="albums-list">
          <!-- Create new album -->
          <div v-if="creating && isMe" class="album-card create-card" style="display:flex;align-items:center;gap:12px;padding:12px;background:#1b1b1b;border-radius:8px;">
            <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;background:#2a2a2a;border-radius:6px;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#42b983" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14"/>
              </svg>
            </div>

            <div style="flex:1;display:flex;flex-direction:column;gap:8px;">
              <input
                v-model.trim="newAlbumName"
                id="new-album"
                aria-label="Album name"
                placeholder="Album name"
                @keyup.enter="confirmCreate"
                @keyup.esc="cancelCreate"
                maxlength="100"
                autofocus
                style="width:100%;padding:10px;border-radius:6px;border:1px solid #333;background:#111;color:#fff;"
              />
              <div style="display:flex;gap:8px;">
                <button class="btn primary small" @click="confirmCreate" :disabled="!newAlbumName.trim()">Create</button>
                <button class="btn ghost small" @click="cancelCreate">Cancel</button>
              </div>
            </div>
          </div>

          <button v-if="!creating && isMe" class="btn" @click="startCreate">New Album</button>

          <!-- Album cards -->
          <ProfileAlbumCard
            v-for="album in filteredAlbums"
            :key="album.id"
            :album="album"
            :isMe="isMe"
          />
        </div>
      </div>

      <!-- Following users -->
      <div class="following-list">
        <h3>Following</h3>
        <div v-if="followingUsers.length" class="users-grid">
          <div
            v-for="fUser in followingUsers"
            :key="fUser.id"
            class="card user-card"
            @click="goToUser(fUser.id)"
          >
            <img class="avatar-small" :src="fUser.avatar_url || defaultAvatar" alt="Avatar" />
            <strong>{{ fUser.name || 'Unknown' }}</strong>
            <div v-if="fUser.email"><small>{{ fUser.email }}</small></div>
          </div>
        </div>
        <div v-else>
          <p>You're not following anyone yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'
import { useProfileAlbumsStore } from '../stores/profileAlbums'
import ProfileAlbumCard from '../components/ProfileAlbumCard.vue'
import api from '../utils/api'

const auth = useAuth()
const albumsStore = useProfileAlbumsStore()
const route = useRoute()
const router = useRouter()

const defaultAvatar = '/images/default-avatar.png'

const user = ref(null)
const userId = ref(null)
const isEditing = ref(false)
const isSaving = ref(false)

const isMe = computed(() => user.value?.id === auth.me?.id)

const form = reactive({
  name: '',
  email: ''
})

// =====================
// Albums state
// =====================
const creating = ref(false)
const newAlbumName = ref('')
const visibilityFilter = ref('')
const loading = ref(true)

const followingUsers = ref([])

const filteredAlbums = computed(() => {
  if (!user.value) return []

  let userAlbums = albumsStore.albums.filter(
    a => a.user_id === user.value.id
  )

  if (isMe.value) {
    // Own profile → allow filtering
    if (visibilityFilter.value) {
      userAlbums = userAlbums.filter(
        a => a.visibility === visibilityFilter.value
      )
    }
  } else {
    // Other user's profile → ONLY public albums
    userAlbums = userAlbums.filter(a => a.visibility === 'public')
  }

  return userAlbums
})

function startEdit() {
  if (!isMe.value) return
  form.name = user.value.name || ''
  form.email = user.value.email || ''
  form.price = user.value.price || ''
  form.tag = user.value.tag || ''
  isEditing.value = true
}

function cancelEdit() {
  isEditing.value = false
}

async function saveProfile() {
  if (!isMe.value || isSaving.value) return
  isSaving.value = true
  try {
    await auth.updateProfile(form)
    Object.assign(user.value, form)
    isEditing.value = false
  } finally {
    isSaving.value = false
  }
}

const avatarInput = ref(null)

function selectAvatar() {
  avatarInput.value.click()
}

async function uploadAvatar(e) {
  const file = e.target.files[0]
  if (!file) return

  const fd = new FormData()
  fd.append('avatar', file)

  const { data } = await api.post('/me/avatar', fd)
  user.value.avatar_url = data.avatar_url
}

async function toggleFollow(targetUser) {
  if (!targetUser?.id) return

  const previous = targetUser.is_following

  try {
    if (targetUser.is_following) {
      await api.delete(`/users/${targetUser.id}/unfollow`)
      targetUser.is_following = false
    } else {
      await api.post(`/users/${targetUser.id}/follow`)
      targetUser.is_following = true
    }

    // Reload following list if this is your profile
    if (isMe.value) {
      await loadFollowingUsers()
    }
  } catch (err) {
    console.error(err)
    // rollback on error
    targetUser.is_following = previous
  }
}

async function loadFollowingUsers() {
  if (!userId.value) return
  try {
    const { data } = await api.get(`/users/${userId.value}/following`)
    followingUsers.value = data?.data ?? []
  } catch {
    followingUsers.value = []
  }
}

function startCreate() {
  if (!isMe.value) return
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

async function loadProfile(id) {
  loading.value = true
  try {
    if (!auth.me) await auth.fetchMe()

    if (!id || Number(id) === auth.me.id) {
      user.value = auth.me
      user.value.is_following = false
    } else {
      const { data } = await api.get(`/users/${id}`)
      user.value = data

      // 👇 determine follow state
      const { data: following } = await api.get(
        `/users/${auth.me.id}/following`
      )

      user.value.is_following = following.data.some(
        u => u.id === user.value.id
      )
    }

    userId.value = user.value.id

    await Promise.all([
      loadFollowingUsers(),
      albumsStore.fetchAll(userId.value)
    ])
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.id,
  id => loadProfile(id),
  { immediate: true }
)

// =====================
// Navigation
// =====================
function goToUser(id) {
  router.push(`/users/${id}`)
}
</script>

<style scoped>
.profile { max-width: 1200px; margin: 24px auto; padding: 12px; color: #fff; }
.profile-header { margin-bottom: 24px; display:flex; align-items:center; gap:16px; }
.avatar { width: 100px; height: 100px; border-radius:50%; object-fit:cover; cursor: pointer; }
.avatar.editable { border: 2px dashed #42b983; }
.avatar-small { width: 40px; height: 40px; border-radius:50%; object-fit:cover; }
.form-group label { display:block; margin-bottom:4px; }
.form-group input { width:100%; padding:8px; border-radius:4px; }
.btn.primary { background:#42b983; color:#fff; border:none; padding:8px 16px; cursor:pointer; }
.albums-list { display:flex; flex-direction:column; gap:1rem; margin-top:12px; }
.create-card { width:100%; }
.visibility-filter { margin:16px 0; }
select { padding:4px 8px; border-radius:4px; }
.users-grid { display:flex; flex-wrap:wrap; gap:12px; }
.user-card { display:flex; align-items:center; gap:8px; padding:8px; background:#222; border-radius:8px; cursor:pointer; }
</style>
