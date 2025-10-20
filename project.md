# Documento de Desarrollo — Plugin “UserPro Global Messages”

**Costo:** {C}

## 1) Resumen

Se desarrollará un **plugin** para WordPress que agrega una **sección en el menú administrativo** desde la cual un usuario autorizado podrá **ver todos los mensajes de todos los usuarios** del sistema de mensajería de UserPro.

## 2) Alcance Funcional

* **Visualización de chats** entre usuarios, mostrando con claridad **quién envió qué y cuándo**.
* **Filtros** en el listado:

  * **Usuario**
  * **Fecha** (rango)
  * **Palabra clave**
  * **Estado** (unread/archive)
* **Censura de mensajes específicos** (reemplazo de contenido por texto de censura).
* **Vaciado (eliminación) de chats completos** según selección del administrador.
* **Exportación** de resultados a **CSV/Excel** (tanto listados como un chat puntual).

> **Nota:** El plugin trabaja en modo lectura/escritura sobre los **archivos de conversación** ubicados en `wp-content/uploads/userpro/{ID_USER}/conversations/{archive|unread}/`.

## 3) Entregables

* **Plugin instalable (.zip)** con una **página de administración** en `wp-admin`.
* **Código fuente** limpio y estructurado.
* **Documentación breve**:

  * Instalación y activación.
  * Configuración mínima.
  * Uso del panel (filtros, lectura, censura, vaciado, exportación).
  * Recomendaciones de respaldo antes de operaciones destructivas.

## 4) Diseño de la Sección Administrativa

* **Menú:** “UserPro → Global Messages”.
* **Listado principal de chats**:

  * Columnas: Usuarios involucrados (A↔B), último mensaje (snippet), fecha del último mensaje, estado (unread/archive).
  * **Filtros**: usuario, fecha (desde–hasta), palabra clave, estado.
  * **Paginación** estándar.
  * Acciones:

    * **Ver chat** (detalle).
    * **Vaciar chat** (confirmación previa).
* **Vista de detalle de chat**:

  * Mensajes en orden cronológico con:

    * **Remitente**, **timestamp**, **contenido**.
  * Acciones por mensaje:

    * **Censurar** (confirmación + texto por defecto “Mensaje censurado por administración”).
  * Acciones del hilo:

    * **Exportar** chat a CSV/Excel.
* **Exportación**:

  * Desde el listado (según filtros aplicados).
  * Desde el detalle de un chat (solo ese hilo).

## 5) Operaciones Soportadas

* **Lectura** de archivos `{otro_usuario_id}.txt` en `archive/` y `unread/`.
* **Censura** de un mensaje:

  * Reescritura del bloque del mensaje reemplazando el texto de `[content]…[/content]`.
* **Vaciado** de chat:

  * Eliminación del archivo `{otro_usuario_id}.txt` en `archive/` y `unread/` del **usuario A**.
  * Eliminación del archivo recíproco en la carpeta del **usuario B** para mantener consistencia.
* **Exportación**:

  * Generación de CSV/Excel con columnas: `from_user`, `to_user`, `timestamp`, `datetime`, `estado`, `contenido`.

## 6) Consideraciones de Seguridad

* Acceso restringido mediante **capability dedicada** (se creará una capacidad específica y se asignará a administradores).
* **Confirmaciones** explícitas antes de cualquier operación destructiva (censura/vaciado).
* **Registros mínimos** (en pantalla) de lo actuado para claridad del operador.
* Sin exposición en el front-end (todo en `wp-admin`).

## 7) Manejo de Estados y Consistencia

* El **estado** (unread/archive) se determina por la **carpeta** donde reside el archivo.
* Para evitar duplicidades A↔B, la interfaz presentará el chat como una sola conversación unificada.
* En **vaciado**, se actuará en **ambas rutas** (usuario A y usuario B) para conservar consistencia.

## 8) Rendimiento

* Lectura **directa** de archivos con **paginación** y aplicación de filtros previos siempre que sea posible.
* En exportaciones y búsquedas por **palabra clave**, se procesará el archivo correspondiente al chat o el subconjunto filtrado para no impactar el rendimiento general.

## 9) Pruebas y Validación

* Casos de prueba:

  * Listado con cada filtro individual y combinados.
  * Visualización del detalle de chat.
  * **Censura** de un mensaje y verificación de su reflejo en la interfaz.
  * **Vaciado** de chat y confirmación de eliminación en ambas rutas de usuarios.
  * **Exportación** correcta (encabezados, codificación, orden cronológico).
* Verificación en entorno de **staging** antes de producción.

## 10) Documentación Incluida

* Guía de **instalación/activación**.
* Guía de **uso** (con capturas: filtros, lectura, censura, vaciado, exportación).
* Guía de **mantenimiento básico** (respaldo previo a operaciones destructivas y ubicación de archivos).

---

**Entrega:** Plugin + código + documentación breve, listo para que, en el futuro, **otro desarrollador** pueda **escalarlo o continuarlo** sin complicaciones.
