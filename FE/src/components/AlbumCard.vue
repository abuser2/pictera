<template>
  <div class="card" @click="$emit('open')">
    <div class="thumbs">
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
        <button class="btn small" @click.stop="$emit('rename', album)">Rename</button>
        <button class="btn small danger" @click.stop="$emit('delete', album.id)">Delete</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '../stores/auth'
import { ref, watch } from 'vue'
const props = defineProps({
  album: {
    type: Object,
    required: true
  }
})
const emit = defineEmits(['rename','open','delete'])
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
.album-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

</style>
