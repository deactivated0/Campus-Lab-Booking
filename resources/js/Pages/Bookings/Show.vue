<template>
  <AppLayout :title="`Booking #${booking.id}`" @new-booking="goCalendar">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
          <div class="text-sm font-semibold">Booking Details</div>

          <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 text-sm">
            <Field label="Title" :value="booking.title" />
            <Field label="Status" :value="booking.status" />
            <Field label="Lab" :value="booking.lab" />
            <Field label="Equipment" :value="booking.equipment" />
            <Field label="Start" :value="booking.starts_at" />
            <Field label="End" :value="booking.ends_at" />
          </div>

          <div class="mt-5 flex flex-wrap gap-3">
            <button
              class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10"
              @click="cancel"
            >
              Cancel Booking
            </button>

            <button
              class="rounded-xl border border-red-600 bg-red-700/10 px-4 py-2 text-sm text-red-200 hover:bg-red-700/20"
              @click="deleteBooking"
            >
              Delete Booking
            </button>

            <button
              v-if="booking.canIssueQr"
              :disabled="issuing"
              class="rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-semibold shadow-lg shadow-indigo-500/20 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="issueQr"
            >
              <span v-if="!issuing">Issue QR (15 min)</span>
              <span v-else>Issuing…</span>
            </button>

            <a
              class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10"
              href="/kiosk"
              target="_blank"
            >
              Open Kiosk
            </a>
          </div>
        </div>
      </div>

      <div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
          <div class="text-sm font-semibold">QR for Kiosk</div>
          <div class="mt-2 text-xs text-slate-500">
            Scan on the tablet kiosk to check-in/out. Tokens expire quickly.
          </div>

          <div class="mt-4 grid place-items-center">
            <div v-if="qrUrl" class="rounded-xl bg-white p-3">
              <canvas ref="canvasEl"></canvas>
              <div class="mt-2 flex gap-2">
                <button class="rounded-xl border border-white/10 bg-white/5 px-3 py-1 text-xs hover:bg-white/10" @click="copyQrUrl">Copy URL</button>
                <button class="rounded-xl border border-white/10 bg-white/5 px-3 py-1 text-xs hover:bg-white/10" @click="copyToken">Copy Token</button>
              </div>
              <div v-if="qrError" class="mt-2 text-xs text-red-500">QR generation failed: {{ qrError }}</div>
              <div v-if="qrError" class="mt-2 text-xs text-slate-500 break-all">Token: {{ page.props.flash.qr?.token || '—' }}</div>
            </div>
            <div v-else class="text-sm text-slate-500">
              Click “Issue QR” to generate a time-limited token.
            </div>
          </div>

          <div v-if="qrUrl" class="mt-4 text-xs text-slate-400 break-all">
            {{ qrUrl }}
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import Swal from 'sweetalert2'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Field from '../../Shared/Field.vue'
import QRCode from 'qrcode'

const props = defineProps({ booking: Object })
const page = usePage()
const canvasEl = ref(null)
const qrUrl = ref(null)

function goCalendar() {
  router.visit('/bookings')
}

function cancel() {
  router.post(`/bookings/${props.booking.id}/cancel`)
}

import { nextTick } from 'vue'

const issuing = ref(false)

async function issueQr() {
  if (issuing.value) return
  issuing.value = true

  try {
    const res = await fetch(`/bookings/${props.booking.id}/issue-qr`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify({}),
    })

    if (!res.ok) {
      const text = await res.text().catch(() => '')
      throw new Error(text || `HTTP ${res.status}`)
    }

    const data = await res.json()
    if (!data?.ok) throw new Error(data?.message || 'Unknown error')

    // Set QR URL immediately and render after DOM updates (ensure canvas exists)
    qrUrl.value = data.url
    await nextTick()
    await renderQr(data.url)

    // Also set flash token in page props by reloading Inertia props in background
    try { router.reload({ only: ['flash'] }) } catch (_) {}
  } catch (e) {
    const msg = String(e.message || e)
    Swal.fire({ title: 'Error', text: msg, icon: 'error', background:'#0b0e14', color:'#e5e7eb' })
  } finally {
    issuing.value = false
  }
}

async function deleteBooking() {
  const confirmed = await Swal.fire({
    title: 'Delete booking?',
    text: 'This will permanently remove the booking.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    background:'#0b0e14',
    color:'#e5e7eb'
  });

  if (!confirmed.isConfirmed) return;

  try {
    const res = await fetch(`/bookings/${props.booking.id}`, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
    });

    if (!res.ok) {
      const txt = await res.text().catch(() => '');
      throw new Error(txt || `HTTP ${res.status}`);
    }

    // Navigate back to bookings list
    router.visit('/bookings');
  } catch (e) {
    Swal.fire({ title: 'Error', text: String(e.message || e), icon: 'error', background:'#0b0e14', color:'#e5e7eb' });
  }
}

const qrError = ref(null)

async function renderQr(url) {
  if (!canvasEl.value) return

  // clear canvas to avoid leftover blank/partial renders
  try { canvasEl.value.getContext('2d')?.clearRect(0,0,canvasEl.value.width, canvasEl.value.height) } catch (_) {}

  try {
    qrError.value = null

    // Ensure canvas has explicit pixel dimensions to avoid blank renders on first draw
    const size = 220
    const ratio = window.devicePixelRatio || 1
    const canvas = canvasEl.value
    try {
      canvas.width = Math.round(size * ratio)
      canvas.height = Math.round(size * ratio)
      canvas.style.width = size + 'px'
      canvas.style.height = size + 'px'
      const ctx = canvas.getContext('2d')
      if (ctx && ctx.setTransform) ctx.setTransform(ratio,0,0,ratio,0,0)
    } catch (_) {}

    await QRCode.toCanvas(canvasEl.value, url, { margin: 1, width: size })
  } catch (e) {
    // Retry once after a short delay — some browsers/canvases need a tick
    console.warn('QR render failed, retrying:', e)
    await new Promise(r => setTimeout(r, 120))
    try {
      await QRCode.toCanvas(canvasEl.value, url, { margin: 1, width: 220 })
      qrError.value = null
      return
    } catch (e2) {
      qrError.value = String(e2)
      console.error('QR render failed on retry:', e2)
    }
  }
}

watch(qrUrl, (v) => { if (v) renderQr(v) })

async function copy(text) {
  try {
    await navigator.clipboard.writeText(text)
    Swal.fire({ title: 'Copied', text: 'Copied to clipboard', icon: 'success', background:'#0b0e14', color:'#e5e7eb' })
  } catch (e) {
    Swal.fire({ title: 'Copy failed', text: String(e), icon: 'error', background:'#0b0e14', color:'#e5e7eb' })
  }
}

function copyQrUrl() {
  const text = page.props.flash?.qr?.url || qrUrl.value
  if (!text) return Swal.fire({ title: 'No URL', text: 'No QR URL available to copy.', icon: 'info', background:'#0b0e14', color:'#e5e7eb' })
  copy(text)
}

function copyToken() {
  const text = page.props.flash?.qr?.token
  if (!text) return Swal.fire({ title: 'No token', text: 'No token available to copy.', icon: 'info', background:'#0b0e14', color:'#e5e7eb' })
  copy(text)
}

onMounted(() => {
  const flash = page.props.flash || {}
  if (flash.qr?.url) {
    qrUrl.value = flash.qr.url
  }
})
</script>
