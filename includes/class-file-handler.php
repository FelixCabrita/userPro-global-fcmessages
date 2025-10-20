<?php
/**
 * Manejador de archivos de conversación
 *
 * @package UserPro_Global_Messages
 */

if (!defined('WPINC')) {
    die;
}

class UPGM_File_Handler {

    /**
     * Directorio base de uploads de UserPro
     */
    private $base_dir;

    /**
     * Constructor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->base_dir = $upload_dir['basedir'] . '/userpro/';
    }

    /**
     * Obtener todos los usuarios con conversaciones
     *
     * @return array Array de IDs de usuarios
     */
    public function get_users_with_conversations() {
        $users = array();

        if (!file_exists($this->base_dir)) {
            return $users;
        }

        $dirs = glob($this->base_dir . '*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $user_id = basename($dir);
            if (is_numeric($user_id)) {
                $conv_dir = $dir . '/conversations/';
                if (file_exists($conv_dir)) {
                    $users[] = intval($user_id);
                }
            }
        }

        return $users;
    }

    /**
     * Obtener todas las conversaciones
     *
     * @return array Array de conversaciones
     */
    public function get_all_conversations() {
        $conversations = array();
        $users = $this->get_users_with_conversations();
        $processed_pairs = array();

        foreach ($users as $user_id) {
            $archive_files = $this->get_conversation_files($user_id, 'archive');
            $unread_files = $this->get_conversation_files($user_id, 'unread');

            $all_files = array_merge($archive_files, $unread_files);

            foreach ($all_files as $file_info) {
                $other_user_id = $file_info['other_user_id'];
                $status = $file_info['status'];

                // Crear clave única para el par de usuarios (siempre menor ID primero)
                $pair_key = $user_id < $other_user_id
                    ? $user_id . '-' . $other_user_id
                    : $other_user_id . '-' . $user_id;

                // Si ya procesamos este par, solo actualizar estado si hay unread
                if (isset($processed_pairs[$pair_key])) {
                    if ($status === 'unread' && $processed_pairs[$pair_key]['status'] === 'archive') {
                        $processed_pairs[$pair_key]['status'] = 'unread';
                    }
                    continue;
                }

                // Obtener mensajes para preview
                $messages = $this->parse_conversation_file($file_info['path']);

                if (!empty($messages)) {
                    $last_message = end($messages);

                    $processed_pairs[$pair_key] = array(
                        'user_id_1' => min($user_id, $other_user_id),
                        'user_id_2' => max($user_id, $other_user_id),
                        'last_message' => $last_message['content'],
                        'last_timestamp' => $last_message['timestamp'],
                        'status' => $status,
                        'message_count' => count($messages)
                    );
                }
            }
        }

        return array_values($processed_pairs);
    }

    /**
     * Obtener archivos de conversación de un usuario
     *
     * @param int $user_id ID del usuario
     * @param string $folder 'archive' o 'unread'
     * @return array
     */
    private function get_conversation_files($user_id, $folder) {
        $files = array();
        $dir = $this->base_dir . $user_id . '/conversations/' . $folder . '/';

        if (!file_exists($dir)) {
            return $files;
        }

        $txt_files = glob($dir . '*.txt');

        foreach ($txt_files as $file) {
            $other_user_id = basename($file, '.txt');
            if (is_numeric($other_user_id)) {
                $files[] = array(
                    'path' => $file,
                    'user_id' => $user_id,
                    'other_user_id' => intval($other_user_id),
                    'status' => $folder
                );
            }
        }

        return $files;
    }

