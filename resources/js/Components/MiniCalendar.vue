<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const now = ref(new Date())

const formatted = computed(() => {
  const d = now.value
  const month = d.toLocaleString(undefined, { month: 'short' })
  const day = d.getDate()
  const weekday = d.toLocaleString(undefined, { weekday: 'short' })
  const iso = d.toISOString().split('T')[0]
  return { month, day, weekday, iso }
})

function openBookings() {
  router.visit(`/bookings?date=${encodeURIComponent(formatted.value.iso)}`)
}
</script>

<template>
  <div class="mini-calendar rounded-md bg-white/2 p-2">
    <button @click="openBookings" class="w-full text-left flex items-center gap-3">
      <div class="flex items-center gap-3">
        <div class="flex flex-col items-center justify-center rounded-md bg-indigo-500/10 px-3 py-2">
          <div class="text-xs text-slate-400">{{ formatted.month }}</div>
          <div class="mt-1 text-lg font-semibold">{{ formatted.day }}</div>
        </div>
        <div>
          <div class="text-xs text-slate-400">{{ formatted.weekday }}</div>
          <div class="mt-1 text-sm font-semibold">Date</div>
        </div>
      </div>
    </button>
  </div>
</template>

<style scoped>
.mini-calendar { min-width: 120px; }
</style>