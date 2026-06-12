<script setup>
import { computed, ref } from 'vue'
import { normalizeDate } from '../../utils/dates'

const props = defineProps({
  maintenances: {
    type: Array,
    required: true,
  },
  buses: {
    type: Array,
    required: true,
  },
})

const search = ref('')
const selectedBus = ref('')
const serviceType = ref('')
const dueState = ref('')
const costFrom = ref('')
const costTo = ref('')

function dateInputValue(date) {
  return date.toISOString().slice(0, 10)
}

function currentMonthRange() {
  const today = new Date()
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
  const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0)
  return {
    from: dateInputValue(firstDay),
    to: dateInputValue(lastDay),
  }
}

const defaultPeriod = currentMonthRange()
const periodFrom = ref(defaultPeriod.from)
const periodTo = ref(defaultPeriod.to)

const serviceTypes = [
  { value: '', label: 'Todos los servicios' },
  { value: 'preventive', label: 'Mantenimiento preventivo' },
  { value: 'oil_change', label: 'Cambio de aceite' },
  { value: 'filter_change', label: 'Cambio de filtros' },
  { value: 'tire_change', label: 'Cambio de neumaticos' },
  { value: 'part_replacement', label: 'Cambio de piezas' },
  { value: 'repair', label: 'Reparacion' },
  { value: 'other', label: 'Otro servicio' },
]

const busOptions = computed(() => {
  return [...new Set(props.maintenances.map((item) => item.bus?.fleet_number).filter(Boolean))].sort()
})

function serviceLabel(value) {
  return serviceTypes.find((item) => item.value === value)?.label || value
}

function maintenanceTotal(service) {
  return Number(service.labor_cost || 0) + (service.items ?? []).reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_cost || 0), 0)
}

const filteredMaintenances = computed(() => {
  const term = search.value.trim().toLowerCase()
  const today = new Date()
  const dueWindow = new Date(today)
  dueWindow.setDate(today.getDate() + 30)

  return props.maintenances.filter((service) => {
    const busName = service.bus?.fleet_number ?? ''
    const itemNames = (service.items ?? []).map((item) => item.name).join(' ')
    const text = [busName, service.bus?.brand, service.bus?.model, service.workshop, service.technician, serviceLabel(service.service_type), itemNames, service.notes].join(' ').toLowerCase()

    if (search.value && !text.includes(term)) return false
    if (selectedBus.value && service.bus?.fleet_number !== selectedBus.value) return false
    if (serviceType.value && service.service_type !== serviceType.value) return false
    if (periodFrom.value && service.service_date < periodFrom.value) return false
    if (periodTo.value && service.service_date > periodTo.value) return false
    const totalCost = maintenanceTotal(service)
    if (costFrom.value && totalCost < Number(costFrom.value)) return false
    if (costTo.value && totalCost > Number(costTo.value)) return false

    if (dueState.value) {
      const dueDate = service.next_due_date ? new Date(service.next_due_date) : null
      const dueMileage = service.next_due_mileage ?? null
      const currentMileage = service.bus?.current_mileage ?? 0
      const overdue = (dueDate && dueDate < today) || (dueMileage !== null && currentMileage >= dueMileage)
      const dueSoon = (dueDate && dueDate >= today && dueDate <= dueWindow) || (dueMileage !== null && dueMileage > currentMileage && dueMileage <= currentMileage + 500)
      const hasDue = dueDate || dueMileage !== null

      if (dueState.value === 'overdue' && !overdue) return false
      if (dueState.value === 'due_soon' && !dueSoon) return false
      if (dueState.value === 'planned' && !hasDue) return false
    }

    return true
  }).sort((a, b) => new Date(b.service_date) - new Date(a.service_date))
})

