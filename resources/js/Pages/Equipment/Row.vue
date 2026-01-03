<template>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <div v-if="canManage" class="drag cursor-move select-none text-slate-500">⠿</div>
      <div>
        <div class="text-sm font-semibold">{{ item.name }}</div>
        <div class="text-xs text-slate-500">
          {{ item.category || '—' }} • {{ item.lab || '—' }} • {{ item.serial_number || 'no serial' }}
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <span
        class="inline-flex rounded-full px-3 py-1 text-xs"
        :class="item.is_active ? 'bg-emerald-500/10 text-emerald-200' : 'bg-slate-500/10 text-slate-300'"
      >
        {{ item.is_active ? 'Active' : 'Inactive' }}
      </span>

      <button v-if="canManage" class="rounded-lg border border-white/10 bg-white/5 px-3 py-1 text-xs hover:bg-white/10" @click="$emit('edit', item)">
        Edit
      </button>
      <button v-if="canManage" class="rounded-lg border border-white/10 bg-white/5 px-3 py-1 text-xs hover:bg-white/10" @click="$emit('del', item)">
        Delete
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  item: Object,
  canManage: Boolean,
})
defineEmits(['edit','del'])
</script>
