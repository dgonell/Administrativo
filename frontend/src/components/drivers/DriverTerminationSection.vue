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
  rehireForm: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['submit', 'submit-rehire'])

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
      <label>Fecha salida <input v-model="form.termination_date" type="date" required /></label>
      <label>
        Tipo
        <select v-model="form.termination_type">
          <option value="resignation">Renuncia</option>
          <option value="dismissal">Despido</option>
        </select>
      </label>
      <label>Razon <input v-model="form.reason" required /></label>
      <label>
        Recontratar
        <select v-model="form.rehire_status">
          <option value="review">Revisar</option>
          <option value="yes">Apto</option>
          <option value="no">No apto</option>
        </select>
      </label>
      <label class="span-2">Motivo <textarea v-model="form.rehire_reason"></textarea></label>
      <label class="attachment-control span-2">
        Adjuntos
        <input type="file" multiple @change="attachFiles" />
        <span>Adjuntar varios documentos</span>
        <small>{{ form.file_path || 'Sin adjuntos seleccionados' }}</small>
      </label>
      <div class="form-actions span-2"><button type="submit" :disabled="isSaving">Registrar salida</button></div>
    </form>

    <form v-if="driver.status !== 'active'" class="form-grid compact-form rehire-panel" @submit.prevent="emit('submit-rehire')">
      <div class="span-2">
        <p class="eyebrow">Recontratacion</p>
        <h4>Volver a contratar</h4>
      </div>
      <label>Fecha reingreso <input v-model="rehireForm.hire_date" type="date" required /></label>
      <label>
        Evaluacion
        <select v-model="rehireForm.rehire_status">
          <option value="yes">Apto</option>
          <option value="review">En revision</option>
        </select>
      </label>
      <label>Motivo <input v-model="rehireForm.reason" placeholder="Vacante disponible, retorno aprobado..." /></label>
      <label>Notas <input v-model="rehireForm.notes" /></label>
      <div class="form-actions span-2"><button type="submit" :disabled="isSaving">Recontratar</button></div>
    </form>

    <div class="table-wrap">
      <table>
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Razon</th><th>Recontratar</th></tr></thead>
        <tbody>
          <tr v-for="record in driver.termination_records ?? []" :key="record.id">
            <td>{{ normalizeDate(record.termination_date) }}</td>
            <td>{{ labelFor(record.termination_type) }}</td>
            <td>{{ record.reason }}</td>
            <td>{{ labelFor(record.rehire_status) }}</td>
          </tr>
          <tr v-if="!driver.termination_records?.length"><td colspan="4">Sin salidas.</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
