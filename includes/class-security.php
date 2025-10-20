<?php
/**
 * Seguridad y validaciones
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}

class UPGM_Security {

    /**
     * Verificar si el usuario actual tiene permisos
     *
     * @return bool
     */
    public static function current_user_can_manage() {
        return current_user_can('manage_userpro_global_messages');
    }

    /**
     * Verificar nonce
     *
     * @param string $action
     * @return bool
     */
    public static function verify_nonce($action) {
        if (!isset($_REQUEST['_wpnonce'])) {
            return false;
        }
        return wp_verify_nonce($_REQUEST['_wpnonce'], $action);
    }

    /**
     * Generar nonce
     *
     * @param string $action
     * @return string
     */
    public static function create_nonce($action) {
        return wp_create_nonce($action);
    }

    /**
     * Verificar permisos y nonce
     *
     * @param string $action
     * @return bool
     */
    public static function verify_permission_and_nonce($action) {
        if (!self::current_user_can_manage()) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'userpro-global-messages'));
        }

        if (!self::verify_nonce($action)) {
            wp_die(__('Token de seguridad inválido.', 'userpro-global-messages'));
        }

        return true;
    }

    /**
     * Sanitizar filtros de entrada
     *
     * @param array $input
     * @return array
     */
    public static function sanitize_filters($input) {
        $sanitized = array();

        if (isset($input['user_id'])) {
            $sanitized['user_id'] = intval($input['user_id']);
        }

        if (isset($input['status'])) {
            $sanitized['status'] = sanitize_text_field($input['status']);
        }

        if (isset($input['date_from'])) {
            $sanitized['date_from'] = sanitize_text_field($input['date_from']);
        }

        if (isset($input['date_to'])) {
            $sanitized['date_to'] = sanitize_text_field($input['date_to']);
        }

        if (isset($input['keyword'])) {
            $sanitized['keyword'] = sanitize_text_field($input['keyword']);
        }

        return $sanitized;
    }

    /**
     * Validar IDs de usuarios
     *
     * @param int $user_id_1
     * @param int $user_id_2
     * @return bool
     */
    public static function validate_user_ids($user_id_1, $user_id_2) {
        $user_id_1 = intval($user_id_1);
        $user_id_2 = intval($user_id_2);

        if ($user_id_1 <= 0 || $user_id_2 <= 0) {
            return false;
        }

        if ($user_id_1 === $user_id_2) {
            return false;
        }

        return true;
    }

    /**
     * Logging de acciones
     *
     * @param string $action
     * @param array $data
     */
    public static function log_action($action, $data = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $user = wp_get_current_user();
        $log_entry = sprintf(
            '[%s] UserPro Global Messages - %s by %s (ID: %d) - Data: %s',
            current_time('Y-m-d H:i:s'),
            $action,
            $user->user_login,
            $user->ID,
            json_encode($data)
        );

        error_log($log_entry);
    }
}
