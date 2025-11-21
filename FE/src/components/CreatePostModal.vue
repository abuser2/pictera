<template>
  <div class="modal-backdrop" @click.self="close">
    <div class="modal">
      <h2>Create New Post</h2>

      <form @submit.prevent="submitPost" class="stack">

        <label>Description:</label>
        <textarea v-model="description" placeholder="Write something..." />

        <label>Select photos:</label>
        <div v-if="loadingAlbums">Loading albums...</div>

        <div v-for="album in albums" :key="album.id" class="album">
            <h4 @click="openAlbum(album.id)" class="album-title">
                {{ album.name }}
            </h4>

            <div v-if="openedAlbumId === album.id" class="photos">
                <div v-if="album.loadingPhotos">Loading photos...</div>

                <div v-else-if="album.photos?.length">
                <img
                    v-for="photo in album.photos"
                    :key="photo.id"
                    :src="photo.url"
                    :class="{ selected: selectedPhotoIds.includes(photo.id) }"
                    @click="togglePhoto(photo.id)"
                />
                </div>

                <p v-else class="no-photos">No photos in this album.</p>
            </div>
        </div>


        <div class="row space">
          <button type="button" class="btn cancel" @click="close">Cancel</button>
          <button type="submit" class="btn primary" :disabled="loading">
            {{ loading ? 'Posting...' : 'Create Post' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../utils/api'

const emit = defineEmits(['close', 'created'])

const description = ref('')
const albums = ref([])
const selectedPhotoIds = ref([])
const loading = ref(false)
const loadingAlbums = ref(true)
const openedAlbumId = ref(null)

function togglePhoto(id) {
  const i = selectedPhotoIds.value.indexOf(id)
  if (i >= 0) selectedPhotoIds.value.splice(i, 1)
  else if (selectedPhotoIds.value.length < 4) selectedPhotoIds.value.push(id)
}

async function openAlbum(id) {
  console.log('Album clicked:', id)

  if (openedAlbumId.value === id) {
    openedAlbumId.value = null
    return
  }

  openedAlbumId.value = id
  const album = albums.value.find(a => a.id === id)
  if (!album) return

  if (album.photos) return

  album.loadingPhotos = true
  console.log(`Fetching album ${id} photos...`)

  try {
    const { data } = await api.get(`/albums/${id}`)
    console.log('Fetched album data:', data)

    album.photos = data.photos || []
  } catch (err) {
    console.error(`Failed to load photos for album ${id}:`, err.response?.data || err.message)
    album.photos = []
  } finally {
    album.loadingPhotos = false
  }
}


async function loadAlbums() {
  loadingAlbums.value = true
  try {
    const { data } = await api.get('/albums?include=photos') 
    albums.value = data.data || data
  } catch (err) {
    console.error('Failed to load albums:', err)
  } finally {
    loadingAlbums.value = false
  }
}

async function submitPost() {
  if (!selectedPhotoIds.value.length) {
    alert('Please select at least one photo.')
    return
  }

  loading.value = true
  const payload = {
    description: description.value,
    date: new Date().toISOString().split('T')[0],
    photo_ids: selectedPhotoIds.value,
  }

  try {
    const res = await api.post('/posts', payload)
    console.log('Post created:', res.data)
    emit('created')
    close()
  } catch (err) {
    console.error('Error creating post:', err.response?.data || err.message)
  } finally {
    loading.value = false
  }
}

function close() {
  emit('close')
}

onMounted(loadAlbums)
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 50;
}
.modal {
  background: #fff;
  padding: 2rem;
  border-radius: 10px;
  width: 600px;
  max-width: 95%;
  max-height: 90vh;
  overflow-y: auto;
}
.albums {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.album h4 {
  margin-bottom: 0.3rem;
}
.photos {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  gap: 0.5rem;
}
.photos img {
  width: 100%;
  height: 80px;
  object-fit: cover;
  border-radius: 6px;
  cursor: pointer;
  transition: transform 0.2s, border 0.2s;
}
.photos img:hover {
  transform: scale(1.05);
}
.photos img.selected {
  outline: 3px solid #42b883;
  transform: scale(1.03);
}

.album-title {
  cursor: pointer;
  color: #42b883;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.album-title:hover {
  text-decoration: underline;
}
.photos {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.photos img {
  width: 100%;
  height: 80px;
  object-fit: cover;
  border-radius: 6px;
  cursor: pointer;
  transition: transform 0.2s, outline 0.2s;
}
.photos img:hover {
  transform: scale(1.05);
}
.photos img.selected {
  outline: 3px solid #42b883;
  transform: scale(1.03);
}
.no-photos {
  color: #999;
  font-size: 0.9rem;
}

</style>
