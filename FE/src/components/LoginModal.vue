<template>
  <div class="backdrop" @click.self="close">
    <transition name="fade">
      <div class="modal" v-if="visible">
        <h2 class="title">Sign in</h2>

        <form @submit.prevent="login" class="stack">
          <div class="field">
            <label>Email</label>
            <input v-model.trim="email" type="email" required placeholder="example@mail.com" />
          </div>

          <div class="field">
            <label>Password</label>
            <input v-model.trim="password" type="password" required placeholder="••••••••" />
          </div>

          <p v-if="error" class="error">{{ error }}</p>

          <div class="row gap">
            <button class="btn" :disabled="loading">
              {{ loading ? 'Loading...' : 'Login' }}
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
import { useAuth } from '../stores/auth'

const emit = defineEmits(['close'])
const auth = useAuth()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const visible = ref(false)

function close() {
  visible.value = false
  setTimeout(() => emit('close'), 250) // animace
}

async function login() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    close()
  } catch (err) {
    error.value = 'Wrong Email or Password'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  visible.value = true
})
</script>

<style scoped>
.backdrop {
  position: fixed;
  inset: 0;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.6);
  z-index: 50;
  backdrop-filter: blur(4px);
}

.modal {
  background: #1c1c1e;
  border: 1px solid #2c2c2e;
  border-radius: 16px;
  padding: 24px;
  width: 320px;
  animation: pop 0.25s ease-out;
}

@keyframes pop {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.title {
  margin-top: 0;
  font-size: 1.4em;
  text-align: center;
  color: #f0f0f0;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.field label {
  font-size: 0.85em;
  color: #aaa;
}
.field input {
  background: #2a2a2d;
  border: 1px solid #3a3a3d;
  border-radius: 8px;
  padding: 8px;
  color: #fff;
}

.error {
  color: #ff7b7b;
  font-size: 0.9em;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
