/**
 * ITU 2025
 *
 * @author Laura Fojtíková (xfojtil00)
 * @file EditPostModal.vue
 * @brief Component for editing posts
 */

<template>
  <div class="modal-backdrop" @click.self="close">
    <div class="modal">
      <h2>Edit Post</h2>

      <!-- Description -->
      <label>Description</label>
      <textarea v-model="caption" />

      <!-- Visibility -->
      <label>Visibility</label>
      <select v-model="visibility">
        <option value="public">Public</option>
        <option value="private">Private</option>
        <option value="unlisted">Unlisted</option>
      </select>

      <!-- Photos -->
      <label>Select photos</label>

      <div v-if="photos.length" class="photos">
        <img
          v-for="p in photos"
          :key="p.id"
          :src="p.url"
          :class="{ selected: selectedPhotoIds.includes(p.id) }"
          @click="togglePhoto(p.id)"
        />
      </div>

      <div class="actions">

        <!-- Delete button-->
        <button class="btn danger" @click="deletePost">Delete</button>
        <div class="right">
          <button class="btn" @click="close">Cancel</button>
          <button class="btn primary" @click="save" :disabled="loading">
            {{ loading ? 'Saving…' : 'Save changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "../utils/api";

const props = defineProps({
  post: { type: Object, required: true }
});
const emit = defineEmits(["close", "updated", "deleted"]);

const caption = ref(props.post.caption || "");
const visibility = ref(props.post.visibility || "public");
const selectedPhotoIds = ref(props.post.photos.map(p => p.id));
const photos = ref([]);
const loading = ref(false);

// choose a photo
function togglePhoto(id) {
  const i = selectedPhotoIds.value.indexOf(id);
  if (i >= 0) selectedPhotoIds.value.splice(i, 1);
  else selectedPhotoIds.value.push(id);
}

// load user's photos
async function loadPhotos() {
  const { data } = await api.get("/photos");
  photos.value = data.data || data;
}

// save edited post
async function save() {
  loading.value = true;
  try {
    await api.patch(`/posts/${props.post.id}`, {
      caption: caption.value,
      visibility: visibility.value,
      photo_ids: selectedPhotoIds.value,
    });
    emit("updated");
    close();
  } finally {
    loading.value = false;
  }
}

// delete post
async function deletePost() {
  if (!confirm("Delete this post?")) return;
  await api.delete(`/posts/${props.post.id}`);
  emit("deleted");
  close();
}

function close() {
  emit("close");
}

onMounted(loadPhotos);
</script>

<style scoped>
.modal {
  background: #fff;
  padding: 2rem;
  border-radius: 12px;
  width: 600px;
}

.photos {
  display: grid;
  grid-template-columns: repeat(auto-fill, 90px);
  gap: 8px;
}

.photos img {
  width: 90px;
  height: 90px;
  object-fit: cover;
  cursor: pointer;
  border-radius: 6px;
  opacity: 0.6;
}

.photos img.selected {
  outline: 3px solid #42b883;
  opacity: 1;
}

.actions {
  display: flex;
  justify-content: space-between;
  margin-top: 1.5rem;
}

.btn.danger {
  background: #d9534f;
  color: #fff;
}
</style>
