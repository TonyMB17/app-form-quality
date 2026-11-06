# Encuestas de satisfacción en consulta externa

Migración web del aplicativo SERVQUAL modificado para consulta externa del primer nivel de atención. La fuente funcional es `Aplicativo_CExtern_Primer_Nivel -.xlsm`. La hoja **`DIGITAR(BD `**, cuyo nombre termina en un espacio, es la pantalla de captura que se migrará primero.

**Cada fila es una encuesta individual. Un EESS registra varias encuestas durante un mismo periodo, pero cada cuenta digitadora solo puede enviar una.** Puede modificar sus respuestas mientras la encuesta esté publicada y antes de la fecha y hora límite configuradas por el administrador. El periodo sirve para organizar la captura y el análisis. La unidad de conteo del tablero inicial es la encuesta enviada.

## Arquitectura y alcance

- Un repositorio con Laravel, Inertia, React, TypeScript y Vite. Laravel gestiona sesiones, autorización, validación y persistencia. React presenta un formulario por pasos.
- MySQL como base de datos. Railway es el destino previsto, con un servicio para Laravel y otro para MySQL conectados por red privada. React se compila como parte del mismo despliegue web.
- Interfaz en español, adaptable a celular y escritorio. Usar `America/Lima` para presentar fechas y horas locales.
- El MVP comprende captura individual, listado del EESS y tablero administrativo. La migración de las salidas completas del Excel se especifica como fase posterior.
- El personal designado registra las encuestas. Cada cuenta digitadora tiene un EESS asignado y el administrador consulta todos los EESS de su ámbito. El MVP se prueba con cuentas y datos ficticios.

## Mapeo de la hoja de digitación

Encabezados en filas 12–14. Captura en `B16:BE467`, con 452 filas disponibles en la plantilla. El bloque estaba vacío en el archivo revisado. La capacidad de la cuadrícula corresponde al Excel y la aplicación puede almacenar más registros.

| Excel | Campo web | Valores y observaciones |
| --- | --- | --- |
| E8, I8, E9:E11 | Código RENIPRESS, establecimiento, microred, red y DIRESA/GERESA/DIRIS | Precargar con el EESS de la cuenta. La hoja resuelve los nombres mediante la tabla `renipress`. |
| B | N.º de encuesta | Entero de 1 a 1000 en Excel. Conservarlo como número visible de digitación. Usar un ID interno independiente. |
| C | Fecha de aplicación | Fecha de la encuesta. Excel permite 01/01/2020 a 31/12/2030. Confirmar la ventana de captura web por campaña antes del piloto. |
| D | Hora de inicio | Hora local, con precisión de minutos. |
| E | Condición del encuestado | 1 Usuario, 2 Acompañante. |
| F | Edad en años | Entero de 0 a 100 en Excel. |
| G | Sexo | 1 Masculino, 2 Femenino. |
| H | Nivel de estudios | 1 Analfabeto, 2 Primaria, 3 Secundaria, 4 Superior técnico, 5 Superior universitario. |
| I | Tipo de seguro | 1 SIS, 2 SOAT, 3 Ninguno. El desplegable de I16:I18 admite también 4 sin etiqueta en el encabezado. Confirmar su significado antes de ofrecerlo o normalizarlo. |
| J | Tipo de paciente | 1 Nuevo, 2 Continuador. |
| K | Especialidad donde fue atendido | La hoja no define una lista de validación. Usar texto en el piloto técnico y confirmar si habrá catálogo institucional. |
| L | Personal que realizó la atención | 1 Médico, 2 Obstetra, 3 Enfermera, 4 Psicólogo, 5 Odontólogo, 6 Otros. |
| M:AH | Expectativas E1–E22 | Valores enteros del 1 al 7 y código especial 99. |
| AI:BD | Percepciones P1–P22 | Misma escala y código especial. Cada P se empareja con su E del mismo número. |
| BE | Hora final | Hora local, con precisión de minutos. |
| BG:CB | Brechas P1−E1 a P22−E22 | Cálculo de resultados. Mostrar en consultas o reportes. |

`B15` cuenta los números de encuesta. `BF` queda fuera de la captura funcional. La hoja `RENIPRESS` contiene el código único en D, nombre en E, ubicación en F:I, códigos de ámbito en J:L y nombres de DISA, red y microrred en M:O. Importar los EESS autorizados usando el código único como texto para preservar ceros iniciales. Validar la vigencia del catálogo con la Red.

## Experiencia de digitación

