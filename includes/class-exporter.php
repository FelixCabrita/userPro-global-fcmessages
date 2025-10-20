<?php
/**
 * Exportador de conversaciones a CSV/Excel
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}

class UPGM_Exporter {

    /**
     * Exportar conversaciones a CSV
     *
     * @param array $conversations Array de conversaciones
     * @param string $filename Nombre del archivo
     */
    public function export_conversations_list($conversations, $filename = 'conversaciones') {
        $filename = sanitize_file_name($filename) . '_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados
        fputcsv($output, array(
            'Usuario 1',
            'Usuario 2',
            'Último Mensaje',
            'Fecha',
            'Estado',
            'Total Mensajes'
        ));

        // Datos
        foreach ($conversations as $conv) {
            $user1 = get_userdata($conv['user_id_1']);
            $user2 = get_userdata($conv['user_id_2']);

            fputcsv($output, array(
                $user1 ? $user1->display_name : 'Usuario #' . $conv['user_id_1'],
                $user2 ? $user2->display_name : 'Usuario #' . $conv['user_id_2'],
                $this->truncate_text($conv['last_message'], 100),
                date('Y-m-d H:i:s', $conv['last_timestamp']),
                $conv['status'],
                $conv['message_count']
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar detalle de conversación a CSV
     *
     * @param array $messages Array de mensajes
     * @param int $user_id_1
     * @param int $user_id_2
     */
    public function export_conversation_detail($messages, $user_id_1, $user_id_2) {
        $user1 = get_userdata($user_id_1);
        $user2 = get_userdata($user_id_2);

        $user1_name = $user1 ? $user1->display_name : 'Usuario_' . $user_id_1;
        $user2_name = $user2 ? $user2->display_name : 'Usuario_' . $user_id_2;

        $filename = sanitize_file_name('chat_' . $user1_name . '_' . $user2_name . '_' . date('Y-m-d_H-i-s') . '.csv');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados
        fputcsv($output, array(
            'De',
            'Para',
            'Fecha/Hora',
            'Timestamp',
            'Estado',
            'Contenido'
        ));

        // Datos
        foreach ($messages as $msg) {
            fputcsv($output, array(
                $msg['from_user_name'],
                $msg['to_user_name'],
                $msg['datetime'],
                $msg['timestamp'],
                $msg['status'],
                $msg['content']
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Exportar a Excel (HTML con formato)
     *
     * @param array $messages Array de mensajes
     * @param int $user_id_1
     * @param int $user_id_2
     */
    public function export_conversation_excel($messages, $user_id_1, $user_id_2) {
        $user1 = get_userdata($user_id_1);
        $user2 = get_userdata($user_id_2);

        $user1_name = $user1 ? $user1->display_name : 'Usuario_' . $user_id_1;
        $user2_name = $user2 ? $user2->display_name : 'Usuario_' . $user_id_2;

        $filename = sanitize_file_name('chat_' . $user1_name . '_' . $user2_name . '_' . date('Y-m-d_H-i-s') . '.xls');

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF"; // BOM UTF-8

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th { background-color: #4CAF50; color: white; padding: 8px; text-align: left; border: 1px solid #ddd; }';
        echo 'td { padding: 8px; border: 1px solid #ddd; }';
        echo 'tr:nth-child(even) { background-color: #f2f2f2; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>De</th>';
        echo '<th>Para</th>';
        echo '<th>Fecha/Hora</th>';
        echo '<th>Timestamp</th>';
        echo '<th>Estado</th>';
        echo '<th>Contenido</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($messages as $msg) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($msg['from_user_name']) . '</td>';
            echo '<td>' . htmlspecialchars($msg['to_user_name']) . '</td>';
            echo '<td>' . htmlspecialchars($msg['datetime']) . '</td>';
            echo '<td>' . htmlspecialchars($msg['timestamp']) . '</td>';
            echo '<td>' . htmlspecialchars($msg['status']) . '</td>';
            echo '<td>' . nl2br(htmlspecialchars($msg['content'])) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';

        exit;
    }

    /**
     * Exportar conversaciones a JSON
     *
     * @param array $conversations Array de conversaciones
     * @param string $filename Nombre del archivo
     */
    public function export_conversations_list_json($conversations, $filename = 'conversaciones') {
        $filename = sanitize_file_name($filename) . '_' . date('Y-m-d_H-i-s') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $export_data = array(
            'export_date' => current_time('c'), // ISO 8601
            'export_version' => UPGM_VERSION,
            'total_conversations' => count($conversations),
            'conversations' => array()
        );

        foreach ($conversations as $conv) {
            $user1 = get_userdata($conv['user_id_1']);
            $user2 = get_userdata($conv['user_id_2']);

            $export_data['conversations'][] = array(
                'user_id_1' => $conv['user_id_1'],
                'user_id_2' => $conv['user_id_2'],
                'user_1_name' => $user1 ? $user1->display_name : null,
                'user_2_name' => $user2 ? $user2->display_name : null,
                'last_message' => $conv['last_message'],
                'last_timestamp' => $conv['last_timestamp'],
                'last_message_date' => date('c', $conv['last_timestamp']),
                'status' => $conv['status'],
                'message_count' => $conv['message_count']
            );
        }

        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Exportar conversación detallada a JSON
     *
     * @param array $messages Array de mensajes
     * @param int $user_id_1
     * @param int $user_id_2
     */
    public function export_conversation_detail_json($messages, $user_id_1, $user_id_2) {
        $user1 = get_userdata($user_id_1);
        $user2 = get_userdata($user_id_2);

        $user1_name = $user1 ? $user1->display_name : 'Usuario_' . $user_id_1;
        $user2_name = $user2 ? $user2->display_name : 'Usuario_' . $user_id_2;

        $filename = sanitize_file_name('chat_' . $user1_name . '_' . $user2_name . '_' . date('Y-m-d_H-i-s') . '.json');

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $export_data = array(
            'export_date' => current_time('c'),
            'export_version' => UPGM_VERSION,
            'conversation' => array(
                'user_id_1' => $user_id_1,
                'user_id_2' => $user_id_2,
                'user_1_name' => $user1 ? $user1->display_name : null,
                'user_1_email' => $user1 ? $user1->user_email : null,
                'user_2_name' => $user2 ? $user2->display_name : null,
                'user_2_email' => $user2 ? $user2->user_email : null,
                'message_count' => count($messages),
                'first_message_date' => !empty($messages) ? date('c', $messages[0]['timestamp']) : null,
                'last_message_date' => !empty($messages) ? date('c', end($messages)['timestamp']) : null,
                'messages' => array()
            )
        );

        foreach ($messages as $msg) {
            $export_data['conversation']['messages'][] = array(
                'from_user_id' => $msg['from_user_id'],
                'to_user_id' => $msg['to_user_id'],
                'from_user_name' => $msg['from_user_name'],
                'to_user_name' => $msg['to_user_name'],
                'timestamp' => $msg['timestamp'],
                'datetime' => $msg['datetime'],
                'datetime_iso' => date('c', $msg['timestamp']),
                'mode' => $msg['mode'],
                'status' => $msg['status'],
                'content' => $msg['content']
            );
        }

        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Exportar logs de auditoría a JSON
     *
     * @param array $logs Array de logs
     */
    public function export_audit_logs_json($logs) {
        $filename = 'audit_logs_' . date('Y-m-d_H-i-s') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $export_data = array(
            'export_date' => current_time('c'),
            'export_version' => UPGM_VERSION,
            'total_records' => count($logs),
            'logs' => array()
        );

        foreach ($logs as $log) {
            $admin = get_userdata($log['admin_user_id']);
            $user1 = get_userdata($log['target_user_id_1']);
            $user2 = get_userdata($log['target_user_id_2']);

            $export_data['logs'][] = array(
                'id' => $log['id'],
                'action_type' => $log['action_type'],
                'admin_user_id' => $log['admin_user_id'],
                'admin_user_name' => $admin ? $admin->display_name : null,
                'target_user_id_1' => $log['target_user_id_1'],
                'target_user_1_name' => $user1 ? $user1->display_name : null,
                'target_user_id_2' => $log['target_user_id_2'],
                'target_user_2_name' => $user2 ? $user2->display_name : null,
                'timestamp_affected' => $log['timestamp_affected'],
                'timestamp_affected_date' => $log['timestamp_affected'] ? date('c', $log['timestamp_affected']) : null,
                'details' => $log['details'], // Ya es array por get_logs()
                'ip_address' => $log['ip_address'],
                'user_agent' => $log['user_agent'],
                'created_at' => $log['created_at'],
                'created_at_iso' => date('c', strtotime($log['created_at']))
            );
        }

        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Truncar texto
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    private function truncate_text($text, $length = 100) {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
}
