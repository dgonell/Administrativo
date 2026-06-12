<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import {
  createFuelAdjustment,
  createFuelClosure,
  createFuelDispatch,
  createFuelHose,
  createFuelMeasurement,
  createFuelPartner,
  createFuelPurchase,
  createFuelTank,
  fetchFuelCatalogs,
  fetchFuelDashboard,
  fetchFuelRecords,
  updateFuelAdjustment,
  updateFuelClosure,
  updateFuelDispatch,
  updateFuelMeasurement,
  updateFuelPartner,
  updateFuelPurchase,
  voidFuelDispatch,
} from '../../services/api'

const props = defineProps({
  permissions: { type: Array, default: () => [] },
  currentUser: { type: Object, required: true },
})

const dashboard = ref({ metrics: {}, tanks: [], hose_totals: [], vehicle_rank: [], recent_alerts: [] })
const catalogs = ref({ tanks: [], hoses: [], company_vehicles: [], drivers: [], partners: [] })
const records = ref([])
const activeTab = ref('dispatches')
const activeForm = ref('')
const editingRecord = ref(null)
const previewRecord = ref(null)
const printJob = reactive({ type: '', record: null, generatedAt: '' })
const isLoading = ref(true)
const isSaving = ref(false)
const modal = reactive({ isOpen: false, type: 'success', title: '', message: '' })
const LITERS_PER_GALLON = 3.785411784

const dispatchForm = reactive({
  fuel_hose_id: '',
  recipient_type: 'company_bus',
  operation_bus_id: '',
  fuel_partner_id: '',
  fuel_partner_vehicle_id: '',
  dispatched_at: localDateTime(),
  hose_counter_start: '',
  hose_counter_end: '',
  km: '',
  driver_id: '',
  driver_name: '',
  authorized_by: '',
  notes: '',
})
const purchaseForm = reactive({ fuel_tank_id: '', received_at: today(), supplier: '', invoice_number: '', liters: '', unit_cost: '', tank_liters_after: '', notes: '' })
const tankForm = reactive({ name: '', fuel_type: 'diesel', capacity_liters: '', current_liters: '', minimum_liters: '', location: '', status: 'active', notes: '' })
const hoseForm = reactive({ fuel_tank_id: '', name: '', current_counter: '', allowed_difference_liters: '0.5', status: 'active', notes: '' })
const partnerForm = reactive({ name: '', document: '', phone: '', email: '', monthly_quota_liters: '', notes: '', vehicle_plate: '', vehicle_brand: '', vehicle_model: '', vehicle_capacity: '', vehicle_efficiency: '', vehicle_quota: '' })
const measurementForm = reactive({ fuel_tank_id: '', measured_at: localDateTime(), physical_liters: '', reason: '', notes: '' })
const closureForm = reactive({ fuel_hose_id: '', closed_on: today(), counter_start: '', counter_end: '', notes: '' })
const adjustmentForm = reactive({ fuel_tank_id: '', adjusted_at: localDateTime(), liters: '', type: 'correccion', reason: '', notes: '' })

function can(permission) { return props.permissions.includes(permission) }
function today() { return new Date().toISOString().slice(0, 10) }
function localDateTime() { return new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16) }
function num(value, decimals = 2) { return Number(value || 0).toLocaleString('es-DO', { maximumFractionDigits: decimals }) }
function gallons(value, decimals = 2) { return num(Number(value || 0) / LITERS_PER_GALLON, decimals) }
function gallonsToLiters(value) { return Number(value || 0) * LITERS_PER_GALLON }
function costPerGallonToLiter(value) { return Number(value || 0) / LITERS_PER_GALLON }
function money(value) { return `RD$ ${Number(value || 0).toLocaleString('es-DO', { maximumFractionDigits: 2 })}` }
function dateValue(value) { return value ? String(value).replace('T', ' ').slice(0, 16) : '' }
function clean(value) { return String(value ?? '').trim() }
function showModal(type, title, message) { Object.assign(modal, { isOpen: true, type, title, message }) }
function closeModal() { modal.isOpen = false }

const selectedHose = computed(() => catalogs.value.hoses.find((hose) => hose.id === Number(dispatchForm.fuel_hose_id)) ?? null)
const dispatchLiters = computed(() => {
  const liters = Number(dispatchForm.hose_counter_end || 0) - Number(dispatchForm.hose_counter_start || 0)
  return liters > 0 ? liters : 0
})
const selectedPartnerVehicles = computed(() => {
  const partner = catalogs.value.partners.find((item) => item.id === Number(dispatchForm.fuel_partner_id))
  return partner?.vehicles ?? []
})
const stockPercent = computed(() => {
  const capacity = Number(dashboard.value.metrics?.capacity_liters || 0)
  if (!capacity) return 0
  return Math.min(100, Math.round((Number(dashboard.value.metrics?.stock_liters || 0) / capacity) * 100))
})
const tabs = computed(() => [
  { key: 'dispatches', label: 'Despachos' },
  { key: 'purchases', label: 'Recepciones' },
  { key: 'closures', label: 'Cierres' },
  { key: 'measurements', label: 'Mediciones' },
  { key: 'adjustments', label: 'Ajustes' },
  { key: 'partners', label: 'Socios' },
  { key: 'alerts', label: 'Alertas' },
])

async function loadAll() {
  isLoading.value = true
  try {
    const [dashboardData, catalogData, recordData] = await Promise.all([
      fetchFuelDashboard(),
      fetchFuelCatalogs(),
      fetchFuelRecords(activeTab.value),
    ])
    dashboard.value = dashboardData
    catalogs.value = catalogData
    records.value = recordData
  } catch (error) {
    showModal('error', 'No se pudo cargar combustible', error.message)
  } finally {
    isLoading.value = false
  }
}

async function changeTab(tab) {
  activeTab.value = tab
  try {
    records.value = await fetchFuelRecords(tab)
  } catch (error) {
    showModal('error', 'No se pudo cargar el listado', error.message)
  }
}

