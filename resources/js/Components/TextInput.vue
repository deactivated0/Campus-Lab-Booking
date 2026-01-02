<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="mt-1 block w-full rounded-md bg-white/5 border border-white/10 text-slate-100 placeholder:text-slate-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2"
        v-model="model"
        ref="input"
    />
</template>
