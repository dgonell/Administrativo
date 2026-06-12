<script setup>
import { ref } from 'vue'
import { APP_MODULES, APP_VERSION, DRIVER_MENU_ITEMS, FINANCE_MENU_GROUPS, OPERATIONS_MODULES } from '../constants/navigation'

const props = defineProps({
  activeModule: {
    type: String,
    required: true,
  },
  isCollapsed: {
    type: Boolean,
    default: false,
  },
  currentUser: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['select-module', 'logout', 'toggle-sidebar'])
const isDriversMenuOpen = ref(true)
const isFinanceMenuOpen = ref(true)
const isSecurityMenuOpen = ref(false)
const isOperationsMenuOpen = ref(false)
const openFinanceGroups = ref({
  Cotizaciones: true,
  Registros: false,
})

function can(permission) {
  return props.currentUser.permissions?.includes(permission)
}

function visibleFinanceItems(items) {
  return items.filter((item) => {
    if (item === 'Cotizar') return can('finance.quotes.manage')
    if (item === 'Historial') return can('finance.quotes.view')
    if (item === 'Clientes') return can('finance.clients.view')
    if (item === 'Rutas') return can('finance.routes.view')

    return true
  })
}

function toggleFinanceGroup(group) {
  const label = group.label
  const shouldOpen = !openFinanceGroups.value[label]
  Object.keys(openFinanceGroups.value).forEach((key) => {
    openFinanceGroups.value[key] = false
  })
  openFinanceGroups.value[label] = shouldOpen

  const firstItem = visibleFinanceItems(group.items)[0]
  if (shouldOpen && firstItem) {
    emit('select-module', firstItem)
  }
}

function toggleRootMenu(menu) {
  const nextValue = {
    drivers: isDriversMenuOpen.value,
    finance: isFinanceMenuOpen.value,
    operations: isOperationsMenuOpen.value,
    security: isSecurityMenuOpen.value,
  }[menu] === false

  isDriversMenuOpen.value = false
  isFinanceMenuOpen.value = false
  isOperationsMenuOpen.value = false
  isSecurityMenuOpen.value = false

  if (menu === 'drivers') isDriversMenuOpen.value = nextValue
  if (menu === 'finance') isFinanceMenuOpen.value = nextValue
  if (menu === 'operations') isOperationsMenuOpen.value = nextValue
  if (menu === 'security') isSecurityMenuOpen.value = nextValue
}
</script>

<template>
  <aside class="sidebar" :class="{ collapsed: isCollapsed }">
    <div class="brand-block">
      <div>
        <h1>SODASA</h1>
      </div>
      <button
        class="sidebar-collapse-button"
        type="button"
        :title="isCollapsed ? 'Abrir menu' : 'Cerrar menu'"
        :aria-label="isCollapsed ? 'Abrir menu lateral' : 'Cerrar menu lateral'"
        @click="emit('toggle-sidebar')"
      >
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path v-if="isCollapsed" d="M9 6l6 6-6 6M4 5h2v14H4z" />
          <path v-else d="M15 6l-6 6 6 6M18 5h2v14h-2z" />
        </svg>
      </button>
    </div>

    <nav aria-label="Principal">
      <div v-if="can('drivers.view')" class="menu-group">
        <button
          class="menu-toggle active"
          type="button"
          title="Empleados"
          :aria-expanded="isDriversMenuOpen"
          aria-controls="drivers-menu"
          @click="toggleRootMenu('drivers')"
        >
          <svg class="menu-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M16 20a4 4 0 0 0-8 0M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6-1.5a2.5 2.5 0 1 0 0-5M21 20a3.5 3.5 0 0 0-3-3.45" />
          </svg>
          <span class="menu-label">Empleados</span>
          <span class="toggle-icon" aria-hidden="true"></span>
        </button>

        <div v-if="isDriversMenuOpen || isCollapsed" id="drivers-menu" class="submenu compact-submenu">
          <strong class="submenu-flyout-title">Empleados</strong>
          <button
            v-for="item in DRIVER_MENU_ITEMS"
            :key="item"
            class="submenu-link"
            :class="{ active: activeModule === item }"
            type="button"
            @click="emit('select-module', item)"
          >
            {{ item }}
          </button>
        </div>
      </div>

      <div v-if="can('finance.quotes.view') || can('finance.quotes.manage') || can('finance.clients.view') || can('finance.routes.view')" class="menu-group">
        <button
          class="menu-toggle active"
          type="button"
          title="Finanzas"
          :aria-expanded="isFinanceMenuOpen"
          aria-controls="admin-menu"
          @click="toggleRootMenu('finance')"
        >
          <svg class="menu-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19V5m0 14h16M8 16V9m4 7V7m4 9v-4" />
          </svg>
          <span class="menu-label">Finanzas</span>
          <span class="toggle-icon" aria-hidden="true"></span>
        </button>

        <div v-if="isFinanceMenuOpen || isCollapsed" id="admin-menu" class="submenu compact-submenu">
          <strong class="submenu-flyout-title">Finanzas</strong>
          <div v-for="group in FINANCE_MENU_GROUPS" :key="group.label" class="submenu-section">
            <button
              v-if="visibleFinanceItems(group.items).length"
              class="submenu-section-toggle"
              type="button"
              :aria-expanded="openFinanceGroups[group.label]"
              :class="{ active: group.items.includes(activeModule) }"
              @click="toggleFinanceGroup(group)"
            >
              <span>{{ group.label }}</span>
              <span class="toggle-icon" aria-hidden="true"></span>
            </button>

            <div v-if="openFinanceGroups[group.label]" class="submenu-section-items">
              <button
                v-for="item in visibleFinanceItems(group.items)"
                :key="item"
                class="submenu-link"
                :class="{ active: activeModule === item }"
                type="button"
                @click="emit('select-module', item)"
              >
                {{ item }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="can('operations.buses.view') || can('fuel.view')" class="menu-group">
        <button
          class="menu-toggle active"
          type="button"
          title="Operaciones"
          :aria-expanded="isOperationsMenuOpen"
          aria-controls="operations-menu"
          @click="toggleRootMenu('operations')"
        >
          <svg class="menu-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 17h14l-1.5-6.5A2 2 0 0 0 15.55 9H8.45a2 2 0 0 0-1.95 1.5L5 17Zm2 0v2m10-2v2M8 13h8M9 9V6h6v3" />
          </svg>
          <span class="menu-label">Operaciones</span>
          <span class="toggle-icon" aria-hidden="true"></span>
        </button>

        <div v-if="isOperationsMenuOpen || isCollapsed" id="operations-menu" class="submenu compact-submenu">
          <strong class="submenu-flyout-title">Operaciones</strong>
          <button
            v-if="can('operations.buses.view')"
            class="submenu-link"
            :class="{ active: activeModule === OPERATIONS_MODULES.buses }"
            type="button"
            @click="emit('select-module', OPERATIONS_MODULES.buses)"
          >
            Autobuses
          </button>
          <button
            v-if="can('fuel.view')"
            class="submenu-link"
            :class="{ active: activeModule === OPERATIONS_MODULES.fuel }"
            type="button"
            @click="emit('select-module', OPERATIONS_MODULES.fuel)"
          >
            Combustible
          </button>
        </div>
      </div>

      <div v-if="can('users.view')" class="menu-group">
        <button
          class="menu-toggle active"
          type="button"
          title="Seguridad"
          :aria-expanded="isSecurityMenuOpen"
          aria-controls="security-menu"
          @click="toggleRootMenu('security')"
        >
          <svg class="menu-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3 5 6v5c0 4.4 2.9 8.4 7 10 4.1-1.6 7-5.6 7-10V6l-7-3Zm0 8v5m0-8h.01" />
          </svg>
          <span class="menu-label">Seguridad</span>
          <span class="toggle-icon" aria-hidden="true"></span>
        </button>

        <div v-if="isSecurityMenuOpen || isCollapsed" id="security-menu" class="submenu compact-submenu">
          <strong class="submenu-flyout-title">Seguridad</strong>
          <button
            class="submenu-link"
            :class="{ active: activeModule === APP_MODULES.users }"
            type="button"
            @click="emit('select-module', APP_MODULES.users)"
          >
            Usuarios
          </button>
        </div>
      </div>
    </nav>

    <div class="app-version">
      <span class="app-user">{{ currentUser.name }}</span>
      <button type="button" class="logout-link" title="Cerrar sesion" @click="emit('logout')">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M10 17l5-5-5-5M15 12H3M21 4v16" />
        </svg>
        <span>Cerrar sesion</span>
      </button>
      <span class="app-version-label">Version</span>
      <strong>{{ APP_VERSION }}</strong>
    </div>
  </aside>
</template>
