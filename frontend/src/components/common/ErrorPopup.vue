<script setup>
defineProps({
  message: {
    type: String,
    default: '',
  },
})

defineEmits(['close'])
</script>

<template>
  <div v-if="message" class="popup-backdrop" role="alertdialog" aria-modal="true" aria-labelledby="error-popup-title">
    <section class="popup">
      <header class="popup-header">
        <h3 id="error-popup-title">Error de conexion</h3>
        <button type="button" class="close-button" aria-label="Cerrar mensaje" @click="$emit('close')">x</button>
      </header>
      <div class="popup-body">
        <p>{{ message }}</p>
        <button type="button" @click="$emit('close')">Entendido</button>
      </div>
    </section>
  </div>
</template>
