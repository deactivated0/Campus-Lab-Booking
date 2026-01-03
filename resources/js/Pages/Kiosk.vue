<template>
  <div class="min-h-screen bg-[#0b0e14] text-slate-100">
    <div class="mx-auto max-w-5xl px-6 py-10">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-2xl font-semibold">Tablet Kiosk</div>
          <div class="text-sm text-slate-500">Scan QR to check-in / return equipment.</div>
        </div>
        <a href="/dashboard" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10">
          Back to App
        </a>
      </div>

      <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
          <div class="text-sm font-semibold">Scanner</div>
          <div class="mt-2 text-xs text-slate-500">Uses html5-qrcode (WebRTC camera).</div>
          <div class="mt-4 overflow-hidden rounded-xl border border-white/10 bg-black/40 p-2">
            <div id="qr-reader" class="w-full"></div>
          </div>

          <div class="mt-4">
            <label class="text-xs text-slate-400">Camera</label>
            <select v-if="cameras.length" v-model="selectedCameraId" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm">
              <option v-for="c in cameras" :key="c.id" :value="c.id">{{ c.label || c.id }}</option>
            </select>
          </div>

          <div class="mt-4 flex gap-2">
            <button class="rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-semibold hover:opacity-90" @click="start">
              Start
            </button>
            <button class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10" @click="stop">
              Stop
            </button>
          </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
          <div class="text-sm font-semibold">Manual Entry</div>
          <div class="mt-2 text-xs text-slate-500">Paste token or URL.</div>

          <input v-model="manual" class="mt-4 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none focus:border-indigo-400/50" placeholder="token or URL..." />

          <div class="mt-4">
            <button class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm hover:bg-white/10" @click="submit(manual)">
              Submit
            </button>
          </div>

          <div class="mt-6 rounded-xl border border-white/10 bg-white/5 p-4">
            <div class="text-xs uppercase tracking-widest text-slate-500">Last Action</div>
            <div class="mt-2 text-sm">{{ last.message || '—' }}</div>
            <div v-if="last.summary" class="mt-2 text-xs text-slate-400">
              {{ last.summary.student }} • {{ last.summary.equipment }} • {{ last.summary.lab }}<br />
              {{ last.summary.window }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import Swal from 'sweetalert2'
import { Html5Qrcode } from 'html5-qrcode'

const props = defineProps({
  scanPostUrl: String,
})

const manual = ref('')
const last = ref({})

let scanner = null
const scannerId = 'qr-reader'

const selectedCameraId = ref(null)
const cameras = ref([])

async function start() {
  try {
    if (!scanner) scanner = new Html5Qrcode(scannerId)
    const cams = await Html5Qrcode.getCameras()
    cameras.value = cams || []
    if (!cams || cams.length === 0) throw new Error('No camera found')

    // Choose selected or first available camera
    const deviceId = selectedCameraId.value || cams[0].id

    try {
      await scanner.start(
        { deviceId: { exact: deviceId } },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        async (decodedText) => {
          // show decoded preview, then submit
          last.value = { message: 'Scanned: ' + decodedText }
          console.debug('Scanned text:', decodedText)
          await submit(decodedText)
        }
      )
    } catch (e) {
      // Fallback: try without exact deviceId
      console.warn('Camera start with exact device id failed, trying default start', e)
      await scanner.start(undefined, { fps: 10, qrbox: { width: 250, height: 250 } }, async (decodedText) => {
        last.value = { message: 'Scanned: ' + decodedText }
        await submit(decodedText)
      })
    }
  } catch (e) {
    Swal.fire({ title: 'Camera error', text: String(e), icon: 'error', background:'#0b0e14', color:'#e5e7eb' })
  }
}

async function stop() {
  if (!scanner) return
  try {
    await scanner.stop()
    await scanner.clear()
  } catch (_) {}
}

async function submit(tokenOrUrl) {
  if (!tokenOrUrl) return

  const res = await fetch(props.scanPostUrl, {
    method: 'POST',
    credentials: 'same-origin', // ensure cookies are sent when available
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    },
    body: JSON.stringify({ token_or_url: tokenOrUrl, kiosk_label: 'Laragon Tablet' }),
  })

  // Handle Laravel CSRF 419 explicitly
  if (res.status === 419) {
    last.value = { message: 'CSRF token mismatch (session missing or expired). Please reload the kiosk page.' };
    Swal.fire({ title: 'Rejected', text: last.value.message, icon: 'error', background:'#0b0e14', color:'#e5e7eb' });
    return
  }

  let data = {}
  try {
    data = await res.json()
  } catch (e) {
    // If response isn't JSON, fall back to text for richer feedback
    const txt = await res.text().catch(() => '')
    data = { ok: res.ok, message: txt || `HTTP ${res.status}` }
  }

  if (!res.ok || !data.ok) {
    const message = data.message || `HTTP ${res.status}`
    last.value = { message }
    Swal.fire({ title: 'Rejected', text: message, icon: 'error', background:'#0b0e14', color:'#e5e7eb' })
    return
  }

  last.value = data
  Swal.fire({ title: data.action === 'check_in' ? 'Checked Out' : 'Returned', text: data.message, icon: 'success', background:'#0b0e14', color:'#e5e7eb' })
}

onBeforeUnmount(stop)
</script>
