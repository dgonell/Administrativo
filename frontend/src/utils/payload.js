export function cleanPayload(payload) {
  return JSON.parse(JSON.stringify(payload), (key, value) => (value === '' ? null : value))
}
