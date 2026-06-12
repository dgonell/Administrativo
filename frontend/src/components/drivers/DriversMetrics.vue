<script setup>
defineProps({
  metrics: {
    type: Array,
    required: true,
  },
})
</script>

<template>
  <section class="metrics compact-metrics" aria-label="Metricas">
    <article v-for="metric in metrics" :key="metric.label" class="metric">
      <span>{{ metric.label }}</span>
      <strong>{{ metric.value }}</strong>
    </article>
  </section>
</template>
