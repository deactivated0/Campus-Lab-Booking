<template>
  <AppLayout title="Bookings" @new-booking="openQuickCreate">
      <div class="rounded-xl border border-white/10 bg-white/5 p-4 mx-auto max-w-5xl px-3 sm:px-4"> 
      <div class="flex items-center justify-between mb-5 w-full max-w-4xl mx-auto px-2">
        <div class="text-center md:text-left">
          <div class="text-xs text-slate-500">Drag cards to change status. Click a card to view details.</div>
        </div>

        <div class="flex gap-3 items-center">
          <div class="flex items-center gap-2 bg-white/5 rounded-xl p-1">
            <button :class="['px-3 py-1 text-sm rounded-md', scope === 'mine' ? 'bg-white/10' : '']" @click="scope = 'mine'">Mine</button>
            <button v-if="isStaff" :class="['px-3 py-1 text-sm rounded-md', scope === 'all' ? 'bg-white/10' : '']" @click="scope = 'all'">All</button>
          </div>

          <button
            @click="loadBookings"
            class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10 transition"
          >
            Refresh
          </button>

          <a
            class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10"
            href="/kiosk"
            target="_blank"
          >
            Open Kiosk (Tablet)
          </a>
        </div>
      </div>

      <!-- Kanban Board -->
      <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-3 overflow-hidden pb-4 mx-auto w-full">
        <div v-for="status in statuses" :key="status" class="kanban-column">
          <div class="kanban-column-header">
            <h3 class="font-semibold text-sm">{{ statusLabel(status) }}</h3>
            <span class="text-xs text-slate-400">{{ bookings[status]?.length || 0 }}</span>
          </div>
          
          <div
            @dragover.prevent="dragOverStatus = status"
            @dragleave="dragOverStatus = null"
            @drop="handleDrop(status)"
            :class="['kanban-column-body', { 'drag-over': dragOverStatus === status }]"
          >
            <div
              v-for="booking in bookings[status]"
              :key="booking.id"
              draggable="true"
              @dragstart="handleDragStart(booking, status)"
              @dragend="dragOverStatus = null"
              @click="viewBooking(booking.id)"
              class="kanban-card"
            >
              <div class="text-xs font-semibold text-blue-300 truncate">{{ booking.equipment || 'No Equipment' }}</div>
              <div class="text-xs text-slate-300 mt-1">{{ booking.lab || 'Lab' }}</div>
              <div class="text-xs text-slate-400 mt-2">{{ formatTime(booking.starts_at) }}</div>
              <div v-if="booking.notes" class="text-xs text-slate-500 mt-2 line-clamp-2">{{ booking.notes }}</div>
            </div>

            <div v-if="!bookings[status] || bookings[status].length === 0" class="kanban-empty">
              No bookings
            </div>
          </div>
        </div>
      </div>

      <div v-if="totalCount === 0" class="text-center text-slate-400 p-6">
        No bookings found. Click <button @click="loadBookings" class="underline">Refresh</button> to retry.
        If you expect bookings here, ensure you have bookings assigned to your account or ask an Admin to confirm there are bookings.
      </div>
      
      <!-- Calendar (shows all bookings) -->
      <div class="mt-6 mx-auto max-w-5xl px-2">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold">Calendar</h3>
          <div class="text-xs text-slate-400">Click an event to open the booking</div>
        </div>
        <div ref="calendarEl" id="bookings-calendar" class="rounded-xl border border-white/10 bg-white/5 p-2" style="min-height: 480px;"></div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import Swal from 'sweetalert2'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  labs: Array,
  equipment: Array,
  roles: Array,
  currentUserId: Number,
})

const isStaff = computed(() => (props.roles || []).includes('Admin') || (props.roles || []).includes('LabStaff'))
const scope = ref(isStaff.value ? 'all' : 'mine')

const statuses = ['pending', 'confirmed', 'checked_out', 'returned', 'cancelled']
const bookings = ref({
  pending: [],
  confirmed: [],
  checked_out: [],
  returned: [],
  cancelled: [],
})

const draggedBooking = ref(null)
const draggedFromStatus = ref(null)
const dragOverStatus = ref(null)

