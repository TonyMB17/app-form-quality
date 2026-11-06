# Pendientes de confirmación para la migración SERVQUAL

Completa la columna **Respuesta**. Puedes responder solo con el ID y tu decisión. Si una decisión requiere un documento, indica su nombre o adjúntalo por separado. El padrón con DNI se usará en la fase de alta de cuentas reales.

## Confirmado

- Cada fila de digitación representa una encuesta individual. Un EESS puede registrar varias en el mismo periodo.
- El administrador ya tiene los datos de las personas que llenarán el formulario. Creará una cuenta por persona con su DNI y EESS en una fase posterior. El MVP usa cuentas y datos ficticios.

## Instrumento y reglas de captura

| ID | Pregunta | Respuesta |
| --- | --- | --- |
| F01 | ¿Cuál es el cuestionario oficial que debemos usar? Adjuntar la versión vigente con los enunciados de E1–E22 y P1–P22. | Confirmado: PDF FORMATO ENCUESTA RED DE SALUD ABANCAY 2026.pdf, p. 2, contiene E1–E22 y P1–P22. 44 enunciados incorporados en la versión abancay-2026; ver PLAN_DESARROLLO.md. |
| F02 | ¿Qué etiqueta corresponde a cada valor del 1 al 7? ¿Qué significa el código 99 y cuándo se permite seleccionarlo? | PDF p. 2: 1 menor calificación, 7 mayor; no define etiquetas intermedias. Usuario confirma No aplica para no aplicabilidad o no desea responder. Se elimina 99 del uso vigente y se conserva vacío como estado distinto. |
| F03 | ¿Qué campos son obligatorios al enviar? Indicar si se requieren todos los pares E/P, si se aceptan respuestas en blanco y en qué casos. | Confirmado por el usuario: campos obligatorios; 44 respuestas con selección explícita de 1–7, No aplica o Sin respuesta. Vacío inicial no permite enviar. Datos generales obligatorios y Otros exige especificación. |
| F04 | ¿Qué significa el código 4 del tipo de seguro, que aparece en algunas celdas del Excel? ¿Debe ofrecerse en la web? | PDF p. 1: 1 SIS, 2 Ninguno, 3 Otro. No figura código 4. El significado del 4 en el Excel sigue sin aclararse; no ofrecerlo. Catálogo adaptado por versión para preservar datos antiguos. |
| F05 | ¿La especialidad será texto libre o se elegirá de un catálogo? Si es catálogo, indicar las opciones. | PDF p. 1: Consultorio / área donde fue atendido, campo libre. No presenta catálogo. Texto libre y rótulo incorporados al formulario. |
| F06 | ¿El número de encuesta se digita o se genera automáticamente? ¿Dónde se reinicia el correlativo y qué debe pasar si se supera el 1000? | Usuario confirma número automático. Implementado correlativo por EESS/periodo sin límite de 1000 y reenvíos sin duplicados. El ámbito de reinicio es la convención técnica actual. |
| F07 | ¿La captura será siempre a cargo del personal designado o también habrá un flujo para que el usuario o acompañante responda directamente? | Piloto acordado con un único digitador ficticio del EESS 00002630 CURAHUASI. El PDF identifica al encuestador; no determina si habrá autoservicio web. |
| F08 | ¿Se podrán corregir encuestas enviadas? Indicar quién podrá hacerlo, hasta cuándo y si requiere autorización. | |
| F09 | ¿Se conservan los rangos del Excel para edad y las reglas de hora de inicio y fin? Indicar cómo registrar una encuesta si falta alguna hora. | Usuario confirma fecha y horas automáticas. Inicio al abrir, final al enviar, America/Lima. El PDF no fija rango etario; 0–100 sigue provisional. |

## EESS, periodos y resultados

| ID | Pregunta | Respuesta |
| --- | --- | --- |
| R01 | ¿Cuál es el catálogo vigente de EESS, códigos RENIPRESS, microredes y red que se debe cargar? ¿Qué EESS participan en el piloto? | Catálogo aportado: Listado de Establecimientos.xls, 91 EESS importados. Piloto con 00002630 CURAHUASI. Vigencia institucional del catálogo pendiente de validación con la Red. |
| R02 | ¿Cómo se definen los periodos de medición? Indicar frecuencia, fecha de apertura y cierre, y si se permite digitar después del cierre encuestas aplicadas dentro del periodo. | Periodo automático por decisión del usuario. Piloto técnico 2026 implementado; periodicidad real y política de cierres aún pendientes. El PDF no las define. |
| R03 | ¿Se confirma que los pares con un 99 o con algún valor vacío se excluyen del denominador? Indicar si se conserva la clasificación de brecha ≥ 0 como satisfacción y brecha < 0 como insatisfacción. | No aplica sustituye 99. Excluir No aplica y vacíos del cálculo sigue siendo regla provisional. El PDF no define denominadores ni clasificación de satisfacción; pendiente validación metodológica. |
| R04 | ¿Se confirma el cálculo por dimensión y total como suma de pares válidos de los ítems? ¿Se aprueba contar todas las encuestas elegibles aunque el Excel omita filas y referencie porcentajes de otros ítems? | |
| R05 | ¿Cada EESS tendrá una meta de encuestas por periodo? Indicar quién la define, qué fuente de población atendida se usa y si se calculará con la hoja Muestra o se ingresará manualmente. | |
| R06 | ¿Qué salidas deben migrarse después del MVP y en qué orden? Considerar Muestra, Variables de Control, Reportes por dimensión, Matriz de mejora y Buen Trato. Indicar si hay funciones de las macros que deban replicarse. | |
| R07 | ¿Hay encuestas históricas que se deban importar? Si aplica, indicar archivos, periodos y cómo se resolverán los duplicados. | |

## Alta posterior de cuentas reales

| ID | Pregunta | Respuesta |
| --- | --- | --- |
| U01 | ¿El DNI será el identificador para iniciar sesión o solo un dato de la cuenta? Si se usará otro identificador, indicarlo. | Implementado DNI para acceso del usuario ficticio, con nombre completo y EESS como datos básicos. Alta y mecanismo de acceso de cuentas reales aún pendientes. |
| U02 | ¿El administrador dará de alta a las personas una por una o también necesitará carga masiva? Si requiere carga masiva, indicar el formato y las columnas disponibles, sin incluir datos personales en este README. | |
| U03 | ¿Cómo se entregarán las credenciales iniciales y cómo se recuperará el acceso? | |
| U04 | ¿Cómo se manejarán los traslados de personal entre EESS y las bajas de cuentas? Indicar si alguien podrá registrar para más de un establecimiento. | |
| U05 | ¿El administrador verá toda la Red o se necesitarán administradores con acceso limitado a determinadas microredes? | |

## Fuente y alcance de la revisión

PDF de Abancay 2026 revisado en sus dos páginas el 6 de noviembre de 2026. Se distingue evidencia del PDF, decisiones del usuario y supuestos provisionales. El plan actualizado está en [PLAN_DESARROLLO.md](PLAN_DESARROLLO.md).
