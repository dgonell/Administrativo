<script setup>
import { DRIVER_MODULES } from '../../constants/navigation'
import { normalizeDate } from '../../utils/dates'
import { labelFor } from '../../utils/labels'

defineProps({
  drivers: {
    type: Array,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['open-driver-modal'])

const rowActions = [
  { mode: DRIVER_MODULES.file, label: 'Exp', tooltip: 'Expediente completo' },
  { mode: DRIVER_MODULES.license, label: 'Lic', tooltip: 'Licencia de conducir' },
  { mode: DRIVER_MODULES.documents, label: 'Doc', tooltip: 'Documentos del chofer' },
  { mode: DRIVER_MODULES.leaves, label: 'Perm', tooltip: 'Permisos medicos y personales' },
  { mode: DRIVER_MODULES.conduct, label: 'Cond', tooltip: 'Conducta e incidentes' },
  { mode: DRIVER_MODULES.termination, label: 'Salida', tooltip: 'Desvinculacion y recontratacion' },
]
</script>

<template>
  <section class="panel">
    <div class="panel-header compact-header">
      <div>
        <h3>Choferes</h3>
        <p>Todos los detalles se gestionan desde los botones de cada fila.</p>
      </div>
      <span class="status">{{ isLoading ? 'Cargando' : 'Servicio listo' }}</span>
    </div>

    <div class="table-wrap">
      <table class="driver-table">
        <thead>
          <tr>
            <th>Codigo</th>
            <th>Chofer</th>
            <th>Contacto</th>
            <th>Licencia</th>
            <th>Estado</th>
            <th>Registros</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!isLoading && drivers.length === 0">
            <td colspan="7">Aun no hay choferes registrados.</td>
          </tr>
          <tr v-for="driver in drivers" :key="driver.id">
            <td><strong>{{ driver.code }}</strong></td>
            <td>
              <div class="stacked-cell">
                <strong>{{ driver.first_name }} {{ driver.last_name }}</strong>
                <span>CI {{ driver.identity_document }}</span>
              </div>
            </td>
            <td>
              <div class="stacked-cell">
                <span>{{ driver.phone ?? 'Sin telefono' }}</span>
                <span>{{ driver.email ?? 'Sin correo' }}</span>
              </div>
            </td>
            <td>
              <div class="stacked-cell">
                <strong>{{ driver.license?.license_number ?? 'Pendiente' }}</strong>
                <span>{{ driver.license?.category ?? 'Sin categoria' }} - {{ normalizeDate(driver.license?.expires_at) || 'Sin fecha' }}</span>
              </div>
            </td>
            <td><span class="badge" :class="`status-${driver.status}`">{{ labelFor(driver.status) }}</span></td>
            <td>
              <div class="record-pills">
                <span>Docs {{ driver.documents_count ?? 0 }}</span>
                <span>Perm {{ driver.medical_leaves_count ?? 0 }}</span>
                <span>Cond {{ driver.conduct_reports_count ?? 0 }}</span>
                <span>{{ labelFor(driver.rehire_status) }}</span>
              </div>
            </td>
            <td>
              <div class="row-actions">
                <button
                  v-for="action in rowActions"
                  :key="action.mode"
                  type="button"
                  :title="action.tooltip"
                  :aria-label="action.tooltip"
                  :data-tooltip="action.tooltip"
                  @click="emit('open-driver-modal', action.mode, driver)"
                >
                  {{ action.label }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
