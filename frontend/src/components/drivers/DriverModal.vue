<script setup>
import NoticeMessage from '../common/NoticeMessage.vue'
import { DRIVER_MODULES } from '../../constants/navigation'
import DriverConductSection from './DriverConductSection.vue'
import DriverCreateForm from './DriverCreateForm.vue'
import DriverDetailGrid from './DriverDetailGrid.vue'
import DriverDocumentsSection from './DriverDocumentsSection.vue'
import DriverLeavesSection from './DriverLeavesSection.vue'
import DriverLicenseForm from './DriverLicenseForm.vue'
import DriverReports from './DriverReports.vue'
import DriverTerminationSection from './DriverTerminationSection.vue'

defineProps({
  activeDriver: { type: Object, default: null },
  catalogs: { type: Object, required: true },
  conductForm: { type: Object, required: true },
  documentForm: { type: Object, required: true },
  driverForm: { type: Object, required: true },
  drivers: { type: Array, required: true },
  errorMessage: { type: String, default: '' },
  isSaving: { type: Boolean, default: false },
  leaveForm: { type: Object, required: true },
  licenseAlerts: { type: Array, required: true },
  message: { type: String, default: '' },
  modalMode: { type: String, required: true },
  modalTitle: { type: String, required: true },
  rehireForm: { type: Object, required: true },
  terminationForm: { type: Object, required: true },
})

const emit = defineEmits([
  'close',
  'submit-conduct',
  'submit-document',
  'submit-driver',
  'submit-leave',
  'submit-license',
  'submit-rehire',
  'submit-termination',
])
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')">
    <section class="modal" role="dialog" aria-modal="true">
      <header class="modal-header">
        <div>
          <p class="eyebrow">Choferes</p>
          <h3>{{ modalTitle }}</h3>
        </div>
        <button class="close-button" type="button" aria-label="Cerrar" @click="emit('close')">X</button>
      </header>

      <NoticeMessage :message="message" type="success" />
      <NoticeMessage :message="errorMessage" />

      <div class="modal-body">
        <DriverCreateForm
          v-if="modalMode === DRIVER_MODULES.create"
          :catalogs="catalogs"
          :form="driverForm"
          :is-saving="isSaving"
          @submit="emit('submit-driver')"
        />
        <DriverDetailGrid v-if="modalMode === DRIVER_MODULES.file && activeDriver" :driver="activeDriver" />
        <DriverLicenseForm
          v-if="modalMode === DRIVER_MODULES.license && activeDriver"
          :form="driverForm"
          :is-saving="isSaving"
          @submit="emit('submit-license')"
        />
        <DriverDocumentsSection
          v-if="modalMode === DRIVER_MODULES.documents && activeDriver"
          :driver="activeDriver"
          :form="documentForm"
          :is-saving="isSaving"
          @submit="emit('submit-document')"
        />
        <DriverLeavesSection
          v-if="modalMode === DRIVER_MODULES.leaves && activeDriver"
          :driver="activeDriver"
          :form="leaveForm"
          :is-saving="isSaving"
          @submit="emit('submit-leave')"
        />
        <DriverConductSection
          v-if="modalMode === DRIVER_MODULES.conduct && activeDriver"
          :driver="activeDriver"
          :form="conductForm"
          :is-saving="isSaving"
          @submit="emit('submit-conduct')"
        />
        <DriverTerminationSection
          v-if="modalMode === DRIVER_MODULES.termination && activeDriver"
          :driver="activeDriver"
          :form="terminationForm"
          :is-saving="isSaving"
          :rehire-form="rehireForm"
          @submit-rehire="emit('submit-rehire')"
          @submit="emit('submit-termination')"
        />
        <DriverReports
          v-if="modalMode === DRIVER_MODULES.reports"
          :drivers="drivers"
          :license-alerts="licenseAlerts"
        />
      </div>
    </section>
  </div>
</template>
