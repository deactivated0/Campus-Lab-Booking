<template>
  <AppLayout title="Equipment List" @new-booking="goCalendar">
    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm font-semibold">Equipment</div>
          <div class="text-xs text-slate-500">Drag & drop to reorder (Staff/Admin). Students can view only.</div>
        </div>

        <button
          v-if="canManage"
          class="rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-semibold shadow-lg shadow-indigo-500/20 hover:opacity-90"
          @click="addItem"
        >
          + Add Equipment
        </button>
      </div>

      <div class="mt-5 overflow-x-auto">
        <draggable
          v-if="canManage"
          v-model="list"
          item-key="id"
          handle=".drag"
          @end="saveOrder"
        >
          <template #item="{ element }">
            <div>
              <div v-if="isFirstInCategory(element)" class="text-sm font-semibold text-slate-200 mt-3 mb-2">{{ element.category || 'Uncategorized' }}</div>
              <div class="mb-2 rounded-xl border border-white/10 bg-white/5 p-4">
                <Row :item="element" :canManage="canManage" @edit="editItem" @del="deleteItem" />
              </div>
            </div>
          </template>
        </draggable>

        <div v-else>
          <div v-for="cat in groupedCategories.order" :key="cat" class="mb-4">
            <div class="text-sm font-semibold text-slate-200 mb-2">{{ cat }}</div>
            <div v-for="it in groupedCategories.map[cat]" :key="it.id" class="mb-2 rounded-xl border border-white/10 bg-white/5 p-4">
              <Row :item="it" :canManage="false" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import Row from './Row.vue'
import draggable from 'vuedraggable'
import Swal from 'sweetalert2'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  labs: Array,
  items: Array,
  canManage: Boolean,
})

const list = ref([...(props.items || [])])

const groupedCategories = computed(() => {
  const order = []
  list.value.forEach(i => {
    const cat = i.category || 'Uncategorized'
    if (!order.includes(cat)) order.push(cat)
  })
  const map = {}
  order.forEach(cat => {
    map[cat] = list.value.filter(i => (i.category || 'Uncategorized') === cat)
  })
  return { order, map }
})

function isFirstInCategory(item) {
  const cat = item.category || 'Uncategorized'
  const first = list.value.find(i => (i.category || 'Uncategorized') === cat)
  return first && first.id === item.id
}

function goCalendar() {
  router.visit('/bookings')
}

function labOptions(selected) {
  return props.labs.map(l => `<option value="${l.id}" ${String(l.id)===String(selected) ? 'selected':''}>${escapeHtml(l.name)}</option>`).join('')
}
function escapeHtml(s) {
  return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
}

async function addItem() {
  const { value } = await Swal.fire({
    title: 'Add Equipment',
    background:'#0b0e14', color:'#e5e7eb',
    showCancelButton: true,
    confirmButtonText: 'Create',
    html: `
      <div style="text-align:left; font-size: 13px">
        <label>Lab</label>
        <select id="lab" class="swal2-input" style="height:44px">${labOptions(props.labs[0]?.id)}</select>
        <label>Name</label>
        <input id="name" class="swal2-input" placeholder="e.g., Canon EOS R5 Kit"/>
        <label>Category</label>
        <input id="category" class="swal2-input" placeholder="Camera / Microscope / Audio"/>
        <label>Serial</label>
        <input id="serial" class="swal2-input" placeholder="Optional"/>
      </div>
    `,
    preConfirm: () => ({
      lab_id: document.getElementById('lab').value,
      name: document.getElementById('name').value,
      category: document.getElementById('category').value || null,
      serial_number: document.getElementById('serial').value || null,
      is_active: true,
    })
  })
  if (!value) return
  router.post('/equipment', value)
}

async function editItem(item) {
  const { value } = await Swal.fire({
    title: 'Edit Equipment',
    background:'#0b0e14', color:'#e5e7eb',
    showCancelButton: true,
    confirmButtonText: 'Save',
    html: `
      <div style="text-align:left; font-size: 13px">
        <label>Lab</label>
        <select id="lab" class="swal2-input" style="height:44px">${labOptions(item.lab_id)}</select>
        <label>Name</label>
        <input id="name" class="swal2-input" value="${escapeHtml(item.name)}"/>
        <label>Category</label>
        <input id="category" class="swal2-input" value="${escapeHtml(item.category || '')}"/>
        <label>Serial</label>
        <input id="serial" class="swal2-input" value="${escapeHtml(item.serial_number || '')}"/>
        <label style="display:flex; align-items:center; gap:8px; margin-top:10px">
          <input id="active" type="checkbox" ${item.is_active ? 'checked' : ''} />
          Active
        </label>
      </div>
    `,
    preConfirm: () => ({
      lab_id: document.getElementById('lab').value,
      name: document.getElementById('name').value,
      category: document.getElementById('category').value || null,
      serial_number: document.getElementById('serial').value || null,
      is_active: document.getElementById('active').checked,
    })
  })
  if (!value) return
  router.put(`/equipment/${item.id}`, value)
}

async function deleteItem(item) {
  const ok = await Swal.fire({
    title: 'Delete?',
    text: item.name,
    icon: 'warning',
    showCancelButton: true,
    background:'#0b0e14', color:'#e5e7eb',
  })
  if (!ok.isConfirmed) return
  router.delete(`/equipment/${item.id}`)
}

async function saveOrder() {
  const ids = list.value.map(i => i.id)
  await fetch('/equipment/reorder', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    },
    body: JSON.stringify({ ids }),
  })
}
</script>
