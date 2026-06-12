export function formatDominicanDocument(value) {
  const digits = String(value ?? '').replace(/\D/g, '').slice(0, 11)
  const first = digits.slice(0, 3)
  const middle = digits.slice(3, 10)
  const last = digits.slice(10, 11)

  if (digits.length <= 3) return first
  if (digits.length <= 10) return `${first}-${middle}`

  return `${first}-${middle}-${last}`
}
