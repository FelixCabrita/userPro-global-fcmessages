# Sistema de Auditoría - UserPro Global Messages

## Resumen Ejecutivo

El plugin **UserPro Global Messages v1.1.0** incluye un **sistema completo de auditoría** que registra automáticamente todas las acciones administrativas destructivas en la base de datos de WordPress, proporcionando **trazabilidad total** y cumplimiento normativo.

---

## Características Principales

### ✅ Registro Automático

**Sin intervención manual requerida**. Cada vez que un administrador:
- Censura un mensaje
- Vacía una conversación completa

El sistema registra automáticamente:
- ✅ Quién realizó la acción (admin user)
- ✅ Qué hizo (tipo de acción)
- ✅ Cuándo lo hizo (timestamp exacto)
- ✅ Dónde lo hizo (IP address y navegador)
- ✅ A quién afectó (usuarios involucrados)
- ✅ Detalles adicionales (JSON con contexto)

### ✅ Almacenamiento Permanente

- **Base de datos**: Tabla dedicada `wp_upgm_audit_log`
- **Inmutable**: No se puede editar ni eliminar desde UI
- **Indexado**: Optimizado para búsquedas rápidas
- **Escalable**: Soporta miles de registros sin degradación

### ✅ Interfaz Avanzada

- **Estadísticas en tiempo real**
- **Filtros múltiples** (tipo, admin, usuario, fecha)
- **Detalles expandibles** por registro
- **Exportación a CSV** para auditorías externas
- **Búsqueda rápida** con paginación

---

## Estructura de la Base de Datos

### Tabla: `wp_upgm_audit_log`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint(20) | ID único autoincremental |
| `action_type` | varchar(50) | Tipo: censor_message, delete_conversation |
| `admin_user_id` | bigint(20) | ID del admin que ejecutó |
| `target_user_id_1` | bigint(20) | Primer usuario afectado |
| `target_user_id_2` | bigint(20) | Segundo usuario afectado |
| `timestamp_affected` | bigint(20) | Timestamp del mensaje (solo censura) |
| `details` | longtext | JSON con información adicional |
| `ip_address` | varchar(45) | IP del administrador (IPv4/IPv6) |
| `user_agent` | varchar(255) | Navegador y dispositivo |
| `created_at` | datetime | Fecha/hora del registro |

### Índices Creados

```sql
PRIMARY KEY (id)
KEY admin_user_id (admin_user_id)
KEY action_type (action_type)
KEY created_at (created_at)
KEY target_users (target_user_id_1, target_user_id_2)
```

**Beneficio**: Consultas optimizadas incluso con millones de registros.

---

## Flujo de Auditoría

### Ejemplo: Censura de Mensaje

```
1. Admin hace clic en "Censurar" mensaje
   ↓
2. JavaScript: Confirmación del usuario
   ↓
3. Backend: Verifica permisos y nonce
   ↓
4. UPGM_File_Handler: Modifica archivos .txt
   ↓
5. UPGM_Audit_Log::log(): Registra en BD
   ├─ Captura user actual
   ├─ Captura IP ($_SERVER)
   ├─ Captura User Agent
   ├─ Inserta registro en tabla
   └─ Retorna ID del log
   ↓
6. Redirect con mensaje de éxito
   ↓
7. Usuario puede ver el registro en Historial
```

### Código de Registro

```php
// En admin/class-admin-page.php (línea 127-136)

if ($result) {
    UPGM_Audit_Log::log(
        UPGM_Audit_Log::ACTION_CENSOR,
        $user_id_1,
        $user_id_2,
        array(
            'original_timestamp' => $timestamp,
            'message_datetime' => date('Y-m-d H:i:s', $timestamp)
        ),
        $timestamp
    );
}
```

---

## Uso de la Interfaz

### Acceso

1. WordPress Admin
2. Menú: **Global Messages**
3. Submenú: **Historial de Auditoría**

### Vista Principal

#### Tarjetas de Estadísticas

```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ Total Acciones  │      Hoy        │  Esta Semana    │   Este Mes      │
│      45         │       3         │       12        │       28        │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

#### Desglose por Tipo

```
┌─────────────────────────────┬────────┐
│ Mensaje Censurado           │   30   │
│ Conversación Eliminada      │   15   │
└─────────────────────────────┴────────┘
```

#### Tabla de Logs

| ID | Fecha/Hora | Acción | Admin | Usuarios Afectados | IP | Detalles |
|----|------------|--------|-------|-------------------|----|----|
| 45 | 15/01/2025 10:30 | Mensaje Censurado | admin | User A ↔ User B | 192.168.1.100 | [Ver] |
| 44 | 14/01/2025 15:20 | Conversación Eliminada | moderador | User C ↔ User D | 10.0.0.5 | [Ver] |

### Filtros Disponibles

```
Tipo de Acción: [Todas ▼]
Administrador:  [Todos ▼]
Usuario:        [Todos ▼]
Desde:          [📅]
Hasta:          [📅]

