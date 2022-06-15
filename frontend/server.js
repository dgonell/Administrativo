import { createReadStream, existsSync, statSync } from 'node:fs'
import { createServer } from 'node:http'
import { extname, join, normalize } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = join(fileURLToPath(new URL('.', import.meta.url)), 'dist')
const port = Number(process.env.PORT || 4173)
const host = process.env.HOST || '0.0.0.0'

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

createServer((request, response) => {
  const filePath = resolvePath(request.url || '/')
  const extension = extname(filePath)

  response.writeHead(200, {
    'Cache-Control': extension === '.html' ? 'no-cache' : 'public, max-age=31536000, immutable',
    'Content-Type': mimeTypes[extension] || 'application/octet-stream',
  })

  createReadStream(filePath).pipe(response)
}).listen(port, host, () => {
  console.log(`Frontend listening on ${host}:${port}`)
})