async function submitForm(kind) {
  isSaving.value = true
  try {
    if (kind === 'dispatch') editingRecord.value ? await updateFuelDispatch(editingRecord.value.id, payloadDispatchEdit()) : await createFuelDispatch(payloadDispatch())
    if (kind === 'purchase') editingRecord.value ? await updateFuelPurchase(editingRecord.value.id, payloadPurchaseEdit()) : await createFuelPurchase(payloadPurchase())
    if (kind === 'tank') await createFuelTank(payloadTank())
    if (kind === 'hose') await createFuelHose(payloadHose())
    if (kind === 'partner') editingRecord.value ? await updateFuelPartner(editingRecord.value.id, payloadPartnerEdit()) : await createFuelPartner(payloadPartner())
    if (kind === 'measurement') editingRecord.value ? await updateFuelMeasurement(editingRecord.value.id, payloadMeasurement()) : await createFuelMeasurement(payloadMeasurement())
    if (kind === 'closure') editingRecord.value ? await updateFuelClosure(editingRecord.value.id, payloadClosureEdit()) : await createFuelClosure(payloadClosure())
    if (kind === 'adjustment') editingRecord.value ? await updateFuelAdjustment(editingRecord.value.id, payloadAdjustmentEdit()) : await createFuelAdjustment(payloadAdjustment())
    closeForm()
    await loadAll()
    showModal('success', 'Registro guardado', 'La operacion de combustible fue registrada correctamente.')
  } catch (error) {
    showModal('error', 'No se pudo guardar', error.message)
  } finally {
    isSaving.value = false
  }
}

function closeForm() {
  activeForm.value = ''
  editingRecord.value = null
}

async function annulDispatch(row) {
  const reason = window.prompt(`Motivo de anulacion del despacho #${row.id}`)
  if (!reason) return
  try {
    await voidFuelDispatch(row.id, { void_reason: reason })
    await loadAll()
    showModal('success', 'Despacho anulado', 'El stock fue revertido y quedo trazabilidad de la anulacion.')
  } catch (error) {
    showModal('error', 'No se pudo anular', error.message)
  }
}

function payloadDispatch() {
  const payload = convertGallonsPayload(cleanNumbers(dispatchForm), ['hose_counter_start', 'hose_counter_end'])
  payload.odometer = payload.km
  payload.hourmeter = null
  payload.cost_center = null
  payload.route_or_service = null
  payload.reason = null
  payload.driver_name = dispatchDriverName(payload)
  payload.operator_name = props.currentUser.name
  delete payload.driver_id
  delete payload.km
  if (payload.recipient_type !== 'company_bus') payload.operation_bus_id = null
  if (payload.recipient_type !== 'partner_vehicle') {
    payload.fuel_partner_id = null
    payload.fuel_partner_vehicle_id = null
  }
  return payload
}

function payloadPurchase() {
  const payload = convertGallonsPayload(cleanNumbers(purchaseForm), ['liters', 'tank_liters_after'])
  if (payload.unit_cost !== null && payload.unit_cost !== undefined) {
    payload.unit_cost = costPerGallonToLiter(payload.unit_cost)
  }
  return payload
}

function payloadPurchaseEdit() {
  const payload = cleanNumbers(purchaseForm)
  if (payload.unit_cost !== null && payload.unit_cost !== undefined) {
    payload.unit_cost = costPerGallonToLiter(payload.unit_cost)
  }
  return {
    received_at: payload.received_at,
    supplier: payload.supplier,
    invoice_number: payload.invoice_number,
    unit_cost: payload.unit_cost,
    notes: payload.notes,
  }
}

function payloadDispatchEdit() {
  const payload = cleanNumbers(dispatchForm)
  return {
    odometer: payload.km,
    driver_name: dispatchDriverName(payload),
    operator_name: props.currentUser.name,
    authorized_by: payload.authorized_by,
    notes: payload.notes,
  }
}

function dispatchDriverName(payload) {
  if (payload.recipient_type !== 'company_bus') return payload.driver_name
  const driver = catalogs.value.drivers.find((item) => item.id === Number(payload.driver_id))
  return driver ? `${driver.code} - ${driver.first_name} ${driver.last_name}` : payload.driver_name
}

function payloadMeasurement() {
  return convertGallonsPayload(cleanNumbers(measurementForm), ['physical_liters'])
}

function payloadClosure() {
  return convertGallonsPayload(cleanNumbers(closureForm), ['counter_start', 'counter_end'])
}

function payloadClosureEdit() {
  const payload = payloadClosure()
  return {
    counter_start: payload.counter_start,
    counter_end: payload.counter_end,
    notes: payload.notes,
  }
}

function payloadAdjustment() {
  return convertGallonsPayload(cleanNumbers(adjustmentForm), ['liters'])
}

function payloadAdjustmentEdit() {
  const payload = cleanNumbers(adjustmentForm)
  return {
    reason: payload.reason,
    notes: payload.notes,
  }
}

function payloadTank() {
  return withNumericDefaults(
    convertGallonsPayload(cleanNumbers(tankForm), ['capacity_liters', 'current_liters', 'minimum_liters']),
    { current_liters: 0, minimum_liters: 0 }
  )
}

function payloadHose() {
  return withNumericDefaults(
    convertGallonsPayload(cleanNumbers(hoseForm), ['current_counter', 'allowed_difference_liters']),
    { current_counter: 0, allowed_difference_liters: gallonsToLiters(0.5) }
  )
}

