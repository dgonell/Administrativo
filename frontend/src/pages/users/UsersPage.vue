<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { createUser, deleteUser, fetchUsers, updateRole, updateUser } from '../../services/api'

const props = defineProps({
  currentUser: { type: Object, required: true },
})

const users = ref([])
const roles = ref([])
const permissionGroups = ref({})
const selectedRoleId = ref('')
const activeView = ref('usuarios')
const error = ref('')
const modal = reactive({ isOpen: false, type: 'success', title: '', message: '' })

const form = reactive({
  id: null,
  name: '',
  email: '',
  password: '',
  is_active: true,
  must_change_password: true,
  role_ids: [],
  allowed: [],
  denied: [],
})

const selectedRole = computed(() => roles.value.find((role) => role.id === Number(selectedRoleId.value)) ?? null)
const canManageUsers = computed(() => props.currentUser.permissions?.includes('users.manage'))
const canManageRoles = computed(() => props.currentUser.permissions?.includes('roles.manage'))
const selectedUserName = computed(() => form.id ? form.name : 'Nuevo usuario')
const activeUsers = computed(() => users.value.filter((user) => user.is_active).length)
const adminUsers = computed(() => users.value.filter((user) => user.roles.some((role) => role.slug === 'administrador')).length)
const orderedUsers = computed(() => [...users.value].sort((a, b) => {
  if (a.is_active !== b.is_active) return a.is_active ? -1 : 1
  return a.name.localeCompare(b.name, 'es')
}))
const orderedPermissionGroups = computed(() => {
  const order = ['Sistema', 'Choferes', 'Finanzas']

  return Object.fromEntries(
    Object.entries(permissionGroups.value)
      .sort(([groupA], [groupB]) => order.indexOf(groupA) - order.indexOf(groupB))
      .map(([group, permissions]) => [group, Object.fromEntries(Object.entries(permissions).sort((a, b) => a[1].localeCompare(b[1], 'es')))]),
  )
})

function showModal(type, title, message) {
  Object.assign(modal, { isOpen: true, type, title, message })
}

function closeModal() {
  modal.isOpen = false
}

async function loadUsers() {
  try {
    error.value = ''
    const data = await fetchUsers()
    users.value = data.users
    roles.value = data.roles
    permissionGroups.value = data.permissions
    selectedRoleId.value = selectedRoleId.value || roles.value[0]?.id || ''
  } catch (requestError) {
    error.value = requestError.message
    showModal('error', 'No se pudo cargar seguridad', requestError.message)
  }
}

function resetForm() {
  Object.assign(form, {
    id: null,
    name: '',
    email: '',
    password: '',
    is_active: true,
    must_change_password: true,
    role_ids: [],
    allowed: [],
    denied: [],
  })
}

function editUser(user) {
  Object.assign(form, {
    id: user.id,
    name: user.name,
    email: user.email,
    password: '',
    is_active: user.is_active,
    must_change_password: user.must_change_password,
    role_ids: [...user.role_ids],
    allowed: user.permission_overrides.filter((item) => item.allowed).map((item) => item.slug),
    denied: user.permission_overrides.filter((item) => !item.allowed).map((item) => item.slug),
  })
}

function userPayload() {
  return {
    name: form.name,
    email: form.email,
    password: form.password || undefined,
    is_active: form.is_active,
    must_change_password: form.must_change_password,
    role_ids: form.role_ids,
    permission_overrides: [
      ...form.allowed.map((slug) => ({ slug, allowed: true })),
      ...form.denied.map((slug) => ({ slug, allowed: false })),
    ],
  }
}

async function saveUser() {
  if (!canManageUsers.value) return

  try {
    const wasEditing = Boolean(form.id)
    if (form.id) {
      await updateUser(form.id, userPayload())
    } else {
      await createUser(userPayload())
    }
    await loadUsers()
    resetForm()
    showModal('success', wasEditing ? 'Usuario actualizado' : 'Usuario creado', 'Los permisos quedaron guardados correctamente.')
  } catch (requestError) {
    showModal('error', 'No se pudo guardar usuario', requestError.message)
  }
}

