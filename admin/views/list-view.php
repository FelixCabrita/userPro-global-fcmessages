<?php
/**
 * Vista de listado de conversaciones
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap upgm-wrap">
    <h1 class="wp-heading-inline"><?php _e('Global Messages - Todas las Conversaciones', 'userpro-global-messages'); ?></h1>

    <?php
    // Mostrar mensajes
    if (isset($_GET['message'])) {
        $message_type = sanitize_text_field($_GET['message']);
        $messages = array(
            'censored' => array('type' => 'success', 'text' => __('Mensaje censurado exitosamente.', 'userpro-global-messages')),
            'deleted' => array('type' => 'success', 'text' => __('Conversación eliminada exitosamente.', 'userpro-global-messages')),
            'error' => array('type' => 'error', 'text' => __('Ocurrió un error al procesar la acción.', 'userpro-global-messages')),
        );

        if (isset($messages[$message_type])) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($messages[$message_type]['type']),
                esc_html($messages[$message_type]['text'])
            );
        }
    }
    ?>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="upgm-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="userpro-global-messages">

            <div class="upgm-filters-row">
                <div class="upgm-filter-item">
                    <label for="filter-user"><?php _e('Usuario:', 'userpro-global-messages'); ?></label>
                    <select name="user_id" id="filter-user">
                        <option value=""><?php _e('Todos los usuarios', 'userpro-global-messages'); ?></option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo esc_attr($user['id']); ?>" <?php selected(isset($filters['user_id']) ? $filters['user_id'] : '', $user['id']); ?>>
                                <?php echo esc_html($user['name']); ?> (ID: <?php echo esc_html($user['id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="upgm-filter-item">
                    <label for="filter-status"><?php _e('Estado:', 'userpro-global-messages'); ?></label>
                    <select name="status" id="filter-status">
                        <option value="all" <?php selected(isset($filters['status']) ? $filters['status'] : 'all', 'all'); ?>><?php _e('Todos', 'userpro-global-messages'); ?></option>
                        <option value="unread" <?php selected(isset($filters['status']) ? $filters['status'] : '', 'unread'); ?>><?php _e('No leídos', 'userpro-global-messages'); ?></option>
                        <option value="archive" <?php selected(isset($filters['status']) ? $filters['status'] : '', 'archive'); ?>><?php _e('Archivados', 'userpro-global-messages'); ?></option>
                    </select>
                </div>

                <div class="upgm-filter-item">
                    <label for="filter-date-from"><?php _e('Desde:', 'userpro-global-messages'); ?></label>
                    <input type="date" name="date_from" id="filter-date-from" value="<?php echo isset($filters['date_from']) ? esc_attr($filters['date_from']) : ''; ?>">
                </div>

                <div class="upgm-filter-item">
                    <label for="filter-date-to"><?php _e('Hasta:', 'userpro-global-messages'); ?></label>
                    <input type="date" name="date_to" id="filter-date-to" value="<?php echo isset($filters['date_to']) ? esc_attr($filters['date_to']) : ''; ?>">
                </div>

                <div class="upgm-filter-item">
                    <label for="filter-keyword"><?php _e('Palabra clave:', 'userpro-global-messages'); ?></label>
                    <input type="text" name="keyword" id="filter-keyword" value="<?php echo isset($filters['keyword']) ? esc_attr($filters['keyword']) : ''; ?>" placeholder="<?php _e('Buscar...', 'userpro-global-messages'); ?>">
                </div>

                <div class="upgm-filter-item">
                    <button type="submit" class="button button-primary"><?php _e('Filtrar', 'userpro-global-messages'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=userpro-global-messages')); ?>" class="button"><?php _e('Limpiar', 'userpro-global-messages'); ?></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Exportar -->
    <?php if (!empty($conversations)): ?>
        <div class="upgm-actions">
            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array_merge($filters, array('action' => 'export_list')), admin_url('admin.php?page=userpro-global-messages')), 'upgm_export_list')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-download"></span> <?php _e('Exportar resultados a CSV', 'userpro-global-messages'); ?>
            </a>
            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array_merge($filters, array('action' => 'export_conversations_json')), admin_url('admin.php?page=userpro-global-messages')), 'upgm_export_json')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-media-code"></span> <?php _e('Exportar resultados a JSON', 'userpro-global-messages'); ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Tabla de conversaciones -->
    <table class="wp-list-table widefat fixed striped upgm-table">
        <thead>
            <tr>
                <th><?php _e('Usuarios', 'userpro-global-messages'); ?></th>
                <th><?php _e('Último Mensaje', 'userpro-global-messages'); ?></th>
                <th><?php _e('Fecha', 'userpro-global-messages'); ?></th>
                <th><?php _e('Estado', 'userpro-global-messages'); ?></th>
                <th><?php _e('Mensajes', 'userpro-global-messages'); ?></th>
                <th><?php _e('Acciones', 'userpro-global-messages'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($conversations_page)): ?>
                <tr>
                    <td colspan="6" class="upgm-no-results">
                        <?php _e('No se encontraron conversaciones.', 'userpro-global-messages'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($conversations_page as $conv): ?>
                    <?php
                    $user1 = get_userdata($conv['user_id_1']);
                    $user2 = get_userdata($conv['user_id_2']);
                    $user1_name = $user1 ? $user1->display_name : 'Usuario #' . $conv['user_id_1'];
                    $user2_name = $user2 ? $user2->display_name : 'Usuario #' . $conv['user_id_2'];
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($user1_name); ?></strong> ↔ <strong><?php echo esc_html($user2_name); ?></strong>
                            <br>
                            <small class="upgm-user-ids">(ID: <?php echo esc_html($conv['user_id_1']); ?> ↔ ID: <?php echo esc_html($conv['user_id_2']); ?>)</small>
                        </td>
                        <td class="upgm-message-preview">
                            <?php echo esc_html(wp_trim_words($conv['last_message'], 15)); ?>
                        </td>
                        <td>
                            <?php echo esc_html(date_i18n('Y-m-d H:i', $conv['last_timestamp'])); ?>
                        </td>
                        <td>
                            <span class="upgm-status upgm-status-<?php echo esc_attr($conv['status']); ?>">
                                <?php echo $conv['status'] === 'unread' ? __('No leído', 'userpro-global-messages') : __('Archivado', 'userpro-global-messages'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="upgm-count"><?php echo esc_html($conv['message_count']); ?></span>
                        </td>
                        <td class="upgm-actions-cell">
                            <a href="<?php echo esc_url(add_query_arg(array('page' => 'userpro-global-messages-detail', 'user_id_1' => $conv['user_id_1'], 'user_id_2' => $conv['user_id_2']), admin_url('admin.php'))); ?>" class="button button-small">
                                <?php _e('Ver Chat', 'userpro-global-messages'); ?>
                            </a>
                            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'delete_conversation', 'user_id_1' => $conv['user_id_1'], 'user_id_2' => $conv['user_id_2'])), 'upgm_delete_conversation')); ?>" class="button button-small upgm-delete-btn">
                                <?php _e('Vaciar', 'userpro-global-messages'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="upgm-stats">
        <p><?php printf(__('Mostrando %d de %d conversaciones', 'userpro-global-messages'), count($conversations_page), $total_items); ?></p>
    </div>
</div>
