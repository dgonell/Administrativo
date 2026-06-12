<script setup>
import { computed, ref } from 'vue'
import { labelFor } from '../../utils/labels'
import { resolveStorageUrl } from '../../services/api'

const props = defineProps({
  drivers: {
    type: Array,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  selectedDriverId: {
    type: String,
    default: '',
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
  canReports: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['create', 'reports', 'select'])
const search = ref('')
const status = ref('')
const licenseState = ref('')
const order = ref('name')
const activeFilters = computed(() => Number(Boolean(search.value.trim())) + Number(Boolean(status.value)) + Number(Boolean(licenseState.value)) + Number(order.value !== 'name'))

const filteredDrivers = computed(() => {
  const term = search.value.trim().toLowerCase()

  return props.drivers.filter((driver) => {
    const text = [
      driver.code,
      driver.first_name,
      driver.last_name,
      driver.identity_document,
      driver.phone,
      driver.license?.license_number,
      driver.department?.name,
      driver.position?.name,
    ].join(' ').toLowerCase()

    if (term && !text.includes(term)) return false
    if (status.value && driver.status !== status.value) return false
    if (licenseState.value === 'alert' && !licenseAlert(driver)) return false
    if (licenseState.value === 'missing' && driver.license?.license_number) return false

    return true
  }).sort((a, b) => {
    if (order.value === 'code') return a.code.localeCompare(b.code, 'es', { numeric: true })
    if (order.value === 'hire') return String(b.hire_date || '').localeCompare(String(a.hire_date || ''))
    return `${a.first_name} ${a.last_name}`.localeCompare(`${b.first_name} ${b.last_name}`, 'es')
  })
})

function licenseAlert(driver) {
  if (!driver.license?.expires_at) return false
  const limit = new Date()
  limit.setDate(limit.getDate() + 30)
  return new Date(driver.license.expires_at) <= limit
}

function initials(driver) {
  return `${driver?.first_name?.[0] ?? ''}${driver?.last_name?.[0] ?? ''}`.toUpperCase()
}

function clearFilters() {
  search.value = ''
  status.value = ''
  licenseState.value = ''
  order.value = 'name'
}
</script>

<template>
  <aside class="profile-sidebar">
    <header class="profile-sidebar-header">
      <div>
        <p class="eyebrow">Personal</p>
        <h2>Conductores</h2>
        <span>{{ filteredDrivers.length }} de {{ drivers.length }} registros</span>
      </div>
      <button v-if="canCreate" type="button" @click="emit('create')">Nuevo</button>
    </header>

    <div class="directory-toolbar">
      <input v-model="search" class="profile-search" placeholder="Buscar nombre, cedula o licencia" />
      <button v-if="canReports" type="button" class="secondary-action directory-report" title="Abrir reporte operativo" @click="emit('reports')">Reporte</button>
    </div>

    <div class="directory-filter-block">
      <div class="directory-filter-header">
        <span>Filtros</span>
        <button v-if="activeFilters" type="button" @click="clearFilters">Limpiar {{ activeFilters }}</button>
      </div>
      <div class="directory-filters">
        <select v-model="status" aria-label="Filtrar por estado">
          <option value="">Estado</option>
          <option value="active">Activos</option>
          <option value="resignation">Renuncias</option>
          <option value="dismissal">Despedidos</option>
        </select>
        <select v-model="licenseState" aria-label="Filtrar por licencia">
          <option value="">Licencia</option>
          <option value="alert">Por vencer</option>
          <option value="missing">Sin licencia</option>
        </select>
        <select v-model="order" aria-label="Ordenar choferes">
          <option value="name">Orden: nombre</option>
          <option value="code">Orden: codigo</option>
          <option value="hire">Orden: ingreso</option>
        </select>
      </div>
    </div>

    <div class="driver-roster" aria-label="Listado de conductores">
      <button
        v-for="driver in filteredDrivers"
        :key="driver.id"
        type="button"
        class="driver-card"
        :class="{ selected: selectedDriverId === String(driver.id) }"
        @click="emit('select', driver)"
      >
        <img
          v-if="driver.photo_path"
          class="avatar small-avatar"
          :src="resolveStorageUrl(driver.photo_path)"
          :alt="`Foto de ${driver.first_name} ${driver.last_name}`"
        />
        <span v-else class="avatar small-avatar">{{ initials(driver) }}</span>
        <span>
          <strong>{{ driver.first_name }} {{ driver.last_name }}</strong>
          <small>{{ driver.code }} - {{ labelFor(driver.status) }}</small>
        </span>
        <span v-if="licenseAlert(driver)" class="driver-alert" title="Licencia vencida o proxima a vencer">!</span>
      </button>

      <p v-if="!isLoading && filteredDrivers.length === 0" class="empty-state">No hay conductores con ese criterio.</p>
      <p v-if="isLoading" class="empty-state">Cargando perfiles...</p>
    </div>
  </aside>
</template>
