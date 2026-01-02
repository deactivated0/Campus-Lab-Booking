<template>
  <div class="rounded-xl border border-white/10 bg-white/5 p-3">
    <div class="text-xs text-slate-400">{{ label }}</div>
    <div class="mt-1 text-sm text-slate-200">{{ value || '—' }}</div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
})
</script>
