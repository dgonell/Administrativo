<script setup>
import { computed, reactive, ref } from 'vue'

const props = defineProps({
  bus: { type: Object, required: true },
  catalogs: { type: Object, default: () => ({ parts: [], tire_positions: [] }) },
  isSaving: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'submit'])
const step = ref(1)
const validationMessage = ref('')
const form = reactive({
  operation_bus_id: props.bus.id,
  service_type: 'oil_change',
  service_date: localDate(),
  mileage: props.bus.current_mileage ?? '',
  workshop: '',
  technician: '',
  labor_cost: '',
  next_due_date: '',
  next_due_mileage: '',
  notes: '',
})
const items = ref([])
const item = reactive(emptyItem())
const serviceTypes = [
  ['oil_change', 'Cambio de aceite'],
  ['filter_change', 'Cambio de filtros'],
  ['tire_change', 'Cambio de neumaticos'],
  ['part_replacement', 'Cambio de piezas'],
  ['preventive', 'Mantenimiento preventivo'],
  ['repair', 'Reparacion'],
  ['other', 'Otro servicio'],
]
const categoryOptions = [
  ['oil', 'Aceite'], ['filter', 'Filtro'], ['tire', 'Neumatico'], ['part', 'Pieza'], ['supply', 'Consumible'],
]
const filteredParts = computed(() => props.catalogs.parts?.filter((part) => part.category === item.category) ?? [])
const total = computed(() => Number(form.labor_cost || 0) + items.value.reduce((sum, row) => sum + Number(row.quantity) * Number(row.unit_cost || 0), 0))
const selectedServiceLabel = computed(() => serviceTypes.find(([value]) => value === form.service_type)?.[1] ?? form.service_type)
function emptyItem() { return { operation_part_id: '', category: 'oil', name: 'Aceite de motor', quantity: 1, tire_position: '', brand: '', reference: '', unit_cost: '', notes: '' } }
function clean(value) { return String(value ?? '').trim() }
function localDate() {
  const date = new Date()
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}
function chooseService(type) {
  if (form.service_type !== type && items.value.length) {
    items.value = []
    validationMessage.value = 'Los insumos fueron reiniciados porque cambiaste el tipo de servicio.'
  } else {
    validationMessage.value = ''
  }
  form.service_type = type
  if (type === 'oil_change') Object.assign(item, emptyItem(), { category: 'oil', name: 'Aceite de motor' })
  if (type === 'filter_change') Object.assign(item, emptyItem(), { category: 'filter', name: 'Filtro de aceite' })
  if (type === 'tire_change') Object.assign(item, emptyItem(), { category: 'tire', name: 'Neumatico' })
  if (type === 'part_replacement') Object.assign(item, emptyItem(), { category: 'part', name: '' })
}
function changeCategory() {
  Object.assign(item, emptyItem(), { category: item.category, name: item.category === 'tire' ? 'Neumatico' : '' })
}
function choosePart() {
  const part = props.catalogs.parts?.find((row) => row.id === Number(item.operation_part_id))
  if (part) item.name = part.name
}
function addItem() {
  validationMessage.value = ''
  if (!clean(item.name)) return validationMessage.value = 'Escribe o selecciona el insumo utilizado.'
  if (!Number(item.quantity)) return validationMessage.value = 'La cantidad debe ser mayor que cero.'
  if (item.category === 'tire' && !item.tire_position) return validationMessage.value = 'Selecciona la posicion del neumatico.'
  if (item.category === 'tire' && items.value.some((row) => row.category === 'tire' && row.tire_position === item.tire_position)) return validationMessage.value = 'Esa posicion de neumatico ya fue agregada.'
  items.value.push({ ...item, operation_part_id: item.operation_part_id || null, name: clean(item.name), unit_cost: Number(item.unit_cost || 0) })
  Object.assign(item, emptyItem(), { category: item.category, name: item.category === 'tire' ? 'Neumatico' : '' })
}
function removeItem(index) { items.value.splice(index, 1) }
function next() {
  validationMessage.value = ''
  if (step.value === 1 && !form.service_date) return validationMessage.value = 'Selecciona la fecha del servicio.'
  if (step.value === 1 && form.mileage && Number(form.mileage) < Number(props.bus.current_mileage || 0)) return validationMessage.value = 'El kilometraje no puede ser menor al kilometraje actual de la unidad.'
  if (step.value === 2 && !items.value.length) return validationMessage.value = 'Agrega al menos un insumo o pieza utilizada.'
  step.value = Math.min(step.value + 1, 3)
}
function submit() {
  validationMessage.value = ''
  if (form.next_due_date && form.next_due_date < form.service_date) return validationMessage.value = 'La proxima revision no puede ser anterior al servicio.'
  if (form.next_due_mileage && form.mileage && Number(form.next_due_mileage) < Number(form.mileage)) return validationMessage.value = 'El proximo kilometraje debe ser mayor o igual al kilometraje actual.'
  emit('submit', { ...form, mileage: form.mileage ? Number(form.mileage) : null, labor_cost: Number(form.labor_cost || 0), next_due_mileage: form.next_due_mileage ? Number(form.next_due_mileage) : null, items: items.value })
}
</script>

