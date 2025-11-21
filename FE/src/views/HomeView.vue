<template>
  <div class="stack home">
    <div class="row space">
      <h2>Welcome to Pictera</h2>
      <button class="btn" @click="showCreate = true">New Post</button>
    </div>

    <div class="grid home-actions">
      <div class="card action" @click="goTo('albums')">
        <h3>View Albums</h3>
      </div>

      <div class="card action" @click="goTo('profile')">
        <h3>My Profile</h3>
      </div>
    </div>

    <div v-if="posts.length" class="posts">
      <div v-for="post in posts" :key="post.id" class="post-card">
        <div class="post-header">
          <div class="poster-info">
            <router-link
              :to="{ name: 'profile', params: { id: post.user?.id } }"
              class="poster-link"
            >
              <strong>{{ post.user?.name || 'Unknown User' }}</strong>
            </router-link>
          </div>

          <div class="post-controls">
            <small class="post-date">{{ formatDate(post.created_at || post.date) }}</small>

            <div v-if="post.user?.id === currentUserId" class="actions">
              <button class="btn small edit" @click="editPost(post)">✎</button>
              <button class="btn small delete" @click="deletePost(post.id)">🗑</button>
            </div>
          </div>
        </div>

        <p v-if="post.description || post.caption || post.text" class="post-desc">
        {{ post.description || post.caption || post.text }}
        </p>

        <div v-if="post.photos?.length" class="post-photos">
          <img
            v-for="photo in post.photos"
            :key="photo.id"
            :src="photo.url"
            alt="Post photo"
          />
        </div>
      </div>
    </div>

    <div v-else>
    <p>No posts yet</p>
    </div>

    <CreatePostModal
      v-if="showCreate"
      @close="showCreate = false"
      @created="reloadPosts"
    />
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ref, onMounted } from 'vue'
import CreatePostModal from '../components/CreatePostModal.vue'
import api from '../utils/api'

const router = useRouter()
const showCreate = ref(false)
const posts = ref([])
const currentUserId = ref(null)

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  if (isNaN(d)) return '—';
  return d.toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function goTo(route) {
  router.push({ name: route })
}

async function reloadPosts() {
  try {
    const { data } = await api.get('/posts')
    posts.value = data.data || data
  } catch (err) {
    console.error('Failed to load posts:', err)
  }
}

onMounted(async () => {
  await fetchCurrentUser()
  await reloadPosts()
})

async function fetchCurrentUser() {
  try {
    const { data } = await api.get('/me')
    currentUserId.value = data.id
  } catch (err) {
    console.error('Failed to load current user:', err)
  }
}

function editPost(post) {
  console.log('Editing post:', post)
}

async function deletePost(id) {
  if (!confirm('Are you sure you want to delete this post?')) return
  try {
    await api.delete(`/posts/${id}`)
    await reloadPosts()
  } catch (err) {
    console.error('Failed to delete post:', err.response?.data || err)
  }
}

onMounted(reloadPosts)
</script>

<style scoped>
.home {
  padding: 2rem;
  color: #fff;
  background: #0f0f0f;
  min-height: 100vh;
}

.home-actions {
  justify-content: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: #fff;
  border-radius: 8px;
  padding: 1.5rem;
  width: 200px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  color: #000;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.posts {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
}

.post-card {
  background: #1b1b1b;
  border: 1px solid #333;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  color: #f0f0f0;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.post-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
}

.post-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.3rem;
}

.poster-info {
  font-weight: 600;
  color: #fff;
}

.post-date {
  color: #aaa;
  font-size: 0.85rem;
}

.post-desc {
  font-size: 1rem;
  margin: 0.8rem 0;
  color: #ddd;
  line-height: 1.4;
}

.post-photos {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.5rem;
}

.post-photos img {
  width: 100%;
  aspect-ratio: 1/1;
  object-fit: cover;
  border-radius: 8px;
  transition: transform 0.2s, opacity 0.2s;
}

.post-photos img:hover {
  transform: scale(1.03);
  opacity: 0.9;
}

.poster-link {
  text-decoration: none;
  color: #42b883;
  transition: color 0.2s;
}
.poster-link:hover {
  color: #68e0a0;
}

.post-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.actions {
  display: flex;
  gap: 0.3rem;
}

.btn.small {
  background: none;
  border: none;
  color: #ccc;
  font-size: 1rem;
  cursor: pointer;
  transition: color 0.2s;
  padding: 0.2rem;
}

.btn.small:hover {
  color: #fff;
}

.btn.small.delete:hover {
  color: #e86b6b;
}

.btn.small.edit:hover {
  color: #42b883;
}
</style>
