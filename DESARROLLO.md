# Desarrollo local

Base técnica: Laravel 12, Inertia 2, React 19, TypeScript y Vite 6. Laravel 12 permite trabajar con PHP 8.2 instalado en este equipo. MySQL 8 es la base de datos prevista.

## Instalación

Requisitos: PHP 8.2 o superior con PDO MySQL, Composer 2, Node.js 22 o superior y MySQL 8. En Windows, usar `npm.cmd` si PowerShell bloquea `npm.ps1`.

```powershell
composer install
npm.cmd ci
Copy-Item .env.example .env
php artisan key:generate
```

Copiar `.env.example` únicamente en una instalación nueva: no sobrescribir un `.env` ya configurado. Crear en MySQL una base exclusiva llamada `app_form_quality` y una cuenta con permisos sobre esa base. Configurar en `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=app_form_quality
DB_USERNAME=app_form_quality
DB_PASSWORD=
```

Completar la contraseña localmente. `.env` está excluido de Git; `.env.example` no contiene secretos. No utilizar una base con información de otro proyecto.

```powershell
php artisan config:clear
php artisan migrate
npm.cmd run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir <http://127.0.0.1:8000>. Para trabajar con recarga automática, ejecutar `composer dev`, que inicia Laravel y Vite. Ese comando funciona en Windows sin depender de Laravel Pail ni de la extensión PCNTL.

## Verificación

```powershell
npm.cmd run build
php artisan test
php artisan migrate:status
```

La compilación incluye la comprobación de TypeScript. Las pruebas automáticas utilizan SQLite en memoria para mantenerse separadas de MySQL; no sustituyen la verificación de migraciones en MySQL. La ruta `/up` verifica que Laravel arranca; no comprueba conectividad con la base de datos.

La configuración usa español y `America/Lima`. Durante esta etapa las sesiones y la caché utilizan archivos, y la cola se ejecuta de forma síncrona. Así se puede revisar la página inicial antes de completar el acceso a MySQL.

## Alcance de esta etapa

Incluye estructura de Laravel, integración de Inertia y React, página inicial adaptable, configuración de MySQL y migraciones estándar de Laravel. El Excel original y los README funcionales se conservan.

El catálogo de establecimientos dispone de tabla, modelo e importación desde XLS. Hay acceso por DNI y contraseña, rol digitador, asignación de EESS, formulario de encuestas y listado con detalle. El tablero administrativo y el despliegue en Railway quedan pendientes.

## Registro de encuestas

Ejecutar `php artisan migrate` y `php artisan db:seed --class=InstrumentoSeeder`. El seeder crea los 22 ítems y sus cinco dimensiones. En local/testing crea el periodo habilitado `Piloto técnico 2026`, con fechas de referencia del 1 de enero al 31 de diciembre de 2026. Repetirlo no reabre un periodo cerrado.

Desde `/mi-establecimiento`, elegir **Registrar encuesta**. Cada cuenta puede enviar una sola encuesta; al volver a intentar capturar, se abre el registro existente. Mientras esté publicada y antes de la fecha y hora límite configuradas por el administrador, se pueden modificar sus respuestas sin crear otro registro. El cierre administrativo o el vencimiento bloquean los cambios en el servidor. El formulario tiene Datos generales, Expectativas, Percepciones y Revisión. Conserva las respuestas al cambiar de paso y ante errores del servidor; no guarda un borrador al cerrar o recargar la página. La revisión permite volver a cada campo. El listado paginado está en `/encuestas`, con detalle y edición cuando siga habilitada.

Metadatos automáticos: periodo, número, fecha y horas no se digitan. El servidor asigna el único periodo habilitado vigente al abrir el formulario, fecha y hora inicial de apertura, hora final de envío y correlativo por EESS/periodo al guardar. Si no hay un único periodo vigente, se bloquea la captura. El correlativo ya no tiene límite de 1000. Un identificador único evita duplicar reenvíos. Las encuestas anteriores se conservan. Los otros campos son opcionales. Edad entre 0 y 100; especialidad como texto; seguro solo códigos 1–3. Horas automáticas con precisión de minutos (America/Lima). Respuestas 1 a 7, texto No aplica o vacío. Un par completo tiene ambos valores presentes, incluso si contiene No aplica; solo los pares 1–7 permiten calcular una brecha. No se asignan respuestas por defecto.

Cabecera y 22 respuestas se guardan en una transacción, con versión `servqual-provisional-v1`. La clave única es EESS + periodo + número. El autor y el EESS se resuelven desde la sesión; intentos de enviarlos en el formulario se rechazan. Listado y detalle se restringen al EESS del digitador. Cada usuario tiene una sola encuesta y solo puede editar sus respuestas mientras la encuesta esté publicada y dentro del plazo configurado.

Las pruebas automáticas cubren el límite de una encuesta por usuario, edición del mismo registro, bloqueo al cerrar o vencer el plazo, duplicados, No aplica y valores vacíos, puntajes inválidos, periodo cerrado, manipulación de EESS, autenticación y aislamiento del listado/detalle. Las pruebas usan SQLite en memoria y no agregan encuestas al MySQL local. Se comprobó además en Edge el recorrido de los cuatro pasos, la edición desde revisión, conservación de respuestas y ancho de 390 px.

## Acceso de prueba

Después de importar los establecimientos, ejecutar `php artisan db:seed --class=UsuarioPruebaSeeder`. Crea únicamente una cuenta ficticia y puede repetirse sin duplicarla ni restablecer su contraseña. Solo se permite en entornos `local` y `testing`. `DatabaseSeeder` utiliza ese mismo seeder.

- Nombre completo: Usuario de Prueba Curahuasi.
- DNI ficticio: `00000001`.
- EESS: `00002630` CURAHUASI.
- Contraseña de demostración local: `Curahuasi-Prueba-2026!`.

Ingresar en `/ingresar`. El perfil muestra únicamente nombre completo, DNI y EESS. No se solicita correo ni hay registro público de usuarios. La contraseña se guarda mediante hash. El rol es interno; solo se crea la cuenta digitadora, sin administrador. Las columnas de correo de Laravel se conservan anulables por compatibilidad y no se usan en este acceso.

El servidor obtiene el establecimiento de la cuenta autenticada; no permite elegirlo ni cambiarlo mediante parámetros del navegador. La ruta `/mi-establecimiento` requiere sesión, rol digitador y EESS asignado. Se renueva la sesión al ingresar y se invalida al salir. El acceso limita intentos fallidos por DNI/IP y solicitudes por IP. Estas credenciales son exclusivamente de demostración; el alta y la recuperación de cuentas reales siguen pendientes.

Para crear un lote separado de hasta 10 cuentas y encuestas ficticias en `local` o `testing`, ejecutar `php artisan piloto:encuestas --cantidad=10`. Selecciona establecimientos sin digitador, asigna DNIs sintéticos `99000001`–`99000010` y usa el mismo DNI como contraseña. Cada cuenta recibe una encuesta con 22 respuestas y el texto `DATOS FICTICIOS DE PRUEBA - NO USAR EN RESULTADOS`; el comando es idempotente y no modifica encuestas existentes.

## Catálogo de establecimientos

Fuente inicial: `Listado de Establecimientos.xls`, hoja `Listado de Establecimientos`, encabezados A1:M1 y 91 registros en A2:M92. La importación usa PhpSpreadsheet para leer XLS directamente, sin modificar el archivo original.

```powershell
php artisan establecimientos:importar "Listado de Establecimientos.xls" --dry-run
php artisan establecimientos:importar "Listado de Establecimientos.xls"
```

El primer comando valida sin escribir. El segundo crea o actualiza por `codigo_renipress` dentro de una transacción, conserva los ID internos y no elimina registros ausentes del archivo. Una repetición sin cambios no modifica registros ni fechas. Los encabezados, códigos duplicados y valores inválidos se comprueban antes de guardar cualquier fila.

La tabla `establecimientos` conserva las 13 columnas de origen: código único como `codigo_renipress` (texto de ocho dígitos con índice único), nombre, clasificación, tipo, departamento, provincia, distrito, red, microrred como `microred`, categoría, norte, este y cota. Tiene ID interno, fechas de creación/actualización e índice por red y microred. Los valores geográficos son decimales anulables de ocho decimales. Se mantienen los nombres NORTE y ESTE, sin asumir un sistema de coordenadas no documentado en el archivo.

El catálogo inicial tiene 91 establecimientos de la Red ABANCAY: CENTENARIO (26), CURAHUASI (13), LAMBRAMA (17), HUANCARAMA (19), MICAELA BASTIDAS (14) y dos con el texto `NO PERTENECE A NINGUNA MICRORED`. Se conservó ese texto del origen. Las 41 cotas vacías se guardan como `NULL`, no como cero. Importar el catálogo no habilita automáticamente establecimientos para un periodo ni crea cuentas de usuarios.

Las pruebas de importación utilizan el XLS original como referencia de lectura y archivos temporales para los casos inválidos, sobre SQLite en memoria. La carga inicial y la repetición también se verificaron en MySQL.

## Opción No aplica

La migración 000005 convierte los 99 existentes a texto `No aplica`. Las columnas de respuestas pasan a texto anulable y el servidor solo admite 1–7 o `No aplica`; los vacíos se guardan como NULL. No aplica incluye no desea responder por instrucción del usuario. La API rechaza 99 en nuevas capturas. El plan de adaptación de los enunciados y catálogos al PDF está en PLAN_DESARROLLO.md.

## Instrumento Abancay 2026 implementado

Nuevas capturas usan abancay-2026, con 44 enunciados transcritos del PDF p. 2 en config/abancay2026.php. La versión viaja dentro del inicio cifrado y no puede alterarse desde el cliente. Seguro: 1 SIS, 2 Ninguno, 3 Otro. La consulta de servqual-provisional-v1 conserva 1 SIS, 2 SOAT, 3 Ninguno. No se reetiquetan registros anteriores. Consultorio / área mantiene texto libre. personal_otro es opcional, máximo 255 caracteres, y el servidor lo anula si personal no es 6. Las reglas restantes de vacíos permitidos y metadatos automáticos continúan vigentes.

## Validación funcional del 6 de noviembre de 2026

Se registraron desde Edge dos encuestas ficticias con el único usuario de CURAHUASI. Permanecen en MySQL como encuestas 1 y 2, identificadas en Consultorio / área con `VALIDACIÓN FICTICIA` y `NO USAR EN RESULTADOS`. Cada una tiene 22 pares. Se comprobaron navegación y conservación de respuestas, guardado, listado, detalle, códigos de seguro, Otros: especifique, edad cero, fecha/horas y numeración automáticas, y reenvío del mismo formulario sin duplicados. Inicio y fin coinciden al minuto porque la prueba automatizada tarda segundos.

Encuesta 1: No aplica en expectativa, un par totalmente vacío y 20 brechas +3. Encuesta 2: No aplica en percepción, un par parcialmente vacío, 10 brechas -3 y 10 brechas cero. Los pares no calculables se presentan como tales. Se corrigió una repetición del texto de expectativa en la columna de brecha. Pasaron las 15 pruebas automáticas (191 aserciones) y la compilación. La obligatoriedad sigue pendiente; no se modificó durante estas validaciones.

## Tablero dinámico

Ruta `/tablero`, disponible únicamente para administradores con sesión. El digitador solo puede listar y abrir el detalle de su propia encuesta; no tiene acceso a resultados ni a otras encuestas de su establecimiento. El administrador puede consultar listados, detalles y resultados de los EESS autorizados; requiere asignaciones explícitas en `administrador_establecimiento` y, sin asignaciones, recibe 403. La captura y edición de respuestas siguen limitadas al digitador propietario, y la configuración de publicación global requiere autorización para todos los EESS.

Filtros por periodo, microred, establecimiento y versión. La lista de EESS depende de la microred y del ámbito autorizado. Totales, listado paginado y resultados usan la misma consulta filtrada. La actualización cada 30 segundos conserva filtros y página, pausa en pestañas ocultas y puede desactivarse. También hay actualización manual y hora de la última respuesta del servidor.

Se muestran encuestas enviadas, EESS con encuestas, pares válidos/excluidos y satisfacción por pregunta, dimensión y total. Solo pares numéricos 1–7 entran al denominador. P−E igual o mayor a cero cuenta como satisfecho; negativo como insatisfecho. No aplica y vacío se excluyen. Dimensiones y total suman conteos de pares, no promedian porcentajes. Sin denominador se muestra Sin pares válidos. No aplica e incompletos son conteos que pueden solaparse; el total de excluidos no los suma dos veces. Se advierte que los datos son ficticios y la metodología es provisional. Metas de muestra y aprobación metodológica siguen pendientes.

Pruebas: 17 aprobadas, 264 aserciones. Incluyen brechas positiva/cero/negativa, No aplica, vacío, denominador cero, filtrado por versión, coherencia de totales/listado y aislamiento de digitador/administrador. Las dos encuestas ficticias de MySQL deben mostrar 2 encuestas, 1 EESS, 40 pares válidos, 4 excluidos y 75,0 % de satisfacción.

## Regla vigente: elección obligatoria

Por decisión del usuario, cada una de las 44 respuestas requiere selección explícita: 1–7, No aplica o Sin respuesta. El estado inicial Seleccione una opción no es una respuesta válida. No aplica y Sin respuesta se almacenan como textos distintos y se excluyen de las brechas y denominadores. Los NULL históricos no se convierten y se identifican como vacíos anteriores. Datos generales obligatorios; Otros: especifique obligatorio al seleccionar Otros. Los metadatos siguen automáticos. Esta regla sustituye las referencias anteriores a vacíos permitidos en nuevas capturas.

## Primera entrega de diseño

Inicio autenticado y formulario con paleta lavanda, tipografía Manrope Variable instalada localmente y componentes React reutilizables en components/Interface.tsx: AppHeader, ActionCard, Stepper, RatingQuestion e Icon. Se mantienen Inertia y las reglas de negocio. Los puntajes utilizan radios nativos con teclado y selección visible, sin valor por defecto. No aplica y Sin respuesta permanecen separados. Animaciones de entrada y transiciones breves con prefers-reduced-motion. Navegación por pasos vuelve al inicio de pantalla o enfoca la pregunta indicada desde revisión. Se verificaron escritorio, ancho de 390 px, conservación de respuestas y movimiento reducido. No se crearon nuevas encuestas durante esta revisión visual.

## Continuidad visual y usabilidad

Acceso con diseño lavanda, contraseña visible/oculta y errores accesibles. Listado con búsqueda exacta por número, filtros por periodo y fecha, paginación que conserva filtros y tarjetas en celular. Detalle con navegación consistente y respuestas apiladas en pantallas pequeñas. Se mantienen confirmación verde y edición de respuestas mientras la encuesta siga abierta.

El formulario dirige al primer error local o del servidor, incluso desde revisión, y advierte ante navegación Inertia o recarga/cierre cuando hay cambios sin enviar. El aviso nativo al cerrar depende del navegador; no es un sistema de borradores ni garantiza interceptar el historial Atrás/Adelante. Los cambios entre pasos no solicitan confirmación. Se verificaron mostrar contraseña, búsqueda, detalle móvil, foco del primer error y cancelar/aceptar la salida, sin crear encuestas adicionales.

## Piloto: borradores y administración

Borrador automático local por cuenta/EESS: se conserva en localStorage con los datos, el paso y el inicio firmado original. Se ofrece continuar o descartar; descartar pide confirmación. La recuperación requiere iniciar sesión con la misma cuenta en el mismo navegador. Funciona durante una interrupción de red; no sincroniza entre dispositivos. Al enviar correctamente se elimina. Si el navegador impide guardar, se muestra error y se mantiene la advertencia de salida. Un borrador de periodo cerrado conserva respuestas, pero no evita las restricciones de envío. Una captura por cuenta/EESS a la vez; no editar simultáneamente en varias pestañas. Los borradores no aparecen en estadísticas.

Por autorización del usuario, se creó el administrador ficticio DNI 00000002, contraseña local Abancay-Admin-Prueba-2026!, con los 91 EESS del catálogo. No se modificó al digitador 00000001. El comando piloto:administrador exige --eess por cada código, es exclusivo de local/testing y no reinicia la contraseña existente. La cuenta administradora consulta tablero y detalles, sin permiso de captura.

PRUEBA_USABILIDAD.md contiene la guía para el usuario que realizará la evaluación. La revisión humana está pendiente de sus observaciones; no se considera completada por las pruebas automatizadas. Verificado en Edge: guardado sin red, recarga y recuperación de respuestas/paso, separación por cuenta y acceso administrador a 91 EESS.

## Resultados visuales (06/11/2026)
- Vista general prioritaria: encuestas, establecimientos, insatisfacción y gráficos de satisfacción y pares incluidos/excluidos.
- Tarjetas por dimensión y semáforo por pregunta. Rangos de insatisfacción del README: <40 % aceptable; 40–60 % en proceso; >60 % por mejorar. Sin pares válidos: estado neutral, sin porcentaje. Clasificación previa al redondeo y aplicación provisional al agregado.
- Conserva filtros autorizados, actualización automática y denominadores existentes. Los motivos de exclusión pueden solaparse y no se suman.
- Validación: build TypeScript/Vite correcto; 19 pruebas / 329 aserciones; 6 casos de límites del semáforo; navegación real y vista móvil de 390 px sin desbordamiento horizontal.

## Replicar la base de datos en otra PC

El esquema versionado se encuentra en `database/schema/mysql-schema.sql`. Es un volcado de estructura MySQL sin cuentas, establecimientos, respuestas ni otros datos de aplicación; solo conserva los nombres y lotes de migración que Laravel necesita para reconocer el esquema. Al ejecutar `php artisan migrate` en una base vacía, Laravel carga automáticamente este esquema y luego aplica cualquier migración nueva. Los archivos de `database/migrations` siguen siendo la fuente de cambios futuros.

Para una instalación nueva, configura una base MySQL vacía en el `.env` local (no copies el `.env` de otra PC), después ejecuta:

```powershell
php artisan config:clear
php artisan migrate
php artisan establecimientos:importar "Listado de Establecimientos.xls"
php artisan db:seed
```

La importación y el seeder cargan el catálogo RENIPRESS y los ítems del instrumento; el seeder también crea la cuenta digitadora ficticia solo en `local` o `testing`. No se exportan encuestas ni credenciales de las cuentas existentes. Si se agrega una migración, se debe conservar y ejecutar con `php artisan migrate` en cada instalación.

Resultados: pestañas General, Microredes y Establecimientos; comparación por dimensiones o 22 preguntas, búsqueda, semáforo y conteos por celda. Agregados limitados por filtros y autorización; incluye unidades sin encuestas. Pruebas del tablero: 97 aserciones; navegación de pestañas y móvil verificados.
