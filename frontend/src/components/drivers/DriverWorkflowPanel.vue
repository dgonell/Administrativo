<script setup>
import { computed } from 'vue'
import DriverConductSection from './DriverConductSection.vue'
import DriverCompleteSummary from './DriverCompleteSummary.vue'
import DriverDocumentsSection from './DriverDocumentsSection.vue'
import DriverLeavesSection from './DriverLeavesSection.vue'
import DriverLicenseForm from './DriverLicenseForm.vue'
import DriverTerminationSection from './DriverTerminationSection.vue'
import { normalizeDate } from '../../utils/dates'
import { labelFor } from '../../utils/labels'

const props = defineProps({
  activeTab: {
    type: String,
    required: true,
  },
  conductForm: {
    type: Object,
    required: true,
  },
  documentForm: {
    type: Object,
    required: true,
  },
  driver: {
    type: Object,
    required: true,
  },
  driverForm: {
    type: Object,
    required: true,
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
  leaveForm: {
    type: Object,
    required: true,
  },
  rehireForm: {
    type: Object,
    required: true,
  },
  terminationForm: {
    type: Object,
    required: true,
  },
  permissions: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'change-tab',
  'prepare-license',
  'submit-conduct',
  'submit-document',
  'submit-leave',
  'submit-license',
  'submit-rehire',
  'submit-termination',
])

const tabs = computed(() => [
  { id: 'resumen', label: 'Resumen', permission: 'drivers.view' },
  { id: 'completo', label: 'Vista completa', permission: 'drivers.view' },
  { id: 'licencia', label: 'Licencia', permission: 'drivers.update' },
  { id: 'documentos', label: 'Documentos', permission: 'drivers.documents.manage' },
  { id: 'permisos', label: 'Permisos', permission: 'drivers.leaves.manage' },
  { id: 'conducta', label: 'Conducta', permission: 'drivers.conduct.manage' },
  { id: 'salida', label: 'Salida', permission: 'drivers.termination.manage' },
].filter((tab) => props.permissions.includes(tab.permission)))

const recentItems = computed(() => [
  ...(props.driver.documents ?? []).map((item) => ({
    id: `doc-${item.id}`,
    title: item.name,
    meta: `Documento - ${labelFor(item.status)} - vence ${normalizeDate(item.expires_at) || 'no aplica'}`,
  })),
  ...(props.driver.medical_leaves ?? []).map((item) => ({
    id: `leave-${item.id}`,
    title: item.leave_type,
    meta: `Permiso - ${normalizeDate(item.started_at)} - ${labelFor(item.status)}`,
  })),
  ...(props.driver.conduct_reports ?? []).map((item) => ({
    id: `conduct-${item.id}`,
    title: item.type,
    meta: `Conducta - ${normalizeDate(item.event_date)} - ${labelFor(item.severity)}`,
  })),
].slice(0, 8))

function changeTab(tab) {
  emit('change-tab', tab)
  if (tab === 'licencia') {
    emit('prepare-license')
  }
}
</script>

<template>
  <section class="workflow-panel">
    <nav class="workflow-tabs" aria-label="Flujo del perfil">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        :class="{ active: activeTab === tab.id }"
        @click="changeTab(tab.id)"
      >
        {{ tab.label }}
      </button>
    </nav>

    <article v-if="activeTab === 'resumen'" class="profile-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Actividad reciente</p>
          <h3>Ultimos movimientos</h3>
        </div>
      </header>
      <ul class="compact-list">
        <li v-for="item in recentItems" :key="item.id">
          <strong>{{ item.title }}</strong>
          <span>{{ item.meta }}</span>
        </li>
        <li v-if="recentItems.length === 0"><span>Sin actividad registrada.</span></li>
      </ul>
    </article>

    <article v-if="activeTab === 'completo'" class="profile-panel">
      <DriverCompleteSummary :driver="driver" @go-to="changeTab" />
    </article>

    <article v-if="activeTab === 'licencia'" class="profile-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Actualizar</p>
          <h3>Licencia de conducir</h3>
        </div>
      </header>
      <DriverLicenseForm :form="driverForm" :is-saving="isSaving" @submit="emit('submit-license')" />
    </article>

    <article v-if="activeTab === 'documentos'" class="profile-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Expediente</p>
          <h3>Documentos</h3>
        </div>
      </header>
      <DriverDocumentsSection :driver="driver" :form="documentForm" :is-saving="isSaving" @submit="emit('submit-document')" />
    </article>

    <article v-if="activeTab === 'permisos'" class="profile-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Historial</p>
          <h3>Permisos</h3>
        </div>
      </header>
      <DriverLeavesSection :driver="driver" :form="leaveForm" :is-saving="isSaving" @submit="emit('submit-leave')" />
    </article>

    <article v-if="activeTab === 'conducta'" class="profile-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Seguimiento</p>
          <h3>Conducta e incidentes</h3>
        </div>
      </header>
      <DriverConductSection :driver="driver" :form="conductForm" :is-saving="isSaving" @submit="emit('submit-conduct')" />
    </article>

    <article v-if="activeTab === 'salida'" class="profile-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Relacion laboral</p>
          <h3>Desvinculacion</h3>
        </div>
      </header>
      <DriverTerminationSection
        :driver="driver"
        :form="terminationForm"
        :is-saving="isSaving"
        :rehire-form="rehireForm"
        @submit="emit('submit-termination')"
        @submit-rehire="emit('submit-rehire')"
      />
    </article>
  </section>
</template>
