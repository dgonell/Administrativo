<script setup>
import { ref } from 'vue'
import ErrorPopup from '../../components/common/ErrorPopup.vue'
import NoticeMessage from '../../components/common/NoticeMessage.vue'
import DriverCreateForm from '../../components/drivers/DriverCreateForm.vue'
import DriverDirectory from '../../components/drivers/DriverDirectory.vue'
import DriverProfileHeader from '../../components/drivers/DriverProfileHeader.vue'
import DriverReports from '../../components/drivers/DriverReports.vue'
import DriverSummaryPanel from '../../components/drivers/DriverSummaryPanel.vue'
import DriverWorkflowPanel from '../../components/drivers/DriverWorkflowPanel.vue'
import { useDrivers } from '../../composables/useDrivers'
import { DRIVER_MODULES } from '../../constants/navigation'

const props = defineProps({
  permissions: {
    type: Array,
    default: () => [],
  },
})

function can(permission) {
  return props.permissions.includes(permission)
}

const {
  activeDriver,
  activeModule,
  catalogs,
  conductForm,
  documentForm,
  driverForm,
  drivers,
  errorMessage,
  isLoading,
  isSaving,
  leaveForm,
  licenseAlerts,
  message,
  rehireForm,
  selectedDriverId,
  terminationForm,
  clearErrorMessage,
  openModal,
  prepareLicenseForm,
  selectDriver,
  setModule,
  submitConduct,
  submitDocument,
  submitDriver,
  submitLeave,
  submitLicense,
  submitPhoto,
  submitRehire,
  submitTermination,
} = useDrivers()

const activeTab = ref('resumen')

function openCreate() {
  activeTab.value = 'resumen'
  openModal(DRIVER_MODULES.create)
}

function openReports() {
  activeTab.value = 'resumen'
  openModal(DRIVER_MODULES.reports)
}

async function chooseDriver(driver) {
  activeTab.value = 'resumen'
  await selectDriver(driver)
}
</script>

<template>
    <main class="content profile-workspace">
      <DriverDirectory
        :drivers="drivers"
        :is-loading="isLoading"
        :selected-driver-id="selectedDriverId"
        :can-create="can('drivers.create')"
        :can-reports="can('drivers.view')"
        @create="openCreate"
        @reports="openReports"
        @select="chooseDriver"
      />

      <section class="profile-main">
        <NoticeMessage :message="message" type="success" />

        <section v-if="activeModule === DRIVER_MODULES.create && can('drivers.create')" class="profile-panel">
          <header class="section-header">
            <div>
              <p class="eyebrow">Personal de ruta</p>
              <h3>Nuevo conductor</h3>
            </div>
          </header>
          <DriverCreateForm :catalogs="catalogs" :form="driverForm" :is-saving="isSaving" @submit="submitDriver" />
        </section>

        <section v-else-if="activeModule === DRIVER_MODULES.reports" class="profile-panel">
          <header class="section-header">
            <div>
              <p class="eyebrow">Inteligencia operativa</p>
              <h3>Reporte de conductores</h3>
            </div>
          </header>
          <DriverReports :drivers="drivers" :license-alerts="licenseAlerts" />
        </section>

        <template v-else-if="activeDriver">
          <DriverProfileHeader v-if="can('drivers.photo')" :driver="activeDriver" @upload-photo="submitPhoto" />
          <DriverProfileHeader v-else :driver="activeDriver" />
          <DriverSummaryPanel :driver="activeDriver" />
          <DriverWorkflowPanel
            :active-tab="activeTab"
            :conduct-form="conductForm"
            :document-form="documentForm"
            :driver="activeDriver"
            :driver-form="driverForm"
            :is-saving="isSaving"
            :leave-form="leaveForm"
            :permissions="permissions"
            :rehire-form="rehireForm"
            :termination-form="terminationForm"
            @change-tab="activeTab = $event"
            @prepare-license="prepareLicenseForm"
            @submit-conduct="submitConduct"
            @submit-document="submitDocument"
            @submit-leave="submitLeave"
            @submit-license="submitLicense"
            @submit-rehire="submitRehire"
            @submit-termination="submitTermination"
          />
        </template>

        <section v-else class="profile-panel empty-profile">
          <p class="eyebrow">Personal de ruta</p>
          <h3>Selecciona un conductor</h3>
          <p>El expediente operativo aparecera aqui cuando selecciones un registro del directorio.</p>
        </section>
      </section>
    </main>

    <ErrorPopup :message="errorMessage" @close="clearErrorMessage" />
</template>