[Filtrar] [Limpiar]
```

### Detalles Expandibles

Al hacer clic en **Ver**:

```
┌─────────────────────────────────────────────────────────┐
│ DETALLES DEL REGISTRO                                   │
├─────────────────────────────────────────────────────────┤
│ Timestamp del Mensaje:                                  │
│ 2025-01-15 10:30:45 (1705317045)                       │
│                                                         │
│ Información Adicional:                                  │
│ {                                                       │
│   "original_timestamp": 1705317045,                    │
│   "message_datetime": "2025-01-15 10:30:45"           │
│ }                                                       │
│                                                         │
│ User Agent:                                             │
│ Mozilla/5.0 (Windows NT 10.0; Win64; x64)             │
│ Chrome/120.0.0.0                                       │
└─────────────────────────────────────────────────────────┘
```

### Botones de Acción

- **Expandir Todos**: Muestra todos los detalles de todos los registros
- **Contraer Todos**: Oculta todos los detalles
- **Exportar Auditoría a CSV**: Descarga archivo con logs actuales (respeta filtros)

---

## Exportación de Logs

### Formato CSV

```csv
ID,Fecha/Hora,Tipo de Acción,Admin,Usuario 1,Usuario 2,Timestamp Afectado,IP,Navegador,Detalles
45,"2025-01-15 10:30:45","Mensaje Censurado","admin (ID: 1)","User A","User B","2025-01-15 10:30:45","192.168.1.100","Chrome/120.0","{...}"
44,"2025-01-14 15:20:30","Conversación Eliminada","moderador (ID: 2)","User C","User D","N/A","10.0.0.5","Firefox/121.0","{...}"
```

### Casos de Uso

1. **Auditoría Externa**: Enviar CSV a auditor independiente
2. **Cumplimiento Legal**: Adjuntar a solicitudes de autoridades
3. **Análisis de Patrones**: Importar a Excel/Google Sheets para gráficos
4. **Documentación**: Archivar para registros históricos
5. **Investigación**: Identificar acciones sospechosas

---

## Seguridad del Sistema

### Capas de Protección

#### 1. Acceso Restringido
- Solo usuarios con capability `manage_userpro_global_messages`
- Por defecto: Solo administradores

#### 2. Inmutabilidad
- No hay UI para editar registros
- No hay UI para eliminar registros
- Solo se puede consultar y exportar

#### 3. Captura Forense
- **IP Address**: Identifica origen de la acción
- **User Agent**: Identifica dispositivo/navegador
- **Timestamp exacto**: Precisión de segundo

#### 4. Validación de Datos
```php
// Sanitización automática
$ip_address = substr(sanitize_text_field($ip), 0, 45);
$user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
$details = json_encode($details, JSON_UNESCAPED_UNICODE);
```

#### 5. Logging Dual
- **Archivo** (error_log): Si WP_DEBUG activo
- **Base de Datos**: Siempre, permanente

---

## Consultas SQL Útiles

### Ver registros de un admin específico

```sql
SELECT * FROM wp_upgm_audit_log
WHERE admin_user_id = 1
ORDER BY created_at DESC;
```

### Contar acciones por tipo

```sql
SELECT action_type, COUNT(*) as total
FROM wp_upgm_audit_log
GROUP BY action_type;
```

### Acciones en rango de fechas

```sql
SELECT * FROM wp_upgm_audit_log
WHERE created_at BETWEEN '2025-01-01' AND '2025-01-31'
ORDER BY created_at DESC;
```

### Acciones que afectaron a un usuario

```sql
SELECT * FROM wp_upgm_audit_log
WHERE target_user_id_1 = 5 OR target_user_id_2 = 5
ORDER BY created_at DESC;
```

### Acciones desde una IP específica

```sql
SELECT * FROM wp_upgm_audit_log
WHERE ip_address = '192.168.1.100'
ORDER BY created_at DESC;
```

---

## Cumplimiento Normativo

### GDPR (Reglamento General de Protección de Datos)

✅ **Artículo 30**: Registro de actividades de tratamiento
- El sistema registra quién accede y modifica datos personales

✅ **Artículo 32**: Seguridad del tratamiento
- Trazabilidad completa de acciones sobre conversaciones

✅ **Artículo 33**: Notificación de brechas
- Logs ayudan a identificar incidentes de seguridad

### SOC 2 (Service Organization Control)

✅ **CC6.1**: Registro de actividades del sistema
- Todos los cambios quedan documentados

✅ **CC6.2**: Revisión de logs
- Exportación facilita auditorías

### ISO 27001

✅ **A.12.4.1**: Registro de eventos
- Sistema cumple con requisito de logging

✅ **A.12.4.3**: Logs de administradores
- Captura específica de acciones de admins privilegiados

### HIPAA (para mensajes médicos)

✅ **164.308**: Registros de acceso
- Documentación de quién accede a qué información

---

## Mantenimiento

### Limpieza de Logs Antiguos (Opcional)

Si deseas eliminar logs antiguos para ahorrar espacio:

```php
// Agregar en wp-config.php o tema
// CUIDADO: Elimina permanentemente

