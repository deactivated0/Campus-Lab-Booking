<template>
  <AppLayout title="Labs" @new-booking="goCalendar">
    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm font-semibold">Labs</div>
          <div class="text-xs text-slate-500">Admin-only management.</div>
        </div>
        <button class="rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-semibold hover:opacity-90" @click="addLab">
          + Add Lab
        </button>
      </div>

      <div class="mt-5 space-y-2">
        <div v-for="l in labs" :key="l.id" class="rounded-xl border border-white/10 bg-white/5 p-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <div class="text-sm font-semibold">{{ l.name }}</div>
              <div class="text-xs text-slate-500">{{ l.location || '—' }} • Capacity: {{ l.capacity ?? '—' }}</div>
            </div>
            <div class="flex gap-2">
              <button class="rounded-lg border border-white/10 bg-white/5 px-3 py-1 text-xs hover:bg-white/10" @click="editLab(l)">Edit</button>
              <button class="rounded-lg border border-white/10 bg-white/5 px-3 py-1 text-xs hover:bg-white/10" @click="delLab(l)">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue'
import Swal from 'sweetalert2'
import { router } from '@inertiajs/vue3'

const props = defineProps({ labs: Array })

function goCalendar() { router.visit('/bookings') }

function escapeHtml(s) {
  return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
}

async function addLab() {
  const { value } = await Swal.fire({
    title: 'Add Lab',
    background:'#0b0e14', color:'#e5e7eb',
    showCancelButton: true,
    confirmButtonText: 'Create',
    html: `
      <div style="text-align:left; font-size: 13px">
        <label>Name</label>
        <input id="name" class="swal2-input" placeholder="Lab 3"/>
        <label>Location</label>
        <input id="location" class="swal2-input" placeholder="Building A"/>
        <label>Capacity</label>
        <input id="capacity" type="number" class="swal2-input" placeholder="24"/>
      </div>
    `,
    preConfirm: () => ({
      name: document.getElementById('name').value,
      location: document.getElementById('location').value || null,
      capacity: document.getElementById('capacity').value ? Number(document.getElementById('capacity').value) : null,
      is_active: true,
    })
  })
  if (!value) return
  router.post('/labs', value)
}

async function editLab(l) {
  const { value } = await Swal.fire({
    title: 'Edit Lab',
    background:'#0b0e14', color:'#e5e7eb',
    showCancelButton: true,
    confirmButtonText: 'Save',
    html: `
      <div style="text-align:left; font-size: 13px">
        <label>Name</label>
        <input id="name" class="swal2-input" value="${escapeHtml(l.name)}"/>
        <label>Location</label>
        <input id="location" class="swal2-input" value="${escapeHtml(l.location || '')}"/>
        <label>Capacity</label>
        <input id="capacity" type="number" class="swal2-input" value="${l.capacity ?? ''}"/>
      </div>
    `,
    preConfirm: () => ({
      name: document.getElementById('name').value,
      location: document.getElementById('location').value || null,
      capacity: document.getElementById('capacity').value ? Number(document.getElementById('capacity').value) : null,
      is_active: true,
    })
  })
  if (!value) return
  router.put(`/labs/${l.id}`, value)
}

async function delLab(l) {
  const ok = await Swal.fire({
    title: 'Delete lab?',
    text: l.name,
    icon: 'warning',
    showCancelButton: true,
    background:'#0b0e14', color:'#e5e7eb',
  })
  if (!ok.isConfirmed) return
  router.delete(`/labs/${l.id}`)
}
</script>