function payloadPartner() {
  const vehicle = partnerForm.vehicle_plate ? [{
    plate: clean(partnerForm.vehicle_plate).toUpperCase(),
    brand: clean(partnerForm.vehicle_brand) || null,
    model: clean(partnerForm.vehicle_model) || null,
    tank_capacity_liters: partnerForm.vehicle_capacity ? gallonsToLiters(partnerForm.vehicle_capacity) : null,
    expected_efficiency: partnerForm.vehicle_efficiency ? Number(partnerForm.vehicle_efficiency) : null,
    monthly_quota_liters: partnerForm.vehicle_quota ? gallonsToLiters(partnerForm.vehicle_quota) : null,
  }] : []

  return {
    name: clean(partnerForm.name),
    document: clean(partnerForm.document) || null,
    phone: clean(partnerForm.phone) || null,
    email: clean(partnerForm.email) || null,
    monthly_quota_liters: partnerForm.monthly_quota_liters ? gallonsToLiters(partnerForm.monthly_quota_liters) : null,
    notes: clean(partnerForm.notes) || null,
    vehicles: vehicle,
  }
}

function payloadPartnerEdit() {
  const payload = cleanNumbers(partnerForm)
  return {
    name: payload.name,
    document: payload.document,
    phone: payload.phone,
    email: payload.email,
    monthly_quota_liters: payload.monthly_quota_liters ? gallonsToLiters(payload.monthly_quota_liters) : null,
    notes: payload.notes,
    is_active: true,
  }
}

function openCreate(form) {
  editingRecord.value = null
  activeForm.value = form
}

function openPreview(row) {
  previewRecord.value = row
}

function closePreview() {
  previewRecord.value = null
}

function openEdit(row) {
  editingRecord.value = row
  if (activeTab.value === 'dispatches') {
    Object.assign(dispatchForm, {
      fuel_hose_id: row.fuel_hose_id ?? '',
      recipient_type: row.recipient_type ?? 'company_bus',
      operation_bus_id: row.operation_bus_id ?? '',
      fuel_partner_id: row.fuel_partner_id ?? '',
      fuel_partner_vehicle_id: row.fuel_partner_vehicle_id ?? '',
      dispatched_at: dateInput(row.dispatched_at, true),
      hose_counter_start: gallons(row.hose_counter_start, 3).replace(/,/g, ''),
      hose_counter_end: gallons(row.hose_counter_end, 3).replace(/,/g, ''),
      km: row.odometer ?? '',
      driver_id: '',
      driver_name: row.driver_name ?? '',
      authorized_by: row.authorized_by ?? '',
      notes: row.notes ?? '',
    })
    activeForm.value = 'dispatch'
  }
  if (activeTab.value === 'purchases') {
    Object.assign(purchaseForm, {
      fuel_tank_id: row.fuel_tank_id ?? '',
      received_at: dateInput(row.received_at),
      supplier: row.supplier ?? '',
      invoice_number: row.invoice_number ?? '',
      liters: gallons(row.liters).replace(/,/g, ''),
      unit_cost: num(Number(row.unit_cost || 0) * LITERS_PER_GALLON, 4).replace(/,/g, ''),
      tank_liters_after: gallons(row.tank_liters_after).replace(/,/g, ''),
      notes: row.notes ?? '',
    })
    activeForm.value = 'purchase'
  }
  if (activeTab.value === 'measurements') {
    Object.assign(measurementForm, { fuel_tank_id: row.fuel_tank_id ?? '', measured_at: dateInput(row.measured_at, true), physical_liters: gallons(row.physical_liters).replace(/,/g, ''), reason: row.reason ?? '', notes: row.notes ?? '' })
    activeForm.value = 'measurement'
  }
  if (activeTab.value === 'closures') {
    Object.assign(closureForm, { fuel_hose_id: row.fuel_hose_id ?? '', closed_on: dateInput(row.closed_on), counter_start: gallons(row.counter_start, 3).replace(/,/g, ''), counter_end: gallons(row.counter_end, 3).replace(/,/g, ''), notes: row.notes ?? '' })
    activeForm.value = 'closure'
  }
  if (activeTab.value === 'adjustments') {
    Object.assign(adjustmentForm, { fuel_tank_id: row.fuel_tank_id ?? '', adjusted_at: dateInput(row.adjusted_at, true), liters: gallons(row.liters).replace(/,/g, ''), type: row.type ?? 'correccion', reason: row.reason ?? '', notes: row.notes ?? '' })
    activeForm.value = 'adjustment'
  }
  if (activeTab.value === 'partners') {
    Object.assign(partnerForm, { name: row.name ?? '', document: row.document ?? '', phone: row.phone ?? '', email: row.email ?? '', monthly_quota_liters: row.monthly_quota_liters ? gallons(row.monthly_quota_liters).replace(/,/g, '') : '', notes: row.notes ?? '', vehicle_plate: '', vehicle_brand: '', vehicle_model: '', vehicle_capacity: '', vehicle_efficiency: '', vehicle_quota: '' })
    activeForm.value = 'partner'
  }
}

function dateInput(value, withTime = false) {
  if (!value) return ''
  const text = String(value).replace(' ', 'T')
  return withTime ? text.slice(0, 16) : text.slice(0, 10)
}

function recipientLabel(row) {
  if (!row) return ''
  if (row.bus) return `Unidad ${row.bus.fleet_number} - ${row.bus.plate}`
  if (row.partner_vehicle) return `${row.partner?.name ?? 'Socio'} - ${row.partner_vehicle.plate}`
  return row.driver_name || 'Externo autorizado'
}

function recordTitle(row = previewRecord.value) {
  if (!row) return ''
  const labels = { dispatches: 'Despacho', purchases: 'Recepcion', closures: 'Cierre diario', measurements: 'Medicion fisica', adjustments: 'Ajuste', partners: 'Socio', alerts: 'Alerta' }
  return `${labels[activeTab.value] ?? 'Registro'} #${row.id}`
}

