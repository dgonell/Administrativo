<script setup>
import { computed } from 'vue'
import { normalizeDate } from '../../utils/dates'
import { labelFor } from '../../utils/labels'

const props = defineProps({
  driver: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['go-to'])

const metricLinks = [
  { label: 'Documentos', valueKey: 'documents', tab: 'documentos' },
  { label: 'Permisos', valueKey: 'medical_leaves', tab: 'permisos' },
  { label: 'Conducta', valueKey: 'conduct_reports', tab: 'conducta' },
]

const driverTimeline = computed(() => {
  const statusEvents = (props.driver.status_histories ?? []).map((history) => ({
    id: `status-${history.id}`,
    date: history.created_at,
    type: history.previous_status ? `${labelFor(history.previous_status)} a ${labelFor(history.new_status)}` : labelFor(history.new_status),
    detail: history.reason ?? 'Movimiento de estado',
  }))

  const terminationEvents = (props.driver.termination_records ?? []).map((record) => ({
    id: `termination-${record.id}`,
    date: record.termination_date,
    type: labelFor(record.termination_type),
    detail: record.reason,
  }))

  return [...statusEvents, ...terminationEvents]
    .filter((event) => event.date)
    .sort((a, b) => new Date(b.date) - new Date(a.date))
})
</script>

<template>
  <section class="complete-summary">
    <div class="summary-hero">
      <div>
        <p class="eyebrow">Vista completa</p>
        <h3>{{ driver.first_name }} {{ driver.last_name }}</h3>
        <p>{{ driver.code }} - {{ driver.identity_document }}</p>
      </div>
      <span class="badge" :class="`status-${driver.status}`">{{ labelFor(driver.status) }}</span>
    </div>

    <div class="summary-metrics">
      <button
        v-for="metric in metricLinks"
        :key="metric.tab"
        type="button"
        class="summary-metric-link"
        @click="emit('go-to', metric.tab)"
      >
        <span>{{ metric.label }}</span>
        <strong>{{ driver[metric.valueKey]?.length ?? 0 }}</strong>
      </button>
    </div>

    <div class="summary-grid">
      <article class="summary-section">
        <h4>Datos personales</h4>
        <dl>
          <div><dt>Telefono</dt><dd>{{ driver.phone ?? 'Pendiente' }}</dd></div>
          <div><dt>Contacto</dt><dd>{{ driver.contact_name }}</dd></div>
          <div><dt>Correo</dt><dd>{{ driver.email ?? 'Pendiente' }}</dd></div>
          <div><dt>Direccion</dt><dd>{{ driver.address ?? 'Pendiente' }}</dd></div>
          <div><dt>Nacimiento</dt><dd>{{ normalizeDate(driver.birth_date) || 'Pendiente' }}</dd></div>
          <div><dt>Emergencia</dt><dd>{{ driver.emergency_contacts?.[0]?.name ?? 'Pendiente' }}</dd></div>
          <div><dt>Telefono emergencia</dt><dd>{{ driver.emergency_contacts?.[0]?.phone ?? 'Pendiente' }}</dd></div>
        </dl>
      </article>

      <article class="summary-section">
        <h4>Operacion</h4>
        <dl>
          <div><dt>Departamento</dt><dd>{{ driver.department?.name ?? 'Sin asignar' }}</dd></div>
          <div><dt>Codigo trabajador TSS</dt><dd>{{ driver.tss_worker_code }}</dd></div>
          <div><dt>Cargo</dt><dd>{{ driver.position?.name ?? 'Sin asignar' }}</dd></div>
          <div><dt>Contrato</dt><dd>{{ driver.contract_type?.name ?? 'Sin asignar' }}</dd></div>
          <div><dt>Ingreso</dt><dd>{{ normalizeDate(driver.hire_date) || 'Pendiente' }}</dd></div>
          <div><dt>Recontratacion</dt><dd>{{ labelFor(driver.rehire_status) }}</dd></div>
        </dl>
      </article>

      <article class="summary-section">
        <h4>Licencia</h4>
        <dl>
          <div><dt>Numero</dt><dd>{{ driver.license?.license_number ?? 'Pendiente' }}</dd></div>
          <div><dt>Categoria</dt><dd>{{ driver.license?.category ?? 'Pendiente' }}</dd></div>
          <div><dt>Emision</dt><dd>{{ normalizeDate(driver.license?.issued_at) || 'Pendiente' }}</dd></div>
          <div><dt>Vencimiento</dt><dd>{{ normalizeDate(driver.license?.expires_at) || 'Pendiente' }}</dd></div>
          <div><dt>Entidad</dt><dd>{{ driver.license?.issuing_entity ?? 'Pendiente' }}</dd></div>
          <div><dt>Restricciones</dt><dd>{{ driver.license?.restrictions ?? 'Sin restricciones' }}</dd></div>
        </dl>
      </article>

      <article class="summary-section">
        <h4>Ultimos registros</h4>
        <ul class="summary-list">
          <li v-for="document in (driver.documents ?? []).slice(0, 3)" :key="`doc-${document.id}`">
            <button type="button" class="summary-list-link" @click="emit('go-to', 'documentos')">
              <strong>{{ document.name }}</strong>
              <span>{{ labelFor(document.status) }} - vence {{ normalizeDate(document.expires_at) || 'no aplica' }}</span>
            </button>
          </li>
          <li v-for="report in (driver.conduct_reports ?? []).slice(0, 2)" :key="`report-${report.id}`">
            <button type="button" class="summary-list-link" @click="emit('go-to', 'conducta')">
              <strong>{{ report.type }}</strong>
              <span>{{ normalizeDate(report.event_date) }} - {{ labelFor(report.severity) }}</span>
            </button>
          </li>
          <li v-if="!(driver.documents?.length || driver.conduct_reports?.length)"><span>Sin registros recientes.</span></li>
        </ul>
      </article>
    </div>

    <article class="summary-section span-summary profile-timeline">
      <div class="section-header">
        <div>
          <p class="eyebrow">Linea de tiempo</p>
          <h4>Historial laboral</h4>
        </div>
        <span class="status">{{ driverTimeline.length }} eventos</span>
      </div>
      <ol>
        <li v-for="event in driverTimeline" :key="event.id">
          <time>{{ normalizeDate(event.date) }}</time>
          <div>
            <strong>{{ event.type }}</strong>
            <span>{{ event.detail }}</span>
          </div>
        </li>
        <li v-if="driverTimeline.length === 0">
          <time>--</time>
          <div><span>Sin historial laboral registrado.</span></div>
        </li>
      </ol>
    </article>

    <article class="summary-section span-summary">
      <h4>Salida</h4>
      <div>
        <ul class="summary-list">
          <li v-for="record in driver.termination_records ?? []" :key="`termination-${record.id}`">
            <strong>{{ labelFor(record.termination_type) }}</strong>
            <span>{{ normalizeDate(record.termination_date) }} - {{ record.reason }}</span>
          </li>
          <li v-if="!driver.termination_records?.length"><span>Sin salidas registradas.</span></li>
        </ul>
      </div>
    </article>
  </section>
</template>
