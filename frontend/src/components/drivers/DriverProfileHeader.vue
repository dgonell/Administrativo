<script setup>
import { normalizeDate } from '../../utils/dates'
import { labelFor } from '../../utils/labels'
import { resolveStorageUrl } from '../../services/api'

const props = defineProps({
  driver: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['upload-photo'])

function photoUrl() {
  return resolveStorageUrl(props.driver.photo_path)
}

function initials() {
  return `${props.driver?.first_name?.[0] ?? ''}${props.driver?.last_name?.[0] ?? ''}`.toUpperCase()
}

function selectPhoto(event) {
  const file = event.target.files?.[0]
  if (!file) return

  emit('upload-photo', file)
  event.target.value = ''
}
</script>

<template>
  <section class="profile-cover">
    <div class="cover-strip"></div>
    <div class="profile-identity">
      <label class="profile-photo-control" title="Cambiar foto del conductor">
        <input type="file" accept="image/*" @change="selectPhoto" />
        <img v-if="photoUrl()" class="avatar profile-photo" :src="photoUrl()" :alt="`Foto de ${driver.first_name} ${driver.last_name}`" />
        <span v-else class="avatar">{{ initials() }}</span>
        <span class="photo-action">Cambiar foto</span>
      </label>
      <div>
        <p class="eyebrow">Empleado {{ driver.code }}</p>
        <h2>{{ driver.first_name }} {{ driver.last_name }}</h2>
        <p><span class="label">Cedula:</span> {{ driver.identity_document ?? 'Sin registrar' }}</p>
      </div>
      <span class="badge" :class="`status-${driver.status}`">{{ labelFor(driver.status) }}</span>
    </div>
    <div class="profile-status-bar">
      <span>Cedula: <strong>{{ driver.identity_document }}</strong></span>
      <span>TSS: <strong>{{ driver.tss_worker_code }}</strong></span>
      <span>Ingreso: <strong>{{ normalizeDate(driver.hire_date) || 'Pendiente' }}</strong></span>
      <span>Licencia: <strong>{{ driver.license?.license_number ?? 'Pendiente' }}</strong></span>
      <span>Recontratacion: <strong>{{ labelFor(driver.rehire_status) }}</strong></span>
    </div>
  </section>
</template>