add_action('admin_init', function() {
    if (current_user_can('manage_options')) {
        global $wpdb;
        $table = $wpdb->prefix . 'upgm_audit_log';

        // Eliminar logs de más de 2 años
        $wpdb->query("
            DELETE FROM {$table}
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR)
        ");
    }
});
```

**⚠️ ADVERTENCIA**: Solo hacer si no hay requisitos legales de retención.

### Backup de Logs

```bash
# Desde terminal/SSH
mysqldump -u usuario -p base_de_datos wp_upgm_audit_log > audit_backup.sql

# Restaurar
mysql -u usuario -p base_de_datos < audit_backup.sql
```

---

## Troubleshooting

### Problema: No se registran acciones

**Verificar:**
1. Tabla existe: `SHOW TABLES LIKE '%upgm_audit_log%';`
2. Permisos de BD: Usuario tiene INSERT privilege
3. Error log de WordPress: `wp-content/debug.log`

**Solución:**
```php
// Recrear tabla manualmente
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
UPGM_Audit_Log::create_table();
```

### Problema: Exportación no descarga

**Causas comunes:**
- Headers ya enviados (output antes de exportar)
- Permisos de archivo temporal

**Solución:**
1. Desactivar otros plugins temporalmente
2. Verificar que no haya espacios antes de `<?php` en archivos del plugin

### Problema: IP siempre es 127.0.0.1

**Causa:** Proxy o servidor local

**Solución:** Configurar en `class-audit-log.php`:
```php
private static function get_client_ip() {
    // Verificar headers de proxy
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}
```

---

## API para Desarrolladores

### Registrar Acción Personalizada

```php
// Desde tu código
UPGM_Audit_Log::log(
    'custom_action',  // Tipo
    $user_id_1,       // Usuario 1
    $user_id_2,       // Usuario 2
    array(            // Detalles (opcional)
        'custom_field' => 'valor',
        'reason' => 'Migración de datos'
    )
);
```

### Consultar Logs Programáticamente

```php
$logs = UPGM_Audit_Log::get_logs(array(
    'per_page' => 100,
    'page' => 1,
    'action_type' => 'censor_message',
    'admin_user_id' => 1,
    'date_from' => '2025-01-01',
    'date_to' => '2025-01-31'
));

foreach ($logs['items'] as $log) {
    echo "Acción: {$log['action_type']} por Admin {$log['admin_user_id']}\n";
}
```

### Obtener Estadísticas

```php
$stats = UPGM_Audit_Log::get_statistics();

echo "Total acciones: " . $stats['total_actions'];
echo "Hoy: " . $stats['today'];
echo "Esta semana: " . $stats['this_week'];
```

---

## Roadmap Futuro

### v1.2.0 (Planeado)

- [ ] Alertas por email cuando se realiza acción
- [ ] Dashboard con gráficos de tendencias
- [ ] Retención automática configurable
- [ ] Exportación a PDF con firma digital
- [ ] Integración con sistemas SIEM externos

### v1.3.0 (Planeado)

- [ ] Búsqueda de texto completo en detalles JSON
- [ ] Comparación de auditorías entre períodos
- [ ] Reportes programados automáticos
- [ ] API REST para integración externa

---

## Conclusión

El **Sistema de Auditoría de UserPro Global Messages** proporciona:

✅ **Trazabilidad Completa**: Quién, qué, cuándo, dónde
✅ **Cumplimiento Normativo**: GDPR, SOC 2, ISO 27001
✅ **Seguridad Forense**: IP, User Agent, detalles técnicos
✅ **Facilidad de Uso**: Interfaz intuitiva, filtros, exportación
✅ **Rendimiento**: Indexado, paginado, optimizado
✅ **Escalabilidad**: Soporta miles de registros sin problemas

**Ideal para:**
- Proyectos empresariales
- Aplicaciones gubernamentales
- Plataformas con datos sensibles
- Sitios que requieren auditorías externas
- Cumplimiento legal y normativo

---

**Versión del documento**: 1.0
**Fecha**: 2025-01-15
**Plugin**: UserPro Global Messages v1.1.0
