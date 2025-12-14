<template>
  <div class="card" @click="$emit('open')">
    <div v-if="displayImage" class="album-cover">
      <img :src="displayImage" :alt="album.name" />
    </div>
    <div v-else class="thumbs">
      <div v-for="i in 3" :key="i" class="ph"/>
    </div>
    
    <div class="album-details">
      <div class="album-info">
        <h4 class="album-title" :title="album.name">{{ album.name }}</h4>
      </div>

      <div v-if="album.user_id === auth.me?.id" class="album-actions" @click.stop>
        <select class="styled-select" v-model="album.visibility" @change="$emit('changeVisibility', album)">
          <option value="public">Public</option>
          <option value="private">Private</option>
        </select>
        
        <div class="button-group">
          <button class="btn-action" @click="$emit('setCover', album)">Edit Cover</button>
          <button class="btn-action" @click="$emit('request-rename', album)">Rename</button>
          <button class="btn-action danger" @click="$emit('delete', album.id)">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '../stores/auth'
import { computed } from 'vue'

const props = defineProps({
  album: {
    type: Object,
    required: true
  }
})

const displayImage = computed(() => {
  if (props.album?.cover_photo?.url) {
    return props.album.cover_photo.url;
  }
  if (props.album?.photos?.length > 0 && props.album.photos[0]?.url) {
    return props.album.photos[0].url;
  }
  return null;
});

const emit = defineEmits(['request-rename','open','delete', 'setCover', 'changeVisibility'])
const auth = useAuth()
</script>

<style>
.card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
  border: 1px solid #f0f0f0;
}
.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.album-cover {
  width: 100%;
  height: 180px;
  background: #f3f4f6;
  position: relative;
}
.album-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.album-details {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.album-title {
  font-size: 1rem;
  font-weight: 700;
  color: #ff8800; /* Насыщенный желтый (amber-600) */
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.album-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 4px;
}

.styled-select {
  width: 100%;
  padding: 6px;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  background-color: #d0813b;
}

.button-group {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 6px;
}

.btn-action {
  padding: 6px 0;
  border: none;
  background-color: #d0813b; /* Светло-желтый фон */
  border-radius: 6px;
  font-size: 0.75rem;
  color: #000000; /* Темно-желтый текст */
  cursor: pointer;
  transition: background 0.1s;
}
.btn-action:hover {
  background-color: #d0813b; /* Желтый при наведении */
}
.btn-action.danger {
  color: #000000;
  border-color: #000000;
  background-color: #d21616;
}
.btn-action.danger:hover {
  background-color: #b05b5b;
}
</style>