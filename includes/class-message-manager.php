<?php
/**
 * Gestor de mensajes - Lógica de negocio
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}

class UPGM_Message_Manager {

    /**
     * File handler instance
     */
    private $file_handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->file_handler = new UPGM_File_Handler();
    }

    /**
     * Obtener conversaciones con filtros aplicados
     *
     * @param array $filters Array de filtros
     * @return array
     */
    public function get_filtered_conversations($filters = array()) {
        $conversations = $this->file_handler->get_all_conversations();

        // Filtro por usuario
        if (!empty($filters['user_id'])) {
            $user_id = intval($filters['user_id']);
            $conversations = array_filter($conversations, function($conv) use ($user_id) {
                return $conv['user_id_1'] == $user_id || $conv['user_id_2'] == $user_id;
            });
        }

        // Filtro por estado
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $status = sanitize_text_field($filters['status']);
            $conversations = array_filter($conversations, function($conv) use ($status) {
                return $conv['status'] === $status;
            });
        }

        // Filtro por fecha
        if (!empty($filters['date_from'])) {
            $date_from = strtotime($filters['date_from']);
            $conversations = array_filter($conversations, function($conv) use ($date_from) {
                return $conv['last_timestamp'] >= $date_from;
            });
        }

        if (!empty($filters['date_to'])) {
            $date_to = strtotime($filters['date_to'] . ' 23:59:59');
            $conversations = array_filter($conversations, function($conv) use ($date_to) {
                return $conv['last_timestamp'] <= $date_to;
            });
        }

        // Filtro por palabra clave
        if (!empty($filters['keyword'])) {
            $keyword = sanitize_text_field($filters['keyword']);
            $filtered = array();

            foreach ($conversations as $conv) {
                $messages = $this->file_handler->get_conversation_between_users(
                    $conv['user_id_1'],
                    $conv['user_id_2']
                );

                foreach ($messages as $msg) {
                    if (stripos($msg['content'], $keyword) !== false) {
                        $filtered[] = $conv;
                        break;
                    }
                }
            }

            $conversations = $filtered;
        }

        // Ordenar por última actividad (más reciente primero)
        usort($conversations, function($a, $b) {
            return $b['last_timestamp'] - $a['last_timestamp'];
        });

        return $conversations;
    }

    /**
     * Obtener datos de conversación para vista detallada
     *
     * @param int $user_id_1
     * @param int $user_id_2
     * @return array
     */
    public function get_conversation_detail($user_id_1, $user_id_2) {
        $messages = $this->file_handler->get_conversation_between_users($user_id_1, $user_id_2);

        // Enriquecer con datos de usuarios
        foreach ($messages as &$msg) {
            $from_user = get_userdata($msg['from_user_id']);
            $to_user = get_userdata($msg['to_user_id']);

            $msg['from_user_name'] = $from_user ? $from_user->display_name : 'Usuario #' . $msg['from_user_id'];
            $msg['to_user_name'] = $to_user ? $to_user->display_name : 'Usuario #' . $msg['to_user_id'];
            $msg['datetime'] = date('Y-m-d H:i:s', $msg['timestamp']);
        }

        return $messages;
    }

    /**
     * Censurar mensaje
     *
     * @param int $user_id_1
     * @param int $user_id_2
     * @param int $timestamp
     * @return bool
     */
    public function censor_message($user_id_1, $user_id_2, $timestamp) {
        return $this->file_handler->censor_message($user_id_1, $user_id_2, $timestamp);
    }

    /**
     * Eliminar conversación
     *
     * @param int $user_id_1
     * @param int $user_id_2
     * @return bool
     */
    public function delete_conversation($user_id_1, $user_id_2) {
        return $this->file_handler->delete_conversation($user_id_1, $user_id_2);
    }

    /**
     * Obtener información de usuario
     *
     * @param int $user_id
     * @return array
     */
    public function get_user_info($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return array(
                'id' => $user_id,
                'name' => 'Usuario #' . $user_id,
                'email' => '',
                'exists' => false
            );
        }

        return array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'exists' => true
        );
    }

    /**
     * Obtener todos los usuarios que tienen conversaciones
     *
     * @return array
     */
    public function get_users_list() {
        $user_ids = $this->file_handler->get_users_with_conversations();
        $users = array();

        foreach ($user_ids as $user_id) {
            $users[] = $this->get_user_info($user_id);
        }

        // Ordenar por nombre
        usort($users, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $users;
    }
}