const reportMetrics = computed(() => {
  const total = filteredMaintenances.value.length
  const totalCost = filteredMaintenances.value.reduce((sum, service) => sum + maintenanceTotal(service), 0)
  const preventive = filteredMaintenances.value.filter((service) => service.service_type === 'preventive').length
  const repairs = filteredMaintenances.value.filter((service) => service.service_type === 'repair').length
  const replacements = filteredMaintenances.value.filter((service) => ['part_replacement', 'tire_change', 'filter_change', 'oil_change'].includes(service.service_type)).length
  const dueSoon = filteredMaintenances.value.filter((service) => {
    const today = new Date()
    const dueWindow = new Date(today)
    dueWindow.setDate(today.getDate() + 30)
    const dueDate = service.next_due_date ? new Date(service.next_due_date) : null
    const dueMileage = service.next_due_mileage ?? null
    const currentMileage = service.bus?.current_mileage ?? 0
    return (dueDate && dueDate >= today && dueDate <= dueWindow) || (dueMileage !== null && dueMileage > currentMileage && dueMileage <= currentMileage + 500)
  }).length
  const busesServiced = new Set(filteredMaintenances.value.map((service) => service.bus?.fleet_number).filter(Boolean)).size
  const averageCost = total ? totalCost / total : 0

  return [
    { label: 'Servicios revisados', value: total, link: 'total' },
    { label: 'Gasto total', value: `RD$ ${totalCost.toLocaleString('es-DO')}` },
    { label: 'Costo promedio', value: `RD$ ${Math.round(averageCost).toLocaleString('es-DO')}` },
    { label: 'Preventivos', value: preventive, link: 'preventive' },
    { label: 'Reparaciones', value: repairs, link: 'repair' },
    { label: 'Reemplazos', value: replacements },
    { label: 'Unidades con mant.', value: busesServiced },
    { label: 'Próximos vencimientos', value: dueSoon, link: 'due_soon' },
  ]
})

const dueCounts = computed(() => {
  const today = new Date()
  const dueWindow = new Date(today)
  dueWindow.setDate(today.getDate() + 30)
  const counts = { overdue: 0, dueSoon: 0, planned: 0 }

  filteredMaintenances.value.forEach((service) => {
    const dueDate = service.next_due_date ? new Date(service.next_due_date) : null
    const dueMileage = service.next_due_mileage ?? null
    const currentMileage = service.bus?.current_mileage ?? 0
    const overdue = (dueDate && dueDate < today) || (dueMileage !== null && currentMileage >= dueMileage)
    const dueSoon = (dueDate && dueDate >= today && dueDate <= dueWindow) || (dueMileage !== null && dueMileage > currentMileage && dueMileage <= currentMileage + 500)

    if (overdue) counts.overdue += 1
    else if (dueSoon) counts.dueSoon += 1
    else if (dueDate || dueMileage !== null) counts.planned += 1
  })

  return counts
})

const serviceTypeDistribution = computed(() => {
  const counts = serviceTypes.filter((type) => type.value).map((type) => {
    const value = filteredMaintenances.value.filter((service) => service.service_type === type.value).length
    return { label: type.label, value }
  })
  const max = Math.max(...counts.map((item) => item.value), 1)
  return counts.map((item) => ({ ...item, width: `${Math.round((item.value / max) * 100)}%` }))
})

const reportInsights = computed(() => {
  const total = filteredMaintenances.value.length
  const totalCost = filteredMaintenances.value.reduce((sum, service) => sum + maintenanceTotal(service), 0)
  const average = total ? Math.round(totalCost / total) : 0
  const topCost = filteredMaintenances.value.reduce((prev, service) => {
    const cost = maintenanceTotal(service)
    return cost > prev.cost ? { cost, service } : prev
  }, { cost: 0, service: null })
  const topTitle = topCost.service ? `${topCost.service.bus?.fleet_number ?? 'Unidad'} - ${serviceLabel(topCost.service.service_type)}` : 'Sin registros'

  return [
    { label: 'Servicio más caro', value: topTitle },
    { label: 'Costo promedio por servicio', value: `RD$ ${average.toLocaleString('es-DO')}` },
    { label: 'Unidades con mantenimiento', value: new Set(filteredMaintenances.value.map((service) => service.bus?.fleet_number).filter(Boolean)).size },
  ]
})

