export function normalizeDate(value) {
  return value ? String(value).slice(0, 10) : ''
}
