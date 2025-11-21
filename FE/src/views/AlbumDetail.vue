<template>
  <div class="stack">
    <div class="row space">
      <h2>{{ album?.title || 'Album' }}</h2>
      <div class="row gap">
        <input v-model="editTitle" class="input short" placeholder="Rename album"/>
        <button class="btn ghost" @click="rename" :disabled="!editTitle">Rename</button>
      </div>
    </div>

    <UploadDropzone @selected="uploadPhoto" />
    <div class="grid photos">
      <div v-for="p in photos" :key="p.id" class="photo">
        <img :src="resolvePhotoUrl(p)" alt="photo"/>
        <div class="row space tiny">
          <span class="cut">{{ p.title || p.original_name }}</span>
          <button class="btn ghost tiny" @click="removeFromAlbum(p.id)">Remove</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../utils/api'
import UploadDropzone from '../components/UploadDropzone.vue'

const route = useRoute()
const id = Number(route.params.id)
const album = ref(null)
const photos = ref([])
const editTitle = ref('')

function resolvePhotoUrl(p){
  // return p.url || (p.path ? `/laravel/storage/${p.path}` : '')
  return p.url || (p.path ? `/~xkaval05/laravel/storage/${p.path}` : '')
}

async function load(){
  // GET /api/albums/:id  :contentReference[oaicite:9]{index=9}
  const { data } = await api.get(`/albums/${id}`)
  album.value = data
  editTitle.value = data.name
  photos.value = data.photos || data?.data?.photos || []
}

async function rename(){
  await api.patch(`/albums/${id}`, { name: editTitle.value }) // :contentReference[oaicite:9]{index=9}
  await load()
}

async function uploadPhoto(file){
  // 1) Upload photo: POST /api/photos  :contentReference[oaicite:10]{index=10}
  const fd = new FormData()
  fd.append('photo', file)
  fd.append('title', file.name)
  const { data: created } = await api.post('/photos', fd, { headers: { 'Content-Type': 'multipart/form-data' }})
  const photoId = created.id || created?.data?.id
  // 2) Add to album: POST /api/albums/:id/add-photo  :contentReference[oaicite:12]{index=12}
  await api.post(`/albums/${id}/add-photo`, { photo_id: photoId })
  await load()
}

async function removeFromAlbum(photoId){
  // DELETE /api/albums/:id/remove-photo/:photoId  :contentReference[oaicite:12]{index=12}
  await api.delete(`/albums/${id}/remove-photo/${photoId}`)
  await load()
}

onMounted(load)
</script>
