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
      />
    </div>

    <AddAlbumModal
      v-if="showCreate"
      @close="showCreate = false"
      @created="onCreated"
    />
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

onMounted(load)
</script>
