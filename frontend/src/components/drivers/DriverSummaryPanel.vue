<script setup>
import { computed } from 'vue'
import { normalizeDate } from '../../utils/dates'
import { labelFor } from '../../utils/labels'

const props = defineProps({
  driver: {
    type: Object,
    required: true,
  },
})

const operationalAlerts = computed(() => {
  const alerts = []
  if (!props.driver.license?.expires_at) alerts.push('Licencia sin fecha de vencimiento.')
  if (!props.driver.documents?.length) alerts.push('Expediente sin documentos adjuntos.')
  if (!props.driver.emergency_contacts?.length) alerts.push('Contacto de emergencia pendiente.')
  return alerts
})
</script>

<template>
  <div v-if="operationalAlerts.length" class="operational-alerts">
    <strong>Alertas operativas</strong>
    <span v-for="alert in operationalAlerts" :key="alert">{{ alert }}</span>
  </div>
  <section class="profile-grid">
    <article class="profile-panel">
      <header class="section-header">
        <div>
          <h3 class="eyebrow">Datos laborales</h3>
        </div>
      </header>
      <dl class="profile-facts">
        <div><dt>Telefono</dt><dd>{{ driver.phone ?? 'Pendiente' }}</dd></div>
        <div><dt>Contacto</dt><dd>{{ driver.contact_name }}</dd></div>
        <div><dt>TSS</dt><dd>{{ driver.tss_worker_code }}</dd></div>
        <div><dt>Correo</dt><dd>{{ driver.email ?? 'Pendiente' }}</dd></div>
        <div><dt>Direccion</dt><dd>{{ driver.address ?? 'Pendiente' }}</dd></div>
        <div><dt>Contrato</dt><dd>{{ driver.contract_type?.name ?? 'Sin asignar' }}</dd></div>
        <div><dt>Fecha de ingreso</dt><dd>{{ normalizeDate(driver.hire_date) || 'Pendiente' }}</dd></div>
        <div><dt>Recontratacion</dt><dd>{{ labelFor(driver.rehire_status) }}</dd></div>
      </dl>
    </article>

    <article class="profile-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Licencia vigente</p>
        </div>
      </header>
      <dl class="profile-facts">
        <div><dt>Numero</dt><dd>{{ driver.license?.license_number ?? 'Pendiente' }}</dd></div>
        <div><dt>Categoria</dt><dd>{{ driver.license?.category ?? 'Pendiente' }}</dd></div>
        <div><dt>Emision</dt><dd>{{ normalizeDate(driver.license?.issued_at) || 'Pendiente' }}</dd></div>
        <div><dt>Vencimiento</dt><dd>{{ normalizeDate(driver.license?.expires_at) || 'Pendiente' }}</dd></div>
        <!-- <div><dt>Entidad</dt><dd>{{ driver.license?.issuing_entity ?? 'Pendiente' }}</dd></div> -->
        <div><dt>Restricciones</dt><dd>{{ driver.license?.restrictions ?? 'Sin restricciones' }}</dd></div>
      </dl>
    </article>
  </section>
</template>
