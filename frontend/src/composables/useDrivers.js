import { computed, onMounted, reactive, ref, watch } from 'vue'
import { DRIVER_MODULES } from '../constants/navigation'
import {
  createDriver,
  createDriverConductReport,
  createDriverDocument,
  createDriverEmergencyContact,
  createDriverMedicalLeave,
  createDriverTerminationRecord,
  fetchDriver,
  fetchDriverCatalogs,
  fetchDrivers,
  rehireDriver,
  updateDriver,
  uploadDriverPhoto,
} from '../services/api'
import { normalizeDate } from '../utils/dates'
import { formatDominicanDocument } from '../utils/formatters'
import { cleanPayload } from '../utils/payload'

const emptyDriverForm = () => ({
  code: '',
  first_name: '',
  last_name: '',
  identity_document: '',
  tss_worker_code: '',
  birth_date: '',
  phone: '',
  contact_name: '',
  email: '',
  address: '',
  department_id: '',
  position_id: '',
  contract_type_id: '',
  hire_date: '',
  status: 'active',
  rehire_status: 'yes',
  notes: '',
  emergency_contact: {
    name: '',
    relationship: '',
    phone: '',
    secondary_phone: '',
  },
  license: {
    license_number: '',
    category: '',
    issued_at: '',
    expires_at: '',
    issuing_entity: 'Direccion General de Seguridad de Transito y Transporte Terrestre',
    restrictions: '',
    observations: '',
  },
})

const DRIVER_DRAFT_STORAGE_KEY = 'sodasa.driver.create.draft'

