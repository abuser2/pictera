<template>
  <div class="stack">
    <div class="row space">
      <h2>Albums</h2>
      <button class="btn" @click="showCreate = true">New Album</button>
    </div>

    <div v-if="loading">Loading...</div>
    <div v-else class="grid">
      <AlbumCard
        v-for="a in albums"
        :key="a.id"
        :album="a"
        @rename="renameAlbum"
        @open="openAlbum(a.id)"
        @delete="deleteAlbum(a.id)"
        @setCover="promptCover"
      />
    </div>

    <AddAlbumModal
      v-if="showCreate"
      @close="showCreate = false"
      @created="onCreated"
    />
    
    <input type="file" ref="coverInput" @change="uploadCover" style="display: none" accept="image/*">
  </div>
</template>




<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../utils/api'
import AlbumCard from '../components/AlbumCard.vue'
import AddAlbumModal from '../components/AddAlbumModal.vue'

const router = useRouter()
const albums = ref([])
const loading = ref(true)
const showCreate = ref(false)
const coverInput = ref(null)
const albumForCover = ref(null)

async function load(){
  loading.value = true
  const { data } = await api.get('/albums')
  albums.value = data?.data || data
  loading.value = false
}
async function renameAlbum({ id, title }){
  await api.patch(`/albums/${id}`, { name: title })
  await load()
}
async function deleteAlbum(id){
  await api.delete(`/albums/${id}`)
  await load()
}
function openAlbum(id){ router.push({ name: 'album', params: { id }}) }
async function onCreated(){ showCreate.value = false; await load() }

function promptCover(album){
  albumForCover.value = album
  coverInput.value.click()
}

async function uploadCover(event){
  const file = event.target.files[0]
  if (!file) return
  const data = new FormData()
  data.append('cover_file', file)

  try {
    console.log(`Uploading cover for album ${albumForCover.value.id}...`)
    const { data: updatedAlbum } = await api.post(`/albums/${albumForCover.value.id}/cover`, data)
    console.log('Upload successful:', updatedAlbum)
    
    const idx = albums.value.findIndex(a => a.id === updatedAlbum.id)
    if (idx !== -1) albums.value[idx] = updatedAlbum
  } catch (error) {
    console.error('Error uploading cover photo:', error)
    alert('An error occurred while uploading the cover. Please check the developer console for more details.')
  } finally {
    albumForCover.value = null
    if (event.target) {
      event.target.value = ''
    }
  }
}


onMounted(load)
</script>