async function deactivateUser(user) {
  if (!canManageUsers.value) return
  if (!window.confirm(`Desactivar a ${user.name} y revocar sus sesiones activas?`)) return

  try {
    await deleteUser(user.id)
    await loadUsers()
    showModal('success', 'Usuario desactivado', 'La sesion del usuario fue revocada.')
  } catch (requestError) {
    showModal('error', 'No se pudo desactivar usuario', requestError.message)
  }
}

async function saveRole() {
  if (!canManageRoles.value || !selectedRole.value) return

  try {
    await updateRole(selectedRole.value.id, {
      name: selectedRole.value.name,
      permission_slugs: selectedRole.value.permissions.map((permission) => permission.slug),
    })
    await loadUsers()
    showModal('success', 'Rol actualizado', 'Los permisos del rol fueron guardados.')
  } catch (requestError) {
    showModal('error', 'No se pudo actualizar rol', requestError.message)
  }
}

function toggleRolePermission(slug, checked) {
  if (!selectedRole.value) return
  const permissions = selectedRole.value.permissions.filter((permission) => permission.slug !== slug)
  if (checked) permissions.push({ slug })
  selectedRole.value.permissions = permissions
}

function hasRolePermission(slug) {
  return selectedRole.value?.permissions.some((permission) => permission.slug === slug)
}

onMounted(loadUsers)
</script>

