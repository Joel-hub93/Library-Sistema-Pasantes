# Guía de instalación y operación

Sistema de Control de Asistencia (Laravel 13 + PHP 8.3). Aplica tanto a un
entorno de **desarrollo** como de **producción**.

## Requisitos

- PHP ^8.3 con las extensiones habituales de Laravel (openssl, pdo, mbstring,
  tokenizer, xml, ctype, json, bcmath, fileinfo).
- Composer 2.
- MySQL 8 (en desarrollo el proyecto usa Laragon).
- Node.js/NPM **no** son necesarios: los assets se sirven desde AdminLTE.

> Las fotos son datos privados y se guardan en `storage/app/private`. **No**
> hace falta `php artisan storage:link`: se sirven por rutas protegidas.

## Desarrollo

```bash
git clone <url-del-repositorio>
cd proyect
composer install
copy .env.example .env          # en Linux/macOS: cp .env.example .env
php artisan key:generate
# Editar .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD (y APP_URL si aplica)
php artisan migrate --seed
php artisan admin:crear
php artisan serve
```

Abre `http://localhost:8000` e inicia sesión con el administrador que creaste.

## Producción

```bash
composer install --no-dev --optimize-autoloader
copy .env.example .env
php artisan key:generate
# Editar .env: APP_ENV=production, APP_DEBUG=false, APP_URL y credenciales de BD
php artisan migrate --force
php artisan db:seed --force
php artisan admin:crear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Qué siembra `migrate --seed`

- Permisos de administrador (guard `web`).
- Roles del sistema `admin` y `subadmin`.
- Rol `pasante` y sus permisos (guard `pasante`).
- **No** siembra ningún usuario: el administrador se crea con `admin:crear`.
- Los datos de ejemplo (carreras, tutores y pasantes) **solo** se cargan si
  `APP_ENV` no es `production`.

## Usuario administrador

No hay credenciales por defecto ni versionadas en el repositorio. El primer
administrador se crea (o se promueve) con:

```bash
php artisan admin:crear
```

Pide el correo y la contraseña (esta última de forma oculta). Para despliegues
automatizados admite pasarlos como argumentos:

```bash
php artisan admin:crear correo@dominio.com --name="Administrador" --password="********"
```

Volver a ejecutarlo con un correo existente lo promueve a admin y actualiza su
contraseña.

## Acceso de pasantes

- El rol y el acceso de un pasante se aseguran al crearlo o editarlo desde el
  panel (`App\Services\AccesoPasante`).
- El pasante entra en `/pasantes/login` con su **cédula** como usuario y
  contraseña.

## Mantenimiento

```bash
php artisan pasantes:sincronizar-acceso   # backfill: asegura rol y contraseña (cédula) de pasantes existentes
php artisan optimize:clear                # limpia cachés antes de probar
```

Ejecuta `pasantes:sincronizar-acceso` solo si ya tenías pasantes en la base
**antes** de que existiera el portal, o para restablecer en masa sus
contraseñas. En una instalación limpia no es necesario.

## Pruebas

```bash
php artisan optimize:clear
php artisan test --compact
```
