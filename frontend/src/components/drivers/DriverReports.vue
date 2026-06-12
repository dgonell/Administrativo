<script setup>
import { computed, ref } from 'vue'
import { normalizeDate } from '../../utils/dates'
import { labelFor } from '../../utils/labels'

const props = defineProps({
  drivers: {
    type: Array,
    required: true,
  },
  licenseAlerts: {
    type: Array,
    required: true,
  },
})

const search = ref('')
const status = ref('')
const department = ref('')
const position = ref('')
const contractType = ref('')
const rehireStatus = ref('')
const licenseState = ref('')
const hireFrom = ref('')
const hireTo = ref('')
const recordState = ref('')

const departments = computed(() => {
  const names = props.drivers.map((driver) => driver.department?.name).filter(Boolean)
  return [...new Set(names)].sort()
})

const positions = computed(() => {
  const names = props.drivers.map((driver) => driver.position?.name).filter(Boolean)
  return [...new Set(names)].sort()
})

const contractTypes = computed(() => {
  const names = props.drivers.map((driver) => driver.contract_type?.name).filter(Boolean)
  return [...new Set(names)].sort()
})

function isLicenseExpired(driver) {
  if (!driver.license?.expires_at) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return new Date(driver.license.expires_at) < today
}

const filteredDrivers = computed(() => {
  const term = search.value.trim().toLowerCase()
  const alertIds = new Set(props.licenseAlerts.map((driver) => driver.id))

  return props.drivers.filter((driver) => {
    const text = [
      driver.code,
      driver.first_name,
      driver.last_name,
      driver.identity_document,
      driver.phone,
      driver.email,
      driver.license?.license_number,
      driver.department?.name,
      driver.position?.name,
    ].join(' ').toLowerCase()

    if (term && !text.includes(term)) return false
    if (status.value === 'dismissal' && !['dismissal', 'terminated'].includes(driver.status)) return false
    if (status.value && status.value !== 'dismissal' && driver.status !== status.value) return false
    if (department.value && driver.department?.name !== department.value) return false
    if (position.value && driver.position?.name !== position.value) return false
    if (contractType.value && driver.contract_type?.name !== contractType.value) return false
    if (rehireStatus.value && driver.rehire_status !== rehireStatus.value) return false
    if (hireFrom.value && (!driver.hire_date || driver.hire_date < hireFrom.value)) return false
    if (hireTo.value && (!driver.hire_date || driver.hire_date > hireTo.value)) return false
    if (licenseState.value === 'por_vencer' && !alertIds.has(driver.id)) return false
    if (licenseState.value === 'vencida' && !isLicenseExpired(driver)) return false
    if (licenseState.value === 'vigente' && (!driver.license?.license_number || alertIds.has(driver.id) || isLicenseExpired(driver))) return false
    if (licenseState.value === 'sin_licencia' && driver.license?.license_number) return false
    if (recordState.value === 'con_documentos' && !(driver.documents_count > 0)) return false
    if (recordState.value === 'sin_documentos' && driver.documents_count > 0) return false
    if (recordState.value === 'con_permisos' && !(driver.medical_leaves_count > 0)) return false
    if (recordState.value === 'con_conducta' && !(driver.conduct_reports_count > 0)) return false
    if (recordState.value === 'con_salidas' && !(driver.termination_records_count > 0)) return false

    return true
  })
})

const reportMetrics = computed(() => [
  { label: 'Resultado', value: filteredDrivers.value.length, link: 'resultado' },
  { label: 'Activos', value: filteredDrivers.value.filter((driver) => driver.status === 'active').length, link: 'activos' },
  { label: 'Renuncias', value: filteredDrivers.value.filter((driver) => driver.status === 'resignation').length, link: 'renuncias' },
  { label: 'Despedidos', value: filteredDrivers.value.filter((driver) => ['dismissal', 'terminated'].includes(driver.status)).length, link: 'despedidos' },
  { label: 'Licencias por vencer', value: filteredDrivers.value.filter((driver) => props.licenseAlerts.some((alert) => alert.id === driver.id)).length, link: 'licencias_por_vencer' },
  { label: 'Licencias vencidas', value: filteredDrivers.value.filter(isLicenseExpired).length },
  { label: 'Sin licencia', value: filteredDrivers.value.filter((driver) => !driver.license?.license_number).length },
  { label: 'Con conducta', value: filteredDrivers.value.filter((driver) => driver.conduct_reports_count > 0).length },
  { label: 'Permisos', value: filteredDrivers.value.reduce((total, driver) => total + (driver.medical_leaves_count ?? 0), 0) },
])

const activeFilters = computed(() => {
  const filters = [
    search.value ? `Busqueda: ${search.value}` : '',
    status.value ? `Estado: ${labelFor(status.value)}` : '',
    department.value ? `Departamento: ${department.value}` : '',
    position.value ? `Cargo: ${position.value}` : '',
    contractType.value ? `Contrato: ${contractType.value}` : '',
    rehireStatus.value ? `Recontratacion: ${labelFor(rehireStatus.value)}` : '',
    licenseState.value ? `Licencia: ${labelFor(licenseState.value)}` : '',
    hireFrom.value ? `Ingreso desde: ${hireFrom.value}` : '',
    hireTo.value ? `Ingreso hasta: ${hireTo.value}` : '',
    recordState.value ? `Registros: ${labelFor(recordState.value)}` : '',
  ].filter(Boolean)

  return filters.length ? filters : ['Todos los conductores']
})

