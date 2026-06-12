<script setup>
import { computed, onMounted, ref } from 'vue'
import { APP_MODULES, FINANCE_MODULES, OPERATIONS_MODULES } from './constants/navigation'
import AppShell from './layouts/AppShell.vue'
import DriversPage from './pages/drivers/DriversPage.vue'
import ChangePasswordPage from './pages/auth/ChangePasswordPage.vue'
import LoginPage from './pages/auth/LoginPage.vue'
import FinancePage from './pages/finance/FinancePage.vue'
import UsersPage from './pages/users/UsersPage.vue'
import BusesPage from './pages/operations/BusesPage.vue'
import FuelPage from './pages/operations/FuelPage.vue'
import { changePassword, fetchCurrentUser, getAuthToken, login, logout, setAuthToken } from './services/api'

const activeModule = ref(APP_MODULES.drivers)
const financeModules = Object.values(FINANCE_MODULES)
const operationsModules = Object.values(OPERATIONS_MODULES)
const currentUser = ref(null)
const isBooting = ref(true)
const bootError = ref('')

const permissions = computed(() => currentUser.value?.permissions ?? [])

function can(permission) {
  return permissions.value.includes(permission)
}

function firstAllowedModule() {
  if (can('drivers.view')) return APP_MODULES.drivers
  if (can('finance.quotes.view')) return FINANCE_MODULES.history
  if (can('finance.quotes.manage')) return FINANCE_MODULES.quote
  if (can('operations.buses.view')) return OPERATIONS_MODULES.buses
  if (can('fuel.view')) return OPERATIONS_MODULES.fuel
  if (can('users.view')) return APP_MODULES.users

  return ''
}

async function handleLogin(payload) {
  const data = await login(payload)
  currentUser.value = data.user
  activeModule.value = firstAllowedModule()
}

async function handleLogout() {
  await logout()
  currentUser.value = null
}

async function handlePasswordChange(payload) {
  const data = await changePassword(payload)
  currentUser.value = data.user
  activeModule.value = firstAllowedModule()
}

async function boot() {
  if (!getAuthToken()) {
    isBooting.value = false
    return
  }

  try {
    const data = await fetchCurrentUser()
    currentUser.value = data.user
    activeModule.value = firstAllowedModule()
  } catch (error) {
    setAuthToken('')
    bootError.value = error.message
  } finally {
    isBooting.value = false
  }
}

onMounted(boot)
</script>

<template>
  <main v-if="isBooting" class="login-page">
    <section class="login-panel">
      <p class="eyebrow">Sistema</p>
      <h1>Cargando sesion...</h1>
    </section>
  </main>

  <LoginPage v-else-if="!currentUser" :on-login="handleLogin" />
  <ChangePasswordPage v-else-if="currentUser.must_change_password" :on-submit="handlePasswordChange" />

  <AppShell
    v-else
    :active-module="activeModule"
    :current-user="currentUser"
    @logout="handleLogout"
    @select-module="activeModule = $event"
  >
    <p v-if="bootError" class="error-text">{{ bootError }}</p>
    <UsersPage v-if="activeModule === APP_MODULES.users && can('users.view')" :current-user="currentUser" />
    <FinancePage
      v-else-if="financeModules.includes(activeModule)"
      :active-module="activeModule"
      :permissions="permissions"
      @select-module="activeModule = $event"
    />
    <BusesPage
      v-else-if="activeModule === OPERATIONS_MODULES.buses && can('operations.buses.view')"
      :permissions="permissions"
    />
    <FuelPage
      v-else-if="activeModule === OPERATIONS_MODULES.fuel && can('fuel.view')"
      :current-user="currentUser"
      :permissions="permissions"
    />
    <DriversPage v-else-if="can('drivers.view')" :permissions="permissions" />
    <section v-else class="content">
      <article class="profile-panel">
        <p class="eyebrow">Acceso limitado</p>
        <h2>No tienes permisos asignados</h2>
        <p>Contacta al administrador para habilitar tu acceso.</p>
      </article>
    </section>
  </AppShell>
</template>
