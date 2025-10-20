<?php
/**
 * Vista de Historial de Auditoría
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap upgm-wrap upgm-audit">
    <h1 class="wp-heading-inline"><?php _e('Historial de Auditoría', 'userpro-global-messages'); ?></h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=userpro-global-messages')); ?>" class="page-title-action">
        &larr; <?php _e('Volver al listado', 'userpro-global-messages'); ?>
    </a>

    <hr class="wp-header-end">

    <!-- Estadísticas Rápidas -->
    <div class="upgm-stats-cards">
        <div class="upgm-stat-card">
            <div class="upgm-stat-icon dashicons dashicons-chart-bar"></div>
            <div class="upgm-stat-content">
                <div class="upgm-stat-value"><?php echo esc_html($statistics['total_actions']); ?></div>
                <div class="upgm-stat-label"><?php _e('Total Acciones', 'userpro-global-messages'); ?></div>
            </div>
        </div>

        <div class="upgm-stat-card">
            <div class="upgm-stat-icon dashicons dashicons-calendar-alt"></div>
            <div class="upgm-stat-content">
                <div class="upgm-stat-value"><?php echo esc_html($statistics['today']); ?></div>
                <div class="upgm-stat-label"><?php _e('Hoy', 'userpro-global-messages'); ?></div>
            </div>
        </div>

        <div class="upgm-stat-card">
            <div class="upgm-stat-icon dashicons dashicons-clock"></div>
            <div class="upgm-stat-content">
                <div class="upgm-stat-value"><?php echo esc_html($statistics['this_week']); ?></div>
                <div class="upgm-stat-label"><?php _e('Esta Semana', 'userpro-global-messages'); ?></div>
            </div>
        </div>

        <div class="upgm-stat-card">
            <div class="upgm-stat-icon dashicons dashicons-calendar"></div>
            <div class="upgm-stat-content">
                <div class="upgm-stat-value"><?php echo esc_html($statistics['this_month']); ?></div>
                <div class="upgm-stat-label"><?php _e('Este Mes', 'userpro-global-messages'); ?></div>
            </div>
        </div>
    </div>

    <!-- Desglose por Tipo de Acción -->
    <?php if (!empty($statistics['by_type'])): ?>
    <div class="upgm-stats-breakdown">
        <h3><?php _e('Desglose por Tipo de Acción', 'userpro-global-messages'); ?></h3>
        <div class="upgm-breakdown-items">
            <?php foreach ($statistics['by_type'] as $action_type => $count): ?>
                <div class="upgm-breakdown-item upgm-action-<?php echo esc_attr(UPGM_Audit_Log::get_action_class($action_type)); ?>">
                    <span class="upgm-breakdown-label"><?php echo esc_html(UPGM_Audit_Log::get_action_label($action_type)); ?></span>
                    <span class="upgm-breakdown-count"><?php echo esc_html($count); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="upgm-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="userpro-global-messages-audit">

            <div class="upgm-filters-row">
                <div class="upgm-filter-item">
                    <label for="filter-action-type"><?php _e('Tipo de Acción:', 'userpro-global-messages'); ?></label>
                    <select name="action_type" id="filter-action-type">
                        <option value=""><?php _e('Todas las acciones', 'userpro-global-messages'); ?></option>
                        <option value="<?php echo esc_attr(UPGM_Audit_Log::ACTION_CENSOR); ?>" <?php selected(isset($filters['action_type']) ? $filters['action_type'] : '', UPGM_Audit_Log::ACTION_CENSOR); ?>>
                            <?php _e('Mensaje Censurado', 'userpro-global-messages'); ?>
                        </option>
                        <option value="<?php echo esc_attr(UPGM_Audit_Log::ACTION_DELETE); ?>" <?php selected(isset($filters['action_type']) ? $filters['action_type'] : '', UPGM_Audit_Log::ACTION_DELETE); ?>>
                            <?php _e('Conversación Eliminada', 'userpro-global-messages'); ?>
                        </option>
                    </select>
                </div>

                <div class="upgm-filter-item">
                    <label for="filter-admin"><?php _e('Administrador:', 'userpro-global-messages'); ?></label>
                    <select name="admin_user_id" id="filter-admin">
                        <option value=""><?php _e('Todos los admins', 'userpro-global-messages'); ?></option>
                        <?php foreach ($active_admins as $admin): ?>
                            <option value="<?php echo esc_attr($admin['id']); ?>" <?php selected(isset($filters['admin_user_id']) ? $filters['admin_user_id'] : '', $admin['id']); ?>>
                                <?php echo esc_html($admin['name']); ?> (ID: <?php echo esc_html($admin['id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="upgm-filter-item">
                    <label for="filter-target-user"><?php _e('Usuario Afectado:', 'userpro-global-messages'); ?></label>
                    <select name="target_user_id" id="filter-target-user">
                        <option value=""><?php _e('Todos los usuarios', 'userpro-global-messages'); ?></option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo esc_attr($user['id']); ?>" <?php selected(isset($filters['target_user_id']) ? $filters['target_user_id'] : '', $user['id']); ?>>
                                <?php echo esc_html($user['name']); ?> (ID: <?php echo esc_html($user['id']); ?>)
                            </option>
                        <?php endforeach; ?>
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
                    <button type="submit" class="button button-primary"><?php _e('Filtrar', 'userpro-global-messages'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=userpro-global-messages-audit')); ?>" class="button"><?php _e('Limpiar', 'userpro-global-messages'); ?></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Exportar -->
    <?php if (!empty($logs)): ?>
        <div class="upgm-actions">
            <?php
            $export_url = add_query_arg(
                array_merge($filters, array('action' => 'export_audit')),
                admin_url('admin.php?page=userpro-global-messages-audit')
            );
            $export_url = wp_nonce_url($export_url, 'upgm_export_audit');

            $export_json_url = add_query_arg(
                array_merge($filters, array('action' => 'export_audit_json')),
                admin_url('admin.php?page=userpro-global-messages-audit')
            );
            $export_json_url = wp_nonce_url($export_json_url, 'upgm_export_audit');
            ?>
            <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">
                <span class="dashicons dashicons-download"></span> <?php _e('Exportar Auditoría a CSV', 'userpro-global-messages'); ?>
            </a>
            <a href="<?php echo esc_url($export_json_url); ?>" class="button button-secondary">
                <span class="dashicons dashicons-media-code"></span> <?php _e('Exportar Auditoría a JSON', 'userpro-global-messages'); ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Tabla de Logs -->
    <table class="wp-list-table widefat fixed striped upgm-table upgm-audit-table">
        <thead>
            <tr>
                <th class="upgm-col-id"><?php _e('ID', 'userpro-global-messages'); ?></th>
                <th class="upgm-col-date"><?php _e('Fecha/Hora', 'userpro-global-messages'); ?></th>
                <th class="upgm-col-action"><?php _e('Acción', 'userpro-global-messages'); ?></th>
                <th class="upgm-col-admin"><?php _e('Admin', 'userpro-global-messages'); ?></th>
                <th class="upgm-col-users"><?php _e('Usuarios Afectados', 'userpro-global-messages'); ?></th>
                <th class="upgm-col-ip"><?php _e('IP', 'userpro-global-messages'); ?></th>
                <th class="upgm-col-details"><?php _e('Detalles', 'userpro-global-messages'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7" class="upgm-no-results">
                        <?php _e('No se encontraron registros de auditoría.', 'userpro-global-messages'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $admin = get_userdata($log['admin_user_id']);
                    $user1 = get_userdata($log['target_user_id_1']);
                    $user2 = get_userdata($log['target_user_id_2']);
                    ?>
                    <tr class="upgm-audit-row">
                        <td class="upgm-col-id">
                            <strong>#<?php echo esc_html($log['id']); ?></strong>
                        </td>
                        <td class="upgm-col-date">
                            <div class="upgm-date-full"><?php echo esc_html(date_i18n('d/m/Y H:i:s', strtotime($log['created_at']))); ?></div>
                            <small class="upgm-date-relative"><?php echo esc_html(human_time_diff(strtotime($log['created_at']), current_time('timestamp'))); ?> <?php _e('atrás', 'userpro-global-messages'); ?></small>
                        </td>
                        <td class="upgm-col-action">
                            <span class="upgm-action-badge upgm-action-<?php echo esc_attr(UPGM_Audit_Log::get_action_class($log['action_type'])); ?>">
                                <?php echo esc_html(UPGM_Audit_Log::get_action_label($log['action_type'])); ?>
                            </span>
                        </td>
                        <td class="upgm-col-admin">
                            <strong><?php echo $admin ? esc_html($admin->display_name) : __('Usuario desconocido', 'userpro-global-messages'); ?></strong>
                            <br>
                            <small class="upgm-user-id">ID: <?php echo esc_html($log['admin_user_id']); ?></small>
                        </td>
                        <td class="upgm-col-users">
                            <div class="upgm-affected-users">
                                <span><?php echo $user1 ? esc_html($user1->display_name) : 'Usuario #' . $log['target_user_id_1']; ?></span>
                                <span class="upgm-separator">↔</span>
                                <span><?php echo $user2 ? esc_html($user2->display_name) : 'Usuario #' . $log['target_user_id_2']; ?></span>
                            </div>
                            <small class="upgm-user-ids">
                                (ID: <?php echo esc_html($log['target_user_id_1']); ?> ↔ <?php echo esc_html($log['target_user_id_2']); ?>)
                            </small>
                        </td>
                        <td class="upgm-col-ip">
                            <code><?php echo esc_html($log['ip_address']); ?></code>
                        </td>
                        <td class="upgm-col-details">
                            <button class="button button-small upgm-toggle-details" data-log-id="<?php echo esc_attr($log['id']); ?>">
                                <span class="dashicons dashicons-visibility"></span> <?php _e('Ver', 'userpro-global-messages'); ?>
                            </button>
                            <div class="upgm-details-content" id="upgm-details-<?php echo esc_attr($log['id']); ?>" style="display: none;">
                                <div class="upgm-details-inner">
                                    <?php if ($log['timestamp_affected']): ?>
                                        <div class="upgm-detail-item">
                                            <strong><?php _e('Timestamp del Mensaje:', 'userpro-global-messages'); ?></strong>
                                            <span><?php echo esc_html(date('Y-m-d H:i:s', $log['timestamp_affected'])); ?> (<?php echo esc_html($log['timestamp_affected']); ?>)</span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($log['details'])): ?>
                                        <div class="upgm-detail-item">
                                            <strong><?php _e('Información Adicional:', 'userpro-global-messages'); ?></strong>
                                            <pre class="upgm-details-json"><?php echo esc_html(json_encode($log['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                        </div>
                                    <?php endif; ?>

                                    <div class="upgm-detail-item">
                                        <strong><?php _e('User Agent:', 'userpro-global-messages'); ?></strong>
                                        <span class="upgm-user-agent"><?php echo esc_html($log['user_agent']); ?></span>
                                    </div>
                                </div>
                            </div>
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
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page
                ));

                if ($page_links) {
                    echo $page_links;
                }
                ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="upgm-stats">
        <p><?php printf(__('Mostrando %d de %d registros', 'userpro-global-messages'), count($logs), $total_items); ?></p>
    </div>

    <!-- Información adicional -->
    <div class="upgm-info-box">
        <h4><?php _e('Información sobre el Historial de Auditoría:', 'userpro-global-messages'); ?></h4>
        <ul>
            <li><?php _e('Todos los registros son permanentes y no pueden ser eliminados desde la interfaz.', 'userpro-global-messages'); ?></li>
            <li><?php _e('Se registra automáticamente cada acción destructiva (censura y vaciado de chats).', 'userpro-global-messages'); ?></li>
            <li><?php _e('La información incluye: quién realizó la acción, cuándo, desde qué IP y qué usuarios fueron afectados.', 'userpro-global-messages'); ?></li>
            <li><?php _e('Puedes exportar el historial completo o filtrado a CSV para auditorías externas.', 'userpro-global-messages'); ?></li>
        </ul>
    </div>
</div>