function clearFilters() {
  search.value = ''
  status.value = ''
  department.value = ''
  position.value = ''
  contractType.value = ''
  rehireStatus.value = ''
  licenseState.value = ''
  hireFrom.value = ''
  hireTo.value = ''
  recordState.value = ''
}

function applyMetricLink(link) {
  if (link === 'resultado') {
    clearFilters()
    return
  }

  if (link === 'activos') {
    status.value = 'active'
    return
  }

  if (link === 'renuncias') {
    status.value = 'resignation'
    return
  }

  if (link === 'despedidos') {
    status.value = 'dismissal'
    return
  }

  if (link === 'licencias_por_vencer') {
    licenseState.value = 'por_vencer'
  }
}

function reportRows() {
  return filteredDrivers.value.map((driver) => ({
    conductor: `${driver.first_name} ${driver.last_name}`,
    codigo: driver.code,
    cedula: driver.identity_document,
    area: driver.department?.name ?? 'Sin asignar',
    cargo: driver.position?.name ?? 'Sin cargo',
    contrato: driver.contract_type?.name ?? 'Sin contrato',
    licencia: driver.license?.license_number ?? 'Pendiente',
    categoria: driver.license?.category ?? 'Sin categoria',
    vence: normalizeDate(driver.license?.expires_at) || 'Sin fecha',
    estado: labelFor(driver.status),
    recontratacion: labelFor(driver.rehire_status),
    documentos: driver.documents_count ?? 0,
    permisos: driver.medical_leaves_count ?? 0,
    conducta: driver.conduct_reports_count ?? 0,
    salidas: driver.termination_records_count ?? 0,
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
  return `reporte-conductores-${date}.${extension}`
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
        <td><strong>${escapeHtml(row.conductor)}</strong><br><span>${escapeHtml(row.codigo)} - ${escapeHtml(row.cedula)}</span></td>
        <td>${escapeHtml(row.area)}<br><span>${escapeHtml(row.cargo)}</span></td>
        <td>${escapeHtml(row.licencia)}<br><span>${escapeHtml(row.categoria)} - ${escapeHtml(row.vence)}</span></td>
        <td>${escapeHtml(row.estado)}</td>
        <td>${escapeHtml(row.recontratacion)}</td>
        <td>Docs ${escapeHtml(row.documentos)} | Perm ${escapeHtml(row.permisos)} | Cond ${escapeHtml(row.conducta)} | Sal ${escapeHtml(row.salidas)}</td>
      </tr>
    `)
    .join('')

  return `
    <table>
      <thead>
        <tr>
          <th>Conductor</th>
          <th>Area</th>
          <th>Licencia</th>
          <th>Estado</th>
          <th>Recontratacion</th>
          <th>Registros</th>
        </tr>
      </thead>
      <tbody>${rows || '<tr><td colspan="6">Sin resultados.</td></tr>'}</tbody>
    </table>
  `
}

function reportHtml() {
  return `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>Reporte ejecutivo de conductores</title>
        <style>
          body { color: #18212b; font-family: Arial, sans-serif; margin: 28px; }
          header { border-bottom: 4px solid #f5b301; margin-bottom: 18px; padding-bottom: 14px; }
          .brand { color: #155e75; font-size: 12px; font-weight: 800; text-transform: uppercase; }
          h1 { font-size: 24px; margin: 6px 0; }
          .meta { color: #64748b; font-size: 12px; }
          .filters { background: #eef2f5; border: 1px solid #d6e0e8; border-radius: 8px; font-size: 12px; margin: 14px 0; padding: 10px; }
          .metrics { display: grid; gap: 8px; grid-template-columns: repeat(5, 1fr); margin: 16px 0; }
          .metrics article { border: 1px solid #d6e0e8; border-radius: 8px; padding: 10px; }
          .metrics span { color: #64748b; display: block; font-size: 11px; font-weight: 700; }
          .metrics strong { color: #0f3f4f; display: block; font-size: 20px; margin-top: 4px; }
          table { border-collapse: collapse; font-size: 11px; width: 100%; }
          th { background: #155e75; color: #fff; padding: 8px; text-align: left; }
          td { border-bottom: 1px solid #d6e0e8; padding: 8px; vertical-align: top; }
          td span { color: #64748b; }
          @media print { body { margin: 18px; } .no-print { display: none; } }
        </style>
      </head>
      <body>
        <header>
          <div class="brand">SODASA</div>
          <h1>Reporte ejecutivo de conductores</h1>
          <div class="meta">Generado: ${escapeHtml(new Date().toLocaleString())} | Registros: ${escapeHtml(filteredDrivers.value.length)}</div>
        </header>
        <section class="filters"><strong>Filtros:</strong> ${activeFilters.value.map(escapeHtml).join(' | ')}</section>
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
  <section class="reports-layout">
    <div class="report-filter-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Filtros</p>
          <h3>Conductores de la flota</h3>
        </div>
        <button type="button" class="text-button" @click="clearFilters">Limpiar</button>
      </header>

      <div class="report-filters">
        <label>Buscar <input v-model="search" placeholder="Nombre, cedula, licencia o telefono" /></label>
        <label>
          Estado
          <select v-model="status">
            <option value="">Todos</option>
            <option value="active">Activo</option>
            <option value="resignation">Renuncia</option>
            <option value="dismissal">Despedido</option>
          </select>
        </label>
        <label>
          Departamento
          <select v-model="department">
            <option value="">Todos</option>
            <option v-for="name in departments" :key="name" :value="name">{{ name }}</option>
          </select>
        </label>
        <label>
          Cargo
          <select v-model="position">
            <option value="">Todos</option>
            <option v-for="name in positions" :key="name" :value="name">{{ name }}</option>
          </select>
        </label>
        <label>
          Contrato
          <select v-model="contractType">
            <option value="">Todos</option>
            <option v-for="name in contractTypes" :key="name" :value="name">{{ name }}</option>
          </select>
        </label>
        <label>
          Recontratacion
          <select v-model="rehireStatus">
            <option value="">Todos</option>
            <option value="yes">Apto</option>
            <option value="review">En revision</option>
            <option value="no">No apto</option>
          </select>
        </label>
        <label>
          Licencia
          <select v-model="licenseState">
            <option value="">Todas</option>
            <option value="vigente">Vigente</option>
            <option value="por_vencer">Por vencer</option>
            <option value="vencida">Vencida</option>
            <option value="sin_licencia">Sin licencia</option>
          </select>
        </label>
        <label>Ingreso desde <input v-model="hireFrom" type="date" /></label>
        <label>Ingreso hasta <input v-model="hireTo" type="date" /></label>
        <label>
          Registros
          <select v-model="recordState">
            <option value="">Todos</option>
            <option value="con_documentos">Con documentos</option>
            <option value="sin_documentos">Sin documentos</option>
            <option value="con_permisos">Con permisos</option>
            <option value="con_conducta">Con conducta</option>
            <option value="con_salidas">Con salida</option>
          </select>
        </label>
      </div>
    </div>

    <div class="report-metrics">
      <button
        v-for="metric in reportMetrics"
        :key="metric.label"
        type="button"
        class="metric"
        :class="{ 'metric-link': metric.link }"
        :disabled="!metric.link"
        @click="metric.link && applyMetricLink(metric.link)"
      >
        <span>{{ metric.label }}</span>
        <strong>{{ metric.value }}</strong>
      </button>
    </div>

    <div class="profile-panel report-results">
      <header class="section-header">
        <div>
          <p class="eyebrow">Reporte</p>
          <h3>Resultado filtrado</h3>
        </div>
        <div class="report-actions">
          <span class="status">{{ filteredDrivers.length }} registros</span>
          <button type="button" class="secondary-action" @click="downloadPdf">Descargar PDF</button>
          <button type="button" class="secondary-action" @click="downloadExcel">Descargar Excel</button>
        </div>
      </header>

      <div class="table-wrap">
        <table class="report-table">
          <thead>
            <tr>
              <th>Conductor</th>
              <th>Area</th>
              <th>Licencia</th>
              <th>Estado</th>
              <th>Recontratacion</th>
              <th>Registros</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="driver in filteredDrivers" :key="driver.id">
              <td>
                <div class="stacked-cell">
                  <strong>{{ driver.first_name }} {{ driver.last_name }}</strong>
                  <span>{{ driver.code }} - {{ driver.identity_document }}</span>
                </div>
              </td>
              <td>
                <div class="stacked-cell">
                  <strong>{{ driver.department?.name ?? 'Sin asignar' }}</strong>
                  <span>{{ driver.position?.name ?? 'Sin cargo' }}</span>
                </div>
              </td>
              <td>
                <div class="stacked-cell">
                  <strong>{{ driver.license?.license_number ?? 'Pendiente' }}</strong>
                  <span>{{ driver.license?.category ?? 'Sin categoria' }} - {{ normalizeDate(driver.license?.expires_at) || 'Sin fecha' }}</span>
                </div>
              </td>
              <td><span class="badge" :class="`status-${driver.status}`">{{ labelFor(driver.status) }}</span></td>
              <td>{{ labelFor(driver.rehire_status) }}</td>
              <td>
                <div class="record-pills">
                  <span>Docs {{ driver.documents_count ?? 0 }}</span>
                  <span>Perm {{ driver.medical_leaves_count ?? 0 }}</span>
                  <span>Cond {{ driver.conduct_reports_count ?? 0 }}</span>
                  <span>Sal {{ driver.termination_records_count ?? 0 }}</span>
                </div>
              </td>
            </tr>
            <tr v-if="filteredDrivers.length === 0">
              <td colspan="6">No hay conductores con los filtros seleccionados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
