<template>
  <div class="stack">
    <div class="row space">
      <h2>{{ album?.name || 'Album' }}</h2>
      <div class="row gap">
        <input type="range" v-model="photoWidth" min="120" max="600" title="Zoom" />
        
        <button class="btn ghost" @click="showShareModal = true">Share</button>
        <button class="btn ghost" @click="toggleSelectionMode" :class="{ active: isSelecting }">
          {{ isSelecting ? 'Cancel' : 'Select' }}
        </button>
        <button v-if="isSelecting && selectedIds.size > 0" class="btn" @click="createAlbumFromSelection">Create Album</button>

        <template v-if="isEditing">
          <input v-model="editTitle" class="input short" placeholder="Rename album"/>
          <button class="btn" @click="rename" :disabled="!editTitle">Save</button>
        </template>
        <button class="btn ghost" @click="isEditing = !isEditing">{{ isEditing ? 'Done' : 'Edit' }}</button>
      </div>
    </div>

    <UploadDropzone @selected="uploadPhoto" />
    <div class="grid photos" :style="{ columnWidth: photoWidth + 'px' }">
      <div 
        v-for="(p, index) in photos" 
        :key="p.id" 
        class="photo"
        :class="{ 'dragging': draggedPhotoId === p.id, 'selected': selectedIds.has(p.id) }"
        :draggable="!isSelecting"
        @dragstart="onDragStart(index, $event)"
        @dragend="onDragEnd"
        @drop="onDrop(index)"
        @dragover.prevent
        @dragenter.prevent
        @click="onPhotoClick(p, index)"
      >
        <img :src="resolvePhotoUrl(p)" alt="photo" @load="setRatio" />
        <div v-if="isEditing" class="row space tiny">
          <span class="cut">{{ p.title || p.original_name }}</span>
          <button class="btn-remove" @click="removeFromAlbum(p.id)">Remove</button>
        </div>
      </div>
    </div>

    <div v-if="lightboxIndex !== -1" class="lightbox" @click.self="closeLightbox">
      <button class="lb-nav left" @click.stop="prevPhoto">❮</button>
      <img :src="resolvePhotoUrl(photos[lightboxIndex])" class="lb-image" />
      <button class="lb-nav right" @click.stop="nextPhoto">❯</button>
      <button class="lb-close" @click.stop="closeLightbox">✕</button>

      <div class="lb-thumbs" ref="thumbsContainer">
        <img 
          v-for="(p, idx) in photos" 
          :key="p.id" 
          :src="resolvePhotoUrl(p)" 
          class="lb-thumb" 
          :class="{ active: idx === lightboxIndex }"
          @click.stop="lightboxIndex = idx"
          :ref="el => thumbRefs[idx] = el"
        />
      </div>
    </div>

    <!-- Create Album Modal -->
    <div v-if="showCreateModal" class="backdrop" @click.self="showCreateModal = false">
      <transition name="fade" appear>
        <div class="modal">
          <h2 class="title">New Album from Selection</h2>
          <div class="form-stack">
            <div class="field">
              <label>Name</label>
              <input v-model="newAlbumName" placeholder="Album name" @keyup.enter="confirmCreateAlbum" />
            </div>
            <div class="row gap" style="margin-top: 10px;">
              <button class="btn" @click="confirmCreateAlbum" :disabled="!newAlbumName">Create</button>
              <button class="btn ghost" @click="showCreateModal = false">Cancel</button>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Share Modal -->
    <div v-if="showShareModal" class="backdrop" @click.self="showShareModal = false">
      <transition name="fade" appear>
        <div class="modal">
          <h2 class="title">Share Album</h2>
          <div class="form-stack">
            <div v-if="shareUrl" class="field">
              <label>Share Link</label>
              <input :value="shareUrl" readonly @click="$event.target.select()" />
            </div>
            <div class="row gap" style="margin-top: 10px; justify-content: flex-end;">
              <button class="btn" @click="generateShareLink">Generate Link</button>
              <button class="btn ghost" @click="showShareModal = false">Close</button>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch, nextTick, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../utils/api'
import UploadDropzone from '../components/UploadDropzone.vue'

const router = useRouter()
const route = useRoute()
const album = ref(null)
const photos = ref([])
const editTitle = ref('')
const isEditing = ref(false)
const photoWidth = ref(240)
const lightboxIndex = ref(-1)
const thumbsContainer = ref(null)
const thumbRefs = ref([])
const pendingUploads = ref(0)
const draggedIndex = ref(null)
const draggedPhotoId = ref(null)
const isSelecting = ref(false)
const selectedIds = reactive(new Set())
const showCreateModal = ref(false)
const showShareModal = ref(false)
const shareUrl = ref('')
const newAlbumName = ref('')

