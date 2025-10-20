<?php
/**
 * Vista de detalle de conversación
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap upgm-wrap upgm-detail">
    <h1 class="wp-heading-inline">
        <?php printf(
            __('Conversación entre %s y %s', 'userpro-global-messages'),
            esc_html($user1_info['name']),
            esc_html($user2_info['name'])
        ); ?>
    </h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=userpro-global-messages')); ?>" class="page-title-action">
        &larr; <?php _e('Volver al listado', 'userpro-global-messages'); ?>
    </a>

    <?php
    // Mostrar mensajes
    if (isset($_GET['message'])) {
        $message_type = sanitize_text_field($_GET['message']);
        $messages_text = array(
            'censored' => array('type' => 'success', 'text' => __('Mensaje censurado exitosamente.', 'userpro-global-messages')),
            'error' => array('type' => 'error', 'text' => __('Ocurrió un error al procesar la acción.', 'userpro-global-messages')),
        );

        if (isset($messages_text[$message_type])) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($messages_text[$message_type]['type']),
                esc_html($messages_text[$message_type]['text'])
            );
        }
    }
    ?>

    <hr class="wp-header-end">

    <!-- Información de usuarios -->
    <div class="upgm-user-info">
        <div class="upgm-user-card">
            <h3><?php echo esc_html($user1_info['name']); ?></h3>
            <p><strong><?php _e('ID:', 'userpro-global-messages'); ?></strong> <?php echo esc_html($user1_info['id']); ?></p>
            <?php if ($user1_info['exists']): ?>
                <p><strong><?php _e('Email:', 'userpro-global-messages'); ?></strong> <?php echo esc_html($user1_info['email']); ?></p>
            <?php endif; ?>
        </div>

        <div class="upgm-user-separator">↔</div>

        <div class="upgm-user-card">
            <h3><?php echo esc_html($user2_info['name']); ?></h3>
            <p><strong><?php _e('ID:', 'userpro-global-messages'); ?></strong> <?php echo esc_html($user2_info['id']); ?></p>
            <?php if ($user2_info['exists']): ?>
                <p><strong><?php _e('Email:', 'userpro-global-messages'); ?></strong> <?php echo esc_html($user2_info['email']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Acciones del hilo -->
    <div class="upgm-thread-actions">
        <h3><?php _e('Acciones del hilo completo:', 'userpro-global-messages'); ?></h3>
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'export_detail', 'user_id_1' => $user_id_1, 'user_id_2' => $user_id_2, 'format' => 'csv')), 'upgm_export_detail')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-media-spreadsheet"></span> <?php _e('Exportar a CSV', 'userpro-global-messages'); ?>
        </a>
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'export_detail', 'user_id_1' => $user_id_1, 'user_id_2' => $user_id_2, 'format' => 'excel')), 'upgm_export_detail')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-media-document"></span> <?php _e('Exportar a Excel', 'userpro-global-messages'); ?>
        </a>
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'export_conversation_json', 'user_id_1' => $user_id_1, 'user_id_2' => $user_id_2)), 'upgm_export_json')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-media-code"></span> <?php _e('Exportar a JSON', 'userpro-global-messages'); ?>
        </a>
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'delete_conversation', 'user_id_1' => $user_id_1, 'user_id_2' => $user_id_2)), 'upgm_delete_conversation')); ?>" class="button button-secondary upgm-delete-btn" style="margin-left: 20px;">
            <span class="dashicons dashicons-trash"></span> <?php _e('Vaciar Conversación Completa', 'userpro-global-messages'); ?>
        </a>
    </div>

    <!-- Mensajes -->
    <div class="upgm-messages-container">
        <h3><?php printf(__('Total de mensajes: %d', 'userpro-global-messages'), count($messages)); ?></h3>

        <?php if (empty($messages)): ?>
            <div class="upgm-no-messages">
                <p><?php _e('No hay mensajes en esta conversación.', 'userpro-global-messages'); ?></p>
            </div>
        <?php else: ?>
            <div class="upgm-messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="upgm-message upgm-message-<?php echo $msg['from_user_id'] == $user_id_1 ? 'user1' : 'user2'; ?>">
                        <div class="upgm-message-header">
                            <strong class="upgm-message-from">
                                <?php echo esc_html($msg['from_user_name']); ?>
                            </strong>
                            <span class="upgm-message-arrow">→</span>
                            <span class="upgm-message-to">
                                <?php echo esc_html($msg['to_user_name']); ?>
                            </span>
                            <span class="upgm-message-date">
                                <?php echo esc_html($msg['datetime']); ?>
                            </span>
                            <span class="upgm-message-status upgm-status-<?php echo esc_attr($msg['status']); ?>">
                                <?php echo esc_html($msg['status']); ?>
                            </span>
                        </div>
                        <div class="upgm-message-content">
                            <?php echo nl2br(esc_html($msg['content'])); ?>
                        </div>
                        <div class="upgm-message-actions">
                            <small class="upgm-message-timestamp">Timestamp: <?php echo esc_html($msg['timestamp']); ?></small>
                            <?php if (strpos($msg['content'], 'censurado por administración') === false): ?>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'censor_message', 'user_id_1' => $user_id_1, 'user_id_2' => $user_id_2, 'timestamp' => $msg['timestamp'])), 'upgm_censor_message')); ?>" class="button button-small upgm-censor-btn">
                                    <span class="dashicons dashicons-hidden"></span> <?php _e('Censurar', 'userpro-global-messages'); ?>
                                </a>
                            <?php else: ?>
                                <span class="upgm-censored-label"><?php _e('Mensaje censurado', 'userpro-global-messages'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Información adicional -->
    <div class="upgm-info-box">
        <h4><?php _e('Información importante:', 'userpro-global-messages'); ?></h4>
        <ul>
            <li><?php _e('Al censurar un mensaje, se reemplazará el contenido por "[Mensaje censurado por administración]" en todos los archivos de conversación.', 'userpro-global-messages'); ?></li>
            <li><?php _e('Al vaciar la conversación, se eliminarán TODOS los archivos de conversación de ambos usuarios (archive y unread).', 'userpro-global-messages'); ?></li>
            <li><?php _e('Estas acciones son irreversibles. Se recomienda hacer respaldo antes de realizar operaciones destructivas.', 'userpro-global-messages'); ?></li>
        </ul>
    </div>
</div>