1. Mostrar EESS, microred y periodo de trabajo en el encabezado.
2. Dividir el formulario en **Datos generales**, **Expectativas**, **Percepciones** y **Revisión**. Mostrar progreso por sección, conservar las respuestas al avanzar o retroceder y permitir llegar al campo que requiere atención desde la revisión.
3. Dentro de Expectativas y Percepciones, agrupar las preguntas por dimensión. En escritorio, facilitar la digitación con teclado y foco visible. En celular, presentar controles que se puedan tocar sin desplazamiento horizontal.
4. Mostrar las etiquetas de los catálogos en la interfaz y guardar sus códigos. Mantener E1–E22 y P1–P22 como identificadores estables de la versión del instrumento. Mientras se obtiene el cuestionario oficial completo, usar estos códigos como rótulos del prototipo.
5. Validar en React para dar respuesta inmediata y repetir las reglas en Laravel. Conservar los datos al recibir un error. Mostrar confirmación solo después de guardar y permitir modificar las respuestas de la única encuesta mientras esté habilitada.
6. Mantener separados la fecha y las horas de aplicación del sello de fecha y hora de digitación. El personal puede estar transcribiendo encuestas aplicadas con anterioridad.

El Excel permite celdas vacías en sus validaciones. Como regla provisional del MVP, exigir número, EESS, periodo y fecha de aplicación para enviar. Mostrar el conteo de pares completos e incompletos. Confirmar la obligatoriedad de los demás campos y el significado del 99 antes de cerrar las reglas del formulario de producción. Conservar vacío y 99 como valores distintos y evitar completar respuestas por defecto.

## Modelo de datos y permisos

| Entidad | Propuesta inicial |
| --- | --- |
| Ámbitos y EESS | DIRESA/GERESA/DIRIS, red, microred y EESS identificado por su código RENIPRESS. |
| Usuarios | Roles administrador y digitador. El servidor resuelve el EESS desde la cuenta del digitador. Ver la sección de alta de usuarios reales. |
| Periodos | Nombre, fechas de referencia y estado habilitado para la captura. Confirmar la periodicidad y si los periodos de aplicación y digitación difieren. |
| Encuestas | ID, número, EESS, periodo, versión del instrumento, datos generales, autor y fecha de guardado. |
| Respuestas | ID de encuesta, número de ítem 1–22, expectativa y percepción como enteros anulables. Restricción única por encuesta e ítem. |

Para el piloto, asumir que el número de encuesta es único por EESS y periodo y confirmar con la Red dónde se reinicia el correlativo. Mientras aplique ese supuesto, usar una restricción única sobre esos tres campos y guardar cabecera y respuestas en una transacción. Una repetición del envío debe devolver un resultado de duplicado comprensible. Encuestas con números distintos del mismo EESS y periodo deben poder coexistir. El digitador solo puede listar y consultar su propia encuesta; el administrador consulta listados, detalles y resultados dentro de su ámbito autorizado. La captura y edición de respuestas permanecen exclusivas del digitador propietario.

## Alta de usuarios reales en una fase posterior

El administrador ya dispone de los datos de las personas que llenarán el formulario. En una fase posterior, dará de alta una cuenta por persona con su **DNI** y le asignará el **EESS** correspondiente. La autenticación y los permisos por EESS se prueban desde el MVP con cuentas ficticias. La pantalla de alta de usuarios reales y cualquier carga del padrón quedan para esa fase.

El DNI identifica a la persona que registra la encuesta y pertenece al módulo de usuarios. La encuesta mantiene su propio número y se vincula al autor mediante el ID interno de la cuenta. La decisión de usar el DNI como nombre de inicio de sesión, así como la entrega y recuperación de credenciales, queda pendiente. Mantener el padrón real y las credenciales fuera del repositorio y de los datos de prueba.

## Tablero del MVP

- Mostrar cantidad de encuestas enviadas, tabla paginada y detalle con datos generales, expectativas y percepciones.
- Filtrar por periodo, microred y EESS. Al elegir microred, ofrecer sus EESS. Aplicar el mismo filtro a los totales y al listado.
- Refrescar los totales y el listado sin perder filtros, por ejemplo cada 30 segundos. Distinguir cantidad de encuestas y cantidad de EESS con encuestas.
- Implementar brechas por pregunta usando las reglas de abajo. Incorporar metas de muestra por EESS y porcentaje de avance cuando se confirme cómo asignarlas.

## Cálculo de satisfacción

| Dimensión | Ítems |
| --- | --- |
| Fiabilidad | 1–5 |
| Capacidad de respuesta | 6–9 |
| Seguridad | 10–13 |
| Empatía | 14–18 |
| Aspectos tangibles | 19–22 |

- Con un par válido de 1 a 7, calcular **percepción menos expectativa**. En `Reportes (Tabla)`, una brecha de cero o positiva cuenta como satisfecho y una brecha negativa cuenta como insatisfecho.
- Para la web, proponer la exclusión del denominador de pares con un 99 o con algún valor vacío. El Excel excluye 99 en las fórmulas de las filas 17–467, pero algunas fórmulas restan cuando falta solo un lado. Validar la decisión metodológica con la Red antes de publicar resultados oficiales.
- Para cada pregunta, el denominador es el número de pares válidos del ítem. Para una dimensión, sumar los conteos de sus ítems y dividir por el total de pares válidos de esa dimensión. Aplicar la misma lógica al total general. Mostrar «Sin pares válidos» cuando el denominador sea cero.
- Usar datos sintéticos para comparar el comportamiento. La fila 16 de digitación carece de fórmulas de brecha. `Reportes (Tabla)` salta las filas 452–466 y algunas celdas de porcentaje de `matriz de mejora` y `RESULT.BUEN TRATO` apuntan a ítems distintos de su etiqueta. En la web, vincular los resultados por ID de ítem y contar todas las encuestas elegibles. Registrar cualquier diferencia al conciliar con Excel.

