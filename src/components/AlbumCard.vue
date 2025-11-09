<template>
  <div class="card" @click="$emit('open')">
    <div class="thumbs">
      <div v-for="i in 3" :key="i" class="ph"/>
    </div>
    <div class="row space">
      <input v-if="editing" v-model="title" class="input short" @keyup.enter="save"/>
      <h3 v-else class="cut">{{ album.name }}</h3>
      <div class="row gap">
        <button class="btn ghost tiny" @click.stop="toggleEdit">{{ editing ? 'Save' : 'Rename' }}</button>
        <button class="btn ghost tiny" @click.stop="$emit('delete')">Delete</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({ album: Object })
const emit = defineEmits(['rename','open','delete'])
const editing = ref(false)
const title = ref(props.album.title)
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
