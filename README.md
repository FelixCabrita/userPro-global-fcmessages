# UserPro Global Messages

Plugin de WordPress para administración global de mensajes de UserPro - visualización, filtrado, censura y exportación de conversaciones.

## Descripción

Este plugin permite a los administradores de WordPress **ver, gestionar y exportar todas las conversaciones** del sistema de mensajería de UserPro desde una interfaz centralizada en el panel de administración.

## Características

### ✅ Funcionalidades Principales

- **Listado Global de Conversaciones**: Ver todas las conversaciones entre todos los usuarios
- **Filtros Avanzados**:
  - Por usuario
  - Por fecha (rango desde-hasta)
  - Por palabra clave
  - Por estado (no leído/archivado)
- **Vista Detallada**: Ver conversación completa con mensajes ordenados cronológicamente
- **Censura de Mensajes**: Reemplazar contenido específico por texto de censura
- **Vaciado de Chats**: Eliminar conversaciones completas de ambos usuarios
- **Exportación**:
  - CSV/Excel del listado de conversaciones
  - CSV/Excel de conversación individual completa

### 🔒 Seguridad

- Capability personalizada: `manage_userpro_global_messages`
- Nonces para todas las operaciones
- Confirmaciones JavaScript antes de acciones destructivas
- Logging de acciones (cuando WP_DEBUG está activo)

## Instalación

### Método 1: Instalación Manual

1. Descargar el plugin como archivo ZIP
2. Ir a **WordPress Admin → Plugins → Añadir nuevo**
3. Clic en **Subir plugin**
4. Seleccionar el archivo ZIP
5. Clic en **Instalar ahora**
6. Activar el plugin

### Método 2: Instalación por FTP

1. Descomprimir el archivo ZIP
2. Subir la carpeta `userPro-global-octomessages` a `/wp-content/plugins/`
3. Activar el plugin desde el panel de WordPress

### Requisitos

- WordPress 5.0 o superior
- PHP 7.2 o superior
- Plugin UserPro instalado (para funcionalidad de mensajería)

## Configuración

### Permisos

Por defecto, solo los **administradores** tienen acceso al plugin. La capability `manage_userpro_global_messages` se asigna automáticamente al rol de administrador durante la activación.

Para dar acceso a otros roles, use un plugin de gestión de roles o agregue el código:

```php
$role = get_role('editor'); // o el rol que desee
$role->add_cap('manage_userpro_global_messages');
```

## Uso

### Acceso al Plugin

1. Ir a **WordPress Admin → Global Messages**
2. Se mostrará el listado de todas las conversaciones

### Listado de Conversaciones

#### Filtrar Conversaciones

1. Usar los filtros en la parte superior:
   - **Usuario**: Seleccionar un usuario específico
   - **Estado**: No leído / Archivado / Todos
   - **Fecha desde**: Fecha inicial
   - **Fecha hasta**: Fecha final
   - **Palabra clave**: Buscar en el contenido de los mensajes

2. Clic en **Filtrar**
3. Clic en **Limpiar** para resetear filtros

#### Acciones Disponibles

- **Ver Chat**: Abre la vista detallada de la conversación
- **Vaciar**: Elimina la conversación completa (requiere confirmación)
- **Exportar resultados a CSV**: Exporta el listado filtrado actual

### Vista Detallada de Conversación

#### Ver Mensajes

- Los mensajes se muestran en orden cronológico
- Cada mensaje muestra:
  - Remitente y destinatario
  - Fecha y hora
  - Timestamp Unix
  - Estado (read/unread)
  - Contenido completo

#### Censurar Mensaje

1. Clic en **Censurar** junto al mensaje
2. Confirmar la acción
3. El contenido se reemplazará por: `[Mensaje censurado por administración]`

**⚠️ IMPORTANTE**: Esta acción es **irreversible** y afecta los archivos de ambos usuarios.

#### Vaciar Conversación

1. Clic en **Vaciar Conversación Completa**
2. Confirmar la acción
3. Se eliminarán **todos los archivos** de la conversación:
   - `/userpro/{user1}/conversations/archive/{user2}.txt`
   - `/userpro/{user1}/conversations/unread/{user2}.txt`
   - `/userpro/{user2}/conversations/archive/{user1}.txt`
   - `/userpro/{user2}/conversations/unread/{user1}.txt`

