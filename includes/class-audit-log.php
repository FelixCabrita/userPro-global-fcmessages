<?php
/**
 * Sistema de Auditoría y Registro de Acciones
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}

class UPGM_Audit_Log {

    /**
     * Nombre de la tabla
     */
    const TABLE_NAME = 'upgm_audit_log';

    /**
     * Tipos de acciones
     */
    const ACTION_CENSOR = 'censor_message';
    const ACTION_DELETE = 'delete_conversation';
    const ACTION_EXPORT_LIST = 'export_list';
    const ACTION_EXPORT_DETAIL = 'export_detail';

    /**
     * Obtener nombre completo de tabla con prefix
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Crear tabla de auditoría
     */
    public static function create_table() {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            action_type varchar(50) NOT NULL,
            admin_user_id bigint(20) UNSIGNED NOT NULL,
            target_user_id_1 bigint(20) UNSIGNED NOT NULL,
            target_user_id_2 bigint(20) UNSIGNED NOT NULL,
            timestamp_affected bigint(20) UNSIGNED DEFAULT NULL,
            details longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY admin_user_id (admin_user_id),
            KEY action_type (action_type),
            KEY created_at (created_at),
            KEY target_users (target_user_id_1, target_user_id_2)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Registrar acción en el log
     *
     * @param string $action_type Tipo de acción
     * @param int $target_user_id_1 ID del primer usuario afectado
     * @param int $target_user_id_2 ID del segundo usuario afectado
     * @param array $details Detalles adicionales (opcional)
     * @param int $timestamp_affected Timestamp del mensaje (solo para censura)
     * @return int|false ID del registro insertado o false en caso de error
     */
    public static function log($action_type, $target_user_id_1, $target_user_id_2, $details = array(), $timestamp_affected = null) {
        global $wpdb;

        // Obtener información del admin actual
        $admin_user = wp_get_current_user();
        if (!$admin_user->ID) {
            return false;
        }

        // Obtener IP y User Agent
        $ip_address = self::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';

        // Preparar datos
        $data = array(
            'action_type' => $action_type,
            'admin_user_id' => $admin_user->ID,
            'target_user_id_1' => $target_user_id_1,
            'target_user_id_2' => $target_user_id_2,
            'timestamp_affected' => $timestamp_affected,
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'created_at' => current_time('mysql')
        );

        $format = array(
            '%s', // action_type
            '%d', // admin_user_id
            '%d', // target_user_id_1
            '%d', // target_user_id_2
            '%d', // timestamp_affected
            '%s', // details
            '%s', // ip_address
            '%s', // user_agent
            '%s'  // created_at
        );

        $result = $wpdb->insert(
            self::get_table_name(),
            $data,
            $format
        );

        if ($result === false) {
            error_log('UPGM Audit Log: Error al insertar registro - ' . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtener IP del cliente
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Sanitizar y limitar a 45 caracteres (IPv6)
        return substr(sanitize_text_field($ip), 0, 45);
    }

    /**
     * Obtener logs con filtros y paginación
     *
     * @param array $args Array de argumentos para filtrar
     * @return array Array con 'items' y 'total'
     */
    public static function get_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'per_page' => 50,
            'page' => 1,
            'action_type' => '',
            'admin_user_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'target_user_id' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $table_name = self::get_table_name();

        // Construir WHERE clause
        $where = array('1=1');
        $where_values = array();

        if (!empty($args['action_type'])) {
            $where[] = 'action_type = %s';
            $where_values[] = $args['action_type'];
        }

        if (!empty($args['admin_user_id'])) {
            $where[] = 'admin_user_id = %d';
            $where_values[] = $args['admin_user_id'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'] . ' 00:00:00';
        }

        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'] . ' 23:59:59';
        }

        if (!empty($args['target_user_id'])) {
            $where[] = '(target_user_id_1 = %d OR target_user_id_2 = %d)';
            $where_values[] = $args['target_user_id'];
            $where_values[] = $args['target_user_id'];
        }

        $where_clause = implode(' AND ', $where);

        // Contar total
        $count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total = $wpdb->get_var($count_query);

        // Construir query principal
        $orderby = in_array($args['orderby'], array('created_at', 'action_type', 'admin_user_id'))
            ? $args['orderby']
            : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $offset = ($args['page'] - 1) * $args['per_page'];
        $limit = $args['per_page'];

        $query = "SELECT * FROM {$table_name}
                  WHERE {$where_clause}
                  ORDER BY {$orderby} {$order}
                  LIMIT %d OFFSET %d";

        $query_values = array_merge($where_values, array($limit, $offset));
        $prepared_query = $wpdb->prepare($query, $query_values);

        $items = $wpdb->get_results($prepared_query, ARRAY_A);

        // Decodificar JSON en details
        foreach ($items as &$item) {
            $item['details'] = json_decode($item['details'], true);
        }

        return array(
            'items' => $items,
            'total' => $total
        );
    }

    /**
     * Obtener estadísticas de auditoría
     *
     * @return array
     */
    public static function get_statistics() {
        global $wpdb;
        $table_name = self::get_table_name();

        $stats = array();

        // Total de acciones
        $stats['total_actions'] = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        // Acciones por tipo
        $actions_by_type = $wpdb->get_results(
            "SELECT action_type, COUNT(*) as count
             FROM {$table_name}
             GROUP BY action_type",
            ARRAY_A
        );

        $stats['by_type'] = array();
        foreach ($actions_by_type as $row) {
            $stats['by_type'][$row['action_type']] = $row['count'];
        }

        // Acciones hoy
        $stats['today'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name}
                 WHERE DATE(created_at) = %s",
                current_time('Y-m-d')
            )
        );

        // Acciones esta semana
        $stats['this_week'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name}
                 WHERE created_at >= %s",
                date('Y-m-d', strtotime('-7 days'))
            )
        );

        // Acciones este mes
        $stats['this_month'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name}
                 WHERE created_at >= %s",
                date('Y-m-01')
            )
        );

        // Admin más activo
        $most_active = $wpdb->get_row(
            "SELECT admin_user_id, COUNT(*) as count
             FROM {$table_name}
             GROUP BY admin_user_id
             ORDER BY count DESC
             LIMIT 1",
            ARRAY_A
        );

        if ($most_active) {
            $stats['most_active_admin'] = array(
                'user_id' => $most_active['admin_user_id'],
                'count' => $most_active['count']
            );
        }

        return $stats;
    }

    /**
     * Obtener lista de admins que han realizado acciones
     *
     * @return array
     */
    public static function get_active_admins() {
        global $wpdb;
        $table_name = self::get_table_name();

        $admin_ids = $wpdb->get_col(
            "SELECT DISTINCT admin_user_id
             FROM {$table_name}
             ORDER BY admin_user_id"
        );

        $admins = array();
        foreach ($admin_ids as $admin_id) {
            $user = get_userdata($admin_id);
            if ($user) {
                $admins[] = array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email
                );
            }
        }

        return $admins;
    }

    /**
     * Exportar logs a CSV
     *
     * @param array $args Argumentos de filtrado
     */
    public static function export_to_csv($args = array()) {
        $logs_data = self::get_logs(array_merge($args, array('per_page' => 999999, 'page' => 1)));
        $logs = $logs_data['items'];

        $filename = 'audit_log_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados
        fputcsv($output, array(
            'ID',
            'Fecha/Hora',
            'Tipo de Acción',
            'Admin',
            'Usuario 1',
            'Usuario 2',
            'Timestamp Afectado',
            'IP',
            'Navegador',
            'Detalles'
        ));

        // Datos
        foreach ($logs as $log) {
            $admin = get_userdata($log['admin_user_id']);
            $user1 = get_userdata($log['target_user_id_1']);
            $user2 = get_userdata($log['target_user_id_2']);

            fputcsv($output, array(
                $log['id'],
                $log['created_at'],
                self::get_action_label($log['action_type']),
                $admin ? $admin->display_name . ' (ID: ' . $admin->ID . ')' : 'Usuario #' . $log['admin_user_id'],
                $user1 ? $user1->display_name : 'Usuario #' . $log['target_user_id_1'],
                $user2 ? $user2->display_name : 'Usuario #' . $log['target_user_id_2'],
                $log['timestamp_affected'] ? date('Y-m-d H:i:s', $log['timestamp_affected']) : 'N/A',
                $log['ip_address'],
                $log['user_agent'],
                json_encode($log['details'], JSON_UNESCAPED_UNICODE)
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Obtener etiqueta legible para tipo de acción
     *
     * @param string $action_type
     * @return string
     */
    public static function get_action_label($action_type) {
        $labels = array(
            self::ACTION_CENSOR => __('Mensaje Censurado', 'userpro-global-messages'),
            self::ACTION_DELETE => __('Conversación Eliminada', 'userpro-global-messages'),
            self::ACTION_EXPORT_LIST => __('Listado Exportado', 'userpro-global-messages'),
            self::ACTION_EXPORT_DETAIL => __('Conversación Exportada', 'userpro-global-messages')
        );

        return isset($labels[$action_type]) ? $labels[$action_type] : $action_type;
    }

    /**
     * Obtener clase CSS para tipo de acción
     *
     * @param string $action_type
     * @return string
     */
    public static function get_action_class($action_type) {
        $classes = array(
            self::ACTION_CENSOR => 'censor',
            self::ACTION_DELETE => 'delete',
            self::ACTION_EXPORT_LIST => 'export',
            self::ACTION_EXPORT_DETAIL => 'export'
        );

        return isset($classes[$action_type]) ? $classes[$action_type] : 'default';
    }

    /**
     * Verificar si la tabla existe
     *
     * @return bool
     */
    public static function table_exists() {
        global $wpdb;
        $table_name = self::get_table_name();
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }
}
