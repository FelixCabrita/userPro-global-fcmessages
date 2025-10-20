<?php
/**
 * Vista de Importar/Exportar
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php _e('Importar/Exportar Conversaciones', 'userpro-global-messages'); ?></h1>

    <?php
    // Mensajes de notificación
    if (isset($_GET['message'])) {
        $message = sanitize_text_field($_GET['message']);

        if ($message === 'imported') {
            $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(__('Importación exitosa. Se importaron %d conversaciones.', 'userpro-global-messages'), $count) . '</p>';
            echo '</div>';
        } elseif ($message === 'import_error') {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . __('Error al importar el archivo. Revisa los detalles más abajo.', 'userpro-global-messages') . '</p>';

            if (isset($_GET['errors'])) {
                $errors = json_decode(urldecode($_GET['errors']), true);
                if (is_array($errors) && !empty($errors)) {
                    echo '<ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul>';
                }
            }
            echo '</div>';
        }
    }
    ?>

    <div class="upgm-import-export-container">

        <!-- Sección de Exportación -->
        <div class="upgm-card">
            <h2><?php _e('Exportar Conversaciones', 'userpro-global-messages'); ?></h2>
            <p><?php _e('Exporta todas las conversaciones del sistema en formato JSON o CSV.', 'userpro-global-messages'); ?></p>

            <div class="upgm-export-options">
                <div class="upgm-export-option">
                    <h3><?php _e('Exportar Listado Completo', 'userpro-global-messages'); ?></h3>
                    <p><?php _e('Exporta un resumen de todas las conversaciones (última interacción, estado, usuarios).', 'userpro-global-messages'); ?></p>

                    <div class="upgm-button-group">
                        <a href="<?php echo esc_url(add_query_arg(array(
                            'page' => 'userpro-global-messages',
                            'action' => 'export_list',
                            '_wpnonce' => UPGM_Security::create_nonce('upgm_export_list')
                        ), admin_url('admin.php'))); ?>" class="button button-secondary">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <?php _e('Exportar CSV', 'userpro-global-messages'); ?>
                        </a>

                        <a href="<?php echo esc_url(add_query_arg(array(
                            'page' => 'userpro-global-messages',
                            'action' => 'export_conversations_json',
                            '_wpnonce' => UPGM_Security::create_nonce('upgm_export_json')
                        ), admin_url('admin.php'))); ?>" class="button button-secondary">
                            <span class="dashicons dashicons-media-code"></span>
                            <?php _e('Exportar JSON', 'userpro-global-messages'); ?>
                        </a>
                    </div>
                </div>

                <div class="upgm-export-option">
                    <h3><?php _e('Exportar Auditoría', 'userpro-global-messages'); ?></h3>
                    <p><?php _e('Exporta el historial completo de acciones administrativas (censuras, eliminaciones).', 'userpro-global-messages'); ?></p>

                    <div class="upgm-button-group">
                        <a href="<?php echo esc_url(add_query_arg(array(
                            'page' => 'userpro-global-messages-audit',
                            'action' => 'export_audit',
                            '_wpnonce' => UPGM_Security::create_nonce('upgm_export_audit')
                        ), admin_url('admin.php'))); ?>" class="button button-secondary">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <?php _e('Exportar CSV', 'userpro-global-messages'); ?>
                        </a>

                        <a href="<?php echo esc_url(add_query_arg(array(
                            'page' => 'userpro-global-messages-audit',
                            'action' => 'export_audit_json',
                            '_wpnonce' => UPGM_Security::create_nonce('upgm_export_audit')
                        ), admin_url('admin.php'))); ?>" class="button button-secondary">
                            <span class="dashicons dashicons-media-code"></span>
                            <?php _e('Exportar JSON', 'userpro-global-messages'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="upgm-info-box">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('Nota sobre formatos:', 'userpro-global-messages'); ?></strong>
                    <ul>
                        <li><strong>CSV:</strong> <?php _e('Ideal para visualización en Excel o análisis de datos.', 'userpro-global-messages'); ?></li>
                        <li><strong>JSON:</strong> <?php _e('Recomendado para respaldos y reimportación posterior. Incluye metadatos completos.', 'userpro-global-messages'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sección de Importación -->
        <div class="upgm-card">
            <h2><?php _e('Importar Conversaciones', 'userpro-global-messages'); ?></h2>
            <p><?php _e('Importa conversaciones desde archivos JSON o CSV exportados previamente.', 'userpro-global-messages'); ?></p>

            <form method="post" enctype="multipart/form-data" class="upgm-import-form">
                <input type="hidden" name="action" value="import_file">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(UPGM_Security::create_nonce('upgm_import_file')); ?>">

                <div class="upgm-form-field">
                    <label for="import_type">
                        <strong><?php _e('Tipo de importación:', 'userpro-global-messages'); ?></strong>
                    </label>
                    <select name="import_type" id="import_type" class="regular-text">
                        <option value="conversations"><?php _e('Conversaciones', 'userpro-global-messages'); ?></option>
                        <option value="conversation_detail"><?php _e('Conversación Detallada', 'userpro-global-messages'); ?></option>
                    </select>
                    <p class="description">
                        <?php _e('Selecciona el tipo de archivo que vas a importar.', 'userpro-global-messages'); ?>
                    </p>
                </div>

                <div class="upgm-form-field">
                    <label for="import_file">
                        <strong><?php _e('Archivo:', 'userpro-global-messages'); ?></strong>
                    </label>
                    <input type="file" name="import_file" id="import_file" accept=".json,.csv" required class="regular-text">
                    <p class="description">
                        <?php _e('Formatos soportados: JSON, CSV. Tamaño máximo: 10MB.', 'userpro-global-messages'); ?>
                    </p>
                </div>

                <div class="upgm-warning-box">
                    <span class="dashicons dashicons-warning"></span>
                    <div>
                        <strong><?php _e('¡Advertencia!', 'userpro-global-messages'); ?></strong>
                        <p><?php _e('La importación verificará que los usuarios existan en el sistema. Si un usuario no existe, se omitirá esa conversación.', 'userpro-global-messages'); ?></p>
                        <p><?php _e('Se recomienda hacer un respaldo antes de importar.', 'userpro-global-messages'); ?></p>
                    </div>
                </div>

                <button type="submit" class="button button-primary button-large">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Importar Archivo', 'userpro-global-messages'); ?>
                </button>
            </form>
        </div>

        <!-- Historial de Importaciones -->
        <?php if (!empty($import_history)): ?>
        <div class="upgm-card">
            <h2><?php _e('Historial de Importaciones', 'userpro-global-messages'); ?></h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Fecha', 'userpro-global-messages'); ?></th>
                        <th><?php _e('Archivo', 'userpro-global-messages'); ?></th>
                        <th><?php _e('Formato', 'userpro-global-messages'); ?></th>
                        <th><?php _e('Tipo', 'userpro-global-messages'); ?></th>
                        <th><?php _e('Importadas', 'userpro-global-messages'); ?></th>
                        <th><?php _e('Omitidas', 'userpro-global-messages'); ?></th>
                        <th><?php _e('Admin', 'userpro-global-messages'); ?></th>
                        <th><?php _e('Estado', 'userpro-global-messages'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($import_history as $entry): ?>
                    <tr>
                        <td>
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry['created_at']))); ?>
                        </td>
                        <td>
                            <code><?php echo esc_html($entry['file_name']); ?></code>
                        </td>
                        <td>
                            <span class="upgm-badge upgm-badge-<?php echo esc_attr($entry['file_format']); ?>">
                                <?php echo esc_html(strtoupper($entry['file_format'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $type_labels = array(
                                'conversations' => __('Conversaciones', 'userpro-global-messages'),
                                'conversation_detail' => __('Detalle', 'userpro-global-messages')
                            );
                            echo esc_html(isset($type_labels[$entry['import_type']]) ? $type_labels[$entry['import_type']] : $entry['import_type']);
                            ?>
                        </td>
                        <td>
                            <strong><?php echo intval($entry['imported_count']); ?></strong>
                        </td>
                        <td>
                            <?php echo intval($entry['skipped_count']); ?>
                        </td>
                        <td>
                            <?php
                            $admin = get_userdata($entry['imported_by']);
                            echo $admin ? esc_html($admin->display_name) : __('Usuario eliminado', 'userpro-global-messages');
                            ?>
                        </td>
                        <td>
                            <?php if ($entry['status'] === 'success'): ?>
                                <span class="upgm-badge upgm-badge-success">✓ <?php _e('Exitoso', 'userpro-global-messages'); ?></span>
                            <?php else: ?>
                                <span class="upgm-badge upgm-badge-error">✗ <?php _e('Error', 'userpro-global-messages'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Documentación -->
        <div class="upgm-card">
            <h2><?php _e('Guía de Uso', 'userpro-global-messages'); ?></h2>

            <div class="upgm-documentation">
                <h3><?php _e('Exportación', 'userpro-global-messages'); ?></h3>
                <ol>
                    <li><?php _e('Selecciona el formato deseado (CSV o JSON).', 'userpro-global-messages'); ?></li>
                    <li><?php _e('El archivo se descargará automáticamente en tu navegador.', 'userpro-global-messages'); ?></li>
                    <li><?php _e('Guarda el archivo en un lugar seguro como respaldo.', 'userpro-global-messages'); ?></li>
                </ol>

                <h3><?php _e('Importación', 'userpro-global-messages'); ?></h3>
                <ol>
                    <li><?php _e('Asegúrate de tener un respaldo actualizado.', 'userpro-global-messages'); ?></li>
                    <li><?php _e('Selecciona el tipo de archivo que vas a importar.', 'userpro-global-messages'); ?></li>
                    <li><?php _e('Elige el archivo desde tu computadora.', 'userpro-global-messages'); ?></li>
                    <li><?php _e('Haz clic en "Importar Archivo" y espera a que termine el proceso.', 'userpro-global-messages'); ?></li>
                    <li><?php _e('Revisa el mensaje de confirmación para ver cuántas conversaciones se importaron.', 'userpro-global-messages'); ?></li>
                </ol>

                <h3><?php _e('Validaciones Automáticas', 'userpro-global-messages'); ?></h3>
                <ul>
                    <li><?php _e('Se verifica que el archivo tenga la estructura correcta.', 'userpro-global-messages'); ?></li>
                    <li><?php _e('Se valida que los usuarios existan en el sistema.', 'userpro-global-messages'); ?></li>
                    <li><?php _e('Se detectan duplicados automáticamente.', 'userpro-global-messages'); ?></li>
                    <li><?php _e('Se omiten conversaciones inválidas sin afectar las demás.', 'userpro-global-messages'); ?></li>
                </ul>
            </div>
        </div>

    </div>
</div>