const activeFilters = computed(() => {
  const filters = [
    search.value ? `Busqueda: ${search.value}` : null,
    selectedBus.value ? `Unidad: ${selectedBus.value}` : null,
    serviceType.value ? `Servicio: ${serviceLabel(serviceType.value)}` : null,
    periodFrom.value ? `Desde: ${periodFrom.value}` : null,
    periodTo.value ? `Hasta: ${periodTo.value}` : null,
    costFrom.value ? `Costo min: RD$ ${costFrom.value}` : null,
    costTo.value ? `Costo max: RD$ ${costTo.value}` : null,
    dueState.value ? `Próxima revisión: ${dueState.value === 'overdue' ? 'Atrasado' : dueState.value === 'due_soon' ? 'Próximo' : 'Planificado'}` : null,
  ].filter(Boolean)
  return filters.length ? filters : ['Todos los servicios de mantenimiento']
})

function clearFilters() {
  const defaultPeriod = currentMonthRange()
  search.value = ''
  selectedBus.value = ''
  serviceType.value = ''
  dueState.value = ''
  periodFrom.value = defaultPeriod.from
  periodTo.value = defaultPeriod.to
  costFrom.value = ''
  costTo.value = ''
}

function applyMetricLink(link) {
  if (link === 'total') { clearFilters(); return }
  if (link === 'preventive') { serviceType.value = 'preventive'; return }
  if (link === 'repair') { serviceType.value = 'repair'; return }
  if (link === 'due_soon') { dueState.value = 'due_soon'; return }
}

