const DEFAULT_API_URL =
  typeof window !== 'undefined' && window.location.hostname.endsWith('.up.railway.app')
    ? 'https://administrativo.up.railway.app/api'
    : 'http://localhost:8000/api'
const API_URL = import.meta.env.VITE_API_URL ?? DEFAULT_API_URL
const API_ORIGIN = API_URL.replace(/\/api\/?$/, '')
const TOKEN_KEY = 'administrativo_auth_token'
const CACHE_PREFIX = 'administrativo_api_cache:'
const CACHE_TTL = 1000 * 60 * 2

let authToken = sessionStorage.getItem(TOKEN_KEY) || ''
const pendingRequests = new Map()

function cloneData(data) {
  if (data === null || data === undefined) return data
  return JSON.parse(JSON.stringify(data))
}

function clearApiCache() {
  Object.keys(sessionStorage)
    .filter((key) => key.startsWith(CACHE_PREFIX))
    .forEach((key) => sessionStorage.removeItem(key))
  pendingRequests.clear()
}

function cacheKey(path) {
  return `${CACHE_PREFIX}${authToken ? 'auth' : 'guest'}:${path}`
}

function readCache(path) {
  try {
    const cached = JSON.parse(sessionStorage.getItem(cacheKey(path)) || 'null')
    if (!cached || Date.now() - cached.timestamp > CACHE_TTL) return null
    return cloneData(cached.data)
  } catch {
    return null
  }
}

function writeCache(path, data) {
  try {
    sessionStorage.setItem(cacheKey(path), JSON.stringify({ timestamp: Date.now(), data }))
  } catch {
    // Storage can be full or disabled; the request result is still valid.
  }
}

export function setAuthToken(token) {
  if ((token || '') !== authToken) clearApiCache()

  authToken = token || ''

  if (authToken) {
    sessionStorage.setItem(TOKEN_KEY, authToken)
  } else {
    sessionStorage.removeItem(TOKEN_KEY)
  }
}

export function getAuthToken() {
  return authToken
}

async function request(path, options = {}) {
  const method = (options.method || 'GET').toUpperCase()
  const shouldCache = method === 'GET' && !options.body

  if (shouldCache) {
    const cached = readCache(path)
    if (cached !== null) return cached

    const pending = pendingRequests.get(cacheKey(path))
    if (pending) return cloneData(await pending)
  } else {
    clearApiCache()
  }

  let response

  const fetchPromise = (async () => {
    try {
      response = await fetch(`${API_URL}${path}`, {
        ...options,
        headers: {
          Accept: 'application/json',
          ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
          ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
          ...options.headers,
        },
      })
    } catch {
      throw new Error('No se pudo conectar con el servidor. Verifica que el backend este activo e intenta nuevamente.')
    }

    if (!response.ok) {
      const error = await response.json().catch(() => ({}))
      const message =
        error.code === 'database_connection_error'
          ? 'No hay conexion con la base de datos. Verifica que el servicio de MySQL este activo e intenta nuevamente.'
          : error.message ?? 'No se pudo completar la solicitud'

      throw new Error(message)
    }

    if (response.status === 204) {
      return null
    }

    const data = await response.json()
    if (shouldCache) writeCache(path, data)
    return data
  })()

  if (shouldCache) pendingRequests.set(cacheKey(path), fetchPromise)

  try {
    return cloneData(await fetchPromise)
  } finally {
    if (shouldCache) pendingRequests.delete(cacheKey(path))
  }
}

