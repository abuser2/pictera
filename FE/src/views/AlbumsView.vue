<template>
  <div class="stack">
    <div class="row space">
      <h2>{{ isShares ? 'Shared Links' : 'Albums' }}</h2>
      <div class="row gap">
        <button class="btn ghost" :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'">Grid</button>
        <button class="btn ghost" :class="{ active: viewMode === 'table' }" @click="viewMode = 'table'">Table</button>
        <button v-if="!isShares" class="btn" @click="showCreate = true">New Album</button>
      </div>
    </div>

    <div v-if="loading">Loading...</div>
    <div v-else-if="viewMode === 'grid'" class="grid">
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
    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Description / Link</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in albums" :key="a.id" @click="openAlbum(a.id)" class="clickable-row">
            <td>{{ a.name }}</td>
            <td class="desc-cell">{{ a.description }}</td>
            <td>{{ new Date(a.created_at).toLocaleDateString() }}</td>
            <td @click.stop>
               <div class="row gap">
                 <button class="btn small" @click="openRenameModal(a)">Rename</button>
                 <button class="btn small danger" @click="deleteAlbum(a.id)">Delete</button>
               </div>
            </td>
          </tr>
        </tbody>
      </table>
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
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../utils/api'
import AlbumCard from '../components/AlbumCard.vue'
import AddAlbumModal from '../components/AddAlbumModal.vue'

const router = useRouter()
const route = useRoute()
const albums = ref([])
const loading = ref(true)
const showCreate = ref(false)
const coverInput = ref(null)
const albumForCover = ref(null)
const showRename = ref(false)
const renameName = ref('')
const albumToRename = ref(null)
const viewMode = ref('grid')

const isShares = computed(() => route.path.startsWith('/shares'))

async function load(){
  loading.value = true
  const type = isShares.value ? 'shared' : 'owned'
  const { data } = await api.get(`/albums?type=${type}`)
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
  await load()
}
async function deleteAlbum(id){
  await api.delete(`/albums/${id}`)
  window.dispatchEvent(new Event('albums-updated'))
  await load()
}
function openAlbum(id){ 
  if (isShares.value) {
    router.push({ name: 'share-detail', params: { id }}) 
  } else {
    router.push({ name: 'album', params: { id }}) 
  }
}
async function onCreated(){ 
  showCreate.value = false
  window.dispatchEvent(new Event('albums-updated'))
  await load() 
}

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
watch(() => route.path, load)
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
.btn.ghost.active { background: #d0813b; color: #fff; border-color: #d0813b; }
.btn.small { padding: 4px 8px; font-size: 0.8rem; }
.btn.danger { background: #ef4444; color: white; }
.fade-enter-active,.fade-leave-active{ transition:opacity .2s ease; }
.fade-enter-from,.fade-leave-to{ opacity:0; }

.table-container { overflow-x: auto; width: 100%; }
.data-table { width: 100%; border-collapse: collapse; color: #ddd; }
.data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
.data-table th { background: #222; color: #fff; }
.clickable-row { cursor: pointer; transition: background 0.2s; }
.clickable-row:hover { background: #2a2a2d; }
.desc-cell { white-space: pre-wrap; max-width: 300px; font-size: 0.85rem; color: #aaa; }
</style>