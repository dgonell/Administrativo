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
      <label>Tipo <input v-model="form.leave_type" required /></label>
      <label>Inicio <input v-model="form.started_at" type="date" required /></label>
      <label>Fin <input v-model="form.ended_at" type="date" /></label>
      <label>Motivo <input v-model="form.reason" /></label>
      <label>
        Estado
        <select v-model="form.status">
          <option value="pending">Pendiente</option>
          <option value="approved">Aprobado</option>
          <option value="rejected">Rechazado</option>
        </select>
      </label>
      <label class="attachment-control span-2">
        Adjuntos
        <input type="file" multiple @change="attachFiles" />
        <span>Adjuntar varios documentos</span>
        <small>{{ form.file_path || 'Sin adjuntos seleccionados' }}</small>
      </label>
      <div class="form-actions"><button type="submit" :disabled="isSaving">Agregar</button></div>
    </form>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tipo</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr></thead>
        <tbody>
          <tr v-for="leave in driver.medical_leaves ?? []" :key="leave.id">
            <td>{{ leave.leave_type }}</td>
            <td>{{ normalizeDate(leave.started_at) }}</td>
            <td>{{ normalizeDate(leave.ended_at) || 'Abierto' }}</td>
            <td>{{ labelFor(leave.status) }}</td>
          </tr>
          <tr v-if="!driver.medical_leaves?.length"><td colspan="4">Sin permisos.</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
