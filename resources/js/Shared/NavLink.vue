<template>
  <a
    :href="href"
    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition"
    :class="active ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'"
  >
    <span class="h-2 w-2 rounded-full" :class="active ? 'bg-indigo-400' : 'bg-slate-600'"></span>
    <span><slot /></span>
  </a>
</template>

<script setup>
defineProps({
  href: { type: String, required: true },
  active: { type: Boolean, default: false },
})
</script>