## Fase posterior y pendientes de validación

| Hoja | Uso previsto |
| --- | --- |
| Muestra | Cálculo de tamaño de muestra a partir de la población atendida y parámetros configurables. |
| Variables de Control | Distribución de las variables generales del encuestado. |
| Reportes (Tabla) | Resultados completos por pregunta, dimensión y total. |
| matriz de mejora | Priorización por insatisfacción. El Excel muestra >60 % Por mejorar, 40–60 % En proceso y <40 % Aceptable. |
| RESULT.BUEN TRATO | Vista para los ítems P1, P3, P9, P10, P17 y P21. Validar las referencias de cada indicador al replicarla. |

Completar [README_PENDIENTES.md](README_PENDIENTES.md) con las decisiones del formulario, la metodología, el catálogo, los periodos y el alta de cuentas. Las reglas provisionales de este README permiten avanzar con datos ficticios. La revisión del Excel fue estática y la revisión de VBA depende de las funciones que se decida migrar. La carga histórica se evaluará cuando haya un archivo con encuestas registradas.

## Primer encargo para Codex

1. Revisar el repositorio y conservar las convenciones existentes. Si está vacío, iniciar Laravel con Inertia, React y TypeScript.
2. Implementar autenticación, roles, migraciones y datos ficticios para dos microredes, varios EESS y un periodo. Crear el catálogo de 22 ítems con sus códigos y dimensiones.
3. Implementar el formulario por pasos, el guardado transaccional y el listado de encuestas del EESS. Usar el mapeo de la hoja como referencia.
4. Implementar el tablero administrativo, los filtros y las brechas con las reglas provisionales explícitas.
5. Documentar ejecución local, variables en `.env.example` sin secretos, compilación de frontend, migraciones y configuración de Railway con red privada y respaldos. Mantener el XLSM original como referencia de trabajo y usar datos ficticios en pruebas y ejemplos.

### Criterios de entrega

- Cada cuenta digitadora registra una sola encuesta; puede modificar sus respuestas únicamente mientras esté publicada y antes del límite configurado. El servidor rechaza otro envío y cualquier edición cuando se cierra o vence el plazo.
- La aplicación conserva las respuestas al navegar entre pasos y al recibir errores de validación.
- Laravel rechaza el acceso de un digitador a encuestas ajenas, cambios del EESS mediante el cuerpo de la petición y puntajes fuera de los códigos admitidos.
- Los filtros del tablero coinciden con el conteo, el listado y el detalle. Las pruebas cubren brecha positiva, cero, negativa, 99, vacío y denominador cero.
- Desde una instalación limpia se pueden ejecutar las migraciones, cargar datos ficticios, compilar el frontend y recorrer el flujo principal.

## Ejecución local

Consultar [DESARROLLO.md](DESARROLLO.md) para instalar dependencias, configurar MySQL, ejecutar la aplicación y verificar esta primera etapa.

## Actualización de captura automática

Por decisión del usuario, periodo, número, fecha y horas dejan de digitarse. Esta regla reemplaza la captura manual descrita anteriormente. El servidor asigna el periodo vigente y el inicio al abrir el formulario, la hora final al enviar y un correlativo por EESS/periodo al guardar, sin límite de 1000. La fecha corresponde ahora al inicio del registro web. Los reenvíos de una misma captura no crean otra encuesta.

## Revisión del instrumento Abancay 2026

Consultar [PLAN_DESARROLLO.md](PLAN_DESARROLLO.md) y [README_PENDIENTES.md](README_PENDIENTES.md). El PDF aportado contiene los 44 enunciados. La escala solo etiqueta sus extremos (1 menor, 7 mayor). Por decisión del usuario, `No aplica` sustituye a 99 en captura y almacenamiento; vacío permanece separado. Las referencias a 99 anteriores describen el Excel o el comportamiento histórico. El seguro del PDF (1 SIS, 2 Ninguno, 3 Otro) requiere adaptación por versión antes de reemplazar el catálogo provisional.

## Regla vigente: elección obligatoria

Por decisión del usuario, cada una de las 44 respuestas requiere selección explícita: 1–7, No aplica o Sin respuesta. El estado inicial Seleccione una opción no es una respuesta válida. No aplica y Sin respuesta se almacenan como textos distintos y se excluyen de las brechas y denominadores. Los NULL históricos no se convierten y se identifican como vacíos anteriores. Datos generales obligatorios; Otros: especifique obligatorio al seleccionar Otros. Los metadatos siguen automáticos. Esta regla sustituye las referencias anteriores a vacíos permitidos en nuevas capturas.
