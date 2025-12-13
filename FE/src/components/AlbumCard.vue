<template>
  <div class="card" @click="$emit('open')">
    <!-- Используем вычисляемое свойство для отображения нужного изображения -->
    <div v-if="displayImage" class="album-cover">
      <img :src="displayImage" :alt="album.name" />
    </div>
    <!-- Или показываем заглушки, если изображений нет -->
    <div v-else class="thumbs">
      <div v-for="i in 3" :key="i" class="ph"/>
    </div>
    <div class="album-card">
      <div @click="$emit('open')" class="album-info">
        <h4 class="album-title">{{ album.title }}</h4>
      </div>

      <!-- Only show for the album owner -->
      <div v-if="album.user_id === auth.me?.id" class="album-actions">
        <select class="select" v-model="album.visibility" @change="$emit('changeVisibility', album)">
          <option value="public">Public</option>
          <option value="private">Private</option>
        </select>
        <button class="btn small" @click.stop="$emit('setCover', album)">Set Cover</button>
        <button class="btn small" @click.stop="$emit('rename', album)">Rename</button>
        <button class="btn small danger" @click.stop="$emit('delete', album.id)">Delete</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '../stores/auth'
import { ref, watch, computed } from 'vue' // 1. Импортировали computed

const props = defineProps({
  album: {
    type: Object,
    required: true
  }
})

// 2. Добавили вычисляемое свойство с логикой
const displayImage = computed(() => {
  // Сначала ищем назначенную обложку
  if (props.album?.cover_photo?.url) {
    return props.album.cover_photo.url;
  }
  // Если ее нет, берем первую фотографию из альбома
  if (props.album?.photos?.length > 0 && props.album.photos[0]?.url) {
    return props.album.photos[0].url;
  }
  // Если изображений нет вообще, возвращаем null
  return null;
});

const emit = defineEmits(['rename','open','delete', 'setCover', 'changeVisibility'])
const editing = ref(false)
const title = ref(props.album.title)
const auth = useAuth()
watch(() => props.album.title, v => title.value = v)

function toggleEdit(){
  if (editing.value) save()
  else editing.value = true
}
function save(){
  editing.value = false
  if (name.value && name.value !== props.album.name) {
    emit('rename', { id: props.album.id, name: name.value })
  }
}
</script>

<style>
.album-cover {
  width: 100%;
  height: 150px; /* или любая другая фиксированная высота */
  overflow: hidden;
  background: #eee;
}
.album-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover; /* Масштабирует изображение, чтобы оно заполнило контейнер */
}
.album-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

</style>