function reportRows() {
  return filteredMaintenances.value.map((service) => ({
    fecha: normalizeDate(service.service_date),
    unidad: service.bus?.fleet_number ?? 'Sin unidad',
    tipo: serviceLabel(service.service_type),
    kilometraje: service.mileage ? `${service.mileage.toLocaleString('es-DO')} km` : 'Pendiente',
    costo: `RD$ ${maintenanceTotal(service).toLocaleString('es-DO')}`,
    taller: service.workshop || 'Sin taller',
    tecnico: service.technician || 'Sin tecnico',
    proximas: service.next_due_date ? normalizeDate(service.next_due_date) : service.next_due_mileage ? `${service.next_due_mileage.toLocaleString('es-DO')} km` : 'No aplica',
    observaciones: service.notes || 'Sin observaciones',
  }))
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function reportFileName(extension) {
  const date = new Date().toISOString().slice(0, 10)
  return `reporte-mantenimientos-${date}.${extension}`
}

function metricsHtml() {
  return reportMetrics.value
    .map((metric) => `<article><span>${escapeHtml(metric.label)}</span><strong>${escapeHtml(metric.value)}</strong></article>`)
    .join('')
}

function tableHtml() {
  const rows = reportRows()
    .map((row) => `
      <tr>
        <td><strong>${escapeHtml(row.unidad)}</strong><br><span>${escapeHtml(row.tipo)}</span></td>
        <td>${escapeHtml(row.fecha)}<br><span>${escapeHtml(row.kilometraje)}</span></td>
        <td>${escapeHtml(row.costo)}<br><span>${escapeHtml(row.proximas)}</span></td>
        <td>${escapeHtml(row.taller)}<br><span>${escapeHtml(row.tecnico)}</span></td>
        <td>${escapeHtml(row.observaciones)}</td>
      </tr>
    `)
    .join('')

  return `
    <table>
      <thead>
        <tr>
          <th>Unidad / Servicio</th>
          <th>Fecha / Km</th>
          <th>Costo / Pr&oacute;ximo</th>
          <th>Taller / T&eacute;cnico</th>
          <th>Observaciones</th>
        </tr>
      </thead>
      <tbody>${rows || '<tr><td colspan="5">Sin resultados para los filtros seleccionados.</td></tr>'}</tbody>
    </table>
  `
}

function reportHtml() {
  return `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>Reporte ejecutivo de mantenimientos</title>
        <style>
          body { color: #17233d; font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 28px; }
          header { border-bottom: 4px solid #2563eb; margin-bottom: 18px; padding-bottom: 16px; }
          .brand { color: #2563eb; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; }
          h1 { font-size: 28px; margin: 6px 0; }
          .meta { color: #475569; font-size: 12px; margin-top: 4px; }
          .filters { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 12px; margin: 18px 0; padding: 14px; }
          .metrics { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 20px 0; }
          .metrics article { border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; background: #ffffff; }
          .metrics span { color: #64748b; display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
          .metrics strong { color: #0f172a; display: block; font-size: 22px; margin-top: 8px; }
          table { border-collapse: collapse; font-size: 11px; width: 100%; }
          th { background: #1d4ed8; color: #fff; padding: 10px 12px; text-align: left; font-weight: 700; }
          td { border-bottom: 1px solid #e2e8f0; padding: 12px; vertical-align: top; }
          td span { color: #475569; font-size: 11px; }
          @media print { .no-print { display: none; } body { margin: 18px; } }
        </style>
      </head>
      <body>
        <header>
          <div class="brand">Mantenimiento de flota</div>
          <h1>Reporte ejecutivo de mantenimientos</h1>
          <div class="meta">Generado: ${escapeHtml(new Date().toLocaleString())} | Registros: ${escapeHtml(filteredMaintenances.value.length)}</div>
        </header>
        <section class="filters"><strong>Filtros:</strong> ${activeFilters.value.map((filter) => escapeHtml(filter)).join(' | ')}</section>
        <section class="metrics">${metricsHtml()}</section>
        ${tableHtml()}
      </body>
    </html>
  `
}

function downloadExcel() {
  const blob = new Blob([reportHtml()], { type: 'application/vnd.ms-excel;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = reportFileName('xls')
  link.click()
  URL.revokeObjectURL(url)
}

function downloadPdf() {
  const printWindow = window.open('', '_blank')
  if (!printWindow) return
  printWindow.document.open()
  printWindow.document.write(reportHtml())
  printWindow.document.close()
  printWindow.focus()
  printWindow.print()
}
</script>

<template>
  <section class="report-page">
    <section class="filter-panel card">
      <header class="filter-panel-header">
        <div>
          <p class="eyebrow">Filtros</p>
          <h3>Consulta operativa</h3>
        </div>
        <button type="button" class="text-button" @click="clearFilters">Limpiar filtros</button>
      </header>

      <div class="filter-grid">
        <label class="filter-search">
          <span>Buscar</span>
          <input v-model="search" placeholder="Unidad, taller, técnico o insumo" />
        </label>

        <label>
          <span>Unidad</span>
          <select v-model="selectedBus">
            <option value="">Todas las unidades</option>
            <option v-for="fleet in busOptions" :key="fleet" :value="fleet">{{ fleet }}</option>
          </select>
        </label>

        <label>
          <span>Servicio</span>
          <select v-model="serviceType">
            <option v-for="type in serviceTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
          </select>
        </label>

        <div class="filter-segments">
          <span>Próxima revisión</span>
          <div>
            <button type="button" :class="{ active: dueState === '' }" @click="dueState = ''">Todos</button>
            <button type="button" :class="{ active: dueState === 'planned' }" @click="dueState = 'planned'">Planificado</button>
            <button type="button" :class="{ active: dueState === 'due_soon' }" @click="dueState = 'due_soon'">Próximo</button>
            <button type="button" :class="{ active: dueState === 'overdue' }" @click="dueState = 'overdue'">Atrasado</button>
          </div>
        </div>

        <label>
          <span>Desde</span>
          <input type="date" v-model="periodFrom" />
        </label>
        <label>
          <span>Hasta</span>
          <input type="date" v-model="periodTo" />
        </label>
        <label>
          <span>Costo mínimo</span>
          <input type="number" min="0" step="100" v-model="costFrom" />
        </label>
        <label>
          <span>Costo máximo</span>
          <input type="number" min="0" step="100" v-model="costTo" />
        </label>
      </div>

      <div class="active-filter-row">
        <span v-for="tag in activeFilters" :key="tag">{{ tag }}</span>
      </div>
    </section>

    <div class="alert-row" v-if="dueCounts.overdue || dueCounts.dueSoon">
      <button v-if="dueCounts.overdue" type="button" class="alert-card warning" @click="dueState = 'overdue'">
        <strong>{{ dueCounts.overdue }}</strong>
        <span>servicios vencidos pendientes</span>
      </button>
      <button v-if="dueCounts.dueSoon" type="button" class="alert-card info" @click="dueState = 'due_soon'">
        <strong>{{ dueCounts.dueSoon }}</strong>
        <span>servicios próximos en 30 días</span>
      </button>
    </div>

    <div class="metric-grid">
      <button
        v-for="metric in reportMetrics"
        :key="metric.label"
        type="button"
        class="metric-card"
        :class="{ clickable: metric.link }"
        :disabled="!metric.link"
        @click="metric.link && applyMetricLink(metric.link)"
      >
        <span>{{ metric.label }}</span>
        <strong>{{ metric.value }}</strong>
      </button>
    </div>

    <div class="analysis-grid">
      <section class="card insight-panel">
        <header class="section-header">
          <div>
            <p class="eyebrow">Resumen</p>
            <h3>Indicadores clave</h3>
          </div>
        </header>
        <div class="insight-list">
          <article v-for="insight in reportInsights" :key="insight.label">
            <span>{{ insight.label }}</span>
            <strong>{{ insight.value }}</strong>
          </article>
        </div>
      </section>

      <section class="card distribution-panel">
        <header class="section-header">
          <div>
            <p class="eyebrow">Distribución</p>
            <h3>Servicios por tipo</h3>
          </div>
        </header>
        <div class="distribution-list">
          <div class="distribution-row" v-for="item in serviceTypeDistribution" :key="item.label">
            <span>{{ item.label }}</span>
            <div class="distribution-bar">
              <div class="distribution-bar-fill" :style="{ width: item.width }"></div>
            </div>
            <strong>{{ item.value }}</strong>
          </div>
        </div>
      </section>
    </div>

    <section class="card report-results">
      <header class="section-header report-results-header">
        <div>
          <p class="eyebrow">Resultados</p>
          <h3>Servicios filtrados</h3>
        </div>
        <div class="result-actions">
          <span class="status">{{ filteredMaintenances.length }} registros</span>
          <button type="button" class="secondary-action icon-action" title="Exportar PDF" @click="downloadPdf">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6M8 16h8M8 12h8" /></svg>
            PDF
          </button>
          <button type="button" class="secondary-action icon-action" title="Exportar Excel" @click="downloadExcel">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4Z" /><path d="M8 8h8M8 12h8M8 16h8M12 4v16" /></svg>
            Excel
          </button>
        </div>
      </header>

      <div class="table-wrap">
        <table class="report-table">
          <thead>
            <tr>
              <th>Unidad / Servicio</th>
              <th>Fecha / Km</th>
              <th>Costo / Próximo</th>
              <th>Taller / Técnico</th>
              <th>Observaciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="service in filteredMaintenances" :key="service.id">
              <td>
                <div class="stacked-cell">
                  <strong>{{ service.bus?.fleet_number ?? 'Sin unidad' }}</strong>
                  <span>{{ serviceLabel(service.service_type) }}</span>
                </div>
              </td>
              <td>
                <div class="stacked-cell">
                  <strong>{{ normalizeDate(service.service_date) }}</strong>
                  <span>{{ service.mileage ? `${service.mileage.toLocaleString('es-DO')} km` : 'Kilometraje pendiente' }}</span>
                </div>
              </td>
              <td>
                <div class="stacked-cell">
                  <strong>RD$ {{ maintenanceTotal(service).toLocaleString('es-DO') }}</strong>
                  <span>{{ service.next_due_date ? normalizeDate(service.next_due_date) : service.next_due_mileage ? `${service.next_due_mileage.toLocaleString('es-DO')} km` : 'No aplica' }}</span>
                </div>
              </td>
              <td>
                <div class="stacked-cell">
                  <strong>{{ service.workshop || 'Sin taller' }}</strong>
                  <span>{{ service.technician || 'Sin técnico' }}</span>
                </div>
              </td>
              <td>{{ service.notes || 'Sin observaciones' }}</td>
            </tr>
            <tr v-if="filteredMaintenances.length === 0">
              <td colspan="5">No hay servicios con los filtros seleccionados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>

<style scoped>
.report-page {
  background: #f3f6f8;
  display: grid;
  gap: 1rem;
  min-height: 0;
  padding: 0.25rem;
}

.card {
  background: #ffffff;
  border: 1px solid #dfe7ec;
  border-radius: 8px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
  min-width: 0;
}

.filter-panel,
.insight-panel,
.distribution-panel,
.report-results {
  padding: 1rem;
}

.section-header,
.filter-panel-header,
.result-actions {
  align-items: center;
  display: flex;
  gap: 0.75rem;
  justify-content: space-between;
}

.section-header h3,
.filter-panel-header h3 {
  color: #17202a;
  font-size: 1.08rem;
  line-height: 1.25;
  margin: 0;
}

.result-actions {
  flex-wrap: wrap;
  justify-content: flex-end;
}

.secondary-action {
  background: #155e75;
  border-radius: 6px;
  color: #fff;
  min-height: 2.25rem;
  padding: 0.5rem 0.75rem;
}

.secondary-action:hover {
  background: #0f3f4f;
}

.icon-action {
  align-items: center;
  display: inline-flex;
  gap: 0.4rem;
  min-height: 2.15rem;
  padding: 0.45rem 0.65rem;
}

.icon-action svg {
  fill: none;
  height: 1rem;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 2;
  width: 1rem;
}

.status {
  background: #e6f6fb;
  border-radius: 999px;
  color: #155e75;
  display: inline-flex;
  font-size: 0.78rem;
  font-weight: 850;
  padding: 0.3rem 0.65rem;
  white-space: nowrap;
}

.text-button {
  background: transparent;
  color: #155e75;
  padding: 0;
}

.text-button:hover {
  background: transparent;
  color: #0f3f4f;
  text-decoration: underline;
}

.filter-panel {
  display: grid;
  gap: 0.85rem;
}

.filter-grid {
  align-items: end;
  display: grid;
  gap: 0.75rem;
  grid-template-columns: minmax(16rem, 1.4fr) repeat(3, minmax(10rem, 1fr));
}

.filter-grid label,
.filter-segments {
  color: #475569;
  display: grid;
  font-size: 0.76rem;
  font-weight: 850;
  gap: 0.35rem;
  min-width: 0;
}

.filter-grid label > span,
.filter-segments > span {
  color: #64748b;
  font-size: 0.68rem;
  font-weight: 900;
  text-transform: uppercase;
}

.filter-grid input,
.filter-grid select {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  color: #17202a;
  min-height: 2.45rem;
  padding: 0.55rem 0.65rem;
  width: 100%;
}

.filter-search input {
  background: #f8fafc;
}

.filter-segments {
  grid-column: span 2;
}

.filter-segments div {
  background: #f8fafc;
  border: 1px solid #dbe7ee;
  border-radius: 6px;
  display: grid;
  gap: 0.25rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  padding: 0.25rem;
}

.filter-segments button {
  background: transparent;
  border-radius: 4px;
  color: #475569;
  font-size: 0.74rem;
  min-height: 2rem;
  padding: 0.35rem 0.45rem;
}

.filter-segments button:hover {
  background: #eef7fb;
  color: #155e75;
}

.filter-segments button.active {
  background: #155e75;
  color: #fff;
}

.active-filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.active-filter-row span {
  background: #eef2f5;
  border: 1px solid #d6e0e8;
  border-radius: 999px;
  color: #475569;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.3rem 0.55rem;
}

.alert-row {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.alert-card {
  border-radius: 8px;
  display: grid;
  gap: 0.15rem;
  justify-items: start;
  min-height: 4.5rem;
  padding: 0.8rem 1rem;
  text-align: left;
}

.alert-card strong {
  font-size: 1.35rem;
}

.alert-card span {
  font-size: 0.85rem;
}

.alert-card.warning {
  background: #fff5f5;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.alert-card.info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
}

.metric-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.metric-card {
  background: #ffffff;
  border: 1px solid #dfe7ec;
  border-radius: 8px;
  color: #17202a;
  display: grid;
  gap: 0.35rem;
  min-height: 5rem;
  padding: 0.85rem;
  text-align: left;
}

.metric-card span,
.insight-list span,
.distribution-row span {
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 900;
  text-transform: uppercase;
}

.metric-card strong {
  font-size: 1.35rem;
  line-height: 1.15;
}

.metric-card.clickable:hover {
  background: #eef7fb;
  border-color: #9ccfe0;
}

.analysis-grid {
  align-items: start;
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
}

.section-header,
.report-results-header {
  margin-bottom: 0.85rem;
}

.insight-list {
  display: grid;
  gap: 0.65rem;
}

.insight-list article {
  background: #f8fafc;
  border: 1px solid #e5ebf1;
  border-radius: 6px;
  display: grid;
  gap: 0.25rem;
  padding: 0.7rem;
}

.insight-list strong {
  color: #17202a;
  font-size: 0.98rem;
  line-height: 1.3;
}

.distribution-list {
  display: grid;
  gap: 0.65rem;
}

.distribution-row {
  align-items: center;
  display: grid;
  gap: 0.65rem;
  grid-template-columns: minmax(8rem, 1fr) minmax(9rem, 1.6fr) 2rem;
}

.distribution-bar {
  background: #eef2f5;
  border-radius: 999px;
  height: 0.65rem;
  overflow: hidden;
}

.distribution-bar-fill {
  background: #155e75;
  border-radius: inherit;
  height: 100%;
}

.distribution-row strong {
  color: #17202a;
  text-align: right;
}

.report-results {
  overflow: hidden;
  padding-bottom: 0;
}

.table-wrap {
  overflow: auto;
}

.report-table {
  border-collapse: collapse;
  min-width: 860px;
  width: 100%;
}

.report-table th,
.report-table td {
  border-bottom: 1px solid #e5ebf1;
  padding: 0.72rem 0.75rem;
  text-align: left;
  vertical-align: top;
}

.report-table th {
  background: #155e75;
  color: #ffffff;
  font-size: 0.74rem;
  position: sticky;
  top: 0;
  z-index: 1;
}

.report-table tbody tr:hover {
  background: #f8fafc;
}

.stacked-cell {
  display: grid;
  gap: 0.2rem;
}

.stacked-cell span {
  color: #64748b;
  font-size: 0.82rem;
}

@media (max-width: 1180px) {
  .filter-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .filter-search,
  .filter-segments {
    grid-column: 1 / -1;
  }

  .metric-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .analysis-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 680px) {
  .filter-panel-header,
  .report-results-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .result-actions {
    justify-content: flex-start;
  }

  .filter-grid,
  .filter-segments div,
  .alert-row,
  .metric-grid {
    grid-template-columns: 1fr;
  }

  .distribution-row {
    grid-template-columns: 1fr auto;
  }

  .distribution-bar {
    grid-column: 1 / -1;
    grid-row: 2;
  }
}
</style>