**⚠️ IMPORTANTE**: Esta acción es **irreversible**.

#### Exportar Conversación

- **Exportar a CSV**: Formato compatible con Excel, Google Sheets
- **Exportar a Excel**: Formato XLS con estilos

Columnas exportadas:
- De (nombre de usuario)
- Para (nombre de usuario)
- Fecha/Hora
- Timestamp
- Estado
- Contenido

## Estructura de Archivos

```
userPro-global-octomessages/
├── userpro-global-messages.php     # Archivo principal
├── README.md                        # Esta documentación
├── includes/
│   ├── class-file-handler.php      # Manejo de archivos de conversación
│   ├── class-message-manager.php   # Lógica de negocio
│   ├── class-exporter.php          # Exportación CSV/Excel
│   └── class-security.php          # Seguridad y validaciones
├── admin/
│   ├── class-admin-page.php        # Página administrativa
│   └── views/
│       ├── list-view.php           # Vista de listado
│       └── detail-view.php         # Vista de detalle
└── assets/
    ├── css/
    │   └── admin-styles.css        # Estilos
    └── js/
        └── admin-scripts.js        # Scripts JavaScript
```

## Arquitectura de Datos

### Ubicación de Conversaciones

Los archivos de conversación se almacenan en:

```
/wp-content/uploads/userpro/{USER_ID}/conversations/{archive|unread}/{OTHER_USER_ID}.txt
```

### Formato de Archivos

Cada archivo `.txt` contiene bloques de mensajes con la siguiente estructura:

```
[mode]sent|inbox[/mode]
[status]unread|read[/status]
[timestamp]1234567890[/timestamp]
[content]Texto del mensaje[/content]
[/]
```

### Funcionamiento

- Cada usuario tiene su propia copia de la conversación
- `mode=sent`: Mensaje enviado por el usuario
- `mode=inbox`: Mensaje recibido de otro usuario
- El plugin unifica ambas perspectivas para mostrar la conversación completa

## Mejores Prácticas

### ⚠️ ANTES de Operaciones Destructivas

1. **Hacer respaldo** de la carpeta `/wp-content/uploads/userpro/`
2. Verificar que tiene los archivos correctos
3. Confirmar la acción

### 🔍 Para Búsquedas Eficientes

- Usar filtros combinados para resultados más precisos
- Las búsquedas por palabra clave son case-insensitive
- Los filtros de fecha usan el timestamp del último mensaje

### 📊 Para Exportaciones

- Exportar solo lo necesario usando filtros
- Para auditorías completas, exportar sin filtros
- Los archivos CSV tienen codificación UTF-8 con BOM

## Troubleshooting

### No veo ninguna conversación

- Verificar que existan archivos en `/wp-content/uploads/userpro/`
- Verificar permisos de lectura de archivos
- Activar WP_DEBUG y revisar logs

### Error al exportar

- Verificar que no haya output antes de la exportación
- Desactivar plugins que modifiquen headers
- Verificar permisos de escritura temporales

### Mensaje no se censura

- Verificar permisos de escritura en archivos
- Verificar que el timestamp sea correcto
- Revisar logs si WP_DEBUG está activo

### Conversación no se elimina

- Verificar permisos de escritura
- Verificar que ambos IDs de usuario sean válidos
- Los archivos pueden estar en uso (poco probable)

## Logging y Debug

Para activar logging de acciones:

```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Las acciones se registrarán en `/wp-content/debug.log` con formato:

```
[2025-01-15 10:30:45] UserPro Global Messages - censor_message by admin (ID: 1) - Data: {"user_id_1":1,"user_id_2":2,"timestamp":1234567890}
```

## Soporte y Desarrollo

### Para Desarrolladores

El plugin está estructurado de forma modular:

- **UPGM_File_Handler**: Maneja lectura/escritura de archivos
- **UPGM_Message_Manager**: Lógica de negocio y filtros
- **UPGM_Exporter**: Generación de archivos CSV/Excel
- **UPGM_Security**: Validaciones y permisos
- **UPGM_Admin_Page**: Renderizado de vistas

### Extender Funcionalidad

```php
// Ejemplo: Hook antes de censurar
add_action('upgm_before_censor', function($user_id_1, $user_id_2, $timestamp) {
    // Tu código aquí
}, 10, 3);