function recordFields(row = previewRecord.value) {
  if (!row) return []
  if (activeTab.value === 'dispatches') return [
    ['Fecha', dateValue(row.dispatched_at)], ['Destino', recipientLabel(row)], ['Tanque', row.tank?.name], ['Manguera', row.hose?.code],
    ['Galones', gallons(row.liters, 3)], ['Contador inicial', gallons(row.hose_counter_start, 3)], ['Contador final', gallons(row.hose_counter_end, 3)],
    ['Km', row.odometer ?? 'N/D'], ['Chofer', row.driver_name || 'N/D'], ['Operador', row.operator_name || 'N/D'], ['Autorizado por', row.authorized_by || 'N/D'], ['Estado', row.status],
  ]
  if (activeTab.value === 'purchases') return [['Fecha', dateValue(row.received_at)], ['Proveedor', row.supplier], ['Factura', row.invoice_number || 'N/D'], ['Tanque', row.tank?.name], ['Galones', gallons(row.liters)], ['Costo por galon', money(Number(row.unit_cost || 0) * LITERS_PER_GALLON)], ['Total', money(row.total_cost)], ['Diferencia', gallons(row.difference_liters)]]
  if (activeTab.value === 'closures') return [['Fecha', dateValue(row.closed_on)], ['Manguera', row.hose?.code], ['Contador inicial', gallons(row.counter_start, 3)], ['Contador final', gallons(row.counter_end, 3)], ['Sistema', gallons(row.system_liters, 3)], ['Contador', gallons(row.counter_liters, 3)], ['Diferencia', gallons(row.difference_liters, 3)]]
  if (activeTab.value === 'measurements') return [['Fecha', dateValue(row.measured_at)], ['Tanque', tankName(row.fuel_tank_id)], ['Teorico', gallons(row.theoretical_liters)], ['Fisico', gallons(row.physical_liters)], ['Diferencia', gallons(row.difference_liters)], ['Motivo', row.reason || 'N/D']]
  if (activeTab.value === 'adjustments') return [['Fecha', dateValue(row.adjusted_at)], ['Tanque', tankName(row.fuel_tank_id)], ['Tipo', row.type], ['Galones', gallons(row.liters)], ['Motivo', row.reason]]
  if (activeTab.value === 'partners') return [['Socio', row.name], ['Documento', row.document || 'N/D'], ['Telefono', row.phone || 'N/D'], ['Email', row.email || 'N/D'], ['Cupo mensual', row.monthly_quota_liters ? `${gallons(row.monthly_quota_liters)} gal` : 'Sin cupo'], ['Vehiculos', row.vehicles?.length || 0]]
  return [['Tipo', row.type], ['Severidad', row.severity], ['Titulo', row.title], ['Estado', row.resolved_at ? 'Resuelta' : 'Abierta']]
}

function tankName(id) {
  return catalogs.value.tanks.find((tank) => tank.id === Number(id))?.name ?? `Tanque ${id}`
}

function printRecord(row = previewRecord.value) {
  printJob.type = 'record'
  printJob.record = row
  printJob.generatedAt = new Date().toLocaleString('es-DO')
  setTimeout(() => window.print(), 0)
}

function printReport() {
  printJob.type = 'report'
  printJob.record = null
  printJob.generatedAt = new Date().toLocaleString('es-DO')
  setTimeout(() => window.print(), 0)
}

function convertGallonsPayload(payload, keys) {
  const next = { ...payload }
  keys.forEach((key) => {
    if (next[key] !== null && next[key] !== undefined && next[key] !== '') {
      next[key] = gallonsToLiters(next[key])
    }
  })
  return next
}

function withNumericDefaults(payload, defaults) {
  const next = { ...payload }
  Object.entries(defaults).forEach(([key, value]) => {
    if (next[key] === null || next[key] === undefined || next[key] === '') {
      next[key] = value
    }
  })
  return next
}

function cleanNumbers(source) {
  return Object.fromEntries(Object.entries(source).map(([key, value]) => {
    if (value === '') return [key, null]
    if (['fuel_tank_id', 'fuel_hose_id', 'operation_bus_id', 'fuel_partner_id', 'fuel_partner_vehicle_id', 'driver_id', 'odometer', 'km'].includes(key)) return [key, Number(value)]
    if (['liters', 'unit_cost', 'tank_liters_after', 'capacity_liters', 'current_liters', 'minimum_liters', 'current_counter', 'allowed_difference_liters', 'physical_liters', 'counter_start', 'counter_end'].includes(key)) return [key, Number(value)]
    return [key, typeof value === 'string' ? clean(value) || null : value]
  }))
}

onMounted(loadAll)
</script>

