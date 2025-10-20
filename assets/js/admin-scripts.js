/**
 * Scripts administrativos para UserPro Global Messages
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Confirmación para censurar mensaje
         */
        $('.upgm-censor-btn').on('click', function(e) {
            if (!confirm(upgmData.confirmCensor)) {
                e.preventDefault();
                return false;
            }
        });

        /**
         * Confirmación para eliminar conversación
         */
        $('.upgm-delete-btn').on('click', function(e) {
            if (!confirm(upgmData.confirmDelete)) {
                e.preventDefault();
                return false;
            }
        });

        /**
         * Auto-dismiss de notificaciones
         */
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);

        /**
         * Resaltar filtros activos
         */
        $('.upgm-filter-item select, .upgm-filter-item input').each(function() {
            if ($(this).val() !== '' && $(this).val() !== 'all') {
                $(this).css('border-color', '#2271b1');
                $(this).css('border-width', '2px');
            }
        });

        /**
         * Limpiar filtros individuales con doble clic
         */
        $('.upgm-filter-item select, .upgm-filter-item input[type="text"]').on('dblclick', function() {
            $(this).val('');
            $(this).css('border-color', '');
            $(this).css('border-width', '');
        });

        $('.upgm-filter-item input[type="date"]').on('dblclick', function() {
            $(this).val('');
            $(this).css('border-color', '');
            $(this).css('border-width', '');
        });

        /**
         * Contador de caracteres en búsqueda
         */
        var $keyword = $('#filter-keyword');
        if ($keyword.length) {
            $keyword.on('input', function() {
                var length = $(this).val().length;
                if (length > 50) {
                    $(this).css('border-color', '#d63638');
                } else if (length > 0) {
                    $(this).css('border-color', '#2271b1');
                } else {
                    $(this).css('border-color', '');
                }
            });
        }

        /**
         * Copiar timestamp al hacer clic
         */
        $('.upgm-message-timestamp').on('click', function() {
            var timestamp = $(this).text().replace('Timestamp: ', '');

            // Crear elemento temporal para copiar
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(timestamp).select();

            try {
                document.execCommand('copy');
                $(this).css('background', '#00a32a');
                $(this).css('color', '#fff');
                $(this).css('padding', '2px 5px');
                $(this).css('border-radius', '3px');

                var $this = $(this);
                setTimeout(function() {
                    $this.css('background', '');
                    $this.css('color', '');
                    $this.css('padding', '');
                }, 1000);
            } catch (err) {
                console.error('No se pudo copiar el timestamp');
            }

            $temp.remove();
        });

        /**
         * Añadir tooltip a timestamp
         */
        $('.upgm-message-timestamp').attr('title', 'Click para copiar timestamp');
        $('.upgm-message-timestamp').css('cursor', 'pointer');

        /**
         * Smooth scroll para mensajes largos
         */
        if ($('.upgm-messages').length) {
            var $messages = $('.upgm-messages');
            if ($messages.children().length > 20) {
                $messages.css('max-height', '800px');
                $messages.css('overflow-y', 'auto');
                $messages.css('padding-right', '10px');
            }
        }

        /**
         * Highlight de búsqueda en resultados
         */
        var urlParams = new URLSearchParams(window.location.search);
        var keyword = urlParams.get('keyword');

        if (keyword && keyword.length > 0) {
            $('.upgm-message-content').each(function() {
                var content = $(this).html();
                var regex = new RegExp('(' + keyword + ')', 'gi');
                var highlighted = content.replace(regex, '<mark style="background: #ffff00; padding: 2px;">$1</mark>');
                $(this).html(highlighted);
            });
        }

        /**
         * Contador de resultados de búsqueda
         */
        if (keyword && keyword.length > 0) {
            var count = $('mark').length;
            if (count > 0) {
                $('.upgm-messages-container > h3').append(
                    ' <span style="color: #2271b1;">(' + count + ' coincidencias encontradas)</span>'
                );
            }
        }

        /**
         * Expandir/contraer mensajes largos
         */
        $('.upgm-message-content').each(function() {
            var $content = $(this);
            var maxHeight = 200;

            if ($content.height() > maxHeight) {
                $content.css({
                    'max-height': maxHeight + 'px',
                    'overflow': 'hidden',
                    'position': 'relative'
                });

                var $toggle = $('<a href="#" class="upgm-toggle-content" style="color: #2271b1; font-size: 12px; margin-top: 5px; display: inline-block;">Ver más...</a>');

                $content.after($toggle);

                $toggle.on('click', function(e) {
                    e.preventDefault();

                    if ($content.css('max-height') === maxHeight + 'px') {
                        $content.css('max-height', 'none');
                        $(this).text('Ver menos...');
                    } else {
                        $content.css('max-height', maxHeight + 'px');
                        $(this).text('Ver más...');
                    }
                });
            }
        });

        /**
         * Loading indicator para exportaciones
         */
        $('a[href*="export_list"], a[href*="export_detail"]').on('click', function() {
            var $btn = $(this);
            var originalText = $btn.html();

            $btn.html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> Exportando...');
            $btn.css('pointer-events', 'none');

            // Restaurar después de 3 segundos (la exportación debería haber comenzado)
            setTimeout(function() {
                $btn.html(originalText);
                $btn.css('pointer-events', '');
            }, 3000);
        });

        // Añadir animación de rotación para el spinner
        $('<style>')
            .text('@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }')
            .appendTo('head');

        /**
         * Toggle detalles en página de auditoría
         */
        $('.upgm-toggle-details').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var logId = $btn.data('log-id');
            var $details = $('#upgm-details-' + logId);

            if ($details.is(':visible')) {
                $details.slideUp(200);
                $btn.removeClass('active');
                $btn.find('span').text('Ver');
            } else {
                $details.slideDown(200);
                $btn.addClass('active');
                $btn.find('span').text('Ocultar');
            }
        });

        /**
         * Expandir/contraer todos los detalles
         */
        if ($('.upgm-audit').length) {
            var $toolbar = $('<div class="upgm-audit-toolbar"></div>');
            var $expandAll = $('<button class="button button-small upgm-expand-all">Expandir Todos</button>');
            var $collapseAll = $('<button class="button button-small upgm-collapse-all">Contraer Todos</button>');

            $toolbar.append($expandAll).append($collapseAll);
            $('.upgm-audit-table').before($toolbar);

            $expandAll.on('click', function(e) {
                e.preventDefault();
                $('.upgm-details-content').slideDown(200);
                $('.upgm-toggle-details').addClass('active').find('span').text('Ocultar');
            });

            $collapseAll.on('click', function(e) {
                e.preventDefault();
                $('.upgm-details-content').slideUp(200);
                $('.upgm-toggle-details').removeClass('active').find('span').text('Ver');
            });
        }

        /**
         * Copiar IP al hacer clic
         */
        $('.upgm-col-ip code').on('click', function() {
            var ip = $(this).text();
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(ip).select();

            try {
                document.execCommand('copy');
                $(this).css('background', '#00a32a');
                $(this).css('color', '#fff');

                var $this = $(this);
                setTimeout(function() {
                    $this.css('background', '');
                    $this.css('color', '');
                }, 1000);
            } catch (err) {
                console.error('No se pudo copiar la IP');
            }

            $temp.remove();
        });

        /**
         * Tooltip para IPs
         */
        $('.upgm-col-ip code').attr('title', 'Click para copiar IP');
        $('.upgm-col-ip code').css('cursor', 'pointer');

        /**
         * Resaltar fila de auditoría al pasar el mouse
         */
        $('.upgm-audit-row').hover(
            function() {
                $(this).css('background-color', '#f6f7f7');
            },
            function() {
                $(this).css('background-color', '');
            }
        );

        /**
         * Contador de resultados filtrados
         */
        if ($('.upgm-audit').length) {
            var totalLogs = $('.upgm-audit-row').length;
            var hasFilters = window.location.search.indexOf('action_type=') > -1 ||
                           window.location.search.indexOf('admin_user_id=') > -1 ||
                           window.location.search.indexOf('date_from=') > -1;

            if (hasFilters && totalLogs > 0) {
                $('.upgm-stats p').append(' <span style="color: #2271b1;">(filtrados)</span>');
            }
        }

        /**
         * Validación de archivo de importación
         */
        $('#import_file').on('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;

            var fileName = file.name;
            var fileSize = file.size;
            var fileExt = fileName.split('.').pop().toLowerCase();

            // Validar extensión
            if (fileExt !== 'json' && fileExt !== 'csv') {
                alert('Formato de archivo no válido. Solo se permiten archivos JSON y CSV.');
                $(this).val('');
                return;
            }

            // Validar tamaño (10MB máximo)
            var maxSize = 10 * 1024 * 1024; // 10MB en bytes
            if (fileSize > maxSize) {
                alert('El archivo es demasiado grande. Tamaño máximo permitido: 10MB.');
                $(this).val('');
                return;
            }

            // Mostrar información del archivo
            var fileInfo = 'Archivo seleccionado: ' + fileName + ' (' + (fileSize / 1024).toFixed(2) + ' KB)';
            if ($(this).next('.file-info').length) {
                $(this).next('.file-info').text(fileInfo);
            } else {
                $(this).after('<p class="file-info description" style="color: #2271b1; margin-top: 5px;">' + fileInfo + '</p>');
            }
        });

        /**
         * Confirmación antes de importar
         */
        $('.upgm-import-form').on('submit', function(e) {
            var file = $('#import_file')[0].files[0];
            if (!file) {
                e.preventDefault();
                alert('Por favor selecciona un archivo para importar.');
                return false;
            }

            var confirmMessage = '¿Estás seguro de que deseas importar este archivo?\n\n' +
                                'Se verificará que los usuarios existan antes de importar.\n' +
                                'Este proceso puede tomar varios minutos dependiendo del tamaño del archivo.';

            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }

            // Mostrar indicador de carga
            var submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true);
            submitButton.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Importando...');

            // Agregar animación CSS si no existe
            if (!$('#import-spinner-animation').length) {
                $('head').append('<style id="import-spinner-animation">@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>');
            }
        });

        /**
         * Estadísticas en consola (solo para debug)
         */
        if (typeof console !== 'undefined') {
            console.log('UserPro Global Messages - Admin Scripts Loaded');

            var totalMessages = $('.upgm-message').length;
            if (totalMessages > 0) {
                console.log('Total messages in view:', totalMessages);
            }

            var totalConversations = $('.upgm-table tbody tr').length - $('.upgm-no-results').length;
            if (totalConversations > 0) {
                console.log('Total conversations in list:', totalConversations);
            }

            var totalLogs = $('.upgm-audit-row').length;
            if (totalLogs > 0) {
                console.log('Total audit logs in view:', totalLogs);
            }
        }

    });

})(jQuery);