function resolvePhotoUrl(p){
  return p.url || (p.path ? `/storage/${p.path}` : '')
}

async function load(){
  // GET /api/albums/:id  :contentReference[oaicite:9]{index=9}
  const { data } = await api.get(`/albums/${route.params.id}`)
  album.value = data
  editTitle.value = data.name
  photos.value = data.photos || data?.data?.photos || []
  thumbRefs.value = []
}

async function rename(){
  await api.patch(`/albums/${route.params.id}`, { name: editTitle.value }) // :contentReference[oaicite:9]{index=9}
  await load()
}

async function uploadPhoto(file){
  pendingUploads.value++
  const nextOrder = photos.value.length + pendingUploads.value
  try {
    // 1) Upload photo: POST /api/photos
    const fd = new FormData()
    fd.append('photo', file)
    fd.append('title', file.name)
    fd.append('description', String(nextOrder))
    const { data: created } = await api.post('/photos', fd, { headers: { 'Content-Type': 'multipart/form-data' }})
    const photoId = created.id || created?.data?.id
    // 2) Add to album: POST /api/albums/:id/add-photo
    await api.post(`/albums/${route.params.id}/add-photo`, { photo_id: photoId })
    await load()
  } finally {
    pendingUploads.value--
  }
}

async function removeFromAlbum(photoId){
  // DELETE /api/albums/:id/remove-photo/:photoId  :contentReference[oaicite:12]{index=12}
  await api.delete(`/albums/${route.params.id}/remove-photo/${photoId}`)
  await load()
}

function setRatio(e) {
  const img = e.target
  const isPortrait = img.naturalHeight > img.naturalWidth
  // 3/4, 4/3
  img.style.aspectRatio = isPortrait ? '3/4' : '4/3'
}

function toggleSelectionMode() {
  isSelecting.value = !isSelecting.value
  selectedIds.clear()
}

function onPhotoClick(p, index) {
  if (isSelecting.value) {
    if (selectedIds.has(p.id)) selectedIds.delete(p.id)
    else selectedIds.add(p.id)
  } else {
    openLightbox(index)
  }
}

function openLightbox(index) {
  lightboxIndex.value = index
  document.body.style.overflow = 'hidden'
}

function closeLightbox() {
  lightboxIndex.value = -1
  document.body.style.overflow = ''
}

function nextPhoto() {
  if (lightboxIndex.value < photos.value.length - 1) {
    lightboxIndex.value++
  } else {
    lightboxIndex.value = 0
  }
}

function prevPhoto() {
  if (lightboxIndex.value > 0) {
    lightboxIndex.value--
  } else {
    lightboxIndex.value = photos.value.length - 1
  }
}

function onKeydown(e) {
  if (lightboxIndex.value === -1) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowRight') nextPhoto()
  if (e.key === 'ArrowLeft') prevPhoto()
}

function onDragStart(index, event) {
  draggedIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.dropEffect = 'move'
  // Скрываем оригинал с небольшой задержкой, чтобы браузер успел сделать "снимок" для перетаскивания
  setTimeout(() => {
    draggedPhotoId.value = photos.value[index].id
  }, 0)
}

async function onDrop(index) {
  const from = draggedIndex.value
  const to = index
  if (from === null || from === to) return

  const item = photos.value[from]
  photos.value.splice(from, 1)
  photos.value.splice(to, 0, item)
  //draggedIndex.value = null

  const ids = photos.value.map(p => p.id)
  await api.post(`/albums/${route.params.id}/reorder-photos`, { photo_ids: ids })
}

  function onDragEnd() {
  draggedIndex.value = null
  draggedPhotoId.value = null
}

function createAlbumFromSelection() {
  newAlbumName.value = ''
  showCreateModal.value = true
}

async function confirmCreateAlbum() {
  if (!newAlbumName.value) return
  try {
    const { data: newAlbum } = await api.post('/albums', { name: newAlbumName.value })
    const photoIds = Array.from(selectedIds)
    await api.post(`/albums/${newAlbum.id}/add-photos`, { photo_ids: photoIds })
    window.dispatchEvent(new Event('albums-updated'))
    showCreateModal.value = false
    router.push(`/albums/${newAlbum.id}`)
  } catch (e) {
    alert('Error creating album')
  }
}

async function generateShareLink() {
  try {
    const { data } = await api.post('/shares', {
      type: 'album',
      id: route.params.id
    })
    shareUrl.value = `${window.location.origin}/share/${data.token}`
  } catch (e) {
    alert('Error generating link')
  }
}

onMounted(() => {
  load()
  window.addEventListener('keydown', onKeydown)
})
onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
watch(() => route.params.id, load)