const totalCount = computed(() => statuses.reduce((s, st) => s + (bookings.value[st]?.length || 0), 0))

// Calendar refs
const calendarEl = ref(null)
let calendarInstance = null

async function ensureFullCalendar() {
  if (window.FullCalendar) return
  // Load CSS
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css'
  document.head.appendChild(link)

  // Load JS
  await new Promise((resolve) => {
    const s = document.createElement('script')
    s.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'
    s.onload = resolve
    document.body.appendChild(s)
  })
}

async function initCalendar() {
  try {
    await ensureFullCalendar()
    if (!calendarEl.value) return

    // Fetch events and build FullCalendar event objects
    const events = await fetch('/bookings/events', { headers: { 'Accept': 'application/json' } }).then(r => r.json()).catch(() => [])
    const fcEvents = (events || []).map(b => ({ id: String(b.id), title: b.title || b.equipment || b.lab || 'Booking', start: b.starts_at || b.start, end: b.ends_at || b.end }))

    if (calendarInstance) {
      calendarInstance.destroy()
      calendarInstance = null
    }

    calendarInstance = new FullCalendar.Calendar(calendarEl.value, {
      initialView: 'dayGridMonth',
      header: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
      editable: false,
      // Global defaults
      eventOverlap: false,
      slotEventOverlap: false,
      height: 'auto',
      aspectRatio: 1.5,
      events: fcEvents,
      eventClick: info => { viewBooking(info.event.id) },
      views: {
        dayGridMonth: {
          // Show compact list-style items in month cells so they sit inside the date square
          eventDisplay: 'list-item',
          // Limit stacked rows and show +n when overflowing
          dayMaxEventRows: 4,
        },
        timeGridWeek: {
          // In time grids, prevent overlap and show block events (respecting duration)
          eventOverlap: false,
          slotEventOverlap: false,
          eventDisplay: 'block',
        },
        timeGridDay: {
          eventOverlap: false,
          slotEventOverlap: false,
          eventDisplay: 'block',
        }
      }
    })
    calendarInstance.render()
  } catch (e) {
    console.error('Failed to init calendar', e)
  }
}

const statusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    checked_out: 'Checked Out',
    returned: 'Returned',
    cancelled: 'Cancelled',
  }
  return labels[status] || status
}

const formatTime = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const handleDragStart = (booking, status) => {
  draggedBooking.value = booking
  draggedFromStatus.value = status
}

