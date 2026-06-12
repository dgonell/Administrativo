<script setup>
import { normalizeDate } from '../../utils/dates'
import { labelFor } from '../../utils/labels'

defineProps({
  driver: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <div class="detail-grid compact-detail">
    <article><span>Codigo</span><strong>{{ driver.code }}</strong></article>
    <article><span>Nombre</span><strong>{{ driver.first_name }} {{ driver.last_name }}</strong></article>
    <article><span>Cedula</span><strong>{{ driver.identity_document }}</strong></article>
    <article><span>Codigo TSS</span><strong>{{ driver.tss_worker_code }}</strong></article>
    <article><span>Telefono</span><strong>{{ driver.phone ?? 'Pendiente' }}</strong></article>
    <article><span>Contacto</span><strong>{{ driver.contact_name }}</strong></article>
    <article><span>Departamento</span><strong>{{ driver.department?.name ?? 'Sin asignar' }}</strong></article>
    <article><span>Cargo</span><strong>{{ driver.position?.name ?? 'Sin asignar' }}</strong></article>
    <article><span>Contrato</span><strong>{{ driver.contract_type?.name ?? 'Sin asignar' }}</strong></article>
    <article><span>Estado</span><strong class="badge" :class="`status-${driver.status}`">{{ labelFor(driver.status) }}</strong></article>
    <article><span>Licencia</span><strong>{{ driver.license?.license_number ?? 'Pendiente' }}</strong></article>
    <article><span>Vencimiento</span><strong>{{ normalizeDate(driver.license?.expires_at) || 'Pendiente' }}</strong></article>
    <article><span>Documentos</span><strong>{{ driver.documents?.length ?? 0 }}</strong></article>
    <article><span>Conducta</span><strong>{{ driver.conduct_reports?.length ?? 0 }}</strong></article>
  </div>
</template>
