# Publicación en Railway

## Estado
Preparación de repositorio. La compilación local no equivale a un despliegue validado en Railway. Falta conectar la cuenta, configurar los servicios y verificar la aplicación en HTTPS.

## GitHub
Repositorio: https://github.com/TonyMB17/app-form-quality

`dev` reúne código y documentación. Promover cambios a `main` por pull request. En GitHub configurar protección de main: PR obligatorio, comprobación `validate` requerida y bloqueo de force-push. Vincular Railway únicamente a main y activar espera de CI si está disponible. No conectar dev al entorno de producción.

## Servicios
1. Crear proyecto Railway con MySQL y servicio web desde el repositorio, rama main.
2. Utilizar Railpack; `railway.json` configura migraciones previas y healthcheck `/up`. No reemplazar el arranque automático por `artisan serve`. `railpack.json` añade el cliente MySQL necesario para cargar el esquema inicial.
3. Añadir volumen al servicio web en `/app/storage/app/private` para las fotografías. Mantener una réplica con esta arquitectura. No montar el volumen sobre toda la aplicación.
4. Copiar los nombres de variables de `.env.production.example` al servicio web. No subir un `.env` real a GitHub.
5. Referenciar MySQL desde las variables del servicio: DB_HOST desde MYSQLHOST privado, DB_PORT desde MYSQLPORT, DB_DATABASE desde MYSQLDATABASE, DB_USERNAME desde MYSQLUSER y DB_PASSWORD desde MYSQLPASSWORD. Usar el selector de referencias de Railway; no copiar valores al repositorio.
6. Generar una APP_KEY exclusiva mediante `php artisan key:generate --show` en una terminal privada y guardarla en Railway. Mantenerla estable en los siguientes despliegues. Configurar APP_URL con el dominio HTTPS real.
7. Mantener APP_ENV=production, APP_DEBUG=false, SESSION_SECURE_COOKIE=true y RAILPACK_SKIP_MIGRATIONS=true (las migraciones ya corren en pre-deploy). Sesiones y caché usan MySQL; las fotos usan el volumen. El proxy se confía en producción para reconocer HTTPS; este ajuste presupone servicio detrás del proxy de Railway.
8. Railpack instala Composer y Node, compila Vite y sirve public/ con FrankenPHP. Verificar extensiones PHP según la plantilla y los logs de build.

## Inicialización (una vez)
En una terminal dentro del servicio desplegado, después de las migraciones:
```sh
php artisan establecimientos:importar "Listado de Establecimientos.xls"
php artisan db:seed --class=EncuestaItemsSeeder --force
php artisan usuarios:crear-administrador --todos
```
El último comando solicita nombre, DNI y contraseña oculta; no tiene contraseña predeterminada y rechaza cuentas existentes. `--todos` autoriza el catálogo actual; alternativamente usar `--eess=00002630` repetido para ámbito limitado. No ejecutar comandos `piloto:*` ni copiar usuarios o fotografías del entorno local.

Ingresar como administrador, crear el periodo con sus fechas de apertura/cierre y las cuentas digitadoras. No se abre un periodo automáticamente en producción.

## Comprobaciones antes de usar datos reales
- CI verde y despliegue saludable; GET /up responde 200.
- Acceso por HTTPS, cookies seguras, inicio/cierre de sesión.
- Encuesta ficticia con fotografía, edición dentro del plazo y bloqueo fuera del plazo.
- Fotografía inaccesible sin sesión y fuera del ámbito autorizado.
- Foto conservada después de un redeploy; resultados y permisos correctos.
- Cámara y teclado virtual desde un móvil real.
- Configurar y comprobar restauración de respaldos de MySQL y del volumen de fotos.

## Operación y recuperación
Revisar logs del servicio y errores de autenticación/carga. Ante fallo de migración, detener la promoción y revisar logs. Respaldar MySQL antes de cambios de esquema. Para fallo de código, redesplegar el commit anterior solo si es compatible con el esquema actual. No ejecutar migrate:fresh ni rollback indiscriminado: la migración que retira versiones históricas transforma datos. Recuperación de base requiere un respaldo verificado; preservar también APP_KEY y fotografías.

## Referencias oficiales
- https://railway.com/guides/laravel
- https://railpack.com/languages/php/
- https://docs.railway.com/volumes
