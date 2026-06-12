<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import {
  assignOperationBusDriver, createOperationBus, deleteOperationBus, fetchDrivers,
  createOperationMaintenance, fetchOperationBusHistory, fetchOperationBuses,
  fetchOperationMaintenanceCatalogs, fetchOperationMaintenances, resolveStorageUrl, updateOperationBus,
  updateOperationBusMileage, updateOperationBusStatus, uploadOperationBusPhoto,
} from '../../services/api'
import MaintenanceWizard from '../../components/operations/MaintenanceWizard.vue'
import MaintenanceReports from '../../components/operations/MaintenanceReports.vue'

const props = defineProps({ permissions: { type: Array, default: () => [] } })
const buses = ref([])
const drivers = ref([])
const history = ref([])
const maintenances = ref([])
const maintenanceCatalogs = ref({ parts: [], tire_positions: [] })
const selectedId = ref(null)
const search = ref('')
const activeView = ref('fleet')
const profileTab = ref('summary')
const filters = reactive({ status: '', brand: '', type: '', assignment: '', order: 'fleet' })
const form = reactive(emptyForm())
const isLoading = ref(true)
const isSaving = ref(false)
const isFormOpen = ref(false)
const pendingPhoto = ref(null)
const modal = reactive({ isOpen: false, type: 'success', title: '', message: '' })
const alertModal = reactive({ isOpen: false })
const quickAction = reactive({ isOpen: false, mode: '', mileage: '', mileageDate: '', driverId: '' })
const isMaintenanceOpen = ref(false)

