/**
 * ITU 2025
 *
 * @author Laura Fojtíková (xfojtil00)
 * @file CreateBookingForm.vue
 * @brief Component for creating a booking
 */

<template>
  <div class="form" @click.stop>

    <!-- Photographer drop-down -->
    <label>Photographer:</label>
    <select v-model="photographerId">
      <option disabled value="">Select photographer</option>
      <option v-for="p in photographers" :key="p.id" :value="p.id">
        {{ p.name }}
      </option>
    </select>

    <!-- Time -->
    <label>Start time:</label>
    <input type="time" v-model="start" />

    <label>End time:</label>
    <input type="time" v-model="end" />

    <!-- Album (optional) -->
    <label>Album:</label>
    <select v-model="albumId">
      <option value="">— none —</option>
      <option v-for="a in albums" :key="a.id" :value="a.id">
        {{ a.name }}
      </option>
    </select>

    <!-- Notes -->
    <label>Notes:</label>
    <textarea v-model="notes"></textarea>

    <!-- Create Booking button -->
    <button class="btn primary" @click="create" :disabled="loading">
      {{ loading ? "Saving…" : "Create Booking" }}
    </button>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import api from "../utils/api";

const props = defineProps({
  date: { type: String, required: true },
});
const emit = defineEmits(["created"]);

const start = ref("10:00");
const end = ref("11:00");
const notes = ref("");
const status = ref("pending");

const photographerId = ref("");
const clientId = ref("");
const albumId = ref("");

const users = ref([]);
const albums = ref([]);
const loading = ref(false);

const photographers = ref([]);

// load data to booking form
async function loadData() {
  try {
    const [usersRes, albumsRes, meRes] = await Promise.all([
      api.get("/albums"),
      api.get("/me"),
    ]);

    albums.value = albumsRes.data.data || albumsRes.data;
    clientId.value = meRes.data.id;

    // default client - logged-in user
    clientId.value = meRes.data.id;
  } catch (err) {
    console.error("Failed to load booking form data:", err);
  }
}

// create a booking
async function create() {
    if (!photographerId.value) {
        alert("Please select a photographer.");
        return;
    }

  loading.value = true;

  try {
    await api.post("/bookings", {
      photographer_id: photographerId.value,
      client_id: clientId.value,
      start_at: `${props.date} ${start.value}:00`,
      end_at: `${props.date} ${end.value}:00`,
      album_id: albumId.value || null,
      status: status.value,
      notes: notes.value,
    });

    emit("created");
  } catch (err) {   // if creation fails
    console.error("Booking creation failed:", err.response?.data || err);
    alert("Booking could not be created.");
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
    const res = await api.get("/photographers");    // get photographers
    photographers.value = res.data;
});

onMounted(loadData);
</script>

<style scoped>
.form {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

select,
input,
textarea {
  padding: 0.3rem;
  border-radius: 4px;
}
</style>