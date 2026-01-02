<template>
  <AppLayout title="Dashboard" @new-booking="goNewBooking">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
      <Card>
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs text-slate-400">Active Now</div>
            <div class="mt-2 text-sm font-semibold">{{ cards.activeNow?.title ?? '—' }}</div>
            <div class="mt-1 text-xs text-slate-500">Due: {{ cards.activeNow?.due ?? '—' }}</div>
          </div>
          <div class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-500/10 text-emerald-300">
            <span>📷</span>
          </div>
        </div>
        <div class="mt-4 inline-flex rounded-full bg-emerald-500/10 px-3 py-1 text-xs text-emerald-200">
          {{ cards.activeNow?.status ?? 'No active booking' }}
        </div>
      </Card>

      <Card>
        <div class="flex items-start justify-between">
          <div class="w-full">
            <div class="mt-2">
              <MiniCalendar />
            </div>
            <div class="mt-2 text-xs text-slate-500 flex items-center gap-3">
              <span v-if="cards.upNext?.status" class="text-slate-400">{{ cards.upNext.status }}</span>
              <a href="/bookings" class="text-indigo-300 text-xs underline">View calendar</a>
            </div>
          </div>
          <div class="ml-3 grid h-10 w-10 place-items-center rounded-xl bg-violet-500/10 text-violet-300">
            <span>🔬</span>
          </div>
        </div>
      </Card>

      <Card>
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs text-slate-400">Account Status</div>
            <div class="mt-2 text-sm font-semibold">Account Status</div>
            <div class="mt-1 text-xs text-slate-500">{{ cards.account?.status ?? '—' }}</div>
          </div>
          <div class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-500/10 text-emerald-300">
            <span>✅</span>
          </div>
        </div>
      </Card>
    </div>

    <div class="mt-6">
      <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
        <div class="text-sm font-semibold">Recent Activity</div>

        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-widest text-slate-400">
              <tr class="border-b border-white/10">
                <th class="py-3">Status</th>
                <th class="py-3">Item</th>
                <th class="py-3">Due</th>
                <th class="py-3">When</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in recentActivity" :key="idx" class="border-b border-white/5">
                <td class="py-3">
                  <span
                    class="inline-flex rounded-full px-3 py-1 text-xs"
                    :class="row.status === 'Returned' ? 'bg-emerald-500/10 text-emerald-200' : 'bg-indigo-500/10 text-indigo-200'"
                  >
                    {{ row.status }}
                  </span>
                </td>
                <td class="py-3 text-slate-200">{{ row.item }}</td>
                <td class="py-3 text-slate-400">{{ row.due }}</td>
                <td class="py-3 text-slate-400">{{ row.when }}</td>
              </tr>
              <tr v-if="recentActivity.length === 0">
                <td colspan="4" class="py-6 text-center text-slate-500">No activity yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../Layouts/AppLayout.vue'
import Card from '../Shared/Card.vue'
import { router } from '@inertiajs/vue3'
import MiniCalendar from '../Components/MiniCalendar.vue'

defineProps({
  cards: Object,
  recentActivity: Array,
  can: Object,
})

function goNewBooking() {
  router.visit('/bookings')
}
</script>