watch(lightboxIndex, (newVal) => {
  if (newVal !== -1) {
    nextTick(() => {
      const container = thumbsContainer.value
      const el = thumbRefs.value[newVal]
      if (container && el) {
        container.scrollTo({
          left: el.offsetLeft - container.clientWidth / 2 + el.offsetWidth / 2,
          behavior: 'smooth'
        })
      }
    })
  }
})

watch(showShareModal, (val) => {
  if (val) shareUrl.value = ''
})
</script>

<style scoped>
.stack { display: flex; flex-direction: column; gap: 24px; padding: 24px; max-width: 100%; margin: 0; box-sizing: border-box; }
.row { display: flex; align-items: center; }
.space { justify-content: space-between; }
.gap { gap: 12px; }

h2 { font-size: 1.8rem; color: #fff; margin: 0; }

/* Inputs & Buttons */
.input {
  padding: 8px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.9rem;
}
.btn {
  background: #d0813b;
  color: #fff;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn:hover { background: #b46b2f; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn.ghost {
  background: transparent;
  color: #374151;
  border: 1px solid #d1d5db;
}
.btn.ghost:hover { background: #f3f4f6; }
.btn.ghost.active { background: #d0813b; color: #fff; border-color: #d0813b; }

/* Grid */
.grid.photos {
  column-gap: 3px;
  display: block;
}

/* Photo Card */
.photo {
  background: transparent;
  border-radius: 0;
  overflow: hidden;
  box-shadow: none;
  transition: none;
  border: none;
  display: inline-block;
  width: 100%;
  break-inside: avoid;
  margin: 0 0 3px 0;
  vertical-align: top;
  cursor: grab;
}
.photo.selected {
  outline: 4px solid #d0813b;
  outline-offset: -4px;
}
.photo.dragging {
  opacity: 0;
}
.photo:active {
  cursor: grabbing;
}
.photo:hover {
  transform: none;
  box-shadow: none;
}

.photo img {
  width: 100%;
  height: auto;
  aspect-ratio: 4/3; /* По умолчанию горизонтальная */
  object-fit: cover;
  display: block;
  transition: aspect-ratio 0.3s ease;
  cursor: pointer;
}

.photo .row.tiny {
  padding: 12px;
  background: #fff;
  border-top: 1px solid #f3f4f6;
}

.cut {
  font-size: 0.9rem;
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 140px;
}

.btn-remove {
  background: transparent;
  color: #ef4444;
  border: 1px solid #fee2e2;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-remove:hover {
  background: #fef2f2;
  border-color: #fecaca;
}

/* Lightbox */
.lightbox {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.95);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}
.lb-image {
  max-width: 90vw;
  max-height: 75vh;
  object-fit: contain;
  box-shadow: 0 0 20px rgba(0,0,0,0.5);
}
.lb-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: #fff;
  font-size: 3rem;
  cursor: pointer;
  padding: 20px;
  opacity: 0.7;
  transition: opacity 0.2s;
}
.lb-nav:hover { opacity: 1; color: #d0813b; }
.lb-nav.left { left: 10px; }
.lb-nav.right { right: 10px; }
.lb-close {
  position: absolute;
  top: 20px;
  right: 20px;
  background: transparent;
  border: none;
  color: #fff;
  font-size: 2rem;
  cursor: pointer;
  opacity: 0.7;
}
.lb-close:hover { opacity: 1; }

.lb-thumbs {
  margin-top: 15px;
  height: 100px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 10px;
  overflow-x: auto;
  padding: 10px;
  background: rgba(0,0,0,0.5);
  box-sizing: border-box;
  position: relative;
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
}
.lb-thumbs::-webkit-scrollbar {
  display: none; /* Chrome/Safari/Opera */
}
.lb-thumb {
  height: 100%;
  width: auto;
  object-fit: cover;
  cursor: pointer;
  opacity: 0.4;
  border-radius: 4px;
  transition: all 0.2s;
  flex-shrink: 0;
}
.lb-thumb:hover { opacity: 0.8; }
.lb-thumb.active { opacity: 1; border: 2px solid #d0813b; transform: scale(1.05); }

/* Modal Styles */
.backdrop { position:fixed; inset:0; display:grid; place-items:center; background:#0008; backdrop-filter:blur(4px); z-index:50; }
.modal { background:#1c1c1e; border:1px solid #2c2c2e; border-radius:16px; padding:20px; width:380px; }
.title { margin:0 0 8px; text-align:center; color:#f0f0f0; }
.form-stack { display:flex; flex-direction:column; gap:12px; }
.field { display:flex; flex-direction:column; gap:6px; }
.field label { font-size:.9em; color:#aaa; }
.field input { background:#2a2a2d; border:1px solid #3a3a3d; border-radius:8px; padding:8px; color:#fff; }
.fade-enter-active,.fade-leave-active{ transition:opacity .2s ease; }
.fade-enter-from,.fade-leave-to{ opacity:0; }
</style>
