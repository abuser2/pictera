<template>
  <div class="backdrop" @click.self="close">
    <transition name="fade">
      <div class="modal" v-if="visible">
        <h2 class="title">New Album</h2>

        <form @submit.prevent="submit" class="stack">
          <div class="field">
            <label>Name</label>
            <input v-model.trim="title" placeholder="Prikl. 'Fotoset prirody'" required />
          </div>

          <div class="field">
            <label>Cover Photo</label>
            <input type="file" @change="onFileChange" accept="image/*" />
          </div>

          <p v-if="error" class="error">{{ error }}</p>

          <div class="row gap">
            <button class="btn" :disabled="loading || !title">
              {{ loading ? 'Creating...' : 'Create' }}
            </button>
            <button class="btn ghost" type="button" @click="close">Cancel</button>
          </div>
        </form>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../utils/api'

const emit = defineEmits(['close','created'])

const visible = ref(false)
const title = ref('')
const loading = ref(false)
const error = ref('')
const coverFile = ref(null)

function onFileChange(e) {
  coverFile.value = e.target.files[0] || null
}

function close() {
  visible.value = false
  setTimeout(() => emit('close'), 200)
}

async function submit() {
  if (!title.value) return
  error.value = ''
  loading.value = true
  try {
    // POST /api/albums { title, description? }
    const fd = new FormData()
    fd.append('name', title.value)
    if (coverFile.value) {
      fd.append('cover_file', coverFile.value)
    }
    await api.post('/albums', fd)
    emit('created')   // update album list in parent
    close()
  } catch (e) {
    error.value = 'Není možné vytvořit album'
  } finally {
    loading.value = false
  }
}

onMounted(() => { visible.value = true })
</script>

<style scoped>
.backdrop { position:fixed; inset:0; display:grid; place-items:center; background:#0008; backdrop-filter:blur(4px); z-index:50; }
.modal { background:#1c1c1e; border:1px solid #2c2c2e; border-radius:16px; padding:20px; width:380px; }
.title { margin:0 0 8px; text-align:center; color:#f0f0f0; }
.stack { display:flex; flex-direction:column; gap:12px; }
.field { display:flex; flex-direction:column; gap:6px; }
.field label { font-size:.9em; color:#aaa; }
.field input, .field textarea { background:#2a2a2d; border:1px solid #3a3a3d; border-radius:8px; padding:8px; color:#fff; }
.error { color:#ff7b7b; font-size:.9em; }
.row { display:flex; align-items:center; }
.gap { gap:8px; }
.btn { background:#d0813b; color:#111; border:0; border-radius:10px; padding:8px 12px; cursor:pointer; }
.btn.ghost { background:transparent; color:#ddd; border:1px solid #3a3a3a; }
.fade-enter-active,.fade-leave-active{ transition:opacity .2s ease; }
.fade-enter-from,.fade-leave-to{ opacity:0; }
</style>
