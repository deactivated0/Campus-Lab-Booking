<template>
  <div class="min-h-screen bg-[#0b0e14] text-slate-100">
    <div class="flex">
      <!-- Sidebar -->
      <aside class="w-72 min-h-screen border-r border-white/10 bg-[#0a0c12]/80 backdrop-blur">
        <div class="px-5 py-6">
          <div class="text-lg font-semibold tracking-wide">
            <span class="text-slate-100">CampusLab</span>
            <span class="text-indigo-300"> Sync</span>
          </div>
          <div class="mt-1 text-xs text-slate-400">Home › Dashboard</div>
        </div>

        <nav class="px-3 space-y-1">
          <NavLink href="/dashboard" :active="is('/dashboard')">Dashboard</NavLink>
          <NavLink href="/bookings" :active="is('/bookings')">Calendar & Kanban</NavLink>
          <NavLink href="/equipment" :active="is('/equipment')">Equipment List</NavLink>

          <div v-if="canApprove" class="pt-3">
            <div class="px-3 py-2 text-xs uppercase tracking-widest text-slate-500">Staff</div>
            <NavLink href="/approvals" :active="is('/approvals')">Approvals</NavLink>
            <NavLink href="/reports" :active="is('/reports')">Reports</NavLink>
          </div>

          <div v-if="isAdmin" class="pt-3">
            <div class="px-3 py-2 text-xs uppercase tracking-widest text-slate-500">Admin</div>
            <NavLink href="/labs" :active="is('/labs')">Labs</NavLink>
            <NavLink href="/admin/users" :active="is('/admin/users')">Users</NavLink>
          </div>
        </nav>

        <div class="mt-auto p-4">
          <div class="rounded-xl border border-white/10 bg-white/5 p-3">
            <div class="text-xs text-slate-400">Signed in as</div>
            <div class="text-sm font-medium">{{ user?.name }}</div>
            <div class="text-xs text-slate-500">{{ roleLabel }}</div>
          </div>

          <button
            class="mt-3 w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm hover:bg-white/10"
            @click="logout"
          >
            Log out
          </button>
        </div>
      </aside>

      <!-- Main -->
      <main class="flex-1">
        <header class="flex items-center justify-between px-8 py-6">
          <div>
            <h1 class="text-2xl font-semibold">{{ title }}</h1>
          </div>

          <button
            class="rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-semibold shadow-lg shadow-indigo-500/20 hover:opacity-90"
            @click="$emit('new-booking')"
          >
            New Booking
          </button>
        </header>

        <div class="px-8 pb-10">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import NavLink from '../Shared/NavLink.vue'

const props = defineProps({
  title: { type: String, default: 'Dashboard' },
})

const page = usePage()
const user = computed(() => page.props.auth?.user)
const roles = computed(() => page.props.auth?.roles ?? [])
const canApprove = computed(() => (page.props?.can?.approve) || roles.value.includes('Admin') || roles.value.includes('LabStaff'))
const isAdmin = computed(() => (page.props?.can?.admin) || roles.value.includes('Admin'))

const roleLabel = computed(() => roles.value.join(', ') || 'Student')

function is(path) {
  return page.url.startsWith(path)
}

function logout() {
  router.post('/logout')
}
</script>
