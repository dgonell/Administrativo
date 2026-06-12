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
  event.target.value = ''
}
</script>

<template>
  <section class="modal-section">
    <form class="form-grid compact-form" @submit.prevent="emit('submit')">
      <label>Fecha <input v-model="form.event_date" type="date" required /></label>
      <label>Tipo <input v-model="form.type" required /></label>
      <label>
        Gravedad
        <select v-model="form.severity">
          <option value="low">Baja</option>
          <option value="medium">Media</option>
          <option value="high">Alta</option>
          <option value="critical">Critica</option>
        </select>
      </label>
      <label>
        Estado
        <select v-model="form.status">
          <option value="open">Abierto</option>
          <option value="reviewed">Revisado</option>
          <option value="closed">Cerrado</option>
        </select>
      </label>
      <label class="span-2">Descripcion <textarea v-model="form.description" required></textarea></label>
      <label class="attachment-control span-2">
        Adjuntos
        <input type="file" multiple @change="attachFiles" />
        <span>Adjuntar varios documentos</span>
        <small>{{ form.file_path || 'Sin adjuntos seleccionados' }}</small>
      </label>
      <div class="form-actions span-2"><button type="submit" :disabled="isSaving">Agregar</button></div>
    </form>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Gravedad</th><th>Estado</th></tr></thead>
        <tbody>
          <tr v-for="report in driver.conduct_reports ?? []" :key="report.id">
            <td>{{ normalizeDate(report.event_date) }}</td>
            <td>{{ report.type }}</td>
            <td>{{ labelFor(report.severity) }}</td>
            <td>{{ labelFor(report.status) }}</td>
          </tr>
          <tr v-if="!driver.conduct_reports?.length"><td colspan="4">Sin reportes.</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
