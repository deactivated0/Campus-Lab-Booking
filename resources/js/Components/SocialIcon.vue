<script setup>
const props = defineProps({ provider: { type: String, required: true } })

const brand = props.provider.toLowerCase()
const colors = {
  github: '#000000',
  google: '#FFFFFF',
  facebook: '#1877F2',
  twitter: '#000000',
  apple: '#000000',
}
const labels = {
  github: 'GH',
  google: 'G',
  facebook: 'F',
  twitter: 'X',
  apple: 'Apple',
}
</script>

<template>
  <svg :class="$attrs.class" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true">
    <rect x="0" y="0" width="24" height="24" rx="5" :fill="colors[brand]" />
    <text x="12" y="16" text-anchor="middle" font-size="11" font-family="ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial" :fill="brand==='google' ? '#000' : '#fff'">{{ labels[brand] }}</text>
  </svg>
</template>
