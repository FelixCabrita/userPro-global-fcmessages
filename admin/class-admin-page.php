<?php
/**
 * Página administrativa
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}

class UPGM_Admin_Page {

    /**
     * Message Manager instance
     */
    private $message_manager;

    /**
     * Exporter instance
     */
    private $exporter;

    /**
     * Importer instance
     */
    private $importer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->message_manager = new UPGM_Message_Manager();
        $this->exporter = new UPGM_Exporter();
        $this->importer = new UPGM_Importer();

        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    /**
     * Agregar página al menú de admin
     */
    public function add_menu_page() {
        add_menu_page(
            __('Global Messages', 'userpro-global-messages'),
            __('Global Messages', 'userpro-global-messages'),
            'manage_userpro_global_messages',
            'userpro-global-messages',
            array($this, 'render_list_page'),
            'dashicons-email',
            30
        );

        add_submenu_page(
            'userpro-global-messages',
            __('Ver Conversación', 'userpro-global-messages'),
            null,
            'manage_userpro_global_messages',
            'userpro-global-messages-detail',
            array($this, 'render_detail_page')
        );

        add_submenu_page(
            'userpro-global-messages',
            __('Historial de Auditoría', 'userpro-global-messages'),
            __('Historial de Auditoría', 'userpro-global-messages'),
            'manage_userpro_global_messages',
            'userpro-global-messages-audit',
            array($this, 'render_audit_page')
        );

        add_submenu_page(
            'userpro-global-messages',
            __('Importar/Exportar', 'userpro-global-messages'),
            __('Importar/Exportar', 'userpro-global-messages'),
            'manage_userpro_global_messages',
            'userpro-global-messages-import-export',
            array($this, 'render_import_export_page')
        );
    }

    /**
     * Encolar scripts y estilos
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'userpro-global-messages') === false) {
            return;
        }

        wp_enqueue_style(
            'upgm-admin-styles',
            UPGM_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            UPGM_VERSION
        );

        wp_enqueue_script(
            'upgm-admin-scripts',
            UPGM_PLUGIN_URL . 'assets/js/admin-scripts.js',
            array('jquery'),
            UPGM_VERSION,
            true
        );

        wp_localize_script('upgm-admin-scripts', 'upgmData', array(
            'confirmCensor' => __('¿Estás seguro de que deseas censurar este mensaje? Esta acción no se puede deshacer.', 'userpro-global-messages'),
            'confirmDelete' => __('¿Estás seguro de que deseas eliminar esta conversación completa? Esta acción no se puede deshacer y eliminará los archivos de ambos usuarios.', 'userpro-global-messages'),
        ));
    }

    /**
     * Manejar acciones
     */
    public function handle_actions() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'userpro-global-messages') === false) {
            return;
        }

        // Censurar mensaje
        if (isset($_GET['action']) && $_GET['action'] === 'censor_message') {
            UPGM_Security::verify_permission_and_nonce('upgm_censor_message');

            $user_id_1 = isset($_GET['user_id_1']) ? intval($_GET['user_id_1']) : 0;
            $user_id_2 = isset($_GET['user_id_2']) ? intval($_GET['user_id_2']) : 0;
            $timestamp = isset($_GET['timestamp']) ? intval($_GET['timestamp']) : 0;

            if (UPGM_Security::validate_user_ids($user_id_1, $user_id_2) && $timestamp > 0) {
                $result = $this->message_manager->censor_message($user_id_1, $user_id_2, $timestamp);

                // Log en archivo (debug)
                UPGM_Security::log_action('censor_message', array(
                    'user_id_1' => $user_id_1,
                    'user_id_2' => $user_id_2,
                    'timestamp' => $timestamp,
                    'result' => $result
                ));

                // Log en base de datos (auditoría)
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

                $redirect_url = remove_query_arg(array('action', '_wpnonce', 'timestamp'));
                $redirect_url = add_query_arg('message', $result ? 'censored' : 'error', $redirect_url);
                wp_redirect($redirect_url);
                exit;
            }
        }

        // Eliminar conversación
        if (isset($_GET['action']) && $_GET['action'] === 'delete_conversation') {
            UPGM_Security::verify_permission_and_nonce('upgm_delete_conversation');

            $user_id_1 = isset($_GET['user_id_1']) ? intval($_GET['user_id_1']) : 0;
            $user_id_2 = isset($_GET['user_id_2']) ? intval($_GET['user_id_2']) : 0;

            if (UPGM_Security::validate_user_ids($user_id_1, $user_id_2)) {
                $result = $this->message_manager->delete_conversation($user_id_1, $user_id_2);

                // Log en archivo (debug)
                UPGM_Security::log_action('delete_conversation', array(
                    'user_id_1' => $user_id_1,
                    'user_id_2' => $user_id_2,
                    'result' => $result
                ));

                // Log en base de datos (auditoría)
                if ($result) {
                    $user1 = get_userdata($user_id_1);
                    $user2 = get_userdata($user_id_2);

                    UPGM_Audit_Log::log(
                        UPGM_Audit_Log::ACTION_DELETE,
                        $user_id_1,
                        $user_id_2,
                        array(
                            'user1_name' => $user1 ? $user1->display_name : 'Usuario #' . $user_id_1,
                            'user2_name' => $user2 ? $user2->display_name : 'Usuario #' . $user_id_2
                        )
                    );
                }

                wp_redirect(add_query_arg(array(
                    'page' => 'userpro-global-messages',
                    'message' => $result ? 'deleted' : 'error'
                ), admin_url('admin.php')));
                exit;
            }
        }

        // Exportar listado
        if (isset($_GET['action']) && $_GET['action'] === 'export_list') {
            UPGM_Security::verify_permission_and_nonce('upgm_export_list');

            $filters = UPGM_Security::sanitize_filters($_GET);
            $conversations = $this->message_manager->get_filtered_conversations($filters);

            UPGM_Security::log_action('export_list', array('count' => count($conversations)));

            $this->exporter->export_conversations_list($conversations);
        }

        // Exportar conversación detallada
        if (isset($_GET['action']) && $_GET['action'] === 'export_detail') {
            UPGM_Security::verify_permission_and_nonce('upgm_export_detail');

            $user_id_1 = isset($_GET['user_id_1']) ? intval($_GET['user_id_1']) : 0;
            $user_id_2 = isset($_GET['user_id_2']) ? intval($_GET['user_id_2']) : 0;
            $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'csv';

            if (UPGM_Security::validate_user_ids($user_id_1, $user_id_2)) {
                $messages = $this->message_manager->get_conversation_detail($user_id_1, $user_id_2);

                UPGM_Security::log_action('export_detail', array(
                    'user_id_1' => $user_id_1,
                    'user_id_2' => $user_id_2,
                    'format' => $format,
                    'count' => count($messages)
                ));

                if ($format === 'excel') {
                    $this->exporter->export_conversation_excel($messages, $user_id_1, $user_id_2);
                } else {
                    $this->exporter->export_conversation_detail($messages, $user_id_1, $user_id_2);
                }
            }
        }

        // Exportar auditoría
        if (isset($_GET['action']) && $_GET['action'] === 'export_audit') {
            UPGM_Security::verify_permission_and_nonce('upgm_export_audit');

            $filters = UPGM_Security::sanitize_filters($_GET);
            UPGM_Audit_Log::export_to_csv($filters);
        }

        // Exportar conversaciones a JSON
        if (isset($_GET['action']) && $_GET['action'] === 'export_conversations_json') {
            UPGM_Security::verify_permission_and_nonce('upgm_export_json');

            $filters = UPGM_Security::sanitize_filters($_GET);
            $conversations = $this->message_manager->get_filtered_conversations($filters);

            UPGM_Security::log_action('export_conversations_json', array('count' => count($conversations)));

            $this->exporter->export_conversations_list_json($conversations);
        }

        // Exportar conversación detallada a JSON
        if (isset($_GET['action']) && $_GET['action'] === 'export_conversation_json') {
            UPGM_Security::verify_permission_and_nonce('upgm_export_json');

            $user_id_1 = isset($_GET['user_id_1']) ? intval($_GET['user_id_1']) : 0;
            $user_id_2 = isset($_GET['user_id_2']) ? intval($_GET['user_id_2']) : 0;

            if (UPGM_Security::validate_user_ids($user_id_1, $user_id_2)) {
                $messages = $this->message_manager->get_conversation_detail($user_id_1, $user_id_2);

                UPGM_Security::log_action('export_conversation_json', array(
                    'user_id_1' => $user_id_1,
                    'user_id_2' => $user_id_2,
                    'count' => count($messages)
                ));

                $this->exporter->export_conversation_detail_json($messages, $user_id_1, $user_id_2);
            }
        }

        // Exportar auditoría a JSON
        if (isset($_GET['action']) && $_GET['action'] === 'export_audit_json') {
            UPGM_Security::verify_permission_and_nonce('upgm_export_audit');

            $filters = UPGM_Security::sanitize_filters($_GET);
            $logs_data = UPGM_Audit_Log::get_logs($filters);

            UPGM_Security::log_action('export_audit_json', array('count' => $logs_data['total']));

            $this->exporter->export_audit_logs_json($logs_data['items']);
        }

        // Importar archivo
        if (isset($_POST['action']) && $_POST['action'] === 'import_file') {
            UPGM_Security::verify_permission_and_nonce('upgm_import_file');

            if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
                $file_path = $_FILES['import_file']['tmp_name'];
                $file_name = sanitize_file_name($_FILES['import_file']['name']);
                $file_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : 'conversations';

                // Determinar formato basado en extensión
                $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $format = ($extension === 'json') ? 'json' : 'csv';

                $result = $this->importer->import_file($file_path, $format, $file_type);

                UPGM_Security::log_action('import_file', array(
                    'file_name' => $file_name,
                    'format' => $format,
                    'type' => $file_type,
                    'success' => $result['success'],
                    'imported' => isset($result['imported']) ? $result['imported'] : 0
                ));

                if ($result['success']) {
                    wp_redirect(add_query_arg(array(
                        'page' => 'userpro-global-messages-import-export',
                        'message' => 'imported',
                        'count' => $result['imported']
                    ), admin_url('admin.php')));
                } else {
                    wp_redirect(add_query_arg(array(
                        'page' => 'userpro-global-messages-import-export',
                        'message' => 'import_error',
                        'errors' => urlencode(json_encode($result['errors']))
                    ), admin_url('admin.php')));
                }
                exit;
            }
        }
    }

    /**
     * Renderizar página de listado
     */
    public function render_list_page() {
        if (!UPGM_Security::current_user_can_manage()) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'userpro-global-messages'));
        }

        // Obtener filtros
        $filters = UPGM_Security::sanitize_filters($_GET);

        // Obtener conversaciones filtradas
        $conversations = $this->message_manager->get_filtered_conversations($filters);

        // Obtener lista de usuarios para el filtro
        $users = $this->message_manager->get_users_list();

        // Paginación
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $total_items = count($conversations);
        $total_pages = ceil($total_items / $per_page);
        $offset = ($current_page - 1) * $per_page;
        $conversations_page = array_slice($conversations, $offset, $per_page);

        // Incluir vista
        include UPGM_PLUGIN_DIR . 'admin/views/list-view.php';
    }

    /**
     * Renderizar página de detalle
     */
    public function render_detail_page() {
        if (!UPGM_Security::current_user_can_manage()) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'userpro-global-messages'));
        }

        $user_id_1 = isset($_GET['user_id_1']) ? intval($_GET['user_id_1']) : 0;
        $user_id_2 = isset($_GET['user_id_2']) ? intval($_GET['user_id_2']) : 0;

        if (!UPGM_Security::validate_user_ids($user_id_1, $user_id_2)) {
            wp_die(__('IDs de usuario inválidos.', 'userpro-global-messages'));
        }

        // Obtener conversación
        $messages = $this->message_manager->get_conversation_detail($user_id_1, $user_id_2);

        // Obtener información de usuarios
        $user1_info = $this->message_manager->get_user_info($user_id_1);
        $user2_info = $this->message_manager->get_user_info($user_id_2);

        // Incluir vista
        include UPGM_PLUGIN_DIR . 'admin/views/detail-view.php';
    }

    /**
     * Renderizar página de auditoría
     */
    public function render_audit_page() {
        if (!UPGM_Security::current_user_can_manage()) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'userpro-global-messages'));
        }

        // Sanitizar filtros
        $filters = array();

        if (isset($_GET['action_type']) && !empty($_GET['action_type'])) {
            $filters['action_type'] = sanitize_text_field($_GET['action_type']);
        }

        if (isset($_GET['admin_user_id']) && !empty($_GET['admin_user_id'])) {
            $filters['admin_user_id'] = intval($_GET['admin_user_id']);
        }

        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }

        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }

        if (isset($_GET['target_user_id']) && !empty($_GET['target_user_id'])) {
            $filters['target_user_id'] = intval($_GET['target_user_id']);
        }

        // Paginación
        $per_page = 50;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $filters['per_page'] = $per_page;
        $filters['page'] = $current_page;

        // Obtener logs
        $logs_data = UPGM_Audit_Log::get_logs($filters);
        $logs = $logs_data['items'];
        $total_items = $logs_data['total'];
        $total_pages = ceil($total_items / $per_page);

        // Obtener estadísticas
        $statistics = UPGM_Audit_Log::get_statistics();

        // Obtener lista de admins activos
        $active_admins = UPGM_Audit_Log::get_active_admins();

        // Obtener lista de usuarios
        $users = $this->message_manager->get_users_list();

        // Incluir vista
        include UPGM_PLUGIN_DIR . 'admin/views/audit-view.php';
    }

    /**
     * Renderizar página de importar/exportar
     */
    public function render_import_export_page() {
        if (!UPGM_Security::current_user_can_manage()) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'userpro-global-messages'));
        }

        // Obtener historial de importaciones
        $import_history = $this->importer->get_import_history(20);

        // Incluir vista
        include UPGM_PLUGIN_DIR . 'admin/views/import-export-view.php';
    }
}
