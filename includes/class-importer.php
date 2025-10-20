<?php
/**
 * Importador de conversaciones desde JSON/CSV
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}

class UPGM_Importer {

    /**
     * Tipos de importación
     */
    const TYPE_CONVERSATIONS = 'conversations';
    const TYPE_AUDIT_LOGS = 'audit_logs';

    /**
     * Errores de validación
     */
    private $errors = array();

    /**
     * Advertencias
     */
    private $warnings = array();

    /**
     * Validar archivo subido
     *
     * @param array $file Archivo de $_FILES
     * @param string $type Tipo de importación
     * @return array|false Datos parseados o false si error
     */
    public function validate_import_file($file, $type) {
        $this->errors = array();
        $this->warnings = array();

        // Verificar que el archivo se subió correctamente
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $this->errors[] = __('No se recibió ningún archivo.', 'userpro-global-messages');
            return false;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = __('Error al subir el archivo.', 'userpro-global-messages');
            return false;
        }

        // Verificar tamaño (máx 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            $this->errors[] = __('El archivo es demasiado grande (máximo 10MB).', 'userpro-global-messages');
            return false;
        }

        // Detectar formato
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $extension = strtolower($extension);

        if ($extension === 'json') {
            return $this->validate_json_file($file['tmp_name'], $type);
        } elseif ($extension === 'csv') {
            return $this->validate_csv_file($file['tmp_name'], $type);
        } else {
            $this->errors[] = __('Formato de archivo no soportado. Use JSON o CSV.', 'userpro-global-messages');
            return false;
        }
    }

    /**
     * Validar archivo JSON
     *
     * @param string $file_path Ruta al archivo
     * @param string $type Tipo de importación
     * @return array|false
     */
    private function validate_json_file($file_path, $type) {
        $content = file_get_contents($file_path);

        if ($content === false) {
            $this->errors[] = __('No se pudo leer el archivo.', 'userpro-global-messages');
            return false;
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = __('El archivo JSON no es válido: ', 'userpro-global-messages') . json_last_error_msg();
            return false;
        }

        // Validar estructura según tipo
        if ($type === self::TYPE_CONVERSATIONS) {
            return $this->validate_conversations_data($data);
        } elseif ($type === self::TYPE_AUDIT_LOGS) {
            return $this->validate_audit_logs_data($data);
        }

        $this->errors[] = __('Tipo de importación no válido.', 'userpro-global-messages');
        return false;
    }

    /**
     * Validar archivo CSV
     *
     * @param string $file_path Ruta al archivo
     * @param string $type Tipo de importación
     * @return array|false
     */
    private function validate_csv_file($file_path, $type) {
        $handle = fopen($file_path, 'r');

        if ($handle === false) {
            $this->errors[] = __('No se pudo leer el archivo CSV.', 'userpro-global-messages');
            return false;
        }

        // Leer encabezados
        $headers = fgetcsv($handle);

        if ($headers === false) {
            $this->errors[] = __('El archivo CSV está vacío.', 'userpro-global-messages');
            fclose($handle);
            return false;
        }

        // Convertir CSV a estructura de array
        $data = array();

        if ($type === self::TYPE_CONVERSATIONS) {
            $data['conversation'] = array('messages' => array());

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 4) continue; // Mínimo: from, to, timestamp, content

                $data['conversation']['messages'][] = array(
                    'from_user_id' => isset($row[0]) ? intval($row[0]) : 0,
                    'to_user_id' => isset($row[1]) ? intval($row[1]) : 0,
                    'timestamp' => isset($row[2]) ? intval($row[2]) : 0,
                    'content' => isset($row[3]) ? $row[3] : '',
                    'status' => isset($row[4]) ? $row[4] : 'unread'
                );
            }
        }

        fclose($handle);

        return $this->validate_conversations_data($data);
    }

    /**
     * Validar datos de conversaciones
     *
     * @param array $data
     * @return array|false
     */
    private function validate_conversations_data($data) {
        // Validar estructura básica
        if (!isset($data['conversation']) && !isset($data['conversations'])) {
            $this->errors[] = __('Estructura JSON inválida: falta "conversation" o "conversations".', 'userpro-global-messages');
            return false;
        }

        // Normalizar: si es array de conversaciones, tomar la primera por ahora
        if (isset($data['conversations']) && is_array($data['conversations'])) {
            if (empty($data['conversations'])) {
                $this->errors[] = __('No hay conversaciones en el archivo.', 'userpro-global-messages');
                return false;
            }
            // Por ahora solo importamos una conversación a la vez
            $this->warnings[] = __('Solo se importará la primera conversación del archivo.', 'userpro-global-messages');
            $conversation = $data['conversations'][0];
        } else {
            $conversation = $data['conversation'];
        }

        // Validar que tenga mensajes
        if (!isset($conversation['messages']) || !is_array($conversation['messages'])) {
            $this->errors[] = __('La conversación no tiene mensajes.', 'userpro-global-messages');
            return false;
        }

        if (empty($conversation['messages'])) {
            $this->errors[] = __('La conversación está vacía.', 'userpro-global-messages');
            return false;
        }

        // Validar mensajes
        foreach ($conversation['messages'] as $index => $msg) {
            if (!isset($msg['from_user_id']) || !isset($msg['to_user_id'])) {
                $this->errors[] = sprintf(__('Mensaje #%d: falta from_user_id o to_user_id.', 'userpro-global-messages'), $index + 1);
                return false;
            }

            if (!isset($msg['timestamp']) || !is_numeric($msg['timestamp'])) {
                $this->errors[] = sprintf(__('Mensaje #%d: timestamp inválido.', 'userpro-global-messages'), $index + 1);
                return false;
            }

            if (!isset($msg['content']) || empty($msg['content'])) {
                $this->warnings[] = sprintf(__('Mensaje #%d: contenido vacío.', 'userpro-global-messages'), $index + 1);
            }
        }

        return $data;
    }

    /**
     * Validar datos de logs de auditoría
     *
     * @param array $data
     * @return array|false
     */
    private function validate_audit_logs_data($data) {
        if (!isset($data['logs']) || !is_array($data['logs'])) {
            $this->errors[] = __('Estructura JSON inválida: falta "logs".', 'userpro-global-messages');
            return false;
        }

        if (empty($data['logs'])) {
            $this->errors[] = __('No hay logs en el archivo.', 'userpro-global-messages');
            return false;
        }

        // Validar cada log
        foreach ($data['logs'] as $index => $log) {
            $required_fields = array('action_type', 'admin_user_id', 'target_user_id_1', 'target_user_id_2', 'created_at');

            foreach ($required_fields as $field) {
                if (!isset($log[$field])) {
                    $this->errors[] = sprintf(__('Log #%d: falta campo requerido "%s".', 'userpro-global-messages'), $index + 1, $field);
                    return false;
                }
            }
        }

        return $data;
    }

    /**
     * Importar conversaciones desde datos validados
     *
     * @param array $data Datos validados
     * @return array Resultado con estadísticas
     */
    public function import_conversations($data) {
        $file_handler = new UPGM_File_Handler();

        // Normalizar datos
        if (isset($data['conversations'])) {
            $conversation = $data['conversations'][0];
        } else {
            $conversation = $data['conversation'];
        }

        $messages = $conversation['messages'];

        // Determinar IDs de usuarios
        $user_ids = array();
        foreach ($messages as $msg) {
            $user_ids[$msg['from_user_id']] = true;
            $user_ids[$msg['to_user_id']] = true;
        }
        $user_ids = array_keys($user_ids);

        if (count($user_ids) !== 2) {
            return array(
                'success' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => array(__('Error: la conversación debe ser entre exactamente 2 usuarios.', 'userpro-global-messages'))
            );
        }

        $user_id_1 = min($user_ids);
        $user_id_2 = max($user_ids);

        // Convertir a formato UserPro
        $result = $this->convert_to_userpro_format($messages, $user_id_1, $user_id_2);

        if (!$result['success']) {
            return array(
                'success' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => array($result['message'])
            );
        }

        return array(
            'success' => true,
            'imported' => 1,
            'skipped' => 0,
            'message' => sprintf(
                __('Importación exitosa: %d mensajes entre usuarios %d y %d.', 'userpro-global-messages'),
                count($messages),
                $user_id_1,
                $user_id_2
            ),
            'stats' => array(
                'messages_imported' => count($messages),
                'user_id_1' => $user_id_1,
                'user_id_2' => $user_id_2
            )
        );
    }

    /**
     * Convertir mensajes a formato UserPro y escribir archivos
     *
     * @param array $messages
     * @param int $user_id_1
     * @param int $user_id_2
     * @return array
     */
    private function convert_to_userpro_format($messages, $user_id_1, $user_id_2) {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/userpro/';

        // Crear directorios si no existen
        $dirs = array(
            $base_dir . $user_id_1 . '/conversations/archive/',
            $base_dir . $user_id_1 . '/conversations/unread/',
            $base_dir . $user_id_2 . '/conversations/archive/',
            $base_dir . $user_id_2 . '/conversations/unread/',
        );

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (!wp_mkdir_p($dir)) {
                    return array(
                        'success' => false,
                        'message' => sprintf(__('No se pudo crear el directorio: %s', 'userpro-global-messages'), $dir)
                    );
                }
            }
        }

        // Separar mensajes por perspectiva
        $messages_user1 = array();
        $messages_user2 = array();

        foreach ($messages as $msg) {
            $block = array(
                'mode' => $msg['from_user_id'] == $user_id_1 ? 'sent' : 'inbox',
                'status' => isset($msg['status']) ? $msg['status'] : 'unread',
                'timestamp' => $msg['timestamp'],
                'content' => $msg['content']
            );

            // Perspectiva usuario 1
            $messages_user1[] = $block;

            // Perspectiva usuario 2 (invertir mode)
            $block_user2 = $block;
            $block_user2['mode'] = $block['mode'] === 'sent' ? 'inbox' : 'sent';
            $messages_user2[] = $block_user2;
        }

        // Escribir archivos
        $file_user1 = $base_dir . $user_id_1 . '/conversations/archive/' . $user_id_2 . '.txt';
        $file_user2 = $base_dir . $user_id_2 . '/conversations/archive/' . $user_id_1 . '.txt';

        $content_user1 = $this->build_userpro_content($messages_user1);
        $content_user2 = $this->build_userpro_content($messages_user2);

        if (file_put_contents($file_user1, $content_user1) === false) {
            return array(
                'success' => false,
                'message' => __('Error al escribir archivo del usuario 1.', 'userpro-global-messages')
            );
        }

        if (file_put_contents($file_user2, $content_user2) === false) {
            return array(
                'success' => false,
                'message' => __('Error al escribir archivo del usuario 2.', 'userpro-global-messages')
            );
        }

        return array('success' => true);
    }

    /**
     * Construir contenido de archivo UserPro
     *
     * @param array $messages
     * @return string
     */
    private function build_userpro_content($messages) {
        $content = '';

        foreach ($messages as $msg) {
            $content .= '[mode]' . $msg['mode'] . '[/mode]' . "\n";
            $content .= '[status]' . $msg['status'] . '[/status]' . "\n";
            $content .= '[timestamp]' . $msg['timestamp'] . '[/timestamp]' . "\n";
            $content .= '[content]' . $msg['content'] . '[/content]' . "\n";
            $content .= '[/]' . "\n";
        }

        return $content;
    }

    /**
     * Importar logs de auditoría
     *
     * @param array $data Datos validados
     * @return array
     */
    public function import_audit_logs($data) {
        global $wpdb;

        $table_name = UPGM_Audit_Log::get_table_name();
        $imported = 0;
        $skipped = 0;

        foreach ($data['logs'] as $log) {
            // Verificar si ya existe (por ID o datos únicos)
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name}
                 WHERE admin_user_id = %d
                 AND target_user_id_1 = %d
                 AND target_user_id_2 = %d
                 AND created_at = %s",
                $log['admin_user_id'],
                $log['target_user_id_1'],
                $log['target_user_id_2'],
                $log['created_at']
            ));

            if ($exists > 0) {
                $skipped++;
                continue;
            }

            // Insertar
            $result = $wpdb->insert(
                $table_name,
                array(
                    'action_type' => $log['action_type'],
                    'admin_user_id' => $log['admin_user_id'],
                    'target_user_id_1' => $log['target_user_id_1'],
                    'target_user_id_2' => $log['target_user_id_2'],
                    'timestamp_affected' => isset($log['timestamp_affected']) ? $log['timestamp_affected'] : null,
                    'details' => isset($log['details']) ? json_encode($log['details']) : null,
                    'ip_address' => isset($log['ip_address']) ? $log['ip_address'] : '',
                    'user_agent' => isset($log['user_agent']) ? $log['user_agent'] : '',
                    'created_at' => $log['created_at']
                ),
                array('%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
            );

            if ($result) {
                $imported++;
            }
        }

        // Registrar en historial
        $this->log_import(
            self::TYPE_AUDIT_LOGS,
            basename($data['filename'] ?? 'unknown'),
            $imported,
            'completed',
            array('imported' => $imported, 'skipped' => $skipped)
        );

        return array(
            'success' => true,
            'message' => sprintf(
                __('Importación exitosa: %d logs importados, %d omitidos (duplicados).', 'userpro-global-messages'),
                $imported,
                $skipped
            ),
            'stats' => array(
                'imported' => $imported,
                'skipped' => $skipped
            )
        );
    }

    /**
     * Registrar importación en historial
     *
     * @param string $type
     * @param string $filename
     * @param int $records_count
     * @param string $status
     * @param array $details
     */
    private function log_import($type, $filename, $records_count, $status, $details = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'upgm_import_history';

        $wpdb->insert(
            $table_name,
            array(
                'import_type' => $type,
                'filename' => $filename,
                'records_count' => $records_count,
                'status' => $status,
                'admin_user_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'details' => json_encode($details)
            ),
            array('%s', '%s', '%d', '%s', '%d', '%s', '%s')
        );
    }

    /**
     * Obtener errores
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Obtener advertencias
     *
     * @return array
     */
    public function get_warnings() {
        return $this->warnings;
    }

    /**
     * Importar archivo (método unificado)
     *
     * @param string $file_path Ruta al archivo temporal
     * @param string $format Formato del archivo (json o csv)
     * @param string $type Tipo de importación (conversations o conversation_detail)
     * @return array Resultado con success, imported, errors
     */
    public function import_file($file_path, $format, $type) {
        $this->errors = array();
        $this->warnings = array();

        // Validar que el archivo existe
        if (!file_exists($file_path)) {
            return array(
                'success' => false,
                'errors' => array(__('El archivo no existe.', 'userpro-global-messages')),
                'imported' => 0,
                'skipped' => 0
            );
        }

        // Leer y parsear archivo según formato
        $data = false;

        if ($format === 'json') {
            $data = $this->validate_json_file($file_path, self::TYPE_CONVERSATIONS);
        } elseif ($format === 'csv') {
            $data = $this->validate_csv_file($file_path, self::TYPE_CONVERSATIONS);
        } else {
            return array(
                'success' => false,
                'errors' => array(__('Formato no soportado.', 'userpro-global-messages')),
                'imported' => 0,
                'skipped' => 0
            );
        }

        // Si la validación falló
        if ($data === false) {
            return array(
                'success' => false,
                'errors' => $this->errors,
                'imported' => 0,
                'skipped' => 0
            );
        }

        // Importar según el tipo
        $result = array();

        if ($type === 'conversations' || $type === 'conversation_detail') {
            $result = $this->import_conversations($data);
        } else {
            return array(
                'success' => false,
                'errors' => array(__('Tipo de importación no válido.', 'userpro-global-messages')),
                'imported' => 0,
                'skipped' => 0
            );
        }

        // Registrar en historial
        global $wpdb;
        $table_name = $wpdb->prefix . 'upgm_import_history';

        $wpdb->insert(
            $table_name,
            array(
                'import_type' => $type,
                'file_name' => basename($file_path),
                'file_format' => $format,
                'imported_count' => isset($result['imported']) ? $result['imported'] : 0,
                'skipped_count' => isset($result['skipped']) ? $result['skipped'] : 0,
                'status' => $result['success'] ? 'success' : 'error',
                'imported_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s')
        );

        return $result;
    }

    /**
     * Crear tabla de historial de importaciones
     */
    public static function create_import_history_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'upgm_import_history';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            import_type varchar(50) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_format varchar(10) NOT NULL,
            imported_count int(11) NOT NULL DEFAULT 0,
            skipped_count int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL,
            imported_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY imported_by (imported_by),
            KEY import_type (import_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Obtener historial de importaciones
     *
     * @param int $limit
     * @return array
     */
    public static function get_import_history($limit = 50) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'upgm_import_history';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name}
                 ORDER BY created_at DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
}