export async function login(payload) {
  const data = await request('/auth/login', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
  setAuthToken(data.token)

  return data
}

export function fetchCurrentUser() {
  return request('/auth/me')
}

export async function logout() {
  try {
    await request('/auth/logout', { method: 'POST' })
  } finally {
    setAuthToken('')
  }
}

export function changePassword(payload) {
  return request('/auth/change-password', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function fetchUsers() {
  return request('/users')
}

export function createUser(payload) {
  return request('/users', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateUser(id, payload) {
  return request(`/users/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function deleteUser(id) {
  return request(`/users/${id}`, { method: 'DELETE' })
}

export function updateRole(id, payload) {
  return request(`/roles/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function fetchDrivers() {
  return request('/drivers?per_page=100')
}

export function fetchDriver(id) {
  return request(`/drivers/${id}`)
}

export function fetchDriverCatalogs() {
  return request('/driver-catalogs')
}

export function createDriver(payload) {
  return request('/drivers', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateDriver(id, payload) {
  return request(`/drivers/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function uploadDriverPhoto(id, file) {
  const formData = new FormData()
  formData.append('photo', file)

  return request(`/drivers/${id}/photo`, {
    method: 'POST',
    body: formData,
  })
}

export function resolveStorageUrl(path) {
  if (!path) return ''
  if (/^(https?:|data:|blob:)/.test(path)) return path

  return `${API_ORIGIN}/storage/${path}`
}

export function createDriverDocument(driverId, payload) {
  return request(`/drivers/${driverId}/documents`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function createDriverEmergencyContact(driverId, payload) {
  return request(`/drivers/${driverId}/emergency-contacts`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function createDriverMedicalLeave(driverId, payload) {
  return request(`/drivers/${driverId}/medical-leaves`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function createDriverConductReport(driverId, payload) {
  return request(`/drivers/${driverId}/conduct-reports`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function createDriverTerminationRecord(driverId, payload) {
  return request(`/drivers/${driverId}/termination-records`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function rehireDriver(driverId, payload) {
  return request(`/drivers/${driverId}/rehire`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function fetchFinanceClients() {
  return request('/finance-clients')
}

export function createFinanceClient(payload) {
  return request('/finance-clients', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateFinanceClient(id, payload) {
  return request(`/finance-clients/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function deleteFinanceClient(id) {
  return request(`/finance-clients/${id}`, { method: 'DELETE' })
}

export function fetchFinanceClientHistory() {
  return request('/finance-clients/history')
}

export function fetchFinanceRoutes() {
  return request('/finance-routes')
}

export function createFinanceRoute(payload) {
  return request('/finance-routes', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateFinanceRoute(id, payload) {
  return request(`/finance-routes/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function deleteFinanceRoute(id) {
  return request(`/finance-routes/${id}`, { method: 'DELETE' })
}

export function fetchFinanceRouteHistory() {
  return request('/finance-routes/history')
}

export function fetchFinanceQuotes() {
  return request('/finance-quotes')
}

export function createFinanceQuote(payload) {
  return request('/finance-quotes', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateFinanceQuote(id, payload) {
  return request(`/finance-quotes/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function fetchOperationBuses() {
  return request('/operation-buses')
}

export function fetchOperationBusHistory() {
  return request('/operation-buses/history')
}

export function createOperationBus(payload) {
  return request('/operation-buses', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateOperationBus(id, payload) {
  return request(`/operation-buses/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function updateOperationBusStatus(id, status) {
  return request(`/operation-buses/${id}/status`, {
    method: 'PATCH',
    body: JSON.stringify({ status }),
  })
}

export function updateOperationBusMileage(id, payload) {
  return request(`/operation-buses/${id}/mileage`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export function assignOperationBusDriver(id, driverId) {
  return request(`/operation-buses/${id}/driver`, {
    method: 'PATCH',
    body: JSON.stringify({ driver_id: driverId || null }),
  })
}

export function deleteOperationBus(id) {
  return request(`/operation-buses/${id}`, { method: 'DELETE' })
}

export function uploadOperationBusPhoto(id, file) {
  const formData = new FormData()
  formData.append('photo', file)

  return request(`/operation-buses/${id}/photo`, {
    method: 'POST',
    body: formData,
  })
}

export function fetchOperationMaintenanceCatalogs() {
  return request('/operation-maintenance-catalogs')
}

export function fetchOperationMaintenances(busId = '') {
  return request(`/operation-maintenances${busId ? `?operation_bus_id=${busId}` : ''}`)
}

export function createOperationMaintenance(payload) {
  return request('/operation-maintenances', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function fetchFuelDashboard() {
  return request('/fuel/dashboard')
}

export function fetchFuelCatalogs() {
  return request('/fuel/catalogs')
}

export function fetchFuelRecords(type = 'dispatches') {
  return request(`/fuel-records?type=${type}`)
}

export function createFuelTank(payload) {
  return request('/fuel-tanks', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function createFuelHose(payload) {
  return request('/fuel-hoses', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function createFuelPartner(payload) {
  return request('/fuel-partners', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function createFuelPurchase(payload) {
  return request('/fuel-purchases', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function createFuelDispatch(payload) {
  return request('/fuel-dispatches', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function voidFuelDispatch(id, payload) {
  return request(`/fuel-dispatches/${id}/void`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export function updateFuelDispatch(id, payload) {
  return request(`/fuel-dispatches/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function createFuelMeasurement(payload) {
  return request('/fuel-measurements', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateFuelMeasurement(id, payload) {
  return request(`/fuel-measurements/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function createFuelClosure(payload) {
  return request('/fuel-closures', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateFuelClosure(id, payload) {
  return request(`/fuel-closures/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function createFuelAdjustment(payload) {
  return request('/fuel-adjustments', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateFuelPurchase(id, payload) {
  return request(`/fuel-purchases/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function updateFuelAdjustment(id, payload) {
  return request(`/fuel-adjustments/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export function updateFuelPartner(id, payload) {
  return request(`/fuel-partners/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}
