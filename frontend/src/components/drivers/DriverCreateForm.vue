<script setup>
import { computed, ref } from 'vue'
import { formatDominicanDocument } from '../../utils/formatters'

const props = defineProps({
  catalogs: {
    type: Object,
    required: true,
  },
  form: {
    type: Object,
    required: true,
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['submit'])
const activeStep = ref(1)
const validationMessage = ref('')
const touchedSteps = ref(new Set())

const steps = [
  { id: 1, label: 'Datos personales' },
  { id: 2, label: 'Laboral y TSS' },
  { id: 3, label: 'Licencia' },
  { id: 4, label: 'Confirmacion' },
]

const isLastStep = computed(() => activeStep.value === steps.length)
const routeCategoryAlert = computed(() => {
  const category = String(props.form.license.category ?? '').trim()
  const categoryNumber = Number(category.match(/\d+/)?.[0] ?? NaN)
  return Number.isFinite(categoryNumber) && categoryNumber < 4
})

const requiredFieldsByStep = {
  1: [
    { path: 'first_name', label: 'Nombres' },
    { path: 'last_name', label: 'Apellidos' },
    { path: 'identity_document', label: 'Cedula' },
    { path: 'emergency_contact.name', label: 'Contacto de emergencia' },
    { path: 'emergency_contact.phone', label: 'Telefono de emergencia' },
    { path: 'emergency_contact.relationship', label: 'Parentesco' },
  ],
  2: [
    { path: 'tss_worker_code', label: 'Codigo trabajador TSS' },
  ],
  3: [
    { path: 'identity_document', label: 'Licencia' },
    { path: 'license.category', label: 'Categoria' },
  ],
}

function getValue(path) {
  return path.split('.').reduce((value, key) => value?.[key], props.form)
}

function missingFields(step) {
  return (requiredFieldsByStep[step] ?? []).filter((field) => !String(getValue(field.path) ?? '').trim())
}

function fieldClass(path) {
  return touchedSteps.value.has(activeStep.value) && !String(getValue(path) ?? '').trim() ? 'field-error' : ''
}

function formatIdentity(form) {
  form.identity_document = formatDominicanDocument(form.identity_document)
}

function validateStep(step) {
  const nextTouchedSteps = new Set(touchedSteps.value)
  nextTouchedSteps.add(step)
  touchedSteps.value = nextTouchedSteps
  const missing = missingFields(step)
  validationMessage.value = missing.length ? `Completa: ${missing.map((field) => field.label).join(', ')}.` : ''
  return missing.length === 0
}

function goToStep(step) {
  if (step <= activeStep.value) {
    activeStep.value = step
    validationMessage.value = ''
    return
  }

  for (let current = activeStep.value; current < step; current += 1) {
    if (!validateStep(current)) return
  }
  activeStep.value = step
  validationMessage.value = ''
}

function nextStep() {
  if (!validateStep(activeStep.value)) return
  validationMessage.value = ''
  activeStep.value = Math.min(activeStep.value + 1, steps.length)
}

function previousStep() {
  validationMessage.value = ''
  activeStep.value = Math.max(activeStep.value - 1, 1)
}

function submitForm() {
  for (const step of [1, 2, 3]) {
    if (!validateStep(step)) {
      activeStep.value = step
      return
    }
  }

  validationMessage.value = ''
  emit('submit')
}
</script>

<template>
  <form class="form-grid compact-form" @submit.prevent="submitForm">
    <nav class="form-steps span-2" aria-label="Pasos del registro">
      <button v-for="step in steps" :key="step.id" type="button" :class="{ active: activeStep === step.id }" @click="goToStep(step.id)">
        <span>{{ step.id }}</span>{{ step.label }}
      </button>
    </nav>

    <div v-if="validationMessage" class="form-warning span-2" role="alert">{{ validationMessage }}</div>

    <template v-if="activeStep === 1">
      <label>Nombres <input v-model="form.first_name" :class="fieldClass('first_name')" required /></label>
      <label>Apellidos <input v-model="form.last_name" :class="fieldClass('last_name')" required /></label>
      <label>Cedula <input v-model="form.identity_document" :class="fieldClass('identity_document')" maxlength="13" placeholder="000-0000000-1" required @input="formatIdentity(form)" /></label>
      <label>Fecha nacimiento <input v-model="form.birth_date" type="date" /></label>
      <label>Telefono <input v-model="form.phone" /></label>
      <label>Correo <input v-model="form.email" type="email" /></label>
      <label class="span-2">Direccion <input v-model="form.address" /></label>
      <label>Contacto <input v-model="form.contact_name" placeholder="Nombre del contacto" required /></label>
      <label>Telefono de emergencia <input v-model="form.emergency_contact.phone" :class="fieldClass('emergency_contact.phone')" required /></label>
      <label>Contacto de emergencia <input v-model="form.emergency_contact.name" :class="fieldClass('emergency_contact.name')" required /></label>
      <label>Parentesco <input v-model="form.emergency_contact.relationship" :class="fieldClass('emergency_contact.relationship')" required /></label>
    </template>

    <template v-else-if="activeStep === 2">
      <label>Codigo trabajador TSS <input v-model="form.tss_worker_code" :class="fieldClass('tss_worker_code')" placeholder="Ej. TSS-000001" required /></label>
      <label>Fecha ingreso <input v-model="form.hire_date" type="date" /></label>
      <label>
        Estado
        <select v-model="form.status">
          <option value="active">Activo</option>
          <option value="resignation">Renuncia</option>
          <option value="dismissal">Despedido</option>
        </select>
      </label>
      <label>
        Cargo
        <select v-model="form.position_id">
          <option value="">Sin asignar</option>
          <option v-for="position in catalogs.positions" :key="position.id" :value="position.id">{{ position.name }}</option>
        </select>
      </label>
      <label>
        Contrato
        <select v-model="form.contract_type_id">
          <option value="">Sin asignar</option>
          <option v-for="contractType in catalogs.contract_types" :key="contractType.id" :value="contractType.id">{{ contractType.name }}</option>
        </select>
      </label>
    </template>

    <template v-else-if="activeStep === 3">
      <label>Licencia <input :value="form.identity_document" :class="fieldClass('identity_document')" disabled /></label>
      <label>Categoria <input v-model="form.license.category" :class="fieldClass('license.category')" required /></label>
      <div v-if="routeCategoryAlert" class="form-warning span-2" role="alert">
        Este chofer no puede manejar en ruta hasta que tenga categoria 4.
      </div>
      <label>Emision licencia <input v-model="form.license.issued_at" type="date" /></label>
      <label>Vence licencia <input v-model="form.license.expires_at" type="date" /></label>
      <label class="span-2">Entidad emisora <input v-model="form.license.issuing_entity" /></label>
      <label class="span-2">Restricciones <textarea v-model="form.license.restrictions"></textarea></label>
    </template>

    <template v-else>
      <div class="driver-confirmation span-2">
        <strong>{{ form.first_name }} {{ form.last_name }}</strong>
        <span>Cedula/licencia: {{ form.identity_document }}</span>
        <span>TSS: {{ form.tss_worker_code }}</span>
        <span>Contacto: {{ form.contact_name }}</span>
        <span v-if="routeCategoryAlert">Alerta: no puede manejar en ruta hasta tener categoria 4.</span>
      </div>
      <label class="span-2">Notas <textarea v-model="form.notes"></textarea></label>
    </template>

    <div class="form-actions span-2">
      <button v-if="activeStep > 1" type="button" class="secondary-action" @click="previousStep">Anterior</button>
      <button v-if="!isLastStep" type="button" @click="nextStep">Continuar</button>
      <button v-else type="submit" :disabled="isSaving">{{ isSaving ? 'Guardando' : 'Guardar chofer' }}</button>
    </div>
  </form>
</template>
