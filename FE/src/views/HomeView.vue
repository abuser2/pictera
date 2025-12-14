/**
 * ITU 2025
 *
 * @author Laura Fojtíková (xfojtil00)
 * @file HomeView.vue
 * @brief View for homepage
 */

<template>
  <div class="home-wrapper">

    <!-- Header -->
    <header class="header">
      <h2>Welcome to Pictera</h2>
      <button class="btn primary" @click="showCreate = true">+ New Post</button>
    </header>

    <!-- Posts feed -->
    <div class="posts-feed">
      <div
        v-for="post in posts"
        :key="post.id"
        class="post-card"
      >
        <!-- Post header -->
        <div class="post-top">
          <router-link
            class="user-link"
            :to="{ name: 'profile', params: { id: post.user?.id }}"
          >
            <strong>{{ post.user?.name || "Unknown User" }}</strong>
          </router-link>

          <span class="date-text">{{ formatDate(post.created_at) }}</span>
          <button class="btn small edit" @click="editingPost = post">✎</button>
        </div>

        <!-- Photos grid -->
        <div v-if="post.photos?.length" class="photo-grid">
          <img
            v-for="photo in post.photos"
            :key="photo.id"
            :src="photo.url"
            class="photo"
            alt="Post photo"
          />
        </div>

        <!-- Caption -->
        <p v-if="post.caption" class="caption">
          {{ post.caption }}
        </p>
      </div>
    </div>

    <!-- Create a post -->
    <CreatePostModal
      v-if="showCreate"
      @close="showCreate = false"
      @created="reloadPosts"
    />

    <!-- Edit a post -->
    <EditPostModal
    v-if="editingPost"
    :post="editingPost"
    @close="editingPost = null"
    @updated="reloadPosts"
    @deleted="reloadPosts"
  />
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "../utils/api";
import CreatePostModal from "../components/CreatePostModal.vue";

const posts = ref([]);
const showCreate = ref(false);
const editingPost = ref(null);

function formatDate(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return d.toLocaleDateString("en-GB", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

// reload posts feed
async function reloadPosts() {
  try {
    const { data } = await api.get("/posts");
    posts.value = data.data || data;
  } catch (err) {
    console.error("Failed to load posts:", err);
  }
}

onMounted(reloadPosts);
</script>

<style scoped>
.home-wrapper {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
  color: #fff;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h2 {
  margin: 0;
  font-size: 1.8rem;
}

.posts-feed {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Post Card */
.post-card {
  background: #1a1a1a;
  padding: 1.2rem;
  border-radius: 12px;
  border: 1px solid #333;
}

/* Top section: username + date */
.post-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.8rem;
}

.user-link {
  color: #42b883;
  text-decoration: none;
  font-weight: 600;
}

.user-link:hover {
  color: #68e0a0;
}

.date-text {
  color: #aaa;
  font-size: 0.85rem;
}

/* Photos grid */
.photo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.4rem;
  margin-bottom: 0.8rem;
}

.photo {
  width: 100%;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  border-radius: 8px;
}

/* Caption */
.caption {
  margin: 0.5rem 0 0 0;
  font-size: 1rem;
  color: #ddd;
}

/* Button */
.btn.primary {
  background: #e67e22;
  border: none;
  padding: 0.5rem 1rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  color: #fff;
}

.btn.primary:hover {
  background: #cf6f1d;
}
</style>