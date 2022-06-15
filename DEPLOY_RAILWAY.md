# Despliegue En Railway

Esta guia despliega el sistema actual desde GitHub usando dos servicios:

- `backend`: Laravel API.
- `frontend`: Vue/Vite.
- `MySQL`: base de datos Railway.

## 1. Subir El Codigo A GitHub

Desde la carpeta `administrativo`:

```bash
git init
git add .
git commit -m "Sistema administrativo inicial"
git branch -M main
git remote add origin https://github.com/TU-USUARIO/TU-REPO.git
git push -u origin main
```

Si ya tienes el repo creado, solo usa `git add`, `commit` y `push`.

## 2. Crear Proyecto En Railway

1. Entra a Railway.
2. Crea un proyecto nuevo.
3. Agrega una base de datos **MySQL**.
4. Agrega un servicio desde GitHub para el backend.
5. En el servicio backend configura el **Root Directory** como:

```text
backend
```

Railway usara `backend/railway.json`.

## 3. Variables Del Backend

En el servicio `backend`, agrega estas variables:

```env
APP_NAME=SODASA
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU-BACKEND.up.railway.app
FRONTEND_URL=https://TU-FRONTEND.up.railway.app
FRONTEND_URLS=https://TU-FRONTEND.up.railway.app

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

ADMIN_EMAIL=admin@administrativo.local
ADMIN_NAME=Administrador
ADMIN_PASSWORD=PonUnaClaveSeguraAqui
```

Genera `APP_KEY` localmente:

```bash
cd backend
php artisan key:generate --show
```

Copia el resultado en Railway como:

```env
APP_KEY=base64:...
```

## 4. Desplegar Backend

Haz deploy del backend. El archivo `backend/railway.json` ejecuta:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link --force
php artisan serve --host=0.0.0.0 --port=$PORT
```

Cuando termine, prueba:

```text
https://TU-BACKEND.up.railway.app/api/health
```

Debe responder:

```json
{"status":"ok"}
```

## 5. Crear Servicio Frontend

Agrega otro servicio desde el mismo repo GitHub.

Configura el **Root Directory** como:

```text
frontend
```

Variable del frontend:

```env
VITE_API_URL=https://TU-BACKEND.up.railway.app/api
```

Railway usara `frontend/railway.json`.

El frontend se compila con Vite y se sirve con `node server.js`, asi no depende de `vite preview` en produccion.

## 6. Conectar Frontend Y Backend

Cuando Railway te de el dominio del frontend, vuelve al backend y actualiza:

```env
FRONTEND_URL=https://TU-FRONTEND.up.railway.app
FRONTEND_URLS=https://TU-FRONTEND.up.railway.app
```

Luego redeploy del backend.

## 7. Actualizar Cambios

Cada vez que hagas cambios:

```bash
git add .
git commit -m "Descripcion del cambio"
git push
```

Railway redeployara los servicios conectados a GitHub.

## Notas Importantes

- Las fotos subidas a `storage` pueden perderse si Railway reinicia el contenedor. Para produccion real conviene configurar un volumen o usar S3/R2.
- `ADMIN_PASSWORD` debe ser una clave fuerte. El seeder crea/actualiza el administrador inicial.
- Si cambias el dominio del frontend, actualiza `FRONTEND_URL` y `FRONTEND_URLS` en backend para evitar errores CORS. Si necesitas mas de un dominio, separalos con coma en `FRONTEND_URLS`.
