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
        @request-rename="openRenameModal"
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
        <!-- Rename Modal -->
    <div v-if="showRename" class="backdrop" @click.self="showRename = false">
      <transition name="fade" appear>
        <div class="modal">
          <h2 class="title">Rename Album</h2>
          <div class="stack">
            <div class="field">
              <label>Name</label>
              <input v-model="renameName" placeholder="Album name" @keyup.enter="confirmRename" />
            </div>
            <div class="row gap" style="margin-top: 10px;">
              <button class="btn" @click="confirmRename">Save</button>
              <button class="btn ghost" @click="showRename = false">Cancel</button>
            </div>
          </div>
        </div>
      </transition>
    </div>
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
const showRename = ref(false)
const renameName = ref('')
const albumToRename = ref(null)

async function load(){
  loading.value = true
  const { data } = await api.get('/albums')
  albums.value = data?.data || data
  loading.value = false
}

function openRenameModal(album) {
  albumToRename.value = album
  renameName.value = album.name
  showRename.value = true
}

async function confirmRename(){
  if (!albumToRename.value) return
  await api.patch(`/albums/${albumToRename.value.id}`, { name: renameName.value })
  showRename.value = false
  albumToRename.value = null
  window.dispatchEvent(new Event('albums-updated'))
  await load()
}
async function deleteAlbum(id){
  await api.delete(`/albums/${id}`)
  window.dispatchEvent(new Event('albums-updated'))
  await load()
}
function openAlbum(id){ router.push({ name: 'album', params: { id }}) }
async function onCreated(){ showCreate.value = false; window.dispatchEvent(new Event('albums-updated')); await load() }

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

<style scoped>
.backdrop { position:fixed; inset:0; display:grid; place-items:center; background:#0008; backdrop-filter:blur(4px); z-index:50; }
.modal { background:#1c1c1e; border:1px solid #2c2c2e; border-radius:16px; padding:20px; width:380px; }
.title { margin:0 0 8px; text-align:center; color:#f0f0f0; }
.stack { display:flex; flex-direction:column; gap:12px; }
.field { display:flex; flex-direction:column; gap:6px; }
.field label { font-size:.9em; color:#aaa; }
.field input { background:#2a2a2d; border:1px solid #3a3a3d; border-radius:8px; padding:8px; color:#fff; }
.row { display:flex; align-items:center; }
.gap { gap:8px; }
.btn { background:#d0813b; color:#111; border:0; border-radius:10px; padding:8px 12px; cursor:pointer; font-weight: 600; }
.btn.ghost { background:transparent; color:#ddd; border:1px solid #3a3a3a; }
.fade-enter-active,.fade-leave-active{ transition:opacity .2s ease; }
.fade-enter-from,.fade-leave-to{ opacity:0; }
</style>