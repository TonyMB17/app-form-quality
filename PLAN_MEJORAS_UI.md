# Plan de mejoras de interfaz — 11/11/2026

## Dirección visual
Interfaz de trabajo para digitadores móviles y administradores. Mantener Manrope, morado #7650a9, lavanda #ece3f8, texto #30283e, fondo #f8f7fb, verde #227454 y blanco. Alineación izquierda, acciones estables, texto legible y estados acompañados de etiquetas. Sin cambios al cuestionario, sus opciones o estructura de datos.

## Entregas
1. Componentes y flujo (implementado): resumen de validación y progreso compartidos, edición en expectativas/percepciones/revisión, conservación de cambios entre pasos, aviso antes de abandonar; navegación móvil en filas, áreas táctiles, corrección de hover de preguntas. Validar compilación y flujos de navegador.
2. Inicio y listados (implementado): priorizar periodo vigente/nueva encuesta/registros recientes, búsqueda de usuarios, formatos de fechas y estados comunes, estados vacíos accionables.
3. Resultados y administración (implementado; revisión autenticada pendiente): comparación compacta con detalle desplegable, mantener denominadores y semáforos, distinguir programado/abierto/finalizado, reducir recorridos extensos.
4. Accesibilidad y movimiento (implementación parcial; pruebas reales pendientes): zoom y errores de imagen, auditoría de teclado/contraste, comprobación con teclado virtual en teléfono real, transiciones breves y respeto de movimiento reducido.

## Criterios de aceptación
No modificar textos de las 22 preguntas ni las opciones. No alterar cálculos/permisos. Sin pérdida de respuestas al cambiar de sección; guardar explícitamente. Sin desplazamiento horizontal de página a 390 px. Foco visible y errores navegables. Cada entrega documenta sus comprobaciones y limitaciones.

## Primera entrega
Se reutilizan ValidationSummary y SurveyProgress en registro y edición. La edición mantiene los datos generales y foto existentes; permite revisar ambos valores antes de guardar. Los estilos heredados todavía requieren consolidación gradual: no se considera completada una migración de todo el CSS.

Comprobación de primera entrega: compilación correcta; navegador a 390 px conserva las respuestas entre pasos, presenta los 22 ítems en revisión y no desborda horizontalmente. No se guardaron cambios durante esta comprobación. Se detectó y corrigió una etiqueta Editar duplicada heredada del CSS. El puerto 8000 responde con otro servicio; vista de revisión iniciada en 8001.

## Segunda entrega implementada
Inicio operativo con periodo vigente, cierre en formato local, nueva encuesta y cinco registros recientes restringidos a cuenta/establecimiento. Usuarios incorpora búsqueda por nombre/DNI y filtro autorizado de establecimiento, aplicados antes de paginar. Listado de encuestas distingue ausencia de datos de ausencia de coincidencias. Fecha compartida aplicada al inicio y listado. Compilación correcta; pruebas de autenticación/usuarios y nuevas aserciones de búsqueda/ámbito pasan. Navegador: inicio escritorio/móvil a 390 px sin desbordamiento y estado sin coincidencias verificados. Pendiente: entrega 3 de resultados/administración y consolidación restante de estilos.

## Tercera entrega implementada
Comparaciones territoriales con resumen visible y detalle desplegable por dimensiones/preguntas; búsqueda también para microredes y bloques de 10 resultados, sin cambiar cálculos ni semáforos. Publicación distingue Borrador, Programado, Abierto, Finalizado y Cerrado con hora del servidor en Lima. Acción Publicar diferenciada de apertura efectiva; fechas compartidas. Build correcto y 4 pruebas / 200 aserciones del tablero y horarios. Pendiente entrega 4: zoom, estados de imagen y revisión completa de accesibilidad.

## Cuarta entrega — 12/11/2026
Visor con zoom 100–400 %, ajuste, recorrido de imagen ampliada, carga/error/reintento. Teclado +/−/0/Escape, ciclo Tab/Shift+Tab dentro del diálogo, restauración de foco y scroll al cerrar. Enlace para saltar al contenido y enunciados asociados mediante aria-describedby. Se limita movimiento en dispositivos sin hover y se respeta reduced-motion.
Comprobación aislada con imagen de prueba, sin datos del sistema: error y reintento, límite 400 %, ajuste, foco atrapado, Escape, botón y fondo, restauración de scroll y viewport 390 px. Build correcto. Pendiente una auditoría integral de contraste/lectores de pantalla y cámara/teclado virtual en teléfono real. La consolidación completa del CSS y la revisión autenticada de administración siguen pendientes; no se declara cumplimiento integral de accesibilidad.

## Continuación de accesibilidad — 12/11/2026
Corregido el contraste de los textos secundarios del resumen de validación. Los campos generales enlazan sus errores mediante aria-describedby y exponen aria-invalid; las escalas también identifican su estado inválido. Verificación aislada de componentes con axe-core (WCAG 2 A/AA y 2.1 AA) a 390 y 1280 px: sin infracciones detectadas en la muestra. Comprobados cambio de respuesta con flechas, apertura por foco y cierre con Escape del enunciado, y ausencia de desbordamiento horizontal móvil. TypeScript y compilación de producción correctos. Esta comprobación no sustituye una auditoría integral ni la prueba con lector de pantalla, cámara y teclado virtual en un teléfono real.

## Revisión de fotografía antes del envío — 16/11/2026
La vista previa permite abrir el visor con zoom sin salir del formulario. Se añaden indicaciones de encuadre y legibilidad; al quitar la foto, el foco vuelve a Tomar foto y se limpia el error anterior. El estado de foto adjunta se anuncia mediante role=status. Verificación aislada con imagen de prueba: selección, apertura, Escape y restauración del foco, eliminación y foco; sin desbordamiento ni infracciones axe detectadas a 320, 390, 768, 1024 y 1440 px. Captura móvil revisada. Build y revisión previa de componentes correctos. No se enviaron encuestas. Continúa pendiente la cámara y el teclado virtual en un teléfono real.

## Dependencia entre expectativas y percepciones — 16/11/2026
Cuando la expectativa es No aplica o Sin respuesta, su percepción queda desactivada con explicación y conserva el mismo motivo al guardar. Al volver a una expectativa numérica se borra la percepción automática y se requiere una nueva elección. Regla compartida por registro, edición y recuperación de borradores; servidor normaliza al crear/actualizar. Sin migración de respuestas históricas ni cambios en preguntas. Compilación y prueba de interacción para ambas opciones correctas.

## Metas por establecimiento — 16/11/2026
Cada establecimiento tiene una meta configurable de 5 encuestas por digitador y periodo. El módulo de administración permite cambiarla para todos los digitadores del establecimiento dentro del ámbito autorizado y consultar el avance individual por periodo; alcanzarla no impide registrar más encuestas. La pantalla de inicio del digitador muestra su avance personal. Pruebas: valor predeterminado, permisos y ámbito, validación de cambios, persistencia al reimportar establecimientos y conteo individual por establecimiento/periodo. Comprobación: 2 pruebas / 102 aserciones y compilación de producción correctas.

## Revisión manual de metas — 16/11/2026
Con base aislada de pruebas, se verificaron la búsqueda con/sin coincidencias y el guardado de meta con confirmación visual. A 320, 390 y 1280 px no hay desbordamiento horizontal. La revisión detectó y corrigió el desbordamiento de navegación móvil y el plural incorrecto para un solo digitador. Suite completa: 35 pruebas / 749 aserciones; TypeScript y compilación de producción correctos.
