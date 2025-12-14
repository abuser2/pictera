<template>
  <div 
    class="dropzone" 
    :class="{ active: isDragging }"
    @click="$refs.input.click()"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="onDrop"
  >
    <div class="content">
      <span class="icon">☁️</span>
      <p>Click or drag photos here to upload</p>
    </div>
    <input 
      ref="input" 
      type="file" 
      multiple 
      accept="image/*" 
      @change="onChange" 
      style="display: none" 
    >
  </div>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['selected'])
const isDragging = ref(false)
const input = ref(null)

function onChange(e) {
  if (e.target.files.length) {
    emit('selected', e.target.files)
  }
  e.target.value = ''
}

function onDrop(e) {
  isDragging.value = false
  if (e.dataTransfer.files.length) {
    emit('selected', e.dataTransfer.files)
  }
}
</script>

<style scoped>
.dropzone { border: 2px dashed #3a3a3d; border-radius: 12px; padding: 32px; text-align: center; cursor: pointer; transition: all 0.2s; background: #1c1c1e; color: #888; }
.dropzone:hover, .dropzone.active { border-color: #d0813b; background: #2a2a2d; color: #f0f0f0; }
.content { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.icon { font-size: 24px; }
p { margin: 0; font-size: 0.9rem; }
</style>