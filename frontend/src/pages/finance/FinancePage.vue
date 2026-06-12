<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { FINANCE_MODULES } from '../../constants/navigation'
import {
  createFinanceClient,
  createFinanceQuote,
  createFinanceRoute,
  deleteFinanceClient,
  deleteFinanceRoute,
  fetchFinanceClientHistory,
  fetchFinanceClients,
  fetchFinanceQuotes,
  fetchFinanceRouteHistory,
  fetchFinanceRoutes,
  updateFinanceClient,
  updateFinanceQuote,
  updateFinanceRoute,
} from '../../services/api'

const props = defineProps({
  activeModule: {
    type: String,
    required: true,
  },
  permissions: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['select-module'])

const routes = ref([])

const capacities = [
  { seats: 15, label: '15 pasajeros' },
  { seats: 30, label: '30 pasajeros' },
  { seats: 50, label: '50 pasajeros' },
  { seats: 63, label: '63 pasajeros' },
]

const companyProfile = {
  name: 'SODASA',
 
  location: 'Santo Domingo, Republica Dominicana',
  phone: '(809) 000-0000',
  email: 'operaciones@sodasa.com.do',
  service: 'Alquiler de autobuses para rutas corporativas, eventos y traslados privados',
}

const clients = ref([])

const clientForm = reactive({ id: null, name: '', rnc: '', contact: '', phone: '', email: '' })
const routeForm = reactive({ id: null, name: '', distance: '', baseRate: '' })

const quote = reactive({
  id: null,
  number: '',
  status: 'draft',
  clientId: '',
  serviceDate: '',
  validUntil: '',
  paymentTerms: '50% para reservar, 50% antes del servicio',
  notes: '',
  finalPrice: 0,
})

const quoteLineForm = reactive({
  id: null,
  registeredRouteId: '',
  routeName: '',
  routeDistance: '',
  capacity: 50,
  days: 1,
  buses: 1,
  pricePerBus: 0,
  pickupPoint: '',
  dropoffPoint: '',
  schedule: '',
})

const quoteLines = ref([])
const savedQuotes = ref([])
const quoteHistoryFilters = reactive({ search: '', status: '', date: '' })
const isLoadingFinance = ref(false)
const financeError = ref('')
const clientHistory = ref([])
const routeHistory = ref([])
const processModal = reactive({
  isOpen: false,
  type: 'success',
  title: '',
  message: '',
  detail: '',
})

const isQuoteModule = computed(() => props.activeModule === FINANCE_MODULES.quote)
const isHistoryModule = computed(() => props.activeModule === FINANCE_MODULES.history)
const isClientsModule = computed(() => props.activeModule === FINANCE_MODULES.clients)
const isRoutesModule = computed(() => props.activeModule === FINANCE_MODULES.routes)

const pageTitle = computed(() => {
  if (isClientsModule.value) return 'Registro de clientes y companias'
  if (isRoutesModule.value) return 'Registro de rutas comerciales'
  if (isHistoryModule.value) return 'Historial de cotizaciones'
  return 'Cotizacion de alquiler de autobuses'
})

const pageDescription = computed(() => {
  if (isClientsModule.value) return 'Gestiona clientes disponibles para cotizaciones.'
  if (isRoutesModule.value) return 'Gestiona rutas y precios de referencia.'
  if (isHistoryModule.value) return 'Consulta las propuestas guardadas y su detalle operativo.'
  return 'Arma una propuesta con varias rutas y define el precio final manualmente.'
})

const selectedClient = computed(() => clients.value.find((client) => client.id === Number(quote.clientId)) ?? null)
const selectedLineCapacity = computed(() => capacities.find((capacity) => capacity.seats === Number(quoteLineForm.capacity)) ?? capacities[2])

const suggestedSubtotal = computed(() => quoteLines.value.reduce((total, line) => total + Number(line.finalPrice || 0), 0))
const lineFinalPrice = computed(() => Math.max(Number(quoteLineForm.buses || 1), 1) * Number(quoteLineForm.pricePerBus || 0))
const currentQuoteNumber = computed(() => quote.number || 'Borrador')
const isProcessSuccess = computed(() => processModal.type === 'success')
const filteredSavedQuotes = computed(() => savedQuotes.value.filter((item) => {
  const term = quoteHistoryFilters.search.trim().toLowerCase()
  if (term && !`${item.number} ${item.client}`.toLowerCase().includes(term)) return false
  if (quoteHistoryFilters.status && item.status !== quoteHistoryFilters.status) return false
  if (quoteHistoryFilters.date && item.createdDate !== quoteHistoryFilters.date) return false
  return true
}))

function can(permission) {
  return props.permissions.includes(permission)
}

function showProcessModal(type, title, message, detail = '') {
  Object.assign(processModal, {
    isOpen: true,
    type,
    title,
    message,
    detail,
  })
}

function closeProcessModal() {
  processModal.isOpen = false
}

function showProcessError(error, title = 'No se pudo completar el proceso') {
  const message = error?.message || 'Ocurrio un error inesperado.'
  financeError.value = message
  showProcessModal('error', title, 'Revisa la informacion e intenta nuevamente.', message)
}

function money(value) {
  return new Intl.NumberFormat('es-DO', {
    style: 'currency',
    currency: 'DOP',
    maximumFractionDigits: 0,
  }).format(value || 0)
}

function dateInputValue(value) {
  if (!value) return ''
  return String(value).split('T')[0].split(' ')[0]
}

function displayDate(value) {
  const inputValue = dateInputValue(value)
  if (!inputValue) return 'Pendiente'

  const [year, month, day] = inputValue.split('-').map(Number)
  if (!year || !month || !day) return inputValue

  return new Date(year, month - 1, day).toLocaleDateString('es-DO')
}

function quoteDocumentTitle(quoteNumber, clientName) {
  return `Cotizacion ${quoteNumber} - ${clientName || 'Cliente pendiente'}`
}

function mapClient(client) {
  return { ...client }
}

function mapRoute(route) {
  return {
    id: route.id,
    name: route.name,
    distance: route.distance,
    baseRate: Number(route.base_rate ?? route.baseRate ?? 0),
  }
}

function mapQuote(item) {
  return {
    id: item.id,
    number: item.number,
    status: item.status || 'draft',
    createdAt: item.created_at ? new Date(item.created_at).toLocaleString('es-DO') : '',
    createdDate: dateInputValue(item.created_at),
    clientId: item.finance_client_id,
    client: item.client?.name || 'Cliente pendiente',
    clientContact: item.client?.contact || 'Sin contacto',
    lines: item.lines?.length ?? 0,
    finalPrice: Number(item.final_price ?? 0),
    validUntil: dateInputValue(item.valid_until),
    validUntilLabel: displayDate(item.valid_until),
    serviceDate: item.service_date || '',
    paymentTerms: item.payment_terms || '',
    notes: item.notes || '',
    detail: (item.lines ?? []).map((line) => ({
      id: line.id,
      routeName: line.route_name,
      capacity: line.capacity,
      days: line.days,
      buses: line.buses,
      pricePerBus: Number(line.price_per_bus ?? 0),
      pickupPoint: line.pickup_point || '',
      dropoffPoint: line.dropoff_point || '',
      schedule: line.schedule || '',
      finalPrice: Number(line.final_price ?? 0),
    })),
  }
}

function mapHistory(item) {
  return {
    id: item.id,
    action: item.action,
    name: item.name,
    date: item.created_at ? new Date(item.created_at).toLocaleString('es-DO') : '',
  }
}

async function loadFinanceData() {
  isLoadingFinance.value = true
  financeError.value = ''

  try {
    const [clientData, routeData, quoteData, clientHistoryData, routeHistoryData] = await Promise.all([
      can('finance.clients.view') ? fetchFinanceClients() : Promise.resolve([]),
      can('finance.routes.view') ? fetchFinanceRoutes() : Promise.resolve([]),
      can('finance.quotes.view') || can('finance.quotes.manage') ? fetchFinanceQuotes() : Promise.resolve([]),
      can('finance.clients.view') ? fetchFinanceClientHistory() : Promise.resolve([]),
      can('finance.routes.view') ? fetchFinanceRouteHistory() : Promise.resolve([]),
    ])

    clients.value = clientData.map(mapClient)
    routes.value = routeData.map(mapRoute)
    savedQuotes.value = quoteData.map(mapQuote)
    clientHistory.value = clientHistoryData.map(mapHistory)
    routeHistory.value = routeHistoryData.map(mapHistory)
    quote.clientId = quote.clientId || clients.value[0]?.id || ''
  } catch (error) {
    showProcessError(error, 'No se pudieron cargar los datos financieros')
  } finally {
    isLoadingFinance.value = false
  }
}

function quotePayload() {
  return {
    finance_client_id: quote.clientId || null,
    status: quote.status,
    service_date: quote.serviceDate || null,
    valid_until: quote.validUntil || null,
    payment_terms: quote.paymentTerms,
    notes: quote.notes,
    final_price: quote.finalPrice,
    lines: quoteLines.value.map((line) => ({
      route_name: line.routeName,
      capacity: line.capacity,
      days: line.days,
      buses: line.buses,
      price_per_bus: line.pricePerBus,
      final_price: line.finalPrice,
      pickup_point: line.pickupPoint,
      dropoff_point: line.dropoffPoint,
      schedule: line.schedule,
    })),
  }
}

async function saveQuote() {
  if (!can('finance.quotes.manage')) return

  if (!quoteLines.value.length) {
    showProcessModal('error', 'Cotizacion incompleta', 'Agrega por lo menos una ruta antes de guardar.')
    return
  }

  try {
    financeError.value = ''
    const wasUpdating = Boolean(quote.id)
    const savedQuote = await persistQuote()
    resetQuote()
    showProcessModal(
      'success',
      wasUpdating ? 'Cotizacion actualizada' : 'Cotizacion guardada',
      wasUpdating ? 'Los cambios fueron guardados correctamente.' : 'La cotizacion fue registrada correctamente en la base de datos.',
      `Numero de cotizacion: ${savedQuote.number}`,
    )
  } catch (error) {
    showProcessError(error, 'No se pudo guardar la cotizacion')
  }
}

async function persistQuote() {
  const savedQuote = quote.id
    ? await updateFinanceQuote(quote.id, quotePayload())
    : await createFinanceQuote(quotePayload())

  await loadFinanceData()

  return mapQuote(savedQuote)
}

function resetQuote() {
  quote.id = null
  quote.number = ''
  quote.status = 'draft'
  quote.serviceDate = ''
  quote.validUntil = ''
  quote.paymentTerms = '50% para reservar, 50% antes del servicio'
  quote.notes = ''
  quote.finalPrice = 0
  quoteLines.value = []
  resetQuoteLineForm()
}

function resetQuoteLineForm(capacity = 50, pricePerBus = 0) {
  Object.assign(quoteLineForm, {
    id: null,
    registeredRouteId: '',
    routeName: '',
    routeDistance: '',
    capacity,
    days: 1,
    buses: 1,
    pricePerBus,
    pickupPoint: '',
    dropoffPoint: '',
    schedule: '',
  })
}

function applyRegisteredRoute(routeId) {
  if (routeId === 'new') {
    Object.assign(quoteLineForm, {
      registeredRouteId: 'new',
      routeName: '',
      routeDistance: '',
      pricePerBus: 0,
    })
    return
  }

  const route = routes.value.find((item) => item.id === Number(routeId))
  if (!route) {
    Object.assign(quoteLineForm, {
      registeredRouteId: '',
      routeName: '',
      routeDistance: '',
      pricePerBus: 0,
    })
    return
  }

  Object.assign(quoteLineForm, {
    registeredRouteId: route.id,
    routeName: route.name,
    routeDistance: route.distance,
    pricePerBus: route.baseRate,
  })
}

async function saveInlineRoute() {
  if (!can('finance.routes.manage')) {
    showProcessModal('error', 'Permiso requerido', 'No tienes permiso para registrar nuevas rutas.')
    return
  }

  if (!quoteLineForm.routeName.trim()) {
    showProcessModal('error', 'Ruta incompleta', 'Escribe el nombre de la nueva ruta antes de guardarla.')
    return
  }

  try {
    financeError.value = ''
    const route = await createFinanceRoute({
      name: quoteLineForm.routeName.trim(),
      distance: Number(quoteLineForm.routeDistance || 0),
      base_rate: Number(quoteLineForm.pricePerBus || 0),
    })
    await loadFinanceData()
    applyRegisteredRoute(route.id)
    showProcessModal('success', 'Ruta registrada', 'La ruta fue creada y seleccionada para esta cotizacion.')
  } catch (error) {
    showProcessError(error, 'No se pudo registrar la ruta')
  }
}

function syncQuoteFinalPrice() {
  quote.finalPrice = quoteLines.value.reduce((total, line) => total + Number(line.finalPrice || 0), 0)
}

function editSavedQuote(item) {
  quote.id = item.id
  quote.number = item.number
  quote.status = item.status
  quote.clientId = item.clientId
  quote.serviceDate = item.serviceDate || ''
  quote.validUntil = item.validUntil || ''
  quote.paymentTerms = item.paymentTerms || '50% para reservar, 50% antes del servicio'
  quote.notes = item.notes || ''
  quote.finalPrice = item.finalPrice
  quoteLines.value = item.detail.map((line) => ({ ...line }))
  emit('select-module', FINANCE_MODULES.quote)
}

function duplicateSavedQuote(item) {
  quote.id = null
  quote.number = ''
  quote.status = 'draft'
  quote.clientId = item.clientId
  quote.serviceDate = item.serviceDate || ''
  quote.validUntil = item.validUntil || ''
  quote.paymentTerms = item.paymentTerms || ''
  quote.notes = item.notes || ''
  quote.finalPrice = item.finalPrice
  quoteLines.value = item.detail.map((line) => ({ ...line, id: Date.now() + Math.random() }))
  emit('select-module', FINANCE_MODULES.quote)
}

function addQuoteLine() {
  if (!can('finance.quotes.manage')) return

  if (!quoteLineForm.routeName.trim()) {
    showProcessModal('error', 'Ruta requerida', 'Completa el nombre de la ruta antes de agregarla.')
    return
  }

  const lastCapacity = quoteLineForm.capacity
  const lastPricePerBus = quoteLineForm.pricePerBus
  const isEditingLine = Boolean(quoteLineForm.id)

  const lineData = {
    id: quoteLineForm.id || Date.now() + Math.random(),
    routeName: quoteLineForm.routeName.trim(),
    capacity: selectedLineCapacity.value.label,
    days: Number(quoteLineForm.days || 1),
    buses: Number(quoteLineForm.buses || 1),
    pricePerBus: Number(quoteLineForm.pricePerBus || 0),
    pickupPoint: quoteLineForm.pickupPoint,
    dropoffPoint: quoteLineForm.dropoffPoint,
    schedule: quoteLineForm.schedule,
    finalPrice: lineFinalPrice.value,
  }

  if (quoteLineForm.id) {
    quoteLines.value = quoteLines.value.map((line) => (line.id === quoteLineForm.id ? lineData : line))
  } else {
    quoteLines.value.push(lineData)
  }

  syncQuoteFinalPrice()
  resetQuoteLineForm(lastCapacity, lastPricePerBus)
  showProcessModal(
    'success',
    isEditingLine ? 'Ruta actualizada' : 'Ruta agregada',
    isEditingLine ? 'La ruta del preview fue actualizada correctamente.' : 'La ruta fue agregada a la cotizacion.',
  )
}

function editQuoteLine(line) {
  const lineCapacity = capacities.find((capacity) => capacity.label === line.capacity)?.seats ?? 50

  Object.assign(quoteLineForm, {
    id: line.id,
    registeredRouteId: '',
    routeName: line.routeName,
    routeDistance: '',
    capacity: lineCapacity,
    days: line.days,
    buses: line.buses,
    pricePerBus: line.pricePerBus,
    pickupPoint: line.pickupPoint,
    dropoffPoint: line.dropoffPoint,
    schedule: line.schedule,
  })
}

function removeQuoteLine(line) {
  quoteLines.value = quoteLines.value.filter((item) => item.id !== line.id)
  if (quoteLineForm.id === line.id) resetQuoteLineForm()
  syncQuoteFinalPrice()
  showProcessModal('success', 'Ruta eliminada', 'La ruta fue retirada de la cotizacion.')
}

async function saveClient() {
  if (!can('finance.clients.manage')) return

  if (!clientForm.name.trim()) {
    showProcessModal('error', 'Cliente incompleto', 'El nombre de la compania es obligatorio.')
    return
  }

  try {
    financeError.value = ''
    const wasUpdating = Boolean(clientForm.id)
    if (clientForm.id) {
      await updateFinanceClient(clientForm.id, cleanClientForm())
    } else {
      const client = await createFinanceClient(cleanClientForm())
      quote.clientId = client.id
    }
    await loadFinanceData()
    resetClientForm()
    showProcessModal(
      'success',
      wasUpdating ? 'Cliente actualizado' : 'Cliente registrado',
      wasUpdating ? 'Los datos del cliente fueron actualizados.' : 'El cliente fue guardado correctamente.',
    )
  } catch (error) {
    showProcessError(error, 'No se pudo guardar el cliente')
  }
}

function cleanClientForm() {
  return {
    name: clientForm.name.trim(),
    rnc: clientForm.rnc.trim(),
    contact: clientForm.contact.trim(),
    phone: clientForm.phone.trim(),
    email: clientForm.email.trim(),
  }
}

function editClient(client) {
  Object.assign(clientForm, client)
}

async function deleteClient(client) {
  if (!can('finance.clients.manage')) return

  try {
    financeError.value = ''
    await deleteFinanceClient(client.id)
    if (quote.clientId === client.id) quote.clientId = ''
    await loadFinanceData()
    if (clientForm.id === client.id) resetClientForm()
    showProcessModal('success', 'Cliente eliminado', 'El cliente fue eliminado correctamente.')
  } catch (error) {
    showProcessError(error, 'No se pudo eliminar el cliente')
  }
}

function resetClientForm() {
  Object.assign(clientForm, { id: null, name: '', rnc: '', contact: '', phone: '', email: '' })
}

async function saveRoute() {
  if (!can('finance.routes.manage')) return

  if (!routeForm.name.trim()) {
    showProcessModal('error', 'Ruta incompleta', 'El nombre de la ruta es obligatorio.')
    return
  }

  try {
    financeError.value = ''
    const wasUpdating = Boolean(routeForm.id)
    if (routeForm.id) {
      await updateFinanceRoute(routeForm.id, cleanRoutePayload())
    } else {
      await createFinanceRoute(cleanRoutePayload())
    }
    await loadFinanceData()
    resetRouteForm()
    showProcessModal(
      'success',
      wasUpdating ? 'Ruta actualizada' : 'Ruta registrada',
      wasUpdating ? 'Los datos de la ruta fueron actualizados.' : 'La ruta fue guardada correctamente.',
    )
  } catch (error) {
    showProcessError(error, 'No se pudo guardar la ruta')
  }
}

function cleanRouteForm() {
  return {
    name: routeForm.name.trim(),
    distance: Number(routeForm.distance || 0),
    baseRate: Number(routeForm.baseRate || 0),
  }
}

function cleanRoutePayload() {
  return {
    name: routeForm.name.trim(),
    distance: Number(routeForm.distance || 0),
    base_rate: Number(routeForm.baseRate || 0),
  }
}

function editRoute(route) {
  Object.assign(routeForm, route)
}

async function deleteRoute(route) {
  if (!can('finance.routes.manage')) return

  try {
    financeError.value = ''
    await deleteFinanceRoute(route.id)
    await loadFinanceData()
    if (routeForm.id === route.id) resetRouteForm()
    showProcessModal('success', 'Ruta eliminada', 'La ruta fue eliminada correctamente.')
  } catch (error) {
    showProcessError(error, 'No se pudo eliminar la ruta')
  }
}

function resetRouteForm() {
  Object.assign(routeForm, { id: null, name: '', distance: '', baseRate: '' })
}


function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function quoteDocumentHtml(source = null) {
  const sourceQuote = source ?? quote
  const sourceLines = source?.detail ?? quoteLines.value
  const client = source
    ? clients.value.find((item) => item.id === Number(source.clientId))
    : selectedClient.value
  const quoteNumber = sourceQuote.number || 'Borrador'
  const clientName = client?.name || source?.client || 'Cliente pendiente'
  const rows = sourceLines.map((line) => {
    const routeDetails = [line.pickupPoint, line.dropoffPoint].filter(Boolean).join(' - ')

    return `
      <tr>
        <td>
          <strong>${escapeHtml(line.routeName)}</strong>
          ${routeDetails ? `<span>${escapeHtml(routeDetails)}</span>` : ''}
        </td>
      <td>${escapeHtml(line.capacity)}</td>
      <td>${escapeHtml(line.days)}</td>
      <td>${escapeHtml(line.buses)}</td>
      <td>${escapeHtml(line.schedule || 'Pendiente')}</td>
      <td>${escapeHtml(money(line.pricePerBus))}</td>
      <td>${escapeHtml(money(line.finalPrice))}</td>
      </tr>
    `
  }).join('')

  return `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>${escapeHtml(quoteDocumentTitle(quoteNumber, clientName))}</title>
        <style>
          * { box-sizing: border-box; }
          body { background: #f4f7f9; color: #18212b; font-family: Arial, sans-serif; margin: 0; padding: 28px; }
          .document { background: #fff; border: 1px solid #d9e4ea; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08); margin: 0 auto; max-width: 1040px; padding: 30px; }
          header { border-bottom: 3px solid #f5b301; display: flex; gap: 28px; justify-content: space-between; padding-bottom: 18px; }
          .brand { color: #155e75; font-size: 13px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; }
          h1 { color: #102f3d; font-size: 25px; margin: 7px 0 0; }
          h2 { color: #102f3d; font-size: 15px; margin: 0 0 12px; text-transform: uppercase; }
          .muted, dt, .note, td span { color: #64748b; font-size: 12px; }
          .company-info { color: #475569; font-size: 12px; line-height: 1.55; margin-top: 10px; max-width: 430px; }
          .quote-meta { background: #f8fafc; border: 1px solid #dbe7ee; border-radius: 10px; display: grid; gap: 10px; min-width: 230px; padding: 14px; }
          .quote-meta div { display: grid; gap: 2px; }
          .quote-meta strong { color: #155e75; font-size: 18px; }
          .box { border: 1px solid #dbe7ee; border-radius: 10px; margin: 20px 0; padding: 16px; }
          dl { display: grid; gap: 12px; grid-template-columns: repeat(4, 1fr); margin: 0; }
          dd { color: #18212b; font-weight: 800; margin: 0; }
          table { border-collapse: separate; border-spacing: 0; margin-top: 18px; overflow: hidden; width: 100%; }
          th { background: #155e75; color: #fff; font-size: 12px; padding: 11px; text-align: left; text-transform: uppercase; }
          td { border-bottom: 1px solid #dbe7ee; padding: 12px 11px; vertical-align: top; }
          tbody tr:nth-child(even) { background: #f8fafc; }
          td strong { display: block; margin-bottom: 3px; }
          .total { background: #155e75; border-radius: 10px; color: #fff; display: flex; justify-content: space-between; margin-top: 20px; padding: 18px; }
          .total strong { font-size: 25px; }
          .terms { border-top: 1px solid #dbe7ee; margin-top: 18px; padding-top: 14px; }
          @media print {
            body { background: #fff; padding: 0; }
            .document { border: 0; box-shadow: none; max-width: none; padding: 18px; }
          }
        </style>
      </head>
      <body>
        <main class="document">
          <header>
            <div>
              <div class="brand">${escapeHtml(companyProfile.name)}</div>
              <h1>Cotizacion de alquiler de autobus</h1>
              <div class="company-info">
                <strong>${escapeHtml(companyProfile.legalName)}</strong><br>
                ${escapeHtml(companyProfile.location)}<br>
                Tel. ${escapeHtml(companyProfile.phone)} | ${escapeHtml(companyProfile.email)}<br>
                ${escapeHtml(companyProfile.service)}
              </div>
            </div>
            <div class="quote-meta">
              <div><span class="muted">Numero de cotizacion</span><strong>${escapeHtml(quoteNumber)}</strong></div>
              <div><span class="muted">Fecha</span><strong>${escapeHtml(new Date().toLocaleDateString('es-DO'))}</strong></div>
              <div><span class="muted">Valida hasta</span><strong>${escapeHtml(displayDate(sourceQuote.validUntil))}</strong></div>
            </div>
          </header>
          <section class="box">
            <h2>Cliente</h2>
            <dl>
              <div><dt>Compania</dt><dd>${escapeHtml(clientName)}</dd></div>
              <div><dt>RNC</dt><dd>${escapeHtml(client?.rnc || 'Pendiente')}</dd></div>
              <div><dt>Contacto</dt><dd>${escapeHtml(client?.contact || source?.clientContact || 'Pendiente')}</dd></div>
              <div><dt>Telefono</dt><dd>${escapeHtml(client?.phone || 'Pendiente')}</dd></div>
            </dl>
          </section>
          <table>
            <thead><tr><th>Ruta</th><th>Capacidad</th><th>Dias</th><th>Autobuses</th><th>Horario</th><th>Precio/autobus</th><th>Total</th></tr></thead>
            <tbody>${rows || '<tr><td colspan="7">Sin rutas cotizadas.</td></tr>'}</tbody>
          </table>
          <div class="total"><span>Precio final cotizado</span><strong>${escapeHtml(money(sourceQuote.finalPrice))}</strong></div>
          <div class="terms">
            <p class="note"><strong>Condiciones:</strong> ${escapeHtml(sourceQuote.paymentTerms)}</p>
            ${sourceQuote.notes ? `<p class="note">${escapeHtml(sourceQuote.notes)}</p>` : ''}
          </div>
        </main>
      </body>
    </html>
  `
}

async function printCurrentQuote() {
  if (!can('finance.quotes.print')) {
    showProcessModal('error', 'Permiso requerido', 'No tienes permiso para imprimir o descargar cotizaciones.')
    return
  }

  if (!quoteLines.value.length) {
    showProcessModal('error', 'Cotizacion incompleta', 'Agrega por lo menos una ruta antes de imprimir.')
    return
  }

  try {
    financeError.value = ''
    const savedQuote = await persistQuote()

    quote.id = savedQuote.id
    quote.number = savedQuote.number
    quote.clientId = savedQuote.clientId
    quote.serviceDate = savedQuote.serviceDate || ''
    quote.validUntil = savedQuote.validUntil || ''
    quote.paymentTerms = savedQuote.paymentTerms || '50% para reservar, 50% antes del servicio'
    quote.notes = savedQuote.notes || ''
    quote.finalPrice = savedQuote.finalPrice
    quoteLines.value = savedQuote.detail.map((line) => ({ ...line }))

    const wasPrinted = printQuote(savedQuote, false)
    if (!wasPrinted) return

    showProcessModal(
      'success',
      'Cotizacion guardada e impresion lista',
      'La cotizacion fue guardada en la base de datos y abierta para imprimir o guardar como PDF.',
      `Numero de cotizacion: ${savedQuote.number}`,
    )
  } catch (error) {
    showProcessError(error, 'No se pudo guardar e imprimir la cotizacion')
  }
}

function printQuote(source = null, notify = true) {
  if (!can('finance.quotes.print')) {
    showProcessModal('error', 'Permiso requerido', 'No tienes permiso para imprimir o descargar cotizaciones.')
    return false
  }

  if (source instanceof Event) {
    source = null
  }
  const printWindow = window.open('', '_blank')
  if (!printWindow) {
    showProcessModal('error', 'No se pudo abrir la impresion', 'El navegador bloqueo la ventana emergente.')
    return false
  }
  printWindow.document.open()
  printWindow.document.write(quoteDocumentHtml(source))
  printWindow.document.close()
  printWindow.focus()
  printWindow.print()
  if (notify) {
    showProcessModal('success', 'Documento generado', 'La cotizacion fue abierta para imprimir o guardar como PDF.')
  }
  return true
}

onMounted(loadFinanceData)
</script>

<template>
  <main class="content finance-workspace">
    <section class="finance-header">
      <div>
        <p class="eyebrow">Finanzas</p>
        <h2>{{ pageTitle }}</h2>
        <p>{{ pageDescription }}</p>
        <p v-if="financeError" class="error-text">{{ financeError }}</p>
        <p v-if="isLoadingFinance" class="empty-state">Cargando datos financieros...</p>
      </div>
    </section>

    <section v-if="isQuoteModule" class="finance-grid">
      <article class="profile-panel quote-form-panel">
        <nav class="finance-flow" aria-label="Flujo de cotizacion">
          <span class="active">1 Cliente</span>
          <span :class="{ active: quoteLines.length }">2 Rutas</span>
          <span :class="{ active: quote.finalPrice }">3 Precio</span>
          <span :class="{ active: quote.id }">4 Guardar</span>
        </nav>
        <header class="section-header">
          <div>
            <p class="eyebrow">Flujo comercial</p>
            <h3>Datos generales</h3>
          </div>
        </header>

        <form class="form-grid compact-form">
          <label>
            Cliente o compania
            <select v-model="quote.clientId">
              <option value="">Seleccionar cliente</option>
              <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
            </select>
          </label>
          <label>Fecha del servicio <input v-model="quote.serviceDate" type="date" /></label>
          <label>Valida hasta <input v-model="quote.validUntil" type="date" /></label>
          <label>
            Estado
            <select v-model="quote.status">
              <option value="draft">Borrador</option>
              <option value="sent">Enviada</option>
              <option value="approved">Aprobada</option>
              <option value="rejected">Rechazada</option>
              <option value="expired">Vencida</option>
            </select>
          </label>
          <label class="span-2">Condiciones de pago <input v-model="quote.paymentTerms" /></label>
          <label class="span-2">Notas <textarea v-model="quote.notes" placeholder="Condiciones, horarios, puntos de recogida..."></textarea></label>
        </form>

        <div class="quote-line-builder">
          <header class="section-header">
            <div>
              <p class="eyebrow">Rutas cotizadas</p>
              <h3>Agregar ruta</h3>
            </div>
          </header>
          <form class="form-grid compact-form" @submit.prevent="addQuoteLine">
            <label>
              Ruta registrada
              <select v-model="quoteLineForm.registeredRouteId" @change="applyRegisteredRoute(quoteLineForm.registeredRouteId)">
                <option value="">Seleccionar ruta</option>
                <option v-for="route in routes" :key="route.id" :value="route.id">{{ route.name }}</option>
                <option value="new">Agregar nueva ruta</option>
              </select>
            </label>
            <label>
              Nombre de ruta
              <input v-model="quoteLineForm.routeName" :disabled="!can('finance.quotes.manage')" placeholder="Origen - destino" required />
            </label>
            <label v-if="quoteLineForm.registeredRouteId === 'new'">
              Distancia KM
              <input v-model.number="quoteLineForm.routeDistance" type="number" min="0" />
            </label>
            <label>
              Capacidad
              <select v-model.number="quoteLineForm.capacity">
                <option v-for="capacity in capacities" :key="capacity.seats" :value="capacity.seats">{{ capacity.label }}</option>
              </select>
            </label>
            <label>Dias <input v-model.number="quoteLineForm.days" type="number" min="1" /></label>
            <label>Autobuses <input v-model.number="quoteLineForm.buses" type="number" min="1" /></label>
            <label>Precio por autobus RD$ <input v-model.number="quoteLineForm.pricePerBus" type="number" min="0" /></label>
            <label>Punto de recogida <input v-model="quoteLineForm.pickupPoint" /></label>
            <label>Punto de destino <input v-model="quoteLineForm.dropoffPoint" /></label>
            <label>Horario <input v-model="quoteLineForm.schedule" placeholder="Ej. 7:00 AM - 5:00 PM" /></label>
            <label>Total de esta ruta <input :value="money(lineFinalPrice)" disabled /></label>
            <div class="form-actions span-2">
              <button
                v-if="quoteLineForm.registeredRouteId === 'new' && can('finance.routes.manage')"
                type="button"
                class="secondary-action"
                @click="saveInlineRoute"
              >
                Guardar nueva ruta
              </button>
              <button type="submit" :disabled="!can('finance.quotes.manage')">{{ quoteLineForm.id ? 'Guardar cambios de ruta' : 'Agregar a cotizacion' }}</button>
              <button v-if="quoteLineForm.id" type="button" class="secondary-action" @click="resetQuoteLineForm()">Cancelar edicion</button>
            </div>
          </form>
        </div>
      </article>

      <aside class="quote-preview-stack">
        <article class="profile-panel quote-summary-panel">
          <header class="quote-document-header">
            <div>
              <p>{{ companyProfile.name }}</p>
              <h3>Propuesta ejecutiva</h3>
              <small>{{ companyProfile.legalName }}</small>
            </div>
            <div class="quote-preview-meta">
              <span>No. {{ currentQuoteNumber }}</span>
              <span>{{ new Date().toLocaleDateString('es-DO') }}</span>
            </div>
          </header>

          <div class="quote-company-card">
            <strong>{{ companyProfile.location }}</strong>
            <span>Tel. {{ companyProfile.phone }} | {{ companyProfile.email }}</span>
            <span>{{ companyProfile.service }}</span>
          </div>

          <div class="quote-client">
            <span>Cliente</span>
            <strong>{{ selectedClient?.name || 'Pendiente de completar' }}</strong>
            <small>{{ selectedClient?.contact || 'Contacto pendiente' }} - {{ selectedClient?.phone || 'Telefono pendiente' }}</small>
          </div>

          <div class="quote-lines quote-route-lines">
            <div v-for="line in quoteLines" :key="line.id">
              <span>
                <strong>{{ line.routeName }}</strong>
                {{ line.capacity }} | {{ line.days }} dia(s) | {{ line.buses }} autobus(es) x {{ money(line.pricePerBus) }}
              </span>
              <strong>{{ money(line.finalPrice) }}</strong>
              <button type="button" class="secondary-action" @click="editQuoteLine(line)">Editar</button>
              <button type="button" class="danger-action" @click="removeQuoteLine(line)">Quitar</button>
            </div>
            <div v-if="quoteLines.length === 0"><span>Sin rutas agregadas.</span><strong>{{ money(0) }}</strong></div>
          </div>

          <label class="quote-final-input">
            Precio final editable
            <input v-model.number="quote.finalPrice" type="number" min="0" />
            <small>Referencia por rutas agregadas: {{ money(suggestedSubtotal) }}</small>
          </label>

          <footer class="quote-total">
            <span>Precio final cotizado</span>
            <strong>{{ money(quote.finalPrice) }}</strong>
          </footer>
        </article>

        <div class="profile-panel quote-preview-actions">
          <button type="button" class="secondary-action" :disabled="!can('finance.quotes.print')" @click="printCurrentQuote">Descargar PDF o imprimir</button>
          <button type="button" :disabled="quoteLines.length === 0 || !can('finance.quotes.manage')" @click="saveQuote">{{ quote.id ? 'Actualizar cotizacion' : 'Guardar cotizacion' }}</button>
        </div>
      </aside>
    </section>

    <section v-if="isRoutesModule" class="profile-panel route-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Rutas</p>
          <h3>Registro de rutas</h3>
        </div>
      </header>
      <form class="form-grid compact-form" @submit.prevent="saveRoute">
        <label class="span-2">Ruta <input v-model="routeForm.name" placeholder="Origen - destino" required /></label>
        <label>Distancia KM <input v-model.number="routeForm.distance" type="number" min="1" /></label>
        <label>Precio referencia RD$ <input v-model.number="routeForm.baseRate" type="number" min="0" /></label>
        <div class="form-actions">
          <button type="submit" :disabled="!can('finance.routes.manage')">{{ routeForm.id ? 'Guardar cambios' : 'Agregar ruta' }}</button>
          <button v-if="routeForm.id" type="button" class="secondary-action" @click="resetRouteForm">Cancelar</button>
        </div>
      </form>
      <div class="crud-list">
        <article v-for="route in routes" :key="route.id">
          <button type="button" class="crud-main" @click="applyRegisteredRoute(route.id)">
            <strong>{{ route.name }}</strong>
            <span>{{ route.distance }} KM - {{ money(route.baseRate) }}</span>
          </button>
          <div class="crud-actions">
            <button type="button" class="secondary-action" :disabled="!can('finance.routes.manage')" @click="editRoute(route)">Editar</button>
            <button type="button" class="danger-action" :disabled="!can('finance.routes.manage')" @click="deleteRoute(route)">Eliminar</button>
          </div>
        </article>
      </div>
      <div class="history-block">
        <h4>Historial</h4>
        <ul>
          <li v-for="item in routeHistory" :key="item.id">
            <strong>{{ item.action }}</strong>
            <span>{{ item.name }} - {{ item.date }}</span>
          </li>
          <li v-if="routeHistory.length === 0"><span>Sin movimientos registrados.</span></li>
        </ul>
      </div>
    </section>

    <section v-if="isClientsModule" class="profile-panel client-panel">
      <header class="section-header">
        <div>
          <p class="eyebrow">Clientes</p>
          <h3>Registro de companias</h3>
        </div>
      </header>
      <form class="form-grid compact-form" @submit.prevent="saveClient">
        <label>Compania <input v-model="clientForm.name" required /></label>
        <label>RNC <input v-model="clientForm.rnc" /></label>
        <label>Contacto <input v-model="clientForm.contact" /></label>
        <label>Telefono <input v-model="clientForm.phone" /></label>
        <label>Correo <input v-model="clientForm.email" type="email" /></label>
        <div class="form-actions">
          <button type="submit" :disabled="!can('finance.clients.manage')">{{ clientForm.id ? 'Guardar cambios' : 'Agregar cliente' }}</button>
          <button v-if="clientForm.id" type="button" class="secondary-action" @click="resetClientForm">Cancelar</button>
        </div>
      </form>
      <div class="crud-list">
        <article v-for="client in clients" :key="client.id" :class="{ selected: quote.clientId === client.id }">
          <button type="button" class="crud-main" @click="quote.clientId = client.id">
            <strong>{{ client.name }}</strong>
            <span>{{ client.contact || 'Sin contacto' }} - {{ client.phone || 'Sin telefono' }}</span>
          </button>
          <div class="crud-actions">
            <button type="button" class="secondary-action" :disabled="!can('finance.clients.manage')" @click="editClient(client)">Editar</button>
            <button type="button" class="danger-action" :disabled="!can('finance.clients.manage')" @click="deleteClient(client)">Eliminar</button>
          </div>
        </article>
      </div>
      <div class="history-block">
        <h4>Historial</h4>
        <ul>
          <li v-for="item in clientHistory" :key="item.id">
            <strong>{{ item.action }}</strong>
            <span>{{ item.name }} - {{ item.date }}</span>
          </li>
          <li v-if="clientHistory.length === 0"><span>Sin movimientos registrados.</span></li>
        </ul>
      </div>
    </section>

    <section v-if="isHistoryModule" class="profile-panel quote-history">
      <header class="section-header">
        <div>
          <p class="eyebrow">Seguimiento</p>
          <h3>Cotizaciones guardadas</h3>
        </div>
      </header>
      <div class="report-filters quote-history-filters">
        <label>Buscar <input v-model="quoteHistoryFilters.search" placeholder="Numero o cliente" /></label>
        <label>
          Estado
          <select v-model="quoteHistoryFilters.status">
            <option value="">Todos</option>
            <option value="draft">Borrador</option>
            <option value="sent">Enviada</option>
            <option value="approved">Aprobada</option>
            <option value="rejected">Rechazada</option>
            <option value="expired">Vencida</option>
          </select>
        </label>
        <label>Fecha <input v-model="quoteHistoryFilters.date" type="date" /></label>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Codigo</th><th>Cliente</th><th>Estado</th><th>Rutas</th><th>Valida hasta</th><th>Precio final</th><th>Acciones</th></tr></thead>
          <tbody>
            <tr v-for="item in filteredSavedQuotes" :key="item.id">
              <td>
                <div class="stacked-cell">
                  <strong>{{ item.number }}</strong>
                  <span>{{ item.createdAt }}</span>
                </div>
              </td>
              <td><span class="quote-status" :class="`quote-${item.status}`">{{ item.status }}</span></td>
              <td>
                <div class="stacked-cell">
                  <strong>{{ item.client }}</strong>
                  <span>{{ item.clientContact }}</span>
                </div>
              </td>
              <td>
                <div class="stacked-cell">
                  <strong>{{ item.lines }} ruta(s)</strong>
                  <span>{{ item.detail.map((line) => line.routeName).join(', ') }}</span>
                </div>
              </td>
              <td>{{ item.validUntilLabel }}</td>
              <td>{{ money(item.finalPrice) }}</td>
              <td>
                <div class="row-actions">
                  <button
                    type="button"
                    class="icon-action"
                    aria-label="Duplicar cotizacion"
                    data-tooltip="Duplicar cotizacion"
                    :disabled="!can('finance.quotes.manage')"
                    @click="duplicateSavedQuote(item)"
                  >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h11v11H8z" /><path d="M5 16V5h11" /></svg>
                  </button>
                  <button
                    type="button"
                    class="icon-action"
                    aria-label="Editar cotizacion"
                    data-tooltip="Editar cotizacion"
                    :disabled="!can('finance.quotes.manage')"
                    @click="editSavedQuote(item)"
                  >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M4 20h4.7L19.1 9.6a2.1 2.1 0 0 0 0-3L17.4 5a2.1 2.1 0 0 0-3 0L4 15.3V20Z" />
                      <path d="m13.5 6 4.5 4.5" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="icon-action"
                    aria-label="Imprimir cotizacion"
                    data-tooltip="Imprimir o guardar PDF"
                    :disabled="!can('finance.quotes.print')"
                    @click="printQuote(item)"
                  >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M7 8V4h10v4" />
                      <path d="M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2" />
                      <path d="M7 14h10v6H7z" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredSavedQuotes.length === 0"><td colspan="7">Sin cotizaciones con esos criterios.</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <div
      v-if="processModal.isOpen"
      class="process-modal-backdrop"
      role="alertdialog"
      aria-modal="true"
      aria-labelledby="process-modal-title"
      @click.self="closeProcessModal"
    >
      <section class="process-modal" :class="processModal.type">
        <div class="process-modal-icon" aria-hidden="true">
          <svg v-if="isProcessSuccess" viewBox="0 0 24 24">
            <path d="M20 6 9 17l-5-5" />
          </svg>
          <svg v-else viewBox="0 0 24 24">
            <path d="M12 8v5" />
            <path d="M12 17h.01" />
            <path d="M10.3 4.2 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z" />
          </svg>
        </div>
        <div>
          <p class="eyebrow">{{ isProcessSuccess ? 'Proceso completado' : 'Proceso detenido' }}</p>
          <h3 id="process-modal-title">{{ processModal.title }}</h3>
          <p>{{ processModal.message }}</p>
          <small v-if="processModal.detail">{{ processModal.detail }}</small>
        </div>
        <button type="button" @click="closeProcessModal">Entendido</button>
      </section>
    </div>
  </main>
</template>