    /**
     * Parsear archivo de conversación
     *
     * @param string $file_path Ruta al archivo
     * @return array Array de mensajes
     */
    public function parse_conversation_file($file_path) {
        if (!file_exists($file_path)) {
            return array();
        }

        $content = file_get_contents($file_path);
        $messages = array();

        // Dividir por bloques de mensajes
        $blocks = explode('[/]', $content);

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            $message = array();

            // Extraer mode
            if (preg_match('/\[mode\](.*?)\[\/mode\]/', $block, $matches)) {
                $message['mode'] = trim($matches[1]);
            }

            // Extraer status
            if (preg_match('/\[status\](.*?)\[\/status\]/', $block, $matches)) {
                $message['status'] = trim($matches[1]);
            }

            // Extraer timestamp
            if (preg_match('/\[timestamp\](.*?)\[\/timestamp\]/', $block, $matches)) {
                $message['timestamp'] = intval(trim($matches[1]));
            }

            // Extraer content
            if (preg_match('/\[content\](.*?)\[\/content\]/s', $block, $matches)) {
                $message['content'] = trim($matches[1]);
            }

            if (!empty($message['timestamp']) && isset($message['content'])) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * Obtener conversación completa entre dos usuarios
     *
     * @param int $user_id_1 ID del primer usuario
     * @param int $user_id_2 ID del segundo usuario
     * @return array Array de mensajes unificados y ordenados
     */
    public function get_conversation_between_users($user_id_1, $user_id_2) {
        $messages = array();

        // Obtener mensajes desde la perspectiva del usuario 1
        $files_user1_archive = $this->base_dir . $user_id_1 . '/conversations/archive/' . $user_id_2 . '.txt';
        $files_user1_unread = $this->base_dir . $user_id_1 . '/conversations/unread/' . $user_id_2 . '.txt';

        // Obtener mensajes desde la perspectiva del usuario 2
        $files_user2_archive = $this->base_dir . $user_id_2 . '/conversations/archive/' . $user_id_1 . '.txt';
        $files_user2_unread = $this->base_dir . $user_id_2 . '/conversations/unread/' . $user_id_1 . '.txt';

        // Parsear y etiquetar mensajes del usuario 1
        foreach (array($files_user1_archive, $files_user1_unread) as $file) {
            if (file_exists($file)) {
                $parsed = $this->parse_conversation_file($file);
                foreach ($parsed as $msg) {
                    // Si es 'sent', el remitente es user_id_1, sino es user_id_2
                    $msg['from_user_id'] = $msg['mode'] === 'sent' ? $user_id_1 : $user_id_2;
                    $msg['to_user_id'] = $msg['mode'] === 'sent' ? $user_id_2 : $user_id_1;
                    $messages[] = $msg;
                }
            }
        }

        // Parsear y etiquetar mensajes del usuario 2
        foreach (array($files_user2_archive, $files_user2_unread) as $file) {
            if (file_exists($file)) {
                $parsed = $this->parse_conversation_file($file);
                foreach ($parsed as $msg) {
                    // Si es 'sent', el remitente es user_id_2, sino es user_id_1
                    $msg['from_user_id'] = $msg['mode'] === 'sent' ? $user_id_2 : $user_id_1;
                    $msg['to_user_id'] = $msg['mode'] === 'sent' ? $user_id_1 : $user_id_2;
                    $messages[] = $msg;
                }
            }
        }

        // Eliminar duplicados por timestamp + from_user + content
        $unique_messages = array();
        $seen = array();

        foreach ($messages as $msg) {
            $key = $msg['timestamp'] . '-' . $msg['from_user_id'] . '-' . md5($msg['content']);
            if (!isset($seen[$key])) {
                $unique_messages[] = $msg;
                $seen[$key] = true;
            }
        }

        // Ordenar por timestamp
        usort($unique_messages, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        return $unique_messages;
    }

    /**
     * Censurar un mensaje específico
     *
     * @param int $user_id_1 ID del primer usuario
     * @param int $user_id_2 ID del segundo usuario
     * @param int $timestamp Timestamp del mensaje
     * @param string $censored_text Texto de censura
     * @return bool
     */
    public function censor_message($user_id_1, $user_id_2, $timestamp, $censored_text = '[Mensaje censurado por administración]') {
        $success = true;

        // Censurar en ambos archivos (archive y unread) de ambos usuarios
        $files = array(
            $this->base_dir . $user_id_1 . '/conversations/archive/' . $user_id_2 . '.txt',
            $this->base_dir . $user_id_1 . '/conversations/unread/' . $user_id_2 . '.txt',
            $this->base_dir . $user_id_2 . '/conversations/archive/' . $user_id_1 . '.txt',
            $this->base_dir . $user_id_2 . '/conversations/unread/' . $user_id_1 . '.txt',
        );

        foreach ($files as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);

                // Buscar y reemplazar el contenido del mensaje con el timestamp específico
                $pattern = '/(\[timestamp\]' . $timestamp . '\[\/timestamp\].*?\[content\])(.*?)(\[\/content\])/s';
                $replacement = '$1' . $censored_text . '$3';
                $new_content = preg_replace($pattern, $replacement, $content);

                if ($new_content !== $content) {
                    $result = file_put_contents($file, $new_content);
                    if ($result === false) {
                        $success = false;
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Eliminar conversación completa entre dos usuarios
     *
     * @param int $user_id_1 ID del primer usuario
     * @param int $user_id_2 ID del segundo usuario
     * @return bool
     */
    public function delete_conversation($user_id_1, $user_id_2) {
        $success = true;

        $files = array(
            $this->base_dir . $user_id_1 . '/conversations/archive/' . $user_id_2 . '.txt',
            $this->base_dir . $user_id_1 . '/conversations/unread/' . $user_id_2 . '.txt',
            $this->base_dir . $user_id_2 . '/conversations/archive/' . $user_id_1 . '.txt',
            $this->base_dir . $user_id_2 . '/conversations/unread/' . $user_id_1 . '.txt',
        );

        foreach ($files as $file) {
            if (file_exists($file)) {
                if (!unlink($file)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Buscar en conversaciones por palabra clave
     *
     * @param string $keyword Palabra clave
     * @return array Conversaciones que contienen la palabra
     */
    public function search_by_keyword($keyword) {
        $results = array();
        $users = $this->get_users_with_conversations();

        foreach ($users as $user_id) {
            $archive_files = $this->get_conversation_files($user_id, 'archive');
            $unread_files = $this->get_conversation_files($user_id, 'unread');

            $all_files = array_merge($archive_files, $unread_files);

            foreach ($all_files as $file_info) {
                $messages = $this->parse_conversation_file($file_info['path']);

                foreach ($messages as $message) {
                    if (stripos($message['content'], $keyword) !== false) {
                        $results[] = array(
                            'user_id' => $file_info['user_id'],
                            'other_user_id' => $file_info['other_user_id'],
                            'message' => $message,
                            'status' => $file_info['status']
                        );
                        break; // Solo necesitamos saber que hay coincidencia
                    }
                }
            }
        }

        return $results;
    }
}