function can(permission) { return props.permissions.includes(permission) }
const canCreate = computed(() => can('operations.buses.create'))
const canUpdate = computed(() => can('operations.buses.update'))
const canPhoto = computed(() => can('operations.buses.photo'))
const canMileage = computed(() => can('operations.buses.mileage.update'))
const canAssignDriver = computed(() => can('operations.buses.driver.assign'))
const canUpdateStatus = computed(() => can('operations.buses.status.update'))
const canRetire = computed(() => can('operations.buses.retire'))
const canViewHistory = computed(() => can('operations.buses.history.view'))
const canViewMaintenance = computed(() => can('operations.maintenance.view'))
const canCreateMaintenance = computed(() => can('operations.maintenance.create'))
const selectedBus = computed(() => buses.value.find((bus) => bus.id === selectedId.value) ?? null)
const brands = computed(() => [...new Set(buses.value.map((bus) => bus.brand))].sort())
const metrics = computed(() => ({
  total: buses.value.length,
  operational: buses.value.filter((bus) => bus.status === 'operational').length,
  workshop: buses.value.filter((bus) => bus.status === 'workshop').length,
  inactive: buses.value.filter((bus) => bus.status === 'inactive').length,
  unassigned: buses.value.filter((bus) => !bus.driver_id).length,
}))
const activeBusFilters = computed(() => Number(Boolean(search.value.trim())) + Number(Boolean(filters.status)) + Number(Boolean(filters.brand)) + Number(Boolean(filters.type)) + Number(Boolean(filters.assignment)) + Number(filters.order !== 'fleet'))
const filteredBuses = computed(() => {
  const term = search.value.trim().toLowerCase()
  return buses.value.filter((bus) => {
    if (filters.status && bus.status !== filters.status) return false
    if (filters.brand && bus.brand !== filters.brand) return false
    if (filters.type && bus.vehicle_type !== filters.type) return false
    if (filters.assignment === 'assigned' && !bus.driver_id) return false
    if (filters.assignment === 'unassigned' && bus.driver_id) return false
    return !term || [bus.fleet_number, bus.brand, bus.model, bus.plate, bus.chassis].join(' ').toLowerCase().includes(term)
  }).sort((a, b) => {
    if (filters.order === 'year') return Number(b.year || 0) - Number(a.year || 0)
    if (filters.order === 'status') return a.status.localeCompare(b.status)
    return a.fleet_number.localeCompare(b.fleet_number, 'es', { numeric: true })
  })
})
const selectedHistory = computed(() => history.value.filter((item) => item.fleet_number === selectedBus.value?.fleet_number))
const selectedMaintenances = computed(() => maintenances.value.filter((item) => item.operation_bus_id === selectedBus.value?.id))
const dueMaintenance = computed(() => selectedMaintenances.value.find((item) => (item.next_due_date && dateValue(item.next_due_date) <= dateValue(new Date().toISOString())) || (item.next_due_mileage && selectedBus.value?.current_mileage >= item.next_due_mileage)))
function maintenanceDueStatus(service) {
  if (!service) return ''
  const today = new Date()
  const dueWindow = new Date(today)
  dueWindow.setDate(today.getDate() + 30)
  const dueDate = service.next_due_date ? new Date(service.next_due_date) : null
  const dueMileage = service.next_due_mileage ?? null
  const currentMileage = Number(selectedBus.value?.current_mileage || 0)
  const overdue = (dueDate && dueDate < today) || (dueMileage !== null && currentMileage >= dueMileage)
  const dueSoon = (dueDate && dueDate >= today && dueDate <= dueWindow) || (dueMileage !== null && dueMileage > currentMileage && dueMileage <= currentMileage + 500)
  if (overdue) return 'Atrasado'
  if (dueSoon) return 'Próximo'
  return 'Planificado'
}
function formatNextDue(service) {
  if (!service) return 'Sin programacion'
  const parts = []
  if (service.next_due_date) parts.push(`fecha ${dateValue(service.next_due_date)}`)
  if (service.next_due_mileage) parts.push(`km ${service.next_due_mileage.toLocaleString('es-DO')}`)
  return parts.join(' - ')
}
const nextDueMaintenance = computed(() => {
  if (!selectedBus.value) return null
  const today = new Date()
  const dueWindow = new Date(today)
  dueWindow.setDate(today.getDate() + 30)
  const currentMileage = Number(selectedBus.value.current_mileage || 0)
  const candidates = selectedMaintenances.value
    .map((service) => {
      const dueDate = service.next_due_date ? new Date(service.next_due_date) : null
      const dueMileage = service.next_due_mileage ?? null
      const overdue = (dueDate && dueDate < today) || (dueMileage !== null && currentMileage >= dueMileage)
      const dueSoon = (dueDate && dueDate >= today && dueDate <= dueWindow) || (dueMileage !== null && dueMileage > currentMileage && dueMileage <= currentMileage + 500)
      return {
        service,
        dueDateTs: dueDate ? dueDate.getTime() : Infinity,
        mileageGap: dueMileage !== null ? dueMileage - currentMileage : Infinity,
        overdue,
        dueSoon,
      }
    })
    .filter(({ service }) => service.next_due_date || service.next_due_mileage !== null)
  if (!candidates.length) return null
  candidates.sort((a, b) => {
    if (a.overdue !== b.overdue) return a.overdue ? -1 : 1
    if (a.dueSoon !== b.dueSoon) return a.dueSoon ? -1 : 1
    if (a.dueDateTs !== b.dueDateTs) return a.dueDateTs - b.dueDateTs
    return a.mileageGap - b.mileageGap
  })
  return candidates[0].service
})
const alerts = computed(() => {
  if (!selectedBus.value) return []
  const bus = selectedBus.value
  return [
    !bus.driver_id && 'Unidad sin chofer asignado.',
    !bus.capacity && 'Capacidad pendiente de registrar.',
    !bus.current_mileage && 'Kilometraje pendiente de actualizar.',
    bus.status === 'workshop' && 'Unidad actualmente en taller.',
    bus.status === 'inactive' && 'Unidad fuera de servicio.',
    dueMaintenance.value && 'La unidad tiene una revision de mantenimiento pendiente.',
  ].filter(Boolean)
})

