/**
 * ITU 2025
 *
 * @author Laura Fojtíková (xfojtil00)
 * @file CreatePostModal.vue
 * @brief Component for creating posts
 */

<template>
  <div class="modal">
    <div class="modal-content">

      <h2>Create Post</h2>

      <!-- Description -->
      <label>Caption:</label>
      <textarea v-model="caption" placeholder="Write a caption..." />

      <!-- Visibility -->
      <label>Visibility:</label>
      <select v-model="visibility">
        <option value="public">Public</option>
        <option value="private">Private</option>
      </select>

      <!-- Photos -->
      <label>Select photos:</label>
      <div class="photos-box">
        <p v-if="photos.length === 0" class="empty">
          You have no uploaded photos yet.
        </p>

        <div v-else class="photo-grid">
          <div
            v-for="p in photos"
            :key="p.id"
            class="photo-item"
            :class="{ selected: selectedPhotos.includes(p.id) }"
            @click="togglePhoto(p.id)"
          >
            <img :src="p.url" alt="" />
          </div>
        </div>
      </div>

      <div class="actions">
        <button class="btn cancel" @click="$emit('close')">Cancel</button>
        <button class="btn create" @click="createPost">Create Post</button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "../utils/api";

const emit = defineEmits(["close", "created"]);

const caption = ref("");
const visibility = ref("public");
const photos = ref([]);
const selectedPhotos = ref([]);
const loading = ref(false);

// load user's photos
async function loadPhotos() {
  try {
    const { data } = await api.get("/photos");
    photos.value = data.data || data;
  } catch (err) {
    console.error("Failed to load photos:", err);
  }
}

// choose a photo
function togglePhoto(id) {
  if (selectedPhotos.value.includes(id)) {
    selectedPhotos.value = selectedPhotos.value.filter(p => p !== id);
  } else {
    selectedPhotos.value.push(id);
  }
}

// create a post
async function createPost() {
  loading.value = true;
  try {
    await api.post("/posts", {
      caption: caption.value,
      visibility: visibility.value,
      photo_ids: selectedPhotos.value,
    });

    emit("created");
    close();
  } catch (err) {
    console.error("Post creation failed:", err.response?.data || err);
    alert("Failed to create post.");
  } finally {
    loading.value = false;
  }
}

function close() {
  emit("close");
}

onMounted(loadPhotos);
</script>

<style scoped>
.modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-content {
  background: white;
  width: 700px;
  padding: 2rem;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

label {
  font-weight: 600;
  margin-top: 0.5rem;
}

textarea,
select {
  width: 100%;
  padding: 0.7rem;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.photos-box {
  margin-top: 0.5rem;
}

.empty {
  color: #777;
  font-size: 0.9rem;
}

.photo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.photo-item {
  border: 2px solid transparent;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  transition: 0.15s;
}

.photo-item.selected {
  border-color: #ff8c42;
}

.photo-item img {
  width: 100%;
  aspect-ratio: 1/1;
  object-fit: cover;
}

.actions {
  display: flex;
  justify-content: space-between;
  margin-top: 1.5rem;
}

.btn {
  padding: 0.7rem 1.4rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 600;
}

.btn.cancel {
  background: #d77b42;
  color: white;
}

.btn.create {
  background: #ff8c42;
  color: white;
}
</style>