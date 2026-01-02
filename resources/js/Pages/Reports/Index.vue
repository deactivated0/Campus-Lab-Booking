<template>
  <AppLayout title="Reports" @new-booking="goCalendar">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2 rounded-2xl border border-white/10 bg-white/5 p-5">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-semibold">Usage Analytics</div>
            <div class="text-xs text-slate-500">Real-time-ish demo analytics (refreshes on load).</div>
          </div>

          <div class="flex gap-2">
            <a :href="exportCsvUrl" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10">
              Export CSV
            </a>
            <a :href="exportPdfUrl" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10">
              Export HTML
            </a>
          </div>
        </div>

        <div class="mt-5">
          <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
          <div v-else class="text-sm text-slate-500">Loading chart…</div>
        </div>
      </div>

      <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
        <div class="text-sm font-semibold">Recent Logs</div>
        <div class="mt-4 space-y-3">
          <div v-for="(l, idx) in logs.slice(0,8)" :key="l.id || idx" class="rounded-xl border border-white/10 bg-white/5 p-3 flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="text-sm font-semibold">{{ l.equipment || '—' }}</div>
              <div class="text-xs text-slate-500">{{ l.student }} • {{ l.lab }}</div>
              <div class="text-xs text-slate-400">{{ l.in }} → {{ l.out || 'active' }}</div>
            </div>

            <div class="flex items-center gap-2">
              <button @click.prevent="confirmDeleteLog(l.id, idx)" class="text-xs text-red-400 hover:text-red-300 rounded px-2 py-1 border border-transparent hover:bg-white/2">Delete</button>
            </div>
          </div>
          <div v-if="logs.length === 0" class="text-sm text-slate-500">No logs yet.</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title, Tooltip, Legend,
  BarElement, CategoryScale, LinearScale
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps({
  exportCsvUrl: String,
  exportPdfUrl: String,
})

const logs = ref([])
const chartData = ref(null)

const chartOptions = {
  responsive: true,
  plugins: {
    legend: { display: false },
    title: { display: true, text: 'Usage by Equipment (last 200 logs)' },
  },
  scales: {
    x: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.06)' } },
    y: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.06)' } },
  },
}

function goCalendar() { router.visit('/bookings') }

onMounted(async () => {
  await loadReports()
})

async function loadReports() {
  const res = await fetch('/reports/data', { headers: { 'Accept': 'application/json' } })
  const data = await res.json()
  logs.value = data.logs || []

  const labels = Object.keys(data.byEquipment || {})
  const values = Object.values(data.byEquipment || {})

  chartData.value = {
    labels,
    datasets: [{ label: 'Count', data: values }],
  }
}

async function confirmDeleteLog(id, idx) {
  const { value } = await Swal.fire({
    title: 'Delete log?',
    text: 'This will permanently remove the selected usage log.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    background:'#0b0e14',
    color:'#e5e7eb',
  })

  if (!value) return

  await deleteLog(id, idx)
}

async function deleteLog(id, idx) {
  try {
    const res = await fetch(`/reports/logs/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' }
    })

    if (!res.ok) throw new Error('Delete failed')

    // Remove locally
    logs.value.splice(idx, 1)

    Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1200, icon: 'success', title: 'Deleted', background:'#0b0e14', color:'#e5e7eb' })

    // Reload aggregation data
    await loadReports()
  } catch (e) {
    Swal.fire({ title: 'Error', text: 'Failed to delete log', icon: 'error', background:'#0b0e14', color:'#e5e7eb' })
  }
}
</script>
