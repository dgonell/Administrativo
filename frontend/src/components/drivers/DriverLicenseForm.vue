<script setup>
defineProps({
  form: {
    type: Object,
    required: true,
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['submit'])
</script>

<template>
  <form class="form-grid compact-form" @submit.prevent="emit('submit')">
    <label>Licencia <input :value="form.identity_document" disabled /></label>
    <label>Categoria <input v-model="form.license.category" /></label>
    <label>Emision <input v-model="form.license.issued_at" type="date" /></label>
    <label>Vencimiento <input v-model="form.license.expires_at" type="date" /></label>
    <label>Entidad <input v-model="form.license.issuing_entity" /></label>
    <label class="span-2">Restricciones <textarea v-model="form.license.restrictions"></textarea></label>
    <div class="form-actions span-2"><button type="submit" :disabled="isSaving">Actualizar</button></div>
  </form>
</template>
