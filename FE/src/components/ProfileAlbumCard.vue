/**
 * ITU 2025
 *
 * @author Radim Dvořák
 * @file ProfileAlbumCard.vue
 * @brief View for Album in profile
 */

<template>
  <div class="profile-album-card">
    <div class="album-header">
      <!-- Album Info: Clickable to go to album page -->
      <div class="album-info" @click="openAlbum">
        <img v-if="album.cover_url" :src="album.cover_url" alt="cover" />
        <div class="album-name">{{ album.name }}</div>
      </div>

      <!-- Expand / collapse button -->
      <button class="btn small expand-btn" @click.stop="toggleExpanded">
        <span v-if="expanded">▲</span>
        <span v-else>▼</span>
      </button>
    </div>

    <!-- Expanded photos -->
    <div v-if="expanded && album.photos?.length" class="album-photos">
      <img v-for="photo in album.photos" :key="photo.id" :src="photo.url" alt="photo" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProfileAlbumsStore } from '../stores/profileAlbums'

const props = defineProps({
  album: { type: Object, required: true }
})

const router = useRouter()
const albumsStore = useProfileAlbumsStore()
const expanded = ref(false)

function openAlbum() {
  router.push({ name: 'album', params: { id: props.album.id } })
}

async function toggleExpanded() {
  expanded.value = !expanded.value
  if (expanded.value && (!props.album.photos || !props.album.photos.length)) {
    await albumsStore.fetchPhotos(props.album.id)
  }
}
</script>

<style scoped>
.profile-album-card {
  background: #1b1b1b;
  border-radius: 8px;
  padding: 0.5rem;
  cursor: default;
  color: #fff;
}
.album-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.album-info {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  cursor: pointer; /* make it clickable */
  flex: 1; /* fill available space */
}
.album-name { font-weight: 600; }
.album-info img {
  width: 100%;
  aspect-ratio: 1/1;
  object-fit: cover;
  border-radius: 6px;
}
.expand-btn {
  background: transparent;
  border: none;
  color: #ccc;
  font-size: 1rem;
  cursor: pointer;
}
.album-photos {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 0.4rem;
  margin-top: 0.5rem;
}
.album-photos img {
  width: 100%;
  aspect-ratio: 1/1;
  object-fit: cover;
  border-radius: 6px;
  transition: transform 0.2s;
}
.album-photos img:hover {
  transform: scale(1.05);
}
</style>