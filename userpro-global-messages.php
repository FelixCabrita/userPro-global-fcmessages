<?php
/**
 * Plugin Name: UserPro Global Messages
 * Plugin URI: https://example.com/userpro-global-messages
 * Description: Administración global de mensajes de UserPro - visualización, filtrado, censura, exportación/importación JSON/CSV y auditoría completa
 * Version: 1.2.0
 * Author: Tu Nombre
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: userpro-global-messages
 * Domain Path: /languages
 */

// Si este archivo es llamado directamente, abortar
if (!defined('WPINC')) {
    die;
}

// Definir constantes del plugin
define('UPGM_VERSION', '1.2.0');
define('UPGM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UPGM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UPGM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class UserPro_Global_Messages {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        require_once UPGM_PLUGIN_DIR . 'includes/class-file-handler.php';
        require_once UPGM_PLUGIN_DIR . 'includes/class-message-manager.php';
        require_once UPGM_PLUGIN_DIR . 'includes/class-exporter.php';
        require_once UPGM_PLUGIN_DIR . 'includes/class-importer.php';
        require_once UPGM_PLUGIN_DIR . 'includes/class-security.php';
        require_once UPGM_PLUGIN_DIR . 'includes/class-audit-log.php';
        require_once UPGM_PLUGIN_DIR . 'admin/class-admin-page.php';
    }

    /**
     * Definir hooks administrativos
     */
    private function define_admin_hooks() {
        if (is_admin()) {
            $admin_page = new UPGM_Admin_Page();
        }
    }
}

/**
 * Activación del plugin
 */
function upgm_activate_plugin() {
    // Cargar clases necesarias
    require_once UPGM_PLUGIN_DIR . 'includes/class-audit-log.php';
    require_once UPGM_PLUGIN_DIR . 'includes/class-importer.php';

    // Crear tabla de auditoría
    UPGM_Audit_Log::create_table();

    // Crear tabla de historial de importaciones
    UPGM_Importer::create_import_history_table();

    // Crear capability personalizada
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_userpro_global_messages');
    }

    // Guardar versión actual para futuras actualizaciones
    update_option('upgm_db_version', UPGM_VERSION);

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'upgm_activate_plugin');

/**
 * Desactivación del plugin
 */
function upgm_deactivate_plugin() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'upgm_deactivate_plugin');

/**
 * Inicializar el plugin
 */
function upgm_init() {
    return UserPro_Global_Messages::get_instance();
}

// Iniciar el plugin
upgm_init();