<template>
  <main class="content fuel-workspace">
    <section class="fuel-header">
      <div>
        <p class="eyebrow">Operaciones</p>
        <h2>Combustible</h2>
      </div>
      <div class="fuel-actions">
        <button v-if="can('fuel.dispatches.manage')" type="button" @click="openCreate('dispatch')">Despacho</button>
        <button v-if="can('fuel.purchases.manage')" type="button" class="secondary-action" @click="openCreate('purchase')">Recepcion</button>
        <button v-if="can('fuel.settings.manage')" type="button" class="secondary-action" @click="openCreate('tank')">Tanque</button>
      </div>
    </section>

    <section class="fuel-metrics">
      <article>
        <span>Stock actual</span>
        <strong>{{ gallons(dashboard.metrics.stock_liters) }} gal</strong>
        <div class="fuel-stock-bar"><i :style="{ width: `${stockPercent}%` }"></i></div>
      </article>
      <article><span>Despachado hoy</span><strong>{{ gallons(dashboard.metrics.today_liters) }} gal</strong></article>
      <article><span>Despachado mes</span><strong>{{ gallons(dashboard.metrics.month_liters) }} gal</strong></article>
      <article><span>Unidades propias</span><strong>{{ gallons(dashboard.metrics.company_liters) }} gal</strong></article>
      <article><span>Socios</span><strong>{{ gallons(dashboard.metrics.partner_liters) }} gal</strong></article>
      <article><span>Costo mes</span><strong>{{ money(dashboard.metrics.month_cost) }}</strong></article>
    </section>

    <section class="fuel-grid">
      <article class="profile-panel fuel-tank-panel">
        <header class="section-header">
          <div>
            <p class="eyebrow">Inventario</p>
            <h3>Tanques y mangueras</h3>
          </div>
          <button v-if="can('fuel.settings.manage')" class="secondary-action" @click="openCreate('hose')">Manguera</button>
        </header>
        <div class="fuel-tank-list">
          <article v-for="tank in catalogs.tanks" :key="tank.id">
            <div>
              <strong>{{ tank.name }}</strong>
              <span>{{ gallons(tank.current_liters) }} / {{ gallons(tank.capacity_liters) }} gal</span>
            </div>
            <small>{{ tank.location || 'Sin ubicacion' }} - {{ tank.status }}</small>
            <ul>
              <li v-for="hose in tank.hoses" :key="hose.id">{{ hose.code }} - {{ hose.name }}: {{ gallons(hose.current_counter, 3) }} gal</li>
            </ul>
          </article>
          <p v-if="!catalogs.tanks.length" class="empty-state">No hay tanques registrados.</p>
        </div>
      </article>

      <article class="profile-panel fuel-records-panel">
        <header class="section-header">
          <div>
            <p class="eyebrow">Control</p>
            <h3>Registros</h3>
          </div>
          <div class="fuel-extra-actions">
            <button class="secondary-action" @click="printReport">Imprimir reporte</button>
            <button v-if="can('fuel.measurements.manage')" class="secondary-action" @click="openCreate('measurement')">Medicion</button>
            <button v-if="can('fuel.closures.manage')" class="secondary-action" @click="openCreate('closure')">Cierre</button>
            <button v-if="can('fuel.adjustments.manage')" class="secondary-action" @click="openCreate('adjustment')">Ajuste</button>
            <button v-if="can('fuel.partners.manage')" class="secondary-action" @click="openCreate('partner')">Socio</button>
          </div>
        </header>
        <nav class="fuel-tabs">
          <button v-for="tab in tabs" :key="tab.key" :class="{ active: activeTab === tab.key }" @click="changeTab(tab.key)">{{ tab.label }}</button>
        </nav>
        <div class="fuel-table-wrap">
          <table class="report-table fuel-table">
            <thead>
              <tr v-if="activeTab === 'dispatches'"><th>Fecha</th><th>Destino</th><th>Manguera</th><th>Galones</th><th>Costo</th><th>Estado</th><th>Acciones</th></tr>
              <tr v-else-if="activeTab === 'purchases'"><th>Fecha</th><th>Proveedor</th><th>Tanque</th><th>Galones</th><th>Total</th><th>Diferencia</th><th>Acciones</th></tr>
              <tr v-else-if="activeTab === 'partners'"><th>Socio</th><th>Documento</th><th>Cupo</th><th>Vehiculos</th><th>Estado</th><th>Acciones</th></tr>
              <tr v-else-if="activeTab === 'alerts'"><th>Tipo</th><th>Severidad</th><th>Titulo</th><th>Mensaje</th><th>Estado</th><th>Acciones</th></tr>
              <tr v-else><th>Fecha</th><th>Elemento</th><th>Teorico/Sistema</th><th>Fisico/Contador</th><th>Diferencia</th><th>Notas</th><th>Acciones</th></tr>
            </thead>
            <tbody>
              <template v-if="activeTab === 'dispatches'">
                <tr v-for="row in records" :key="row.id">
                  <td>{{ dateValue(row.dispatched_at) }}</td>
                  <td>{{ row.bus ? `Unidad ${row.bus.fleet_number}` : row.partner_vehicle ? `${row.partner?.name} - ${row.partner_vehicle.plate}` : row.driver_name || 'Externo' }}</td>
                  <td>{{ row.hose?.code }}</td>
                  <td>{{ gallons(row.liters, 3) }}</td>
                  <td>{{ money(row.total_cost) }}</td>
                  <td>{{ row.status }}</td>
                  <td><div class="record-actions"><button class="table-action" @click="openPreview(row)">Ver</button><button class="table-action secondary-action" @click="openEdit(row)">Editar</button><button class="table-action secondary-action" @click="printRecord(row)">Imprimir</button><button v-if="row.status === 'posted' && can('fuel.dispatches.void')" class="table-action danger-action" @click="annulDispatch(row)">Anular</button></div></td>
                </tr>
              </template>
              <template v-else-if="activeTab === 'purchases'">
                <tr v-for="row in records" :key="row.id"><td>{{ dateValue(row.received_at) }}</td><td>{{ row.supplier }}</td><td>{{ row.tank?.name }}</td><td>{{ gallons(row.liters) }}</td><td>{{ money(row.total_cost) }}</td><td>{{ gallons(row.difference_liters) }}</td><td><div class="record-actions"><button class="table-action" @click="openPreview(row)">Ver</button><button class="table-action secondary-action" @click="openEdit(row)">Editar</button><button class="table-action secondary-action" @click="printRecord(row)">Imprimir</button></div></td></tr>
              </template>
              <template v-else-if="activeTab === 'partners'">
                <tr v-for="row in records" :key="row.id"><td>{{ row.name }}</td><td>{{ row.document || 'N/D' }}</td><td>{{ row.monthly_quota_liters ? `${gallons(row.monthly_quota_liters)} gal` : 'Sin cupo' }}</td><td>{{ row.vehicles?.length || 0 }}</td><td>{{ row.is_active ? 'Activo' : 'Inactivo' }}</td><td><div class="record-actions"><button class="table-action" @click="openPreview(row)">Ver</button><button class="table-action secondary-action" @click="openEdit(row)">Editar</button><button class="table-action secondary-action" @click="printRecord(row)">Imprimir</button></div></td></tr>
              </template>
              <template v-else-if="activeTab === 'alerts'">
                <tr v-for="row in records" :key="row.id"><td>{{ row.type }}</td><td>{{ row.severity }}</td><td>{{ row.title }}</td><td>{{ row.message }}</td><td>{{ row.resolved_at ? 'Resuelta' : 'Abierta' }}</td><td><div class="record-actions"><button class="table-action" @click="openPreview(row)">Ver</button><button class="table-action secondary-action" @click="printRecord(row)">Imprimir</button></div></td></tr>
              </template>
              <template v-else>
                <tr v-for="row in records" :key="row.id">
                  <td>{{ dateValue(row.measured_at || row.closed_on || row.adjusted_at) }}</td>
                  <td>{{ row.hose?.code || row.type || row.fuel_tank_id }}</td>
                  <td>{{ gallons(row.theoretical_liters ?? row.system_liters ?? row.liters, 3) }}</td>
                  <td>{{ gallons(row.physical_liters ?? row.counter_liters ?? 0, 3) }}</td>
                  <td>{{ gallons(row.difference_liters, 3) }}</td>
                  <td>{{ row.notes || row.reason || '' }}</td>
                  <td><div class="record-actions"><button class="table-action" @click="openPreview(row)">Ver</button><button class="table-action secondary-action" @click="openEdit(row)">Editar</button><button class="table-action secondary-action" @click="printRecord(row)">Imprimir</button></div></td>
                </tr>
              </template>
            </tbody>
          </table>
          <p v-if="isLoading" class="empty-state">Cargando registros...</p>
          <p v-else-if="!records.length" class="empty-state">No hay registros para esta vista.</p>
        </div>
      </article>
    </section>

    <div v-if="activeForm" class="modal-backdrop" @click.self="closeForm">
      <section class="modal fuel-modal">
        <header class="modal-header">
          <div><p class="eyebrow">Combustible</p><h3>{{ editingRecord ? 'Editar' : { dispatch: 'Registrar despacho', purchase: 'Registrar recepcion', tank: 'Registrar tanque', hose: 'Registrar manguera', partner: 'Registrar socio', measurement: 'Medicion fisica', closure: 'Cierre diario', adjustment: 'Ajuste de inventario' }[activeForm] }}</h3></div>
          <button class="close-button" @click="closeForm">x</button>
        </header>

        <form v-if="activeForm === 'dispatch'" class="form-grid modal-body" @submit.prevent="submitForm('dispatch')">
          <label>Manguera<select v-model="dispatchForm.fuel_hose_id" :disabled="Boolean(editingRecord)" required><option value="">Seleccionar</option><option v-for="hose in catalogs.hoses" :key="hose.id" :value="hose.id">{{ hose.code }} - {{ hose.name }}</option></select></label>
          <label>Tipo<select v-model="dispatchForm.recipient_type" :disabled="Boolean(editingRecord)"><option value="company_bus">Unidad empresa</option><option value="partner_vehicle">Vehiculo socio</option><option value="authorized_external">Externo autorizado</option></select></label>
          <label v-if="dispatchForm.recipient_type === 'company_bus'" class="span-2">Unidad<select v-model="dispatchForm.operation_bus_id" :disabled="Boolean(editingRecord)" required><option value="">Seleccionar</option><option v-for="bus in catalogs.company_vehicles" :key="bus.id" :value="bus.id">{{ bus.fleet_number }} - {{ bus.plate }} - {{ bus.brand }} {{ bus.model }}</option></select></label>
          <template v-if="dispatchForm.recipient_type === 'partner_vehicle'"><label>Socio<select v-model="dispatchForm.fuel_partner_id" :disabled="Boolean(editingRecord)" required><option value="">Seleccionar</option><option v-for="partner in catalogs.partners" :key="partner.id" :value="partner.id">{{ partner.name }}</option></select></label><label>Vehiculo<select v-model="dispatchForm.fuel_partner_vehicle_id" :disabled="Boolean(editingRecord)" required><option value="">Seleccionar</option><option v-for="vehicle in selectedPartnerVehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.plate }} - {{ vehicle.brand }} {{ vehicle.model }}</option></select></label></template>
          <label>Fecha y hora<input v-model="dispatchForm.dispatched_at" type="datetime-local" :disabled="Boolean(editingRecord)" required /></label><label>Contador inicial gal<input v-model="dispatchForm.hose_counter_start" type="number" step="0.001" :disabled="Boolean(editingRecord)" :placeholder="selectedHose ? gallons(selectedHose.current_counter, 3) : ''" required /></label><label>Contador final gal<input v-model="dispatchForm.hose_counter_end" type="number" step="0.001" :disabled="Boolean(editingRecord)" required /></label><label>Galones calculados<input :value="num(dispatchLiters, 3)" disabled /></label>
          <label>Km<input v-model="dispatchForm.km" type="number" min="0" /></label><label v-if="dispatchForm.recipient_type === 'company_bus'">Chofer<select v-model="dispatchForm.driver_id"><option value="">Seleccionar chofer</option><option v-for="driver in catalogs.drivers" :key="driver.id" :value="driver.id">{{ driver.code }} - {{ driver.first_name }} {{ driver.last_name }}</option></select></label><label v-else>Chofer<input v-model="dispatchForm.driver_name" /></label><label>Operador<input :value="currentUser.name" disabled /></label><label>Autorizado por<input v-model="dispatchForm.authorized_by" /></label><label class="span-2">Observaciones<textarea v-model="dispatchForm.notes" rows="3"></textarea></label>
          <div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">Guardar despacho</button></div>
        </form>

        <form v-else-if="activeForm === 'purchase'" class="form-grid modal-body" @submit.prevent="submitForm('purchase')">
          <label>Tanque<select v-model="purchaseForm.fuel_tank_id" :disabled="Boolean(editingRecord)" required><option value="">Seleccionar</option><option v-for="tank in catalogs.tanks" :key="tank.id" :value="tank.id">{{ tank.name }}</option></select></label><label>Fecha<input v-model="purchaseForm.received_at" type="date" required /></label><label>Proveedor<input v-model="purchaseForm.supplier" required /></label><label>Factura<input v-model="purchaseForm.invoice_number" /></label><label>Galones<input v-model="purchaseForm.liters" type="number" step="0.01" :disabled="Boolean(editingRecord)" required /></label><label>Costo por galon<input v-model="purchaseForm.unit_cost" type="number" step="0.0001" /></label><label>Medicion posterior gal<input v-model="purchaseForm.tank_liters_after" type="number" step="0.01" :disabled="Boolean(editingRecord)" /></label><label class="span-2">Notas<textarea v-model="purchaseForm.notes" rows="3"></textarea></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">Guardar recepcion</button></div>
        </form>

        <form v-else-if="activeForm === 'tank'" class="form-grid modal-body" @submit.prevent="submitForm('tank')">
          <label>Nombre<input v-model="tankForm.name" required /></label><label>Combustible<select v-model="tankForm.fuel_type"><option value="diesel">Diesel</option><option value="gasoline">Gasolina</option><option value="gnv">GNV</option><option value="other">Otro</option></select></label><label>Capacidad gal<input v-model="tankForm.capacity_liters" type="number" step="0.01" required /></label><label>Stock inicial gal<input v-model="tankForm.current_liters" type="number" step="0.01" /></label><label>Minimo gal<input v-model="tankForm.minimum_liters" type="number" step="0.01" /></label><label>Estado<select v-model="tankForm.status"><option value="active">Activo</option><option value="maintenance">Mantenimiento</option><option value="blocked">Bloqueado</option></select></label><label class="span-2">Ubicacion<input v-model="tankForm.location" /></label><label class="span-2">Notas<textarea v-model="tankForm.notes" rows="3"></textarea></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">Guardar tanque</button></div>
        </form>

        <form v-else-if="activeForm === 'hose'" class="form-grid modal-body" @submit.prevent="submitForm('hose')">
          <label>Tanque<select v-model="hoseForm.fuel_tank_id" required><option value="">Seleccionar</option><option v-for="tank in catalogs.tanks" :key="tank.id" :value="tank.id">{{ tank.name }}</option></select></label><label>Nombre<input v-model="hoseForm.name" required /></label><label>Contador actual gal<input v-model="hoseForm.current_counter" type="number" step="0.001" /></label><label>Diferencia permitida gal<input v-model="hoseForm.allowed_difference_liters" type="number" step="0.001" /></label><label>Estado<select v-model="hoseForm.status"><option value="active">Activa</option><option value="maintenance">Mantenimiento</option><option value="blocked">Bloqueada</option></select></label><label class="span-2">Codigo automatico<input value="Se asigna como M-ID al guardar" disabled /></label><label class="span-2">Notas<textarea v-model="hoseForm.notes" rows="3"></textarea></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">Guardar manguera</button></div>
        </form>

        <form v-else-if="activeForm === 'partner'" class="form-grid modal-body" @submit.prevent="submitForm('partner')">
          <label>Socio<input v-model="partnerForm.name" required /></label><label>Documento<input v-model="partnerForm.document" /></label><label>Telefono<input v-model="partnerForm.phone" /></label><label>Email<input v-model="partnerForm.email" type="email" /></label><label>Cupo mensual gal<input v-model="partnerForm.monthly_quota_liters" type="number" step="0.01" /></label><template v-if="!editingRecord"><label>Placa vehiculo<input v-model="partnerForm.vehicle_plate" /></label><label>Marca<input v-model="partnerForm.vehicle_brand" /></label><label>Modelo<input v-model="partnerForm.vehicle_model" /></label><label>Capacidad vehiculo gal<input v-model="partnerForm.vehicle_capacity" type="number" step="0.01" /></label><label>Rendimiento esperado<input v-model="partnerForm.vehicle_efficiency" type="number" step="0.001" /></label><label>Cupo vehiculo gal<input v-model="partnerForm.vehicle_quota" type="number" step="0.01" /></label></template><label class="span-2">Notas<textarea v-model="partnerForm.notes" rows="3"></textarea></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">Guardar socio</button></div>
        </form>

        <form v-else-if="activeForm === 'measurement'" class="form-grid modal-body" @submit.prevent="submitForm('measurement')">
          <label>Tanque<select v-model="measurementForm.fuel_tank_id" :disabled="Boolean(editingRecord)" required><option value="">Seleccionar</option><option v-for="tank in catalogs.tanks" :key="tank.id" :value="tank.id">{{ tank.name }}</option></select></label><label>Fecha<input v-model="measurementForm.measured_at" type="datetime-local" required /></label><label>Galones fisicos<input v-model="measurementForm.physical_liters" type="number" step="0.01" required /></label><label>Motivo<input v-model="measurementForm.reason" /></label><label class="span-2">Notas<textarea v-model="measurementForm.notes" rows="3"></textarea></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">Guardar medicion</button></div>
        </form>

        <form v-else-if="activeForm === 'closure'" class="form-grid modal-body" @submit.prevent="submitForm('closure')">
          <label>Manguera<select v-model="closureForm.fuel_hose_id" :disabled="Boolean(editingRecord)" required><option value="">Seleccionar</option><option v-for="hose in catalogs.hoses" :key="hose.id" :value="hose.id">{{ hose.code }} - {{ hose.name }}</option></select></label><label>Fecha<input v-model="closureForm.closed_on" type="date" :disabled="Boolean(editingRecord)" required /></label><label>Contador inicial gal<input v-model="closureForm.counter_start" type="number" step="0.001" required /></label><label>Contador final gal<input v-model="closureForm.counter_end" type="number" step="0.001" required /></label><label class="span-2">Notas<textarea v-model="closureForm.notes" rows="3"></textarea></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">Guardar cierre</button></div>
        </form>

        <form v-else-if="activeForm === 'adjustment'" class="form-grid modal-body" @submit.prevent="submitForm('adjustment')">
          <label>Tanque<select v-model="adjustmentForm.fuel_tank_id" :disabled="Boolean(editingRecord)" required><option value="">Seleccionar</option><option v-for="tank in catalogs.tanks" :key="tank.id" :value="tank.id">{{ tank.name }}</option></select></label><label>Fecha<input v-model="adjustmentForm.adjusted_at" type="datetime-local" :disabled="Boolean(editingRecord)" required /></label><label>Galones (+/-)<input v-model="adjustmentForm.liters" type="number" step="0.01" :disabled="Boolean(editingRecord)" required /></label><label>Tipo<select v-model="adjustmentForm.type" :disabled="Boolean(editingRecord)"><option value="correccion">Correccion</option><option value="merma">Merma</option><option value="fuga">Fuga</option><option value="calibracion">Calibracion</option><option value="otro">Otro</option></select></label><label class="span-2">Motivo<input v-model="adjustmentForm.reason" required /></label><label class="span-2">Notas<textarea v-model="adjustmentForm.notes" rows="3"></textarea></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">Guardar ajuste</button></div>
        </form>
      </section>
    </div>

    <div v-if="previewRecord" class="modal-backdrop" @click.self="closePreview">
      <section class="modal fuel-preview-modal">
        <header class="modal-header">
          <div>
            <p class="eyebrow">Vista previa</p>
            <h3>{{ recordTitle(previewRecord) }}</h3>
          </div>
          <button class="close-button" @click="closePreview">x</button>
        </header>
        <div class="modal-body fuel-preview-body">
          <section class="fuel-preview-document">
            <header>
              <div>
                <strong>SODASA</strong>
                <span>Control de combustible</span>
              </div>
              <small>{{ dateValue(previewRecord.created_at) || 'Registro operativo' }}</small>
            </header>
            <div class="fuel-preview-grid">
              <article v-for="[label, value] in recordFields(previewRecord)" :key="label">
                <span>{{ label }}</span>
                <strong>{{ value || 'N/D' }}</strong>
              </article>
            </div>
            <section v-if="previewRecord.notes || previewRecord.message" class="fuel-preview-notes">
              <span>Notas</span>
              <p>{{ previewRecord.notes || previewRecord.message }}</p>
            </section>
            <footer>
              <span>Generado por {{ currentUser.name }}</span>
              <span>Documento de control interno</span>
            </footer>
          </section>
          <div class="form-actions">
            <button class="secondary-action" @click="openEdit(previewRecord)" v-if="activeTab !== 'alerts'">Editar</button>
            <button @click="printRecord(previewRecord)">Imprimir</button>
          </div>
        </div>
      </section>
    </div>

    <section class="fuel-print-area" aria-hidden="true">
      <article class="fuel-print-document">
        <header class="fuel-print-header">
          <div>
            <p>SODASA</p>
            <h1>{{ printJob.type === 'report' ? `Reporte de ${tabs.find((tab) => tab.key === activeTab)?.label}` : recordTitle(printJob.record) }}</h1>
            <span>Modulo de combustible</span>
          </div>
          <aside>
            <strong>{{ printJob.generatedAt }}</strong>
            <span>Generado por {{ currentUser.name }}</span>
          </aside>
        </header>
        <section v-if="printJob.type === 'report'" class="fuel-print-summary">
          <div><span>Stock</span><strong>{{ gallons(dashboard.metrics.stock_liters) }} gal</strong></div>
          <div><span>Despachado mes</span><strong>{{ gallons(dashboard.metrics.month_liters) }} gal</strong></div>
          <div><span>Costo mes</span><strong>{{ money(dashboard.metrics.month_cost) }}</strong></div>
          <div><span>Registros</span><strong>{{ records.length }}</strong></div>
        </section>
        <section v-if="printJob.type === 'record' && printJob.record" class="fuel-print-fields">
          <div v-for="[label, value] in recordFields(printJob.record)" :key="label">
            <span>{{ label }}</span>
            <strong>{{ value || 'N/D' }}</strong>
          </div>
        </section>
        <table v-if="printJob.type === 'report'" class="fuel-print-table">
          <thead>
            <tr><th>Registro</th><th>Fecha</th><th>Detalle</th><th>Galones</th><th>Estado</th></tr>
          </thead>
          <tbody>
            <tr v-for="row in records" :key="row.id">
              <td>#{{ row.id }}</td>
              <td>{{ dateValue(row.dispatched_at || row.received_at || row.measured_at || row.closed_on || row.adjusted_at || row.created_at) }}</td>
              <td>{{ recipientLabel(row) || row.supplier || row.name || row.hose?.code || row.title || row.reason || row.type }}</td>
              <td>{{ gallons(row.liters ?? row.counter_liters ?? row.physical_liters ?? row.monthly_quota_liters ?? 0, 3) }}</td>
              <td>{{ row.status || (row.is_active === false ? 'Inactivo' : 'Activo') }}</td>
            </tr>
          </tbody>
        </table>
        <section class="fuel-print-signatures">
          <div><span>Preparado por</span></div>
          <div><span>Revisado por</span></div>
          <div><span>Autorizado por</span></div>
        </section>
      </article>
    </section>

    <div v-if="modal.isOpen" class="process-modal-backdrop" role="alertdialog" aria-modal="true" @click.self="closeModal"><section class="process-modal" :class="modal.type"><div class="process-modal-icon"><svg viewBox="0 0 24 24"><path v-if="modal.type === 'success'" d="M20 6 9 17l-5-5" /><path v-else d="M12 8v5m0 3h.01M10.3 3.8 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.8a2 2 0 0 0-3.4 0Z" /></svg></div><p class="eyebrow">{{ modal.type === 'success' ? 'Proceso completado' : 'Proceso detenido' }}</p><h3>{{ modal.title }}</h3><p>{{ modal.message }}</p><button @click="closeModal">Entendido</button></section></div>
  </main>
</template>
