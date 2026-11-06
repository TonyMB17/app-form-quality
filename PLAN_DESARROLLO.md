# Plan de desarrollo actualizado

Revisión: 6 de noviembre de 2026. Fuente: `FORMATO ENCUESTA RED DE SALUD ABANCAY 2026.pdf`, dos páginas, revisadas visualmente. Las decisiones explícitas del usuario prevalecen sobre el formato impreso.

## Hallazgos confirmados

- Página 1: datos generales, presentación al encuestado y confidencialidad. Condición, sexo, estudios y tipo de usuario coinciden con el mapeo previo.
- Página 1: seguro **1 SIS, 2 Ninguno, 3 Otro**. El Excel anterior usaba 2 SOAT y 3 Ninguno. No cambiar las etiquetas históricas sin distinguir versiones.
- Página 1: **Consultorio / área donde fue atendido** es texto libre; no se presenta un catálogo de especialidades.
- Página 1: personal incluye Médico, Obstetra, Enfermera, Psicólogo, Odontólogo y **Otros con espacio para especificar**. El papel no muestra códigos numéricos para esta lista.
- Página 2: están los 22 enunciados E y los 22 P. Deben transcribirse por separado: las percepciones no son una transformación mecánica del texto de expectativas. Por ejemplo, E9 trata de mecanismos para resolver problemas y P9 pregunta si el problema se resolvió inmediatamente.
- Página 2: expectativas evalúa la importancia de la atención esperada; percepciones, la atención recibida. Escala 1–7, con 1 como menor calificación y 7 como mayor. No hay etiquetas intermedias como «regular» o «bueno».
- Por instrucción del usuario, **No aplica** comprende no aplicabilidad o deseo de no responder. No es una puntuación ni equivale a vacío. El PDF no contiene esta opción: es una decisión adicional del proyecto.
- Se mantiene la automatización solicitada de periodo, número, fecha y horas, aunque el papel los presente como campos manuales.

## Implementado en esta revisión

La captura, revisión, detalle y validación aceptan 1–7, `No aplica` o vacío. La migración convierte los 99 existentes a `No aplica`, conserva puntajes y nulos, y no cambia encuestas ni respuestas ajenas a esa conversión. La brecha solo se calcula con dos puntajes de 1–7. Un par con ambos campos contestados puede contener No aplica y no ser calculable.

## Adaptación al PDF implementada

1. Implementada una versión `abancay-2026` con los 44 enunciados verificados contra la página 2. Guardar textos, códigos E/P y dimensiones por versión; conservar las encuestas del instrumento provisional.
2. Implementado el catálogo de seguro 1 SIS / 2 Ninguno / 3 Otro solo para la nueva versión. Mostrar las etiquetas anteriores en registros provisionales. No convertir el código 2 antiguo en Ninguno: representaba SOAT.
3. Actualizado el rótulo «Especialidad» a «Consultorio / área donde fue atendido», manteniendo texto libre. Añadir «Especifique» al elegir Otros en personal. Definir antes de exigirlo si se permite omitir ese texto.
4. Incorporadas las instrucciones de expectativas y percepciones y la escala con sus extremos, sin inventar descripciones intermedias. Mostrar cada código junto a su enunciado completo. Usar una columna de preguntas en celular y revisar legibilidad en escritorio.
5. Confirmar obligatoriedad de los ocho datos generales y de los 44 campos de respuesta. La frase del PDF «sírvase contestar todas las preguntas» expresa una instrucción al encuestado, pero no resuelve el tratamiento técnico de omisiones. Hasta decidirlo, conservar la regla provisional de vacíos permitidos.
6. Probar captura completa, No aplica, vacíos, correcciones antes del envío, errores de validación, reenvíos, metadatos automáticos y consulta histórica con la versión correcta. Mantener un único usuario de prueba de CURAHUASI.

## Entrega posterior: tablero

Confirmar acceso administrador y alcance territorial antes de habilitarlo. Implementar conteo de encuestas, filtros coherentes y cálculo por pregunta/dimensión solo con pares numéricos válidos. Confirmar con la Red exclusiones del denominador, criterio de satisfacción y agregación antes de presentar resultados oficiales. El PDF no define estos cálculos ni metas de muestra.

## Pendientes que el documento no resuelve

Correcciones de encuestas enviadas, periodicidad y cierre de captura, metas de muestra, importación histórica, obligatoriedad de campos, administración de cuentas y recuperación de credenciales. Ver `README_PENDIENTES.md` para las decisiones por ID.

### Estado de la entrega

Los 44 enunciados están en config/abancay2026.php y se presentan en captura, revisión y detalle para la versión abancay-2026. La versión queda fijada en el inicio firmado del formulario. Registros provisionales conservan el catálogo de seguro anterior y no reciben enunciados retroactivamente. Otros: especifique se guarda solo al seleccionar Otros y es opcional. Pendientes: obligatoriedad definitiva y tablero.

## Regla vigente: elección obligatoria

Por decisión del usuario, cada una de las 44 respuestas requiere selección explícita: 1–7, No aplica o Sin respuesta. El estado inicial Seleccione una opción no es una respuesta válida. No aplica y Sin respuesta se almacenan como textos distintos y se excluyen de las brechas y denominadores. Los NULL históricos no se convierten y se identifican como vacíos anteriores. Datos generales obligatorios; Otros: especifique obligatorio al seleccionar Otros. Los metadatos siguen automáticos. Esta regla sustituye las referencias anteriores a vacíos permitidos en nuevas capturas.