<template>
  <main class="content users-workspace">
    <section class="finance-header">
      <div>
        <p class="eyebrow">Seguridad</p>
        <h2>Usuarios y permisos</h2>
        <p>Administra accesos, roles y permisos especificos por usuario.</p>
        <p v-if="error" class="error-text">{{ error }}</p>
      </div>
      <div class="security-kpis">
        <article><span>Usuarios</span><strong>{{ users.length }}</strong></article>
        <article><span>Activos</span><strong>{{ activeUsers }}</strong></article>
        <article><span>Admins</span><strong>{{ adminUsers }}</strong></article>
      </div>
      <div class="security-tabs">
        <button type="button" :class="{ active: activeView === 'usuarios' }" @click="activeView = 'usuarios'">Usuarios</button>
        <button type="button" :class="{ active: activeView === 'roles' }" @click="activeView = 'roles'">Roles</button>
      </div>
    </section>

    <section v-if="activeView === 'usuarios'" class="users-layout">
      <article class="profile-panel users-list-panel">
        <header class="section-header">
          <div>
            <p class="eyebrow">Accesos</p>
            <h3>Usuarios registrados</h3>
          </div>
        </header>
        <div class="security-list">
          <article v-for="user in orderedUsers" :key="user.id" :class="{ selected: form.id === user.id }">
            <div class="user-avatar" aria-hidden="true">{{ user.name.slice(0, 2).toUpperCase() }}</div>
            <div class="user-card-main">
              <strong>{{ user.name }}</strong>
              <span>{{ user.email }}</span>
              <small>{{ user.roles.map((role) => role.name).join(', ') || 'Sin rol' }}</small>
              <small>{{ user.sessions?.length ?? 0 }} sesion(es) activa(s)</small>
            </div>
            <div class="user-card-side">
              <span class="mini-status" :class="{ active: user.is_active }">{{ user.is_active ? 'Activo' : 'Inactivo' }}</span>
              <div class="crud-actions">
                <button type="button" class="secondary-action" :disabled="!canManageUsers" @click="editUser(user)">Editar</button>
                <button type="button" class="danger-action" :disabled="!canManageUsers || user.id === currentUser.id" @click="deactivateUser(user)">Desactivar</button>
              </div>
            </div>
          </article>
        </div>
      </article>

      <article class="profile-panel user-editor-panel">
        <header class="section-header">
          <div>
            <p class="eyebrow">Editor</p>
            <h3>{{ form.id ? 'Editar usuario' : 'Nuevo usuario' }}</h3>
          </div>
          <span class="status-pill" :class="{ active: form.is_active }">{{ form.is_active ? 'Activo' : 'Desactivado' }}</span>
        </header>

        <form class="user-editor-form" @submit.prevent="saveUser">
          <div class="form-grid compact-form">
            <label>Nombre <input v-model="form.name" :disabled="!canManageUsers" required /></label>
            <label>Correo <input v-model="form.email" :disabled="!canManageUsers" type="email" required /></label>
            <label>Contrasena <input v-model="form.password" :disabled="!canManageUsers" type="password" :required="!form.id" /></label>
            <label class="checkbox-line"><input v-model="form.is_active" :disabled="!canManageUsers" type="checkbox" /> Usuario activo</label>
            <label class="checkbox-line"><input v-model="form.must_change_password" :disabled="!canManageUsers" type="checkbox" /> Cambiar contrasena al ingresar</label>
          </div>

          <div class="role-picker">
            <h4>Roles</h4>
            <div>
              <label v-for="role in roles" :key="role.id" class="role-chip" :class="{ selected: form.role_ids.includes(role.id) }">
                <input v-model="form.role_ids" :disabled="!canManageUsers" type="checkbox" :value="role.id" />
                {{ role.name }}
              </label>
            </div>
          </div>

          <details class="direct-permissions">
            <summary>Permisos directos avanzados - {{ selectedUserName }}</summary>
            <div class="permission-groups-grid">
              <details v-for="(permissions, group) in orderedPermissionGroups" :key="group" class="permission-group compact">
                <summary>{{ group }}</summary>
                <div v-for="(label, slug) in permissions" :key="slug" class="permission-row">
                  <span>{{ label }}</span>
                  <label title="Permitir este permiso directamente"><input v-model="form.allowed" :disabled="!canManageUsers" type="checkbox" :value="slug" /> Permitir</label>
                  <label title="Bloquear este permiso aunque venga por rol"><input v-model="form.denied" :disabled="!canManageUsers" type="checkbox" :value="slug" /> Bloquear</label>
                </div>
              </details>
            </div>
          </details>

          <div class="form-actions sticky-actions">
            <button type="submit" :disabled="!canManageUsers">{{ form.id ? 'Guardar cambios' : 'Crear usuario' }}</button>
            <button v-if="form.id" type="button" class="secondary-action" @click="resetForm">Cancelar</button>
          </div>
        </form>
      </article>
    </section>

    <section v-if="activeView === 'roles'" class="profile-panel role-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Roles</p>
          <h3>Permisos por rol</h3>
        </div>
        <div class="role-switcher">
          <button
            v-for="role in roles"
            :key="role.id"
            type="button"
            :class="{ active: selectedRoleId === role.id }"
            @click="selectedRoleId = role.id"
          >
            {{ role.name }}
          </button>
        </div>
      </header>

      <div v-if="selectedRole" class="role-editor">
        <label class="role-name-input">Nombre del rol <input v-model="selectedRole.name" :disabled="!canManageRoles" /></label>
        <div class="permission-groups-grid">
          <details v-for="(permissions, group) in orderedPermissionGroups" :key="group" class="permission-group compact" open>
            <summary>{{ group }}</summary>
            <label v-for="(label, slug) in permissions" :key="slug" class="checkbox-line permission-check">
              <input
                :checked="hasRolePermission(slug)"
                :disabled="!canManageRoles"
                type="checkbox"
                @change="toggleRolePermission(slug, $event.target.checked)"
              />
              {{ label }}
            </label>
          </details>
        </div>
        <div class="form-actions">
          <button type="button" :disabled="!canManageRoles" @click="saveRole">Guardar permisos del rol</button>
        </div>
      </div>
    </section>

    <div v-if="modal.isOpen" class="process-modal-backdrop" role="alertdialog" aria-modal="true" @click.self="closeModal">
      <section class="process-modal" :class="modal.type">
        <div class="process-modal-icon" aria-hidden="true">
          <svg v-if="modal.type === 'success'" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5" /></svg>
          <svg v-else viewBox="0 0 24 24"><path d="M12 8v5" /><path d="M12 17h.01" /><path d="M10.3 4.2 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z" /></svg>
        </div>
        <div>
          <p class="eyebrow">{{ modal.type === 'success' ? 'Proceso completado' : 'Proceso detenido' }}</p>
          <h3>{{ modal.title }}</h3>
          <p>{{ modal.message }}</p>
        </div>
        <button type="button" @click="closeModal">Entendido</button>
      </section>
    </div>
  </main>
</template>