function emptyForm() {
  return { id: null, fleet_number: '', brand: '', model: '', vehicle_type: 'bus', plate: '', chassis: '', year: '', capacity: '', color: '', current_mileage: '', mileage_updated_at: '', acquired_at: '', insurer: '', driver_id: '', status: 'operational', notes: '' }
}
function statusLabel(value) { return { operational: 'Operativo', workshop: 'En taller', inactive: 'Fuera de servicio' }[value] ?? value }
function typeLabel(value) { return { bus: 'Autobus', minibus: 'Minibus', van: 'Camioneta', truck: 'Camion' }[value] ?? value }
function serviceLabel(value) { return { oil_change: 'Cambio de aceite', filter_change: 'Cambio de filtros', tire_change: 'Cambio de neumaticos', part_replacement: 'Cambio de piezas', preventive: 'Mantenimiento preventivo', repair: 'Reparacion', other: 'Otro servicio' }[value] ?? value }
function maintenanceTotal(service) { return Number(service.labor_cost || 0) + (service.items ?? []).reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_cost || 0), 0) }
function dateValue(value) { return value ? String(value).split('T')[0] : '' }
function clean(value) { return String(value ?? '').trim() }
function showModal(type, title, message) { Object.assign(modal, { isOpen: true, type, title, message }) }
function closeModal() { modal.isOpen = false }
function openAlertModal() { alertModal.isOpen = true }
function closeAlertModal() { alertModal.isOpen = false }
function clearFilters() { Object.assign(filters, { status: '', brand: '', type: '', assignment: '', order: 'fleet' }); search.value = '' }
function selectBus(bus) { selectedId.value = bus.id; activeView.value = 'fleet'; profileTab.value = 'summary' }
function startCreate() { Object.assign(form, emptyForm()); pendingPhoto.value = null; isFormOpen.value = true }
function startEdit(bus = selectedBus.value) {
  if (!bus) return
  Object.assign(form, { ...emptyForm(), ...bus, driver_id: bus.driver_id ?? '', year: bus.year ?? '', capacity: bus.capacity ?? '', current_mileage: bus.current_mileage ?? '', mileage_updated_at: dateValue(bus.mileage_updated_at), acquired_at: dateValue(bus.acquired_at) })
  pendingPhoto.value = null
  isFormOpen.value = true
}
function closeForm() { isFormOpen.value = false; pendingPhoto.value = null; Object.assign(form, emptyForm()) }
function payload() {
  return { ...form, id: undefined, legacy_id: undefined, driver: undefined, photo_path: undefined, fleet_number: clean(form.fleet_number), brand: clean(form.brand), model: clean(form.model), year: form.year ? Number(form.year) : null, capacity: form.capacity ? Number(form.capacity) : null, current_mileage: form.current_mileage ? Number(form.current_mileage) : null, driver_id: form.driver_id ? Number(form.driver_id) : null, chassis: clean(form.chassis).toUpperCase() || null, plate: clean(form.plate).toUpperCase(), notes: clean(form.notes) || null, color: clean(form.color) || null, insurer: clean(form.insurer) || null, mileage_updated_at: form.mileage_updated_at || null, acquired_at: form.acquired_at || null }
}
async function loadData() {
  isLoading.value = true
  try {
    const [busData, historyData, maintenanceData, catalogs, driverData] = await Promise.all([fetchOperationBuses(), canViewHistory.value ? fetchOperationBusHistory() : Promise.resolve([]), canViewMaintenance.value ? fetchOperationMaintenances() : Promise.resolve([]), canViewMaintenance.value ? fetchOperationMaintenanceCatalogs() : Promise.resolve({ parts: [], tire_positions: [] }), props.permissions.includes('drivers.view') ? fetchDrivers() : Promise.resolve([])])
    buses.value = busData; history.value = historyData; maintenances.value = maintenanceData; maintenanceCatalogs.value = catalogs
    drivers.value = driverData.data ?? driverData
    if (!selectedBus.value && buses.value.length) selectedId.value = buses.value[0].id
  } catch (error) { showModal('error', 'No se pudo cargar la flota', error.message) } finally { isLoading.value = false }
}
async function submit() {
  isSaving.value = true
  try {
    const previous = buses.value.find((bus) => bus.id === form.id)
    let bus = form.id ? await updateOperationBus(form.id, payload()) : await createOperationBus(payload())
    if (form.id && previous) {
      if (canUpdateStatus.value && previous.status !== form.status) bus = await updateOperationBusStatus(bus.id, form.status)
      if (canMileage.value && (previous.current_mileage !== Number(form.current_mileage || 0) || dateValue(previous.mileage_updated_at) !== form.mileage_updated_at) && form.current_mileage !== '') bus = await updateOperationBusMileage(bus.id, { current_mileage: Number(form.current_mileage), mileage_updated_at: form.mileage_updated_at })
      if (canAssignDriver.value && String(previous.driver_id ?? '') !== String(form.driver_id ?? '')) bus = await assignOperationBusDriver(bus.id, form.driver_id)
    }
    if (pendingPhoto.value && canPhoto.value) await uploadOperationBusPhoto(bus.id, pendingPhoto.value)
    closeForm(); await loadData(); selectedId.value = bus.id
    showModal('success', 'Registro guardado', 'La informacion de la unidad se actualizo correctamente.')
  } catch (error) { showModal('error', 'No se pudo guardar', error.message) } finally { isSaving.value = false }
}
async function changeStatus(status) {
  if (!selectedBus.value || selectedBus.value.status === status) return
  if (status === 'inactive' && !window.confirm('Esta unidad quedara fuera de servicio. Deseas continuar?')) return
  try { await updateOperationBusStatus(selectedBus.value.id, status); await loadData(); showModal('success', 'Estado actualizado', 'El estado operativo de la unidad se actualizo correctamente.') }
  catch (error) { showModal('error', 'No se pudo cambiar el estado', error.message) }
}
function openQuickAction(mode) {
  if (!selectedBus.value) return
  Object.assign(quickAction, { isOpen: true, mode, mileage: selectedBus.value.current_mileage ?? '', mileageDate: dateValue(selectedBus.value.mileage_updated_at), driverId: selectedBus.value.driver_id ?? '' })
}
function closeQuickAction() { Object.assign(quickAction, { isOpen: false, mode: '', mileage: '', mileageDate: '', driverId: '' }) }
async function submitQuickAction() {
  if (!selectedBus.value) return
  isSaving.value = true
  try {
    if (quickAction.mode === 'mileage') {
      await updateOperationBusMileage(selectedBus.value.id, { current_mileage: Number(quickAction.mileage), mileage_updated_at: quickAction.mileageDate })
    }
    if (quickAction.mode === 'driver') await assignOperationBusDriver(selectedBus.value.id, quickAction.driverId)
    closeQuickAction()
    await loadData()
    showModal('success', 'Registro actualizado', 'El cambio operativo se guardo correctamente.')
  } catch (error) { showModal('error', 'No se pudo guardar el cambio', error.message) } finally { isSaving.value = false }
}
async function deactivate() {
  if (!selectedBus.value || !window.confirm(`Retirar de servicio la unidad ${selectedBus.value.fleet_number}?`)) return
  try { await deleteOperationBus(selectedBus.value.id); await loadData(); showModal('success', 'Unidad retirada', 'La unidad se conserva en el historial y quedo fuera de servicio.') }
  catch (error) { showModal('error', 'No se pudo retirar la unidad', error.message) }
}
async function uploadPhoto(event) {
  const file = event.target.files?.[0]
  if (!file || !selectedBus.value) return
  try { await uploadOperationBusPhoto(selectedBus.value.id, file); await loadData(); showModal('success', 'Foto actualizada', 'La imagen de la unidad se guardo correctamente.') }
  catch (error) { showModal('error', 'No se pudo subir la foto', error.message) }
}
async function submitMaintenance(payload) {
  isSaving.value = true
  try { await createOperationMaintenance(payload); isMaintenanceOpen.value = false; await loadData(); profileTab.value = 'maintenance'; showModal('success', 'Mantenimiento registrado', 'El servicio y sus insumos quedaron guardados en el historial de la unidad.') }
  catch (error) { showModal('error', 'No se pudo registrar el mantenimiento', error.message) } finally { isSaving.value = false }
}
function selectFormPhoto(event) { pendingPhoto.value = event.target.files?.[0] ?? null }
function handleKeydown(event) {
  if (event.key !== 'Escape') return
  if (quickAction.isOpen) closeQuickAction()
  else if (isMaintenanceOpen.value) isMaintenanceOpen.value = false
  else if (isFormOpen.value) closeForm()
  else if (modal.isOpen) closeModal()
}
onMounted(() => { loadData(); window.addEventListener('keydown', handleKeydown) })
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <main class="content profile-workspace operations-profile-workspace">
    <article class="profile-sidebar bus-directory">
      <header class="profile-sidebar-header">
        <div>
          <p class="eyebrow">Operaciones</p>
          <h2>Autobuses</h2>
          <span>{{ filteredBuses.length }} unidades encontradas</span>
        </div>
        <button v-if="canCreate" type="button" @click="startCreate">Nuevo</button>
      </header>

      <div class="directory-toolbar">
        <input v-model="search" class="profile-search" placeholder="Buscar ficha, placa, marca o chasis" />
        <button v-if="canViewMaintenance" type="button" class="secondary-action directory-report" @click="activeView = 'report'">Reporte</button>
      </div>

      <div class="bus-sidebar-tabs">
        <button :class="{ active: activeView === 'fleet' }" @click="activeView = 'fleet'">Flota</button>
        <button v-if="canViewHistory" :class="{ active: activeView === 'history' }" @click="activeView = 'history'">Historial</button>
      </div>

      <div class="directory-filter-block">
        <div class="directory-filter-header">
          <span>Filtros</span>
          <button v-if="activeBusFilters" type="button" @click="clearFilters">Limpiar {{ activeBusFilters }}</button>
        </div>
        <div class="directory-filters">
          <select v-model="filters.status" aria-label="Filtrar por estado"><option value="">Estado</option><option value="operational">Operativos</option><option value="workshop">En taller</option><option value="inactive">Fuera servicio</option></select>
          <select v-model="filters.assignment" aria-label="Filtrar por asignacion"><option value="">Asignacion</option><option value="assigned">Con chofer</option><option value="unassigned">Sin chofer</option></select>
          <select v-model="filters.brand" aria-label="Filtrar por marca"><option value="">Marca</option><option v-for="brand in brands" :key="brand">{{ brand }}</option></select>
          <select v-model="filters.type" aria-label="Filtrar por tipo"><option value="">Tipo</option><option value="bus">Autobus</option><option value="minibus">Minibus</option><option value="van">Camioneta</option><option value="truck">Camion</option></select>
          <select v-model="filters.order" aria-label="Ordenar unidades"><option value="fleet">Orden: ficha</option><option value="year">Orden: ano</option><option value="status">Orden: estado</option></select>
        </div>
      </div>
      <div class="bus-list">
        <button v-for="bus in filteredBuses" :key="bus.id" :class="{ selected: selectedId === bus.id }" @click="selectBus(bus)"><span class="bus-number">{{ bus.fleet_number }}</span><span><strong>{{ bus.brand }} {{ bus.model }}</strong><small>{{ bus.plate }} - {{ statusLabel(bus.status) }}</small></span><i class="bus-status-dot" :class="bus.status"></i></button>
        <p v-if="isLoading" class="empty-state">Cargando unidades...</p>
        <p v-else-if="!filteredBuses.length" class="empty-state">No hay unidades con estos criterios.</p>
      </div>
    </article>

    <section class="profile-main">
      <section v-if="activeView === 'fleet'" class="operations-metrics bus-main-metrics">
        <button v-for="item in [{ key: '', label: 'Total', value: metrics.total }, { key: 'operational', label: 'Operativos', value: metrics.operational }, { key: 'workshop', label: 'En taller', value: metrics.workshop }, { key: 'inactive', label: 'Fuera servicio', value: metrics.inactive }]" :key="item.label" :class="{ active: filters.status === item.key }" @click="filters.status = item.key"><span>{{ item.label }}</span><strong>{{ item.value }}</strong></button>
        <button :class="{ active: filters.assignment === 'unassigned' }" @click="filters.assignment = filters.assignment === 'unassigned' ? '' : 'unassigned'"><span>Sin chofer</span><strong>{{ metrics.unassigned }}</strong></button>
      </section>

      <section v-if="activeView === 'history'" class="profile-panel operations-history-general">
        <header class="section-header"><div><p class="eyebrow">Trazabilidad</p><h3>Historial general de la flota</h3></div></header>
        <div class="bus-history"><ul><li v-for="item in history" :key="item.id"><strong>{{ item.action }} - unidad {{ item.fleet_number }}</strong><span>{{ item.detail }} - {{ dateValue(item.created_at) }}</span></li></ul></div>
      </section>

      <section v-else-if="activeView === 'report'" class="profile-panel report-fullscreen-panel">
        <MaintenanceReports :maintenances="maintenances" :buses="buses" />
      </section>

      <article v-else-if="selectedBus" class="profile-panel bus-profile">
        <header class="bus-profile-header">
          <div class="bus-profile-identity"><label v-if="canPhoto" class="bus-photo-control" title="Cambiar foto"><input type="file" accept="image/*" @change="uploadPhoto" /><img v-if="selectedBus.photo_path" :src="resolveStorageUrl(selectedBus.photo_path)" /><span v-else>{{ selectedBus.fleet_number }}</span></label><div v-else class="bus-photo-control static"><img v-if="selectedBus.photo_path" :src="resolveStorageUrl(selectedBus.photo_path)" /><span v-else>{{ selectedBus.fleet_number }}</span></div><div><p class="eyebrow">{{ typeLabel(selectedBus.vehicle_type) }}</p><h3>Unidad {{ selectedBus.fleet_number }}</h3><span class="bus-state" :class="selectedBus.status">{{ statusLabel(selectedBus.status) }}</span></div></div>
          <div class="bus-profile-controls">
            <label class="bus-status-select">
              <span>Estado</span>
              <select :value="selectedBus.status" :disabled="!canUpdateStatus" @change="changeStatus($event.target.value)">
                <option value="operational">Operativo</option>
                <option value="workshop">En taller</option>
                <option value="inactive">Fuera de servicio</option>
              </select>
            </label>
            <div v-if="canUpdate || canMileage || canAssignDriver || canRetire" class="bus-actions"><button v-if="canUpdate" class="icon-action" title="Editar ficha completa" data-tooltip="Editar ficha completa" @click="startEdit()"><svg viewBox="0 0 24 24"><path d="m14 6 4 4M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Z" /></svg></button><button v-if="canMileage" class="icon-action" title="Actualizar kilometraje" data-tooltip="Actualizar kilometraje" @click="openQuickAction('mileage')"><svg viewBox="0 0 24 24"><path d="M5 19a8 8 0 1 1 14 0M12 13l3-3M4 19h16" /></svg></button><button v-if="canAssignDriver" class="icon-action" title="Asignar chofer" data-tooltip="Asignar chofer" @click="openQuickAction('driver')"><svg viewBox="0 0 24 24"><path d="M16 20a4 4 0 0 0-8 0M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7-1v6m-3-3h6" /></svg></button><button v-if="canRetire && selectedBus.status !== 'inactive'" class="icon-action danger-action" title="Retirar unidad" data-tooltip="Retirar unidad" @click="deactivate"><svg viewBox="0 0 24 24"><path d="M5 12h14" /></svg></button></div>
          </div>
        </header>
        <div v-if="alerts.length || nextDueMaintenance" class="bus-notification-strip">
          <button v-if="alerts.length" type="button" class="bus-notification warning" @click="openAlertModal">
            <strong>{{ alerts.length }}</strong>
            <span>Atenciones operativas</span>
          </button>
          <button v-if="nextDueMaintenance" type="button" class="bus-notification info" @click="openAlertModal">
            <strong>{{ maintenanceDueStatus(nextDueMaintenance) }}</strong>
            <span>{{ serviceLabel(nextDueMaintenance.service_type) }}</span>
          </button>
        </div>
        <nav class="bus-profile-tabs"><button :class="{ active: profileTab === 'summary' }" @click="profileTab = 'summary'">Resumen</button><button :class="{ active: profileTab === 'technical' }" @click="profileTab = 'technical'">Datos tecnicos</button><button v-if="canViewMaintenance" :class="{ active: profileTab === 'maintenance' }" @click="profileTab = 'maintenance'">Mantenimientos</button><button v-if="canViewHistory" :class="{ active: profileTab === 'history' }" @click="profileTab = 'history'">Historial</button></nav>
        <div v-if="profileTab === 'summary'" class="bus-detail-grid">
          <div><span>Marca y modelo</span><strong>{{ selectedBus.brand }} {{ selectedBus.model }}</strong></div><div><span>Placa</span><strong>{{ selectedBus.plate }}</strong></div><div><span>Chasis</span><strong>{{ selectedBus.chassis || 'Pendiente' }}</strong></div><div><span>Ano y color</span><strong>{{ selectedBus.year || 'Pendiente' }} - {{ selectedBus.color || 'Color pendiente' }}</strong></div><div><span>Capacidad</span><strong>{{ selectedBus.capacity ? `${selectedBus.capacity} pasajeros` : 'Pendiente' }}</strong></div><div><span>Kilometraje</span><strong>{{ selectedBus.current_mileage ? `${selectedBus.current_mileage.toLocaleString('es-DO')} km` : 'Pendiente' }}</strong></div><div><span>Chofer asignado</span><strong>{{ selectedBus.driver ? `${selectedBus.driver.code} - ${selectedBus.driver.first_name} ${selectedBus.driver.last_name}` : 'Sin asignar' }}</strong></div><div><span>Aseguradora</span><strong>{{ selectedBus.insurer || 'Pendiente' }}</strong></div>
        </div>
        <div v-if="profileTab === 'technical'" class="bus-detail-grid"><div><span>Chasis</span><strong>{{ selectedBus.chassis || 'Pendiente' }}</strong></div><div><span>Tipo de vehiculo</span><strong>{{ typeLabel(selectedBus.vehicle_type) }}</strong></div><div><span>Ano</span><strong>{{ selectedBus.year || 'Pendiente' }}</strong></div><div><span>Color</span><strong>{{ selectedBus.color || 'Pendiente' }}</strong></div><div><span>Adquisicion</span><strong>{{ dateValue(selectedBus.acquired_at) || 'Pendiente' }}</strong></div><div><span>Aseguradora</span><strong>{{ selectedBus.insurer || 'Pendiente' }}</strong></div></div>
        <section v-if="profileTab === 'maintenance'" class="bus-maintenance-panel"><header><div><h4>Mantenimientos registrados</h4><span>{{ selectedMaintenances.length }} servicios</span></div><button v-if="canCreateMaintenance" @click="isMaintenanceOpen = true">Registrar servicio</button></header><article v-for="service in selectedMaintenances" :key="service.id"><div class="maintenance-record-header"><div><strong>{{ serviceLabel(service.service_type) }}</strong><span>{{ dateValue(service.service_date) }} - {{ service.mileage ? `${service.mileage.toLocaleString('es-DO')} km` : 'Sin kilometraje' }}</span></div><strong>RD$ {{ maintenanceTotal(service).toLocaleString('es-DO') }}</strong></div><ul><li v-for="row in service.items" :key="row.id">{{ row.quantity }} x {{ row.name }}<small v-if="row.tire_position"> - {{ row.tire_position }}</small></li></ul><footer v-if="service.next_due_date || service.next_due_mileage"><span>Proxima revision: {{ dateValue(service.next_due_date) || 'sin fecha' }}<template v-if="service.next_due_mileage"> - {{ service.next_due_mileage.toLocaleString('es-DO') }} km</template></span></footer></article><p v-if="!selectedMaintenances.length">Esta unidad aun no tiene mantenimientos registrados.</p></section>
        <section v-if="!['history', 'maintenance'].includes(profileTab)" class="bus-notes"><span>Observaciones</span><p>{{ selectedBus.notes || 'Sin observaciones registradas.' }}</p></section>
        <section v-if="profileTab === 'history' && canViewHistory" class="bus-history"><h4>Linea de tiempo</h4><ul><li v-for="item in selectedHistory" :key="item.id"><strong>{{ item.action }}</strong><span>{{ item.detail }} - {{ dateValue(item.created_at) }}</span></li></ul></section>
      </article>

      <section v-else class="profile-panel empty-profile">
        <p class="eyebrow">Control de flota</p>
        <h3>Selecciona una unidad</h3>
        <p>El expediente operativo aparecera aqui cuando selecciones una unidad del directorio.</p>
      </section>
    </section>

    <div v-if="isFormOpen" class="modal-backdrop" @click.self="closeForm"><section class="modal bus-form-modal"><header class="modal-header"><div><p class="eyebrow">{{ form.id ? 'Actualizacion' : 'Nuevo registro' }}</p><h3>{{ form.id ? `Editar unidad ${form.fleet_number}` : 'Registrar unidad' }}</h3></div><button class="close-button" @click="closeForm">x</button></header><form class="form-grid modal-body" @submit.prevent="submit">
      <label v-if="canPhoto" class="span-2 bus-form-photo">Foto de la unidad<input type="file" accept="image/*" @change="selectFormPhoto" /><small>{{ pendingPhoto ? pendingPhoto.name : (form.photo_path ? 'La unidad ya tiene una foto registrada.' : 'Selecciona una imagen JPG, PNG o WEBP.') }}</small></label><label>Ficha<input v-model="form.fleet_number" required /></label><label>Tipo<select v-model="form.vehicle_type"><option value="bus">Autobus</option><option value="minibus">Minibus</option><option value="van">Camioneta</option><option value="truck">Camion</option></select></label><label>Placa<input v-model="form.plate" required /></label><label>Marca<input v-model="form.brand" required /></label><label>Modelo<input v-model="form.model" required /></label><label>Chasis<input v-model="form.chassis" /></label><label>Ano<input v-model="form.year" type="number" min="1950" max="2100" /></label><label>Color<input v-model="form.color" /></label><label>Capacidad<input v-model="form.capacity" type="number" min="1" max="100" /></label><label>Kilometraje<input v-model="form.current_mileage" type="number" min="0" :disabled="form.id && !canMileage" /></label><label>Actualizacion kilometraje<input v-model="form.mileage_updated_at" type="date" :disabled="form.id && !canMileage" /></label><label>Fecha de adquisicion<input v-model="form.acquired_at" type="date" /></label><label>Aseguradora<input v-model="form.insurer" /></label><label>Estado<select v-model="form.status" :disabled="form.id && !canUpdateStatus"><option value="operational">Operativo</option><option value="workshop">En taller</option><option value="inactive">Fuera de servicio</option></select></label><label class="span-2">Chofer asignado<select v-model="form.driver_id" :disabled="form.id && !canAssignDriver"><option value="">Sin asignar</option><option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.code }} - {{ driver.first_name }} {{ driver.last_name }}</option></select></label><label class="span-2">Observaciones<textarea v-model="form.notes" rows="3"></textarea></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeForm">Cancelar</button><button :disabled="isSaving">{{ isSaving ? 'Guardando...' : 'Guardar unidad' }}</button></div>
    </form></section></div>
    <div v-if="quickAction.isOpen" class="modal-backdrop" @click.self="closeQuickAction"><section class="modal bus-quick-modal"><header class="modal-header"><div><p class="eyebrow">Actualizacion rapida</p><h3>{{ quickAction.mode === 'mileage' ? 'Actualizar kilometraje' : 'Asignar chofer' }}</h3></div><button class="close-button" @click="closeQuickAction">x</button></header><form class="modal-body form-grid" @submit.prevent="submitQuickAction"><template v-if="quickAction.mode === 'mileage'"><label>Kilometraje actual<input v-model="quickAction.mileage" type="number" min="0" required /></label><label>Fecha de actualizacion<input v-model="quickAction.mileageDate" type="date" required /></label></template><label v-else class="span-2">Chofer<select v-model="quickAction.driverId"><option value="">Sin asignar</option><option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.code }} - {{ driver.first_name }} {{ driver.last_name }}</option></select></label><div class="form-actions span-2"><button type="button" class="secondary-action" @click="closeQuickAction">Cancelar</button><button :disabled="isSaving">Guardar cambio</button></div></form></section></div>
    <div v-if="alertModal.isOpen && selectedBus" class="modal-backdrop" @click.self="closeAlertModal">
      <section class="modal bus-alert-modal">
        <header class="modal-header">
          <div>
            <p class="eyebrow">Notificaciones</p>
            <h3>Unidad {{ selectedBus.fleet_number }}</h3>
          </div>
          <button class="close-button" @click="closeAlertModal">x</button>
        </header>
        <div class="modal-body bus-alert-modal-body">
          <section v-if="alerts.length" class="bus-alert-detail">
            <strong>Atencion operativa</strong>
            <ul>
              <li v-for="alert in alerts" :key="alert">{{ alert }}</li>
            </ul>
          </section>
          <section class="bus-alert-detail">
            <strong>Proximo mantenimiento</strong>
            <div v-if="nextDueMaintenance">
              <span>{{ serviceLabel(nextDueMaintenance.service_type) }}</span>
              <p>{{ maintenanceDueStatus(nextDueMaintenance) }} - {{ formatNextDue(nextDueMaintenance) }}</p>
              <button v-if="canCreateMaintenance" type="button" @click="closeAlertModal(); isMaintenanceOpen = true">Registrar servicio</button>
            </div>
            <p v-else>Esta unidad no tiene proximas revisiones programadas.</p>
          </section>
        </div>
      </section>
    </div>
    <div v-if="isMaintenanceOpen && selectedBus" class="modal-backdrop" @click.self="isMaintenanceOpen = false"><MaintenanceWizard :bus="selectedBus" :catalogs="maintenanceCatalogs" :is-saving="isSaving" @close="isMaintenanceOpen = false" @submit="submitMaintenance" /></div>
    <div v-if="modal.isOpen" class="process-modal-backdrop" role="alertdialog" aria-modal="true" @click.self="closeModal"><section class="process-modal" :class="modal.type"><div class="process-modal-icon"><svg viewBox="0 0 24 24"><path v-if="modal.type === 'success'" d="M20 6 9 17l-5-5" /><path v-else d="M12 8v5m0 3h.01M10.3 3.8 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.8a2 2 0 0 0-3.4 0Z" /></svg></div><p class="eyebrow">{{ modal.type === 'success' ? 'Proceso completado' : 'Proceso detenido' }}</p><h3>{{ modal.title }}</h3><p>{{ modal.message }}</p><button @click="closeModal">Entendido</button></section></div>
  </main>
</template>