// Ejemplo: Modificar filtros
add_filter('upgm_conversation_filters', function($filters) {
    // Agregar filtros personalizados
    return $filters;
});
```

## Historial de Auditoría

### 📊 Sistema de Trazabilidad Completa

El plugin incluye un **sistema avanzado de auditoría** que registra automáticamente todas las acciones destructivas en la base de datos.

#### Acceso al Historial

1. Ir a **WordPress Admin → Global Messages → Historial de Auditoría**
2. Ver todas las acciones registradas con información completa

#### Información Registrada

Para cada acción se guarda:

| Campo | Descripción |
|-------|-------------|
| **ID** | Identificador único del registro |
| **Fecha/Hora** | Timestamp exacto de la acción |
| **Tipo de Acción** | Censura de mensaje / Eliminación de conversación |
| **Admin** | Usuario que realizó la acción |
| **Usuarios Afectados** | IDs y nombres de los usuarios involucrados |
| **IP** | Dirección IP del administrador |
| **User Agent** | Navegador y dispositivo utilizado |
| **Detalles** | Información adicional en formato JSON |

#### Estadísticas en Tiempo Real

- **Total de acciones** realizadas
- **Acciones hoy**
- **Acciones esta semana**
- **Acciones este mes**
- **Desglose por tipo** de acción

#### Filtros Avanzados

- Por tipo de acción
- Por administrador
- Por usuario afectado
- Por rango de fechas

#### Exportación de Logs

Exportar el historial completo o filtrado a CSV para:
- Auditorías externas
- Cumplimiento normativo
- Análisis de patrones
- Documentación legal

#### Características de Seguridad

✅ **Registros inmutables**: No se pueden editar ni eliminar desde la UI
✅ **Trazabilidad total**: Quién hizo qué, cuándo y desde dónde
✅ **Almacenamiento permanente**: Base de datos de WordPress
✅ **Detalles técnicos**: IP y User Agent para auditorías forenses

#### Ejemplo de Uso

```
Escenario: Un admin censura un mensaje inapropiado

Registro automático:
- Acción: Mensaje Censurado
- Admin: juan_admin (ID: 1)
- Usuarios: Usuario A ↔ Usuario B
- Timestamp del mensaje: 2025-01-15 10:30:45
- IP: 192.168.1.100
- Navegador: Chrome 120.0 en Windows 11
- Detalles: { "original_timestamp": 1705317045 }
```

#### Acceder a Detalles

1. En la página de auditoría, clic en **Ver** en la columna Detalles
2. Se despliega información completa:
   - Timestamp del mensaje censurado (si aplica)
   - Información adicional en JSON
   - User Agent completo
3. Copiar IP: Clic en la IP para copiarla al portapapeles

#### Botones de Acción Rápida

- **Expandir Todos**: Muestra todos los detalles
- **Contraer Todos**: Oculta todos los detalles
- **Exportar Auditoría**: Descarga CSV con registros actuales/filtrados

### Cumplimiento y Normativas

Este sistema de auditoría ayuda a cumplir con:

- **GDPR**: Trazabilidad de acciones sobre datos personales
- **SOC 2**: Control de acceso y registro de cambios
- **ISO 27001**: Gestión de seguridad de la información
- **Auditorías Internas**: Documentación de acciones administrativas

---

## Changelog

### 1.1.0 - 2025-01-15

- ✅ **NUEVO: Sistema de Auditoría**
  - Registro automático en base de datos
  - Historial completo con filtros
  - Estadísticas en tiempo real
  - Exportación de logs a CSV
  - Detalles expandibles por registro
  - Captura de IP y User Agent
- ✅ Mejoras en seguridad
- ✅ Nuevas tablas de base de datos
- ✅ Interfaz mejorada

### 1.0.0 - 2025-01-15

- ✅ Lanzamiento inicial
- ✅ Listado de conversaciones
- ✅ Filtros avanzados
- ✅ Vista detallada
- ✅ Censura de mensajes
- ✅ Vaciado de chats
- ✅ Exportación CSV/Excel
- ✅ Sistema de seguridad
- ✅ Logging de acciones

## Licencia

GPL-2.0+

## Créditos

Desarrollado para administración de mensajes de UserPro.

---

**⚠️ ADVERTENCIA**: Este plugin trabaja directamente con archivos del sistema de UserPro. Siempre haga respaldo antes de realizar operaciones destructivas (censura o vaciado).