const handleDrop = async (newStatus) => {
  if (!draggedBooking.value || draggedFromStatus.value === newStatus) {
    dragOverStatus.value = null
    return
  }

  const booking = draggedBooking.value
  const oldStatus = draggedFromStatus.value

  // Update UI optimistically
  const fromIndex = bookings.value[oldStatus].findIndex(b => b.id === booking.id)
  if (fromIndex > -1) {
    bookings.value[oldStatus].splice(fromIndex, 1)
  }
  bookings.value[newStatus].push(booking)

  dragOverStatus.value = null
  draggedBooking.value = null
  draggedFromStatus.value = null

  // Update server using a plain fetch so we can show a friendly toast and avoid Inertia's "plain response" dialog
  try {
    const res = await fetch(`/bookings/${booking.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify({ status: newStatus }),
    })

    if (!res.ok) {
      throw new Error('Failed to update status')
    }

    // Show a smaller, less-intrusive toast on success (non-blocking)
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Updated',
      showConfirmButton: false,
      timer: 1200,
      background: '#0b0e14',
      color: '#e5e7eb',
      width: 180,
      padding: '6px 8px',
      customClass: { popup: 'swal-toast--small', title: 'swal-toast-title--small' },
    })
  } catch (e) {
    // Revert on error
    const index = bookings.value[newStatus].findIndex(b => b.id === booking.id)
    if (index > -1) {
      bookings.value[newStatus].splice(index, 1)
    }
    bookings.value[oldStatus].push(booking)
    Swal.fire({ title: 'Error', text: 'Failed to update status', icon: 'error', background:'#0b0e14', color:'#e5e7eb' })
  }
}

const viewBooking = (id) => {
  router.visit(`/bookings/${id}`)
}

const escapeHtml = (s) => {
  return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
}

const unavailableEquipment = ref(new Set())

async function fetchUnavailable(labId, starts, ends) {
  if (!labId || !starts || !ends) { unavailableEquipment.value = new Set(); return }
  try {
    const res = await fetch(`/bookings/availability?lab_id=${labId}&starts_at=${encodeURIComponent(starts)}&ends_at=${encodeURIComponent(ends)}`)
    if (!res.ok) { unavailableEquipment.value = new Set(); return }
    const json = await res.json()
    unavailableEquipment.value = new Set((json.unavailable || []).map(String))
  } catch (e) { unavailableEquipment.value = new Set() }
}

async function openQuickCreate() {
  const { value: form } = await Swal.fire({
    title: 'New Booking',
    background: '#0b0e14',
    color: '#e5e7eb',
    confirmButtonText: 'Submit',
    showCancelButton: true,
    customClass: { popup: 'swal-booking-popup' },
    html: `
      <div class="swal-booking-form" style="font-size:13px; text-align:left">
        <div class="form-row">
          <label class="swal-label">Lab</label>
          <select id="lab" class="swal2-input swal-select">${labOptionsHtml()}</select>
        </div>

        <div class="form-row">
          <label class="swal-label">Equipment</label>
          <select id="equipment" class="swal2-input swal-select">${equipmentOptionsHtml(props.labs[0]?.id)}</select>
        </div>

        <div class="grid-dates">
          <div>
            <label class="swal-label">Start</label>
            <input id="starts" type="datetime-local" class="swal2-input" />
          </div>
          <div>
            <label class="swal-label">End</label>
            <input id="ends" type="datetime-local" class="swal2-input" />
          </div>
        </div>

        <div class="form-row textarea-row">
          <label class="swal-label">Notes</label>
          <textarea id="notes" class="swal2-textarea" placeholder="Optional notes..." rows="3"></textarea>
        </div>
      </div>
    `,
      didOpen: async () => {
      const labSel = document.getElementById('lab')
      const eqSel = document.getElementById('equipment')

      const refreshEq = () => { eqSel.innerHTML = equipmentOptionsHtml(labSel.value) }
      const updateAvailability = async () => {
        const startInput = document.getElementById('starts')
        const endInput = document.getElementById('ends')
        const s = startInput?.value
        const e = endInput?.value
        await fetchUnavailable(labSel.value, s, e)
        refreshEq()
      }

      labSel.addEventListener('change', () => { updateAvailability(); refreshEq(); })

      // Listen for start/end changes to fetch availability and keep end sensible
      const startInput = document.getElementById('starts')
      const endInput = document.getElementById('ends')

      if (startInput) {
        startInput.addEventListener('change', async () => {
          // If end is not set or is earlier than start, set end = start + 1 hour
          if (endInput && startInput.value) {
            try {
              const s = new Date(startInput.value)
              const e = endInput.value ? new Date(endInput.value) : null
              if (!e || e <= s) {
                const next = new Date(s.getTime() + 60 * 60 * 1000)
                endInput.value = next.toISOString().slice(0,16)
              }
            } catch (e) { /* ignore parsing errors */ }
          }
          await updateAvailability()
        })
      }

      if (endInput) {
        endInput.addEventListener('change', updateAvailability)
      }

      // Make sure the datetime inputs have sensible defaults
      const now = new Date();
      if (startInput && !startInput.value) startInput.value = new Date(now.getTime() + 15 * 60 * 1000).toISOString().slice(0,16)
      if (endInput && !endInput.value) endInput.value = new Date(now.getTime() + 60 * 60 * 1000).toISOString().slice(0,16)

      // Initial availability check
      await updateAvailability()
    },
    preConfirm: () => {
      const lab_id = document.getElementById('lab').value
      const equipment_id = document.getElementById('equipment').value
      const starts_at = document.getElementById('starts').value
      const ends_at = document.getElementById('ends').value
      const notes = document.getElementById('notes').value

      // Client-side validation: prevent selecting equipment that's unavailable
      if (equipment_id && unavailableEquipment.value && unavailableEquipment.value.has(String(equipment_id))) {
        Swal.showValidationMessage('Selected equipment is unavailable for those times')
        return false
      }

      return { lab_id, equipment_id: equipment_id || null, starts_at, ends_at, notes }
    },
  })

  if (!form) return

  router.post('/bookings', form, {
    onSuccess: () => {
      Swal.fire({ title: 'Submitted!', text: 'Booking request created.', icon: 'success', background:'#0b0e14', color:'#e5e7eb' })
      loadBookings()
    },
    onError: (errors) => {
      let msg = 'Please check the inputs.'
      try {
        if (errors && typeof errors === 'object') {
          if (errors.errors) errors = errors.errors
          else if (errors.props && errors.props.errors) errors = errors.props.errors
          else if (errors.response && errors.response.data && errors.response.data.errors) errors = errors.response.data.errors

          if (errors && typeof errors === 'object' && Object.keys(errors).length > 0) {
            msg = Object.values(errors).flat().map(v => Array.isArray(v) ? v.join(' ') : v).join('\n') || msg
          } else if (errors && errors.message) {
            msg = errors.message
          }
        }
      } catch (e) {}

      Swal.fire({ title: 'Error', text: msg, icon: 'error', background:'#0b0e14', color:'#e5e7eb' })
    },
  })
}

function labOptionsHtml() {
  const seen = new Set()
  const unique = []
  ;(props.labs || []).forEach(l => {
    const key = `${l.code || ''}|${l.name}`
    if (!seen.has(key)) { seen.add(key); unique.push(l) }
  })
  unique.sort((a,b) => (a.code || '').localeCompare(b.code || '') || (a.name || '').localeCompare(b.name || ''))
  return unique.map(l => `<option value="${l.id}">${l.code ? escapeHtml(l.code) + ' — ' : ''}${escapeHtml(l.name)}${l.location ? ' — ' + escapeHtml(l.location) : ''}</option>`).join('')
}

function equipmentOptionsHtml(labId) {
  const items = props.equipment.filter(e => String(e.lab_id) === String(labId))
  return [`<option value="">(No equipment)</option>`]
    .concat(items.map(e => {
      const isUnavailable = unavailableEquipment.value && unavailableEquipment.value.has(String(e.id))
      return `<option value="${e.id}" ${isUnavailable ? 'disabled' : ''}>${e.serial_number ? escapeHtml(e.serial_number) + ' — ' : ''}${escapeHtml(e.name)}${e.category ? ' — ' + escapeHtml(e.category) : ''}${isUnavailable ? ' — Unavailable' : ''}</option>`
    }))
    .join('')
}

async function loadBookings() {
  try {
    // Get bookings from the last 90 days to +90 days
    const now = new Date()
    const start = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000)
    const end = new Date(now.getTime() + 90 * 24 * 60 * 60 * 1000)

    const startStr = start.toISOString().split('T')[0]
    const endStr = end.toISOString().split('T')[0]

    console.log('Loading bookings from', startStr, 'to', endStr)

    // Fetch without date filters so controller can decide visible rows (staff see all; others see their own)
    const res = await fetch('/bookings/events', { headers: { 'Accept': 'application/json' } })
    const data = await res.json()
    
    console.log('Fetched bookings:', data)
    
    // Reset columns
    statuses.forEach(status => {
      bookings.value[status] = []
    })
    
    // Group by status
    data.forEach(booking => {
      const status = booking.status || 'pending'
      if (!bookings.value[status]) return

      // Map fields (include user info for client-side filtering)
      const mapped = {
        id: booking.id,
        equipment: booking.equipment || booking.title || 'No Equipment',
        lab: booking.lab || 'Lab',
        starts_at: booking.start || booking.starts_at,
        notes: booking.notes || '',
        status: status,
        user_id: booking.user_id ?? null,
        user: booking.user ?? null,
      }

      // If scope is 'mine', only show bookings for the current user
      if (scope.value === 'mine') {
        if (mapped.user_id && props.currentUserId && String(mapped.user_id) !== String(props.currentUserId)) return
      }

      bookings.value[status].push(mapped)
    })

    console.log('Grouped bookings:', bookings.value)

    // Update calendar events if calendar is present
    try {
      if (calendarInstance) {
        // Convert column bookings into calendar events
        const all = []
        statuses.forEach(st => {
          (bookings.value[st] || []).forEach(b => {
            all.push({ id: String(b.id), title: b.title || b.equipment || b.lab || 'Booking', start: b.starts_at, end: b.ends_at })
          })
        })
        calendarInstance.removeAllEvents()
        all.forEach(ev => calendarInstance.addEvent(ev))
      }
    } catch (e) { console.error('Failed to update calendar events', e) }
  } catch (e) {
    console.error('Failed to load bookings:', e)
  }
}

onMounted(() => {
  loadBookings()
  // Initialize calendar below kanban
  initCalendar()
  
  // Reload bookings when tab comes into focus
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      loadBookings()
    }
  })
})
</script>

<style scoped>
/* Kanban Board Container */
.grid {
  display: grid;
  gap: 0.5rem;
  width: 100%;
  overflow-x: hidden;
  padding-bottom: 1rem;
  align-items: start;
}

/* Kanban Column */
.kanban-column {
  min-width: 0;
  max-width: none;
  display: flex;
  flex-direction: column;
  height: calc(100vh - 240px);
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
} 

.kanban-column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: rgba(255, 255, 255, 0.05);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  flex-shrink: 0;
}

.kanban-column-header h3 {
  margin: 0;
  color: #e5e7eb;
  font-size: 14px;
  font-weight: 600;
}

.kanban-column-header span {
  color: #94a3b8;
  font-size: 12px;
  background: rgba(255, 255, 255, 0.05);
  padding: 2px 8px;
  border-radius: 12px;
}

.kanban-column-body {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: background 0.2s;
  min-height: 0;
}

.kanban-column-body.drag-over {
  background: rgba(100, 200, 255, 0.1);
  border-color: rgba(100, 200, 255, 0.3);
}
</style>

<style>
/* Global styles for SweetAlert2 booking popup */
.swal-booking-popup .swal2-html-container .swal-booking-form { width: 340px; max-width: calc(100vw - 48px); box-sizing: border-box; }
.swal-booking-popup .swal2-html-container .swal-booking-form .swal-label { display:block; font-size:12px; color:#94a3b8; margin-bottom:6px; }
.swal-booking-popup .swal2-html-container .swal-booking-form .form-row { margin-bottom:10px; }
.swal-booking-popup .swal2-html-container .swal-booking-form .swal-select,
.swal-booking-popup .swal2-html-container .swal-booking-form .swal2-input,
.swal-booking-popup .swal2-html-container .swal-booking-form .swal2-textarea {
  width:100% !important; min-width:0; font-size:13px; height:36px; padding:8px; box-sizing:border-box; border-radius:8px;
}
.swal-booking-popup .swal2-html-container .swal-booking-form .swal2-textarea { height:64px; resize:vertical }
/* Stack start/end vertically for clearer layout */
.swal-booking-popup .swal2-html-container .swal-booking-form .grid-dates { display:grid; grid-template-columns: 1fr; gap:8px; }
@media (max-width:640px) {
  .swal-booking-popup .swal2-html-container .swal-booking-form { width: calc(100vw - 48px) }
}

/* Card styles moved into global block to avoid stray text outside style tags */
.kanban-card {
  padding: 10px;
  cursor: grab;
  transition: all 0.2s;
  user-select: none;
  flex-shrink: 0;
  overflow: hidden;
  word-break: break-word;
  overflow-wrap: anywhere;
}

.kanban-card:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(100, 200, 255, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.kanban-card:active {
  cursor: grabbing;
}

.kanban-empty {
  flex: 1;  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-size: 13px;
  font-style: italic;
  text-align: center;
  padding: 20px;
}

/* Small sweetalert2 toast tweaks */
.swal-toast--small {
  font-size: 12px !important;
  padding: 6px 8px !important;
  min-width: 0 !important;
  box-shadow: 0 4px 8px rgba(0,0,0,0.25) !important;
}

.swal-toast--small .swal2-title {
  font-size: 12px !important;
  margin: 0 !important;
}

.swal2-popup.swal-toast--small {
  background: rgba(11,14,20,0.95) !important;
}

/* Scrollbar styling */
.kanban-column-body::-webkit-scrollbar {
  width: 6px;
}

.kanban-column-body::-webkit-scrollbar-track {
  background: transparent;
}

.kanban-column-body::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
}

.kanban-column-body::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