<template>
  <section class="modal maintenance-wizard">
    <header class="modal-header"><div><p class="eyebrow">Unidad {{ bus.fleet_number }}</p><h3>Registrar mantenimiento</h3></div><button class="close-button" type="button" @click="emit('close')">x</button></header>
    <div class="maintenance-steps"><button v-for="value in 3" :key="value" type="button" :class="{ active: step === value, complete: step > value }" :disabled="value > step" @click="step = value"><span>{{ value }}</span></button><strong>{{ ['Servicio', 'Insumos', 'Revision'][step - 1] }}</strong></div>
    <div class="modal-body">
      <p v-if="validationMessage" class="maintenance-validation">{{ validationMessage }}</p>
      <section v-if="step === 1" class="maintenance-step">
        <div class="maintenance-service-types"><button v-for="[value, label] in serviceTypes" :key="value" type="button" :class="{ active: form.service_type === value }" @click="chooseService(value)">{{ label }}</button></div>
        <div class="form-grid"><label>Fecha del servicio<input v-model="form.service_date" type="date" required /></label><label>Kilometraje<input v-model="form.mileage" type="number" min="0" /></label><label>Taller<input v-model="form.workshop" /></label><label>Tecnico responsable<input v-model="form.technician" /></label></div>
      </section>
      <section v-if="step === 2" class="maintenance-step">
        <div class="maintenance-item-form">
          <label>Tipo<select v-model="item.category" @change="changeCategory"><option v-for="[value, label] in categoryOptions" :key="value" :value="value">{{ label }}</option></select></label>
          <label>Catalogo<select v-model="item.operation_part_id" @change="choosePart"><option value="">Escribir manualmente</option><option v-for="part in filteredParts" :key="part.id" :value="part.id">{{ part.name }}</option></select></label>
          <label>Descripcion<input v-model="item.name" /></label>
          <label>Cantidad<input v-model="item.quantity" type="number" min="1" :disabled="item.category === 'tire'" /></label>
          <label v-if="item.category === 'tire'">Posicion<select v-model="item.tire_position"><option value="">Seleccionar</option><option v-for="position in catalogs.tire_positions" :key="position">{{ position }}</option></select></label>
          <label>Marca<input v-model="item.brand" /></label><label>Referencia<input v-model="item.reference" /></label><label>Costo unitario<input v-model="item.unit_cost" type="number" min="0" step="0.01" /></label>
          <button type="button" @click="addItem">Agregar insumo</button>
        </div>
        <div class="maintenance-items"><article v-for="(row, index) in items" :key="`${row.name}-${index}`"><div><strong>{{ row.quantity }} x {{ row.name }}</strong><span>{{ row.tire_position || row.brand || 'Sin detalle adicional' }}</span></div><button type="button" title="Quitar insumo" @click="removeItem(index)">x</button></article><p v-if="!items.length">Agrega al menos un insumo o pieza utilizada.</p></div>
      </section>
      <section v-if="step === 3" class="maintenance-step">
        <div class="form-grid"><label>Costo de mano de obra<input v-model="form.labor_cost" type="number" min="0" step="0.01" /></label><label>Proxima revision<input v-model="form.next_due_date" type="date" /></label><label>Proximo kilometraje<input v-model="form.next_due_mileage" type="number" min="0" /></label><label class="span-2">Observaciones<textarea v-model="form.notes" rows="3"></textarea></label></div>
        <div class="maintenance-review"><div><span>Servicio</span><strong>{{ selectedServiceLabel }}</strong></div><div><span>Fecha</span><strong>{{ form.service_date }}</strong></div><div><span>Kilometraje</span><strong>{{ form.mileage ? `${Number(form.mileage).toLocaleString('es-DO')} km` : 'No indicado' }}</strong></div><div><span>Insumos</span><strong>{{ items.length }}</strong></div></div>
        <div class="maintenance-items compact"><article v-for="(row, index) in items" :key="`${row.name}-review-${index}`"><div><strong>{{ row.quantity }} x {{ row.name }}</strong><span>{{ row.tire_position || row.brand || 'Sin detalle adicional' }}</span></div></article></div>
        <div class="maintenance-total"><span>Costo registrado</span><strong>RD$ {{ total.toLocaleString('es-DO') }}</strong></div>
      </section>
    </div>
    <footer class="maintenance-wizard-actions"><button v-if="step > 1" class="secondary-action" type="button" @click="step--">Anterior</button><span></span><button v-if="step < 3" type="button" @click="next">Continuar</button><button v-else type="button" :disabled="isSaving || !items.length" @click="submit">{{ isSaving ? 'Guardando...' : 'Registrar mantenimiento' }}</button></footer>
  </section>
</template>
