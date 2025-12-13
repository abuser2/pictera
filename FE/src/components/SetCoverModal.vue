<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <h3>Set Cover for "{{ album.name }}"</h3>
      <p>Select a new image to upload as the album cover.</p>

      <div class="file-input-wrapper">
        <input type="file" @change="handleFileChange" accept="image/*" ref="fileInput" />
        <div v-if="previewUrl" class="image-preview">
          <img :src="previewUrl" alt="Image preview" />
        </div>
      </div>

      <div class="modal-actions">
        <button class="btn" @click="uploadCover" :disabled="!file || isLoading">
          {{ isLoading ? 'Uploading...' : 'Upload and Set' }}
        </button>
        <button class="btn secondary" @click="$emit('close')">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from '../lib/api'; // Предполагаем, что у вас есть настроенный API-клиент

const props = defineProps({
  album: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close', 'cover-updated']);

const file = ref(null);
const previewUrl = ref(null);
const isLoading = ref(false);

function handleFileChange(event) {
  const selectedFile = event.target.files[0];
  if (!selectedFile) {
    file.value = null;
    previewUrl.value = null;
    return;
  }
  file.value = selectedFile;
  // Создаем URL для предпросмотра
  previewUrl.value = URL.createObjectURL(selectedFile);
}

async function uploadCover() {
  if (!file.value) return;

  isLoading.value = true;
  const formData = new FormData();
  formData.append('cover_file', file.value);

  try {
    const updatedAlbum = await api.post(`/api/albums/${props.album.id}/set-cover`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    emit('cover-updated', updatedAlbum); // Отправляем обновленный альбом родителю
    emit('close');
  } catch (error) {
    console.error('Failed to upload cover:', error);
    alert('Error uploading cover. Please try again.');
  } finally {
    isLoading.value = false;
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}
.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
}
.file-input-wrapper {
  margin: 1.5rem 0;
}
.image-preview {
  margin-top: 1rem;
  max-width: 100%;
}
.image-preview img {
  max-width: 100%;
  max-height: 200px;
  border-radius: 4px;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
}
</style>