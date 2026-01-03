<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Inertia } from '@inertiajs/inertia'
import Swal from 'sweetalert2'

const props = defineProps({ users: Array, roles: Array })

const processing = ref(null)

const onChangeRole = (userId, role) => {
  processing.value = userId
  Inertia.put(
    route('admin.users.update.role', { user: userId }),
    { role },
    {
      preserveState: true,
      onSuccess: () => {
        processing.value = null
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Role updated', showConfirmButton: false, timer: 1400, background:'#0b0e14', color:'#e5e7eb' })
      },
      onError: () => {
        processing.value = null
        Swal.fire({ title: 'Error', text: 'Failed to update role', icon: 'error', background:'#0b0e14', color:'#e5e7eb' })
      }
    }
  )
}
</script>

<template>
  <AppLayout title="Users">
    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
      <h2 class="text-lg font-semibold mb-4">Manage users</h2>
      <table class="w-full table-auto">
        <thead>
          <tr class="text-left text-sm text-slate-400">
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="border-t border-white/6">
            <td class="py-3">{{ u.name }}</td>
            <td class="py-3 text-slate-400">{{ u.email }}</td>
            <td class="py-3">
              <select :value="u.roles[0] || 'Student'" @change="e => onChangeRole(u.id, e.target.value)" :disabled="processing === u.id" class="rounded-md bg-white/5 border border-white/10 text-slate-100 px-2 py-1">
                <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
              </select>
            </td>
            <td class="py-3 text-right text-xs text-slate-400">ID: {{ u.id }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
