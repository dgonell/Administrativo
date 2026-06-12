<script setup>
import { ref } from 'vue'
import AppSidebar from './AppSidebar.vue'

defineProps({
  activeModule: {
    type: String,
    required: true,
  },
  currentUser: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['select-module', 'logout'])
const isSidebarCollapsed = ref(false)
</script>

<template>
  <main class="shell" :class="{ 'sidebar-is-collapsed': isSidebarCollapsed }">
    <AppSidebar
      :active-module="activeModule"
      :is-collapsed="isSidebarCollapsed"
      :current-user="currentUser"
      @logout="emit('logout')"
      @select-module="emit('select-module', $event)"
      @toggle-sidebar="isSidebarCollapsed = !isSidebarCollapsed"
    />
    <slot />
  </main>
</template>
