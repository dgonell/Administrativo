<script setup>
import { normalizeDate } from '../../utils/dates'
import { labelFor } from '../../utils/labels'

const props = defineProps({
  driver: {
    type: Object,
    required: true,
  },
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

function attachFiles(event) {
  const files = Array.from(event.target.files ?? [])
  if (!files.length) return

  props.form.file_path = files.map((file) => file.name).join(', ')
  if (!props.form.name) {
    props.form.name = files.length === 1 ? files[0].name : `${files.length} documentos adjuntos`
  }
  event.target.value = ''
}
</script>

<template>
  <section class="modal-section">
    <form class="form-grid compact-form" @submit.prevent="emit('submit')">
      <label>Tipo <input v-model="form.document_type" required /></label>
      <label>Nombre <input v-model="form.name" required /></label>
      <label class="attachment-control span-2">
        Adjuntos
        <input type="file" multiple @change="attachFiles" />
        <span>Adjuntar varios documentos</span>
        <small>{{ form.file_path || 'Sin adjuntos seleccionados' }}</small>
      </label>
      <label>Vence <input v-model="form.expires_at" type="date" /></label>
      <label>
        Estado
        <select v-model="form.status">
          <option value="valid">Vigente</option>
          <option value="pending">Pendiente</option>
          <option value="expired">Vencido</option>
          <option value="replaced">Reemplazado</option>
        </select>
      </label>
      <div class="form-actions"><button type="submit" :disabled="isSaving">Agregar</button></div>
    </form>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tipo</th><th>Nombre</th><th>Vence</th><th>Estado</th></tr></thead>
        <tbody>
          <tr v-for="document in driver.documents ?? []" :key="document.id">
            <td>{{ document.document_type }}</td>
            <td>{{ document.name }}</td>
            <td>{{ normalizeDate(document.expires_at) || 'No aplica' }}</td>
            <td>{{ labelFor(document.status) }}</td>
          </tr>
          <tr v-if="!driver.documents?.length"><td colspan="4">Sin documentos.</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