export function useDrivers() {
  const activeModule = ref(DRIVER_MODULES.list)
  const modalMode = ref('')
  const drivers = ref([])
  const selectedDriverId = ref('')
  const selectedDriver = ref(null)
  const catalogs = reactive({ departments: [], positions: [], contract_types: [] })
  const isLoading = ref(false)
  const isSaving = ref(false)
  const message = ref('')
  const errorMessage = ref('')

  const driverForm = reactive(emptyDriverForm())
  const documentForm = reactive({
    document_type: 'cedula',
    name: '',
    file_path: '',
    issued_at: '',
    expires_at: '',
    status: 'valid',
    notes: '',
  })
  const leaveForm = reactive({
    leave_type: 'Permiso medico',
    started_at: '',
    ended_at: '',
    reason: '',
    description: '',
    file_path: '',
    status: 'pending',
  })
  const conductForm = reactive({
    event_date: '',
    type: 'Evaluacion de conducta',
    severity: 'low',
    description: '',
    action_taken: '',
    file_path: '',
    status: 'open',
  })
  const terminationForm = reactive({
    termination_date: '',
    termination_type: 'resignation',
    reason: '',
    description: '',
    rehire_status: 'yes',
    rehire_reason: '',
    file_path: '',
  })
  const rehireForm = reactive({
    hire_date: '',
    reason: '',
    rehire_status: 'yes',
    notes: '',
  })

  const activeDriver = computed(() => selectedDriver.value ?? drivers.value[0] ?? null)
  const metrics = computed(() => [
    { label: 'Activos', value: drivers.value.filter((driver) => driver.status === 'active').length },
    { label: 'Renuncias', value: drivers.value.filter((driver) => driver.status === 'resignation').length },
    { label: 'Despedidos', value: drivers.value.filter((driver) => ['dismissal', 'terminated'].includes(driver.status)).length },
    { label: 'Total', value: drivers.value.length },
  ])
  const licenseAlerts = computed(() => {
    const today = new Date()
    const warningLimit = new Date()
    warningLimit.setDate(today.getDate() + 30)

    return drivers.value.filter((driver) => {
      if (!driver.license?.expires_at) return false
      return new Date(driver.license.expires_at) <= warningLimit
    })
  })
  const modalTitle = computed(() => {
    if (modalMode.value === DRIVER_MODULES.create) return 'Nuevo conductor'
    if (modalMode.value === DRIVER_MODULES.reports) return 'Reporte de conductores'
    const name = activeDriver.value ? `${activeDriver.value.code} - ${activeDriver.value.first_name} ${activeDriver.value.last_name}` : 'Conductor'
    return `${modalMode.value}: ${name}`
  })

  function resetDriverForm() {
    Object.assign(driverForm, emptyDriverForm())
  }

  function driverFormSnapshot() {
    return JSON.parse(JSON.stringify(driverForm))
  }

  function mergeDriverDraft(draft) {
    const emptyForm = emptyDriverForm()
    return {
      ...emptyForm,
      ...draft,
      emergency_contact: {
        ...emptyForm.emergency_contact,
        ...(draft?.emergency_contact ?? {}),
      },
      license: {
        ...emptyForm.license,
        ...(draft?.license ?? {}),
      },
    }
  }

  function driverDraftHasData(draft = driverFormSnapshot()) {
    const emptyForm = emptyDriverForm()
    return JSON.stringify(mergeDriverDraft(draft)) !== JSON.stringify(emptyForm)
  }

  function saveDriverDraft() {
    if (typeof localStorage === 'undefined') return
    const draft = driverFormSnapshot()

    if (!driverDraftHasData(draft)) {
      localStorage.removeItem(DRIVER_DRAFT_STORAGE_KEY)
      return
    }

    localStorage.setItem(DRIVER_DRAFT_STORAGE_KEY, JSON.stringify(draft))
  }

  function loadDriverDraft() {
    if (typeof localStorage === 'undefined') return false
    const storedDraft = localStorage.getItem(DRIVER_DRAFT_STORAGE_KEY)
    if (!storedDraft) return false

    try {
      const draft = mergeDriverDraft(JSON.parse(storedDraft))
      Object.assign(driverForm, draft)
      return driverDraftHasData(draft)
    } catch {
      localStorage.removeItem(DRIVER_DRAFT_STORAGE_KEY)
      return false
    }
  }

  function clearDriverDraft() {
    if (typeof localStorage === 'undefined') return
    localStorage.removeItem(DRIVER_DRAFT_STORAGE_KEY)
  }

  function preserveCreateDraft() {
    if (activeModule.value === DRIVER_MODULES.create) {
      saveDriverDraft()
    }
  }

  function fillDriverForm(driver) {
    Object.assign(driverForm, {
      code: driver.code ?? '',
      first_name: driver.first_name ?? '',
      last_name: driver.last_name ?? '',
      identity_document: formatDominicanDocument(driver.identity_document),
      tss_worker_code: driver.tss_worker_code ?? '',
      birth_date: normalizeDate(driver.birth_date),
      phone: driver.phone ?? '',
      contact_name: driver.contact_name ?? '',
      email: driver.email ?? '',
      address: driver.address ?? '',
      department_id: driver.department_id ?? '',
      position_id: driver.position_id ?? '',
      contract_type_id: driver.contract_type_id ?? '',
      hire_date: normalizeDate(driver.hire_date),
      status: driver.status ?? 'active',
      rehire_status: driver.rehire_status ?? 'review',
      notes: driver.notes ?? '',
      emergency_contact: {
        name: driver.emergency_contacts?.[0]?.name ?? '',
        relationship: driver.emergency_contacts?.[0]?.relationship ?? '',
        phone: driver.emergency_contacts?.[0]?.phone ?? '',
        secondary_phone: driver.emergency_contacts?.[0]?.secondary_phone ?? '',
      },
      license: {
        license_number: formatDominicanDocument(driver.license?.license_number),
        category: driver.license?.category ?? '',
        issued_at: normalizeDate(driver.license?.issued_at),
        expires_at: normalizeDate(driver.license?.expires_at),
        issuing_entity: driver.license?.issuing_entity ?? '',
        restrictions: driver.license?.restrictions ?? '',
        observations: driver.license?.observations ?? '',
      },
    })
  }

  function resetDocumentForm() {
    Object.assign(documentForm, {
      document_type: 'cedula',
      name: '',
      file_path: '',
      issued_at: '',
      expires_at: '',
      status: 'valid',
      notes: '',
    })
  }

  function resetLeaveForm() {
    Object.assign(leaveForm, {
      leave_type: 'Permiso medico',
      started_at: '',
      ended_at: '',
      reason: '',
      description: '',
      file_path: '',
      status: 'pending',
    })
  }

  function resetConductForm() {
    Object.assign(conductForm, {
      event_date: '',
      type: 'Evaluacion de conducta',
      severity: 'low',
      description: '',
      action_taken: '',
      file_path: '',
      status: 'open',
    })
  }

  function resetTerminationForm() {
    Object.assign(terminationForm, {
      termination_date: '',
      termination_type: 'resignation',
      reason: '',
      description: '',
      rehire_status: 'yes',
      rehire_reason: '',
      file_path: '',
    })
  }

  function resetRehireForm() {
    Object.assign(rehireForm, {
      hire_date: '',
      reason: '',
      rehire_status: 'yes',
      notes: '',
    })
  }

  async function loadDrivers() {
    isLoading.value = true
    errorMessage.value = ''

    try {
      const [driverResponse, catalogResponse] = await Promise.all([fetchDrivers(), fetchDriverCatalogs()])
      drivers.value = driverResponse.data ?? []
      Object.assign(catalogs, catalogResponse)

      if (!selectedDriverId.value && drivers.value.length) {
        selectedDriverId.value = String(drivers.value[0].id)
      }

      if (selectedDriverId.value) {
        await loadDriverDetail()
        fillDriverForm(selectedDriver.value)
      }
    } catch (error) {
      errorMessage.value = error.message || 'No se pudo conectar con el backend. Inicia Laravel en http://localhost:8000.'
    } finally {
      isLoading.value = false
    }
  }

  async function loadDriverDetail() {
    if (!selectedDriverId.value) {
      selectedDriver.value = null
      return
    }

    selectedDriver.value = await fetchDriver(selectedDriverId.value)
  }

  async function selectDriver(driver) {
    if (!driver) return

    preserveCreateDraft()
    selectedDriverId.value = String(driver.id)
    message.value = ''
    errorMessage.value = ''
    modalMode.value = ''
    activeModule.value = DRIVER_MODULES.list
    await loadDriverDetail()
    fillDriverForm(selectedDriver.value)
  }

  async function refreshCurrentDriver() {
    const currentId = selectedDriverId.value
    const response = await fetchDrivers()
    drivers.value = response.data ?? []
    selectedDriverId.value = currentId || (drivers.value[0] ? String(drivers.value[0].id) : '')
    await loadDriverDetail()
  }

  async function setModule(module) {
    if (module !== DRIVER_MODULES.create) preserveCreateDraft()
    activeModule.value = module
    message.value = ''
    errorMessage.value = ''

    if (module === DRIVER_MODULES.list) {
      modalMode.value = ''
      return
    }

    if (module === DRIVER_MODULES.create || module === DRIVER_MODULES.reports) {
      openModal(module)
      return
    }

    await openDriverModal(module, activeDriver.value)
  }

  function openModal(mode) {
    if (mode !== DRIVER_MODULES.create) preserveCreateDraft()
    modalMode.value = mode
    activeModule.value = mode
    message.value = ''
    errorMessage.value = ''

    if (mode === DRIVER_MODULES.create) {
      if (!loadDriverDraft()) resetDriverForm()
    }
  }

  function prepareLicenseForm() {
    if (!activeDriver.value) return
    fillDriverForm(activeDriver.value)
  }

  async function openDriverModal(mode, driver) {
    if (!driver) {
      errorMessage.value = 'Primero registra o selecciona un conductor.'
      return
    }

    selectedDriverId.value = String(driver.id)
    await loadDriverDetail()
    fillDriverForm(selectedDriver.value)

    modalMode.value = mode
    activeModule.value = mode
    message.value = ''
    errorMessage.value = ''
  }

  function closeModal() {
    preserveCreateDraft()
    modalMode.value = ''
    activeModule.value = DRIVER_MODULES.list
  }

  function clearErrorMessage() {
    errorMessage.value = ''
  }

  function validateRequiredDriverForm() {
    const requiredFields = [
      ['first_name', 'Nombres'],
      ['last_name', 'Apellidos'],
      ['identity_document', 'Cedula y licencia'],
      ['emergency_contact.name', 'Contacto de emergencia'],
      ['emergency_contact.phone', 'Telefono de emergencia'],
      ['emergency_contact.relationship', 'Parentesco del contacto de emergencia'],
      ['tss_worker_code', 'Codigo trabajador TSS'],
      ['license.category', 'Categoria de licencia'],
    ]
    const missing = requiredFields.filter(([path]) => {
      const value = path.split('.').reduce((current, key) => current?.[key], driverForm)
      return !String(value ?? '').trim()
    })

    return missing.map(([, label]) => label)
  }

  async function submitDriver() {
    const missingFields = validateRequiredDriverForm()
    if (missingFields.length) {
      errorMessage.value = `Completa los campos obligatorios: ${missingFields.join(', ')}.`
      return
    }

    isSaving.value = true
    message.value = ''
    errorMessage.value = ''

    try {
      driverForm.license.license_number = driverForm.identity_document
      const payload = cleanPayload(driverForm)
      const emergencyContact = payload.emergency_contact
      delete payload.emergency_contact
      const driver = await createDriver(payload)
      await createDriverEmergencyContact(driver.id, emergencyContact)
      clearDriverDraft()
      selectedDriverId.value = String(driver.id)
      await refreshCurrentDriver()
      modalMode.value = ''
      activeModule.value = DRIVER_MODULES.list
      fillDriverForm(selectedDriver.value)
      message.value = 'Conductor registrado con codigo automatico.'
    } catch (error) {
      errorMessage.value = error.message
    } finally {
      isSaving.value = false
    }
  }

  async function submitLicense() {
    if (!activeDriver.value) return
    driverForm.license.license_number = driverForm.identity_document
    await submitRelated(
      () => updateDriver(activeDriver.value.id, cleanPayload(driverForm)),
      () => fillDriverForm(activeDriver.value),
      'Licencia actualizada.'
    )
  }

  async function submitDocument() {
    if (!activeDriver.value) return
    await submitRelated(() => createDriverDocument(activeDriver.value.id, cleanPayload(documentForm)), resetDocumentForm, 'Documento registrado.')
  }

  async function submitLeave() {
    if (!activeDriver.value) return
    await submitRelated(() => createDriverMedicalLeave(activeDriver.value.id, cleanPayload(leaveForm)), resetLeaveForm, 'Permiso registrado.')
  }

  async function submitConduct() {
    if (!activeDriver.value) return
    await submitRelated(() => createDriverConductReport(activeDriver.value.id, cleanPayload(conductForm)), resetConductForm, 'Reporte registrado.')
  }

  async function submitTermination() {
    if (!activeDriver.value) return
    await submitRelated(
      () => createDriverTerminationRecord(activeDriver.value.id, cleanPayload(terminationForm)),
      resetTerminationForm,
      'Desvinculacion registrada.'
    )
  }

  async function submitRehire() {
    if (!activeDriver.value) return
    await submitRelated(
      () => rehireDriver(activeDriver.value.id, cleanPayload(rehireForm)),
      resetRehireForm,
      'Conductor recontratado y agregado al historial.'
    )
  }

  async function submitPhoto(file) {
    if (!activeDriver.value || !file) return

    isSaving.value = true
    message.value = ''
    errorMessage.value = ''

    try {
      selectedDriver.value = await uploadDriverPhoto(activeDriver.value.id, file)
      await refreshCurrentDriver()
      message.value = 'Foto del conductor actualizada.'
    } catch (error) {
      errorMessage.value = error.message
    } finally {
      isSaving.value = false
    }
  }

  async function submitRelated(action, reset, successMessage) {
    isSaving.value = true
    message.value = ''
    errorMessage.value = ''

    try {
      await action()
      reset()
      await refreshCurrentDriver()
      message.value = successMessage
    } catch (error) {
      errorMessage.value = error.message
    } finally {
      isSaving.value = false
    }
  }

  watch(driverForm, () => {
    if (activeModule.value === DRIVER_MODULES.create) {
      saveDriverDraft()
    }
  }, { deep: true })

  onMounted(loadDrivers)

  return {
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
    metrics,
    modalMode,
    modalTitle,
    rehireForm,
    selectedDriverId,
    terminationForm,
    closeModal,
    clearErrorMessage,
    openDriverModal,
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
  }
}
