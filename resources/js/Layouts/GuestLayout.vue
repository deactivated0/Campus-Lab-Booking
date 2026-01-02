<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="min-h-screen bg-[#0b0e14] text-slate-100 flex items-center justify-center py-10">
    <div class="w-full max-w-md px-6">
      <div class="flex items-center gap-4 mb-6">
        <Link href="/" class="flex items-center gap-3">
          <ApplicationLogo class="h-12 w-12 text-indigo-300" />
          <div>
            <div class="text-lg font-semibold">CampusLab <span class="text-indigo-300">Sync</span></div>
            <div class="text-xs text-slate-400">Book & manage equipment</div>
          </div>
        </Link>
      </div>

      <div class="rounded-xl border border-white/10 bg-white/5 p-6 shadow-sm">
        <slot />
      </div>

      <div class="mt-6 text-center text-xs text-slate-500">
        <p>Need help? Contact your lab administrator.</p>
      </div>
    </div>
  </div>
</template>