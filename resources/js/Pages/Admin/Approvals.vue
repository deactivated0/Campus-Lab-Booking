<template>
  <AppLayout title="Approvals" @new-booking="goCalendar">
    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm font-semibold">Pending Booking Requests</div>
          <div class="text-xs text-slate-500">Lab staff can confirm requests → QR tokens become available.</div>
        </div>
      </div>

      <div class="mt-5 overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="text-xs uppercase tracking-widest text-slate-400">
            <tr class="border-b border-white/10">
              <th class="py-3">User</th>
              <th class="py-3">Lab</th>
              <th class="py-3">Equipment</th>
              <th class="py-3">Time</th>
              <th class="py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in pending" :key="b.id" class="border-b border-white/5">
              <td class="py-3">{{ b.user }}</td>
              <td class="py-3">{{ b.lab }}</td>
              <td class="py-3">{{ b.equipment }}</td>
              <td class="py-3 text-slate-400">{{ b.starts_at }} → {{ b.ends_at }}</td>
              <td class="py-3 text-right">
                <button
                  class="rounded-lg bg-emerald-500/15 px-3 py-1 text-xs text-emerald-200 hover:bg-emerald-500/25"
                  @click="approve(b)"
                >
                  Approve
                </button>
              </td>
            </tr>
            <tr v-if="pending.length === 0">
              <td colspan="5" class="py-8 text-center text-slate-500">No pending bookings.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

defineProps({ pending: Array })

function goCalendar() { router.visit('/bookings') }

async function approve(b) {
  const ok = await Swal.fire({
    title: 'Approve booking?',
    text: `${b.user} • ${b.equipment}`,
    icon: 'question',
    showCancelButton: true,
    background:'#0b0e14', color:'#e5e7eb',
  })
  if (!ok.isConfirmed) return
  router.post(`/bookings/${b.id}/approve`)
}
</script>
