# Encuestas de calidad de atención

Sistema de la Red de Salud Abancay con Laravel 12, Inertia, React, TypeScript y MySQL.

## Funcionamiento vigente
- Cada digitador registra múltiples encuestas de pacientes anónimos en su establecimiento.
- El administrador define periodos, publicación, cierre y ámbito de establecimientos.
- Se conservan las 22 preguntas de expectativas y percepciones. Una expectativa «No aplica» o «Sin respuesta» desactiva su percepción y conserva el mismo motivo.
- Cada nueva encuesta exige una fotografía privada. Las respuestas pueden modificarse mientras el periodo permita edición.
- Resultados generales, microredes, establecimientos y datos generales, con semáforos sobre pares válidos.

## Desarrollo local
Requisitos: PHP 8.2+, Composer 2, Node 22 y MySQL. Extensiones PHP: las requeridas por Composer, incluidas GD, ZIP, XML, mbstring y PDO MySQL; SQLite para pruebas.

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
```
Configura MySQL en `.env`, crea la base vacía y ejecuta:
```sh
php artisan migrate
php artisan establecimientos:importar "Listado de Establecimientos.xls"
php artisan db:seed
npm run dev
```
En otra terminal: `php artisan serve`. `db:seed` crea cuentas ficticias únicamente en local/testing. No reutilices esas cuentas en producción.

## Validación
```sh
npm run build
php artisan test
composer audit
npm audit --omit=dev
```
Las pruebas usan SQLite en memoria; no modifican MySQL local. GitHub Actions ejecuta build y pruebas en `dev`, `main` y pull requests.

## Ramas y publicación
- `dev`: desarrollo, documentación completa y revisión de cambios.
- `main`: versión destinada a producción. Promover desde `dev` mediante pull request con CI correcto.
- Railway debe desplegar exclusivamente `main`. La documentación también se conserva en main para mantener cada versión reproducible; la raíz pública es `public/`.

Guía: [Publicación en Railway](docs/DEPLOY_RAILWAY.md). No se versionan `.env`, fotos, logs, dependencias ni archivos compilados.

## Base de datos en Railway
GitHub solo recibe el código: no publica la base MySQL local, las fotografías ni los usuarios existentes. Para una instalación nueva:

1. Añade un servicio MySQL en Railway y conecta sus variables al servicio web.
2. El primer despliegue ejecutará `php artisan migrate --force` y creará todas las tablas en la base vacía.
3. Importa el catálogo de establecimientos, carga los 22 ítems y crea el primer administrador siguiendo la guía de Railway.
4. Inicia sesión como administrador y crea los digitadores y el periodo desde la aplicación.

No subas respaldos `.sql` con datos a GitHub; el único SQL versionado es el esquema de Laravel sin cuentas ni encuestas. Si los datos locales ya son datos reales que deben conservarse, migra una copia completa y verificada directamente a MySQL de Railway; no copies únicamente la tabla `users`, porque sus establecimientos, permisos y encuestas tienen relaciones asociadas. La guía de despliegue explica ambas alternativas.

## Documentación
- [Plan de interfaz](PLAN_MEJORAS_UI.md)
- [Desarrollo](DESARROLLO.md)
- [Prueba guiada](PRUEBA_USABILIDAD.md)
- [Referencia histórica de Excel](docs/REFERENCIA_EXCEL.md)

Los planes históricos pueden contener decisiones reemplazadas. Este README describe el comportamiento vigente.
