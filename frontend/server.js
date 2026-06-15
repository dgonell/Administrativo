import { createReadStream, existsSync, statSync } from 'node:fs'
import { createServer } from 'node:http'
import { extname, join, normalize } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = join(fileURLToPath(new URL('.', import.meta.url)), 'dist')
const port = Number(process.env.PORT || 4173)
const host = process.env.HOST || '0.0.0.0'
const backendUrl = (process.env.BACKEND_URL || process.env.VITE_API_URL || '').replace(/\/api\/?$/, '').replace(/\/$/, '')

const mimeTypes = {
  '.css': 'text/css; charset=utf-8',
  '.html': 'text/html; charset=utf-8',
  '.ico': 'image/x-icon',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.svg': 'image/svg+xml',
  '.webp': 'image/webp',
}

function resolvePath(urlPath) {
  const decodedPath = decodeURIComponent(urlPath.split('?')[0])
  const normalizedPath = normalize(decodedPath).replace(/^(\.\.[/\\])+/, '')
  const filePath = join(root, normalizedPath)

  if (!filePath.startsWith(root)) {
    return join(root, 'index.html')
  }

  if (existsSync(filePath) && statSync(filePath).isFile()) {
    return filePath
  }

  return join(root, 'index.html')
}

async function proxyToBackend(request, response) {
  if (!backendUrl) {
    response.writeHead(503, { 'Content-Type': 'application/json; charset=utf-8' })
    response.end(JSON.stringify({ message: 'BACKEND_URL is not configured' }))
    return
  }

  const target = new URL(request.url || '/', backendUrl)
  const headers = new Headers(request.headers)
  headers.delete('host')

  const fetchOptions = {
    method: request.method,
    headers,
  }

  if (!['GET', 'HEAD'].includes(request.method || 'GET')) {
    fetchOptions.body = request
    fetchOptions.duplex = 'half'
  }

  try {
    const backendResponse = await fetch(target, fetchOptions)

    response.writeHead(backendResponse.status, Object.fromEntries(backendResponse.headers.entries()))

    if (backendResponse.body) {
      for await (const chunk of backendResponse.body) {
        response.write(chunk)
      }
    }

    response.end()
  } catch (error) {
    console.error('Backend proxy error:', error)
    response.writeHead(502, { 'Content-Type': 'application/json; charset=utf-8' })
    response.end(JSON.stringify({ message: 'No se pudo conectar con el backend' }))
  }
}

createServer((request, response) => {
  const urlPath = request.url || '/'

  if (urlPath.startsWith('/api/') || urlPath === '/api' || urlPath.startsWith('/storage/')) {
    proxyToBackend(request, response)
    return
  }

  const filePath = resolvePath(urlPath)
  const extension = extname(filePath)

  response.writeHead(200, {
    'Cache-Control': extension === '.html' ? 'no-cache' : 'public, max-age=31536000, immutable',
    'Content-Type': mimeTypes[extension] || 'application/octet-stream',
  })

  createReadStream(filePath).pipe(response)
}).listen(port, host, () => {
  console.log(`Frontend listening on ${host}:${port}`)
})
