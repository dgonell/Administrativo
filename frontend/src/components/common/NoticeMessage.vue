<script setup>
defineProps({
  message: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'error',
  },
})
</script>

<template>
  <p v-if="message" class="notice" :class="{ success: type === 'success' }">{{ message }}</p>
</template>
