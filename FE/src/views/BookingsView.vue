/**
 * ITU 2025
 *
 * @author Laura Fojtíková (xfojtil00)
 * @file BookingsView.vue
 * @brief View for bookings tab
 */

<template>
  <div class="booking-page">
    <h2>Bookings</h2>

    <!-- Month controls -->
    <div class="controls">
      <button @click="prevMonth">‹</button>
      <span>{{ monthLabel }}</span>
      <button @click="nextMonth">›</button>
    </div>

    <!-- Calendar -->
    <div class="calendar">
      <div class="weekday" v-for="w in weekdays" :key="w">
        {{ w }}
      </div>

      <div
        v-for="day in days"
        :key="day.key"
        class="day"
        :class="{ empty: day.empty }"
        @click="!day.empty && toggleDay(day.date)"
      >
        <span v-if="!day.empty" class="number">{{ day.day }}</span>

        <div v-if="day.bookings.length" class="dot"></div>

        <!-- Expanded day -->
        <div v-if="expandedDay === day.date" class="expanded">
          <h4>{{ day.date }}</h4>

          <div v-if="day.bookings.length">
            <div
              v-for="b in day.bookings"
              :key="b.id"
              class="booking"
            >
              {{ formatTime(b.start_at) }}–{{ formatTime(b.end_at) }}
              <br />
              <small>
                {{ b.client?.name }} → {{ b.photographer?.name }}
              </small>
            </div>
          </div>

          <!-- Open booking form -->
          <CreateBookingForm
            :date="day.date"
            @created="reload"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../utils/api";
import CreateBookingForm from "../components/CreateBookingForm.vue";

const bookings = ref([]);
const expandedDay = ref(null);
const current = ref(new Date());

const weekdays = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

const monthLabel = computed(() =>
  current.value.toLocaleString("en-US", {
    month: "long",
    year: "numeric",
  })
);

// load created bookings
async function loadBookings() {
  const { data } = await api.get("/bookings");
  bookings.value = data.data ?? data;
}

function reload() {
  loadBookings();
}

// expand chosen day
function toggleDay(date) {
  expandedDay.value = expandedDay.value === date ? null : date;
}

// choose between months
function prevMonth() {
  expandedDay.value = null;
  current.value = new Date(
    current.value.getFullYear(),
    current.value.getMonth() - 1,
    1
  );
}

function nextMonth() {
  expandedDay.value = null;
  current.value = new Date(
    current.value.getFullYear(),
    current.value.getMonth() + 1,
    1
  );
}

function formatTime(ts) {
  return new Date(ts).toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
  });
}

const days = computed(() => {
  const year = current.value.getFullYear();
  const month = current.value.getMonth();

  const first = new Date(year, month, 1);
  const offset = (first.getDay() + 6) % 7;
  const total = new Date(year, month + 1, 0).getDate();

  const arr = [];

  for (let i = 0; i < offset; i++) {
    arr.push({ empty: true, key: `e${i}`, bookings: [] });
  }

  for (let d = 1; d <= total; d++) {
    const date = new Date(year, month, d).toISOString().split("T")[0];

    arr.push({
      key: date,
      day: d,
      date,
      empty: false,
      bookings: bookings.value.filter(b =>
        b.start_at.startsWith(date)
      ),
    });
  }

  return arr;
});

onMounted(loadBookings);
</script>

<style scoped>
.booking-page {
  padding: 1.5rem;
  color: #fff;
}

.controls {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1rem;
}

.calendar {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.4rem;
}

.weekday {
  font-weight: bold;
  text-align: center;
}

.day {
  min-height: 90px;
  border: 1px solid #333;
  padding: 4px;
  cursor: pointer;
  position: relative;
}

.day.empty {
  background: transparent;
  border: none;
  cursor: default;
}

.number {
  font-size: 0.9rem;
}

.dot {
  width: 6px;
  height: 6px;
  background: #42b883;
  border-radius: 50%;
  margin-top: 4px;
}

.expanded {
  margin-top: 6px;
  background: #1e1e1e;
  padding: 6px;
}

.booking {
  font-size: 0.85rem;
  margin-bottom: 4px;
}
</style>
