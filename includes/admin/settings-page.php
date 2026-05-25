<?php
// Añadir menú en el admin
// add_action('admin_menu', 'bono_credit_admin_menu');

function bono_credit_admin_menu() {
    add_menu_page(
        'Configuración de Créditos',
        'Créditos Bonos',
        'manage_options',
        'bono-credit-settings',
        'bono_credit_settings_page',
        'dashicons-money-alt',
        30
    );
    
    add_submenu_page(
        'bono-credit-settings',
        'Configuración',
        'Configuración',
        'manage_options',
        'bono-credit-settings',
        'bono_credit_settings_page'
    );
    
    add_submenu_page(
        'bono-credit-settings',
        'Historial',
        'Historial de Transacciones',
        'manage_options',
        'bono-credit-history',
        'bono_credit_history_page'
    );
}

// Página principal de configuración
function bono_credit_settings_page() {
    // Procesar guardado de configuración
    if (isset($_POST['bono_save_settings'])) {
        check_admin_referer('bono_credit_settings_nonce');
        
        update_option('bono_credit_enabled', isset($_POST['bono_credit_enabled']) ? 'yes' : 'no');
        update_option('bono_credit_min_amount', floatval($_POST['bono_credit_min_amount']));
        update_option('bono_credit_max_amount', floatval($_POST['bono_credit_max_amount']));
        update_option('bono_credit_checkout_position', sanitize_text_field($_POST['bono_credit_checkout_position']));
        update_option('bono_credit_auto_process', isset($_POST['bono_credit_auto_process']) ? 'yes' : 'no');
        update_option('bono_credit_send_email', isset($_POST['bono_credit_send_email']) ? 'yes' : 'no');
        
        echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
    }
    
    // Obtener valores actuales
    $enabled = get_option('bono_credit_enabled', 'yes');
    $min_amount = get_option('bono_credit_min_amount', 0);
    $max_amount = get_option('bono_credit_max_amount', 0);
    $position = get_option('bono_credit_checkout_position', 'before_payment');
    $auto_process = get_option('bono_credit_auto_process', 'yes');
    $send_email = get_option('bono_credit_send_email', 'yes');
    
    ?>
    <div class="wrap">
        <h1><i class="dashicons dashicons-money-alt"></i> Configuración de Créditos Bonos Premium</h1>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <div class="card-header">
                <h2 class="h3">Estado del Sistema</h2>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <?php wp_nonce_field('bono_credit_settings_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="bono_credit_enabled">Estado del sistema</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="bono_credit_enabled" 
                                           name="bono_credit_enabled" 
                                           value="1" 
                                           <?php checked($enabled, 'yes'); ?>>
                                    Activar sistema de créditos
                                </label>
                                <p class="description">
                                    Cuando está desactivado, los usuarios no podrán usar crédito en el checkout.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="bono_credit_min_amount">Monto mínimo</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="bono_credit_min_amount" 
                                       name="bono_credit_min_amount" 
                                       value="<?php echo esc_attr($min_amount); ?>" 
                                       step="0.01" 
                                       min="0"
                                       class="regular-text">
                                <p class="description">
                                    Monto mínimo de crédito que se puede usar en una compra (0 = sin mínimo).
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="bono_credit_max_amount">Monto máximo</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="bono_credit_max_amount" 
                                       name="bono_credit_max_amount" 
                                       value="<?php echo esc_attr($max_amount); ?>" 
                                       step="0.01" 
                                       min="0"
                                       class="regular-text">
                                <p class="description">
                                    Monto máximo de crédito que se puede usar en una compra (0 = sin máximo).
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label>Posición en checkout</label>
                            </th>
                            <td>
                                <select name="bono_credit_checkout_position" class="regular-text">
                                    <option value="before_payment" <?php selected($position, 'before_payment'); ?>>
                                        Antes de los métodos de pago (recomendado)
                                    </option>
                                    <option value="after_payment" <?php selected($position, 'after_payment'); ?>>
                                        Después de los métodos de pago
                                    </option>
                                    <option value="custom" <?php selected($position, 'custom'); ?>>
                                        Usar shortcode [bono_credit_checkout]
                                    </option>
                                </select>
                                <p class="description">
                                    Dónde aparecerá la opción de usar crédito en el checkout.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label>Procesamiento automático</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="bono_credit_auto_process" 
                                           value="1" 
                                           <?php checked($auto_process, 'yes'); ?>>
                                    Procesar crédito automáticamente después del pago
                                </label>
                                <p class="description">
                                    Si se desactiva, el crédito deberá procesarse manualmente desde el historial.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label>Notificaciones por email</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="bono_credit_send_email" 
                                           value="1" 
                                           <?php checked($send_email, 'yes'); ?>>
                                    Enviar email cuando se use crédito
                                </label>
                                <p class="description">
                                    Los usuarios recibirán un email confirmando el uso de su crédito.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" 
                                name="bono_save_settings" 
                                class="button button-primary">
                            <i class="dashicons dashicons-yes-alt"></i> Guardar configuración
                        </button>
                        
                        <button type="button" 
                                class="button button-secondary"
                                onclick="if(confirm('¿Restaurar configuración predeterminada?')) {
                                    document.getElementById('bono_credit_enabled').checked = true;
                                    document.getElementById('bono_credit_min_amount').value = '0';
                                    document.getElementById('bono_credit_max_amount').value = '0';
                                }">
                            <i class="dashicons dashicons-image-rotate"></i> Restaurar predeterminados
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <div class="card-header">
                <h2 class="h3">Información del Sistema</h2>
            </div>
            <div class="card-body">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Métrica</th>
                            <th>Valor</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $wpdb;
                        
                        // Total usuarios con crédito
                        $total_users = $wpdb->get_var(
                            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}usuario_creditos WHERE saldo > 0"
                        );
                        
                        // Crédito total en el sistema
                        $total_credit = $wpdb->get_var(
                            "SELECT SUM(saldo) FROM {$wpdb->prefix}usuario_creditos"
                        );
                        
                        // Transacciones hoy
                        $today = date('Y-m-d');
                        $transactions_today = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}credito_transacciones WHERE DATE(fecha_transaccion) = %s",
                            $today
                        ));
                        ?>
                        <tr>
                            <td>Sistema activo</td>
                            <td><?php echo $enabled === 'yes' ? '✅ Activado' : '❌ Desactivado'; ?></td>
                            <td>
                                <span class="dashicons dashicons-<?php echo $enabled === 'yes' ? 'yes' : 'no'; ?>"></span>
                            </td>
                        </tr>
                        <tr>
                            <td>Usuarios con crédito</td>
                            <td><?php echo intval($total_users); ?> usuarios</td>
                            <td>
                                <span class="dashicons dashicons-groups"></span>
                            </td>
                        </tr>
                        <tr>
                            <td>Crédito total en sistema</td>
                            <td><strong><?php echo number_format(floatval($total_credit), 2); ?> €</strong></td>
                            <td>
                                <span class="dashicons dashicons-money"></span>
                            </td>
                        </tr>
                        <tr>
                            <td>Transacciones hoy</td>
                            <td><?php echo intval($transactions_today); ?> transacciones</td>
                            <td>
                                <span class="dashicons dashicons-chart-area"></span>
                            </td>
                        </tr>
                        <tr>
                            <td>Procesamiento automático</td>
                            <td><?php echo $auto_process === 'yes' ? '✅ Activado' : '❌ Manual'; ?></td>
                            <td>
                                <span class="dashicons dashicons-<?php echo $auto_process === 'yes' ? 'update' : 'editor-help'; ?>"></span>
                            </td>
                        </tr>
                        <tr>
                            <td>Notificaciones email</td>
                            <td><?php echo $send_email === 'yes' ? '✅ Activadas' : '❌ Desactivadas'; ?></td>
                            <td>
                                <span class="dashicons dashicons-email"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mt-3">
                    <h4>Acciones rápidas:</h4>
                    <p>
                        <a href="?page=bono-credit-history" class="button">
                            <i class="dashicons dashicons-list-view"></i> Ver historial completo
                        </a>
                        
                        <a href="<?php echo admin_url('users.php'); ?>" class="button">
                            <i class="dashicons dashicons-admin-users"></i> Gestionar usuarios
                        </a>
                        
                        <a href="<?php echo home_url('/dashboard.php?page=wallet'); ?>" target="_blank" class="button">
                            <i class="dashicons dashicons-external"></i> Ir a gestión de créditos
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .card {
            background: white;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 20px;
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
        }
        .card-body {
            padding: 20px;
        }
        .mt-3 {
            margin-top: 15px;
        }
        </style>
    </div>
    <?php
}

// Página de historial
function bono_credit_history_page() {
    global $wpdb;
    
    // Paginación
    $per_page = 20;
    $current_page = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);
    $offset = ($current_page - 1) * $per_page;
    
    // Filtrar por fecha
    $date_filter = isset($_GET['date_filter']) ? sanitize_text_field($_GET['date_filter']) : '';
    $where = '';
    
    if ($date_filter === 'today') {
        $where = " WHERE DATE(fecha_transaccion) = CURDATE()";
    } elseif ($date_filter === 'week') {
        $where = " WHERE fecha_transaccion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_filter === 'month') {
        $where = " WHERE fecha_transaccion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
    
    // Total transacciones
    $total_transactions = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}credito_transacciones" . $where
    );
    
    // Obtener transacciones
    $transactions = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}credito_transacciones 
         {$where} 
         ORDER BY fecha_transaccion DESC 
         LIMIT {$offset}, {$per_page}"
    );
    
    ?>
    <div class="wrap">
        <h1><i class="dashicons dashicons-list-view"></i> Historial de Transacciones</h1>
        
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <div class="row" style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h2 class="h3" style="margin: 0;">Transacciones de Crédito</h2>
                    </div>
                    <div>
                        <form method="get" action="" style="display: inline-block;">
                            <input type="hidden" name="page" value="bono-credit-history">
                            <select name="date_filter" onchange="this.form.submit()" class="regular-text">
                                <option value="">Todas las fechas</option>
                                <option value="today" <?php selected($date_filter, 'today'); ?>>Hoy</option>
                                <option value="week" <?php selected($date_filter, 'week'); ?>>Últimos 7 días</option>
                                <option value="month" <?php selected($date_filter, 'month'); ?>>Últimos 30 días</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <?php if ($transactions): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Saldo Anterior</th>
                            <th>Saldo Nuevo</th>
                            <th>Pedido</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trans): 
                            $user_info = get_userdata($trans->user_id);
                            $user_display = $user_info ? $user_info->display_name . ' (#' . $trans->user_id . ')' : 'Usuario #' . $trans->user_id;
                            
                            $tipo_class = $trans->tipo == 'credito' ? 'success' : 'danger';
                            $tipo_text = $trans->tipo == 'credito' ? '➕ Crédito' : '➖ Débito';
                        ?>
                        <tr>
                            <td><?php echo $trans->id; ?></td>
                            <td><?php echo esc_html($user_display); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $tipo_class; ?>" 
                                      style="background: <?php echo $tipo_class == 'success' ? '#28a745' : '#dc3545'; ?>; 
                                             color: white; padding: 3px 8px; border-radius: 3px;">
                                    <?php echo $tipo_text; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo number_format($trans->monto, 2); ?> €</strong>
                            </td>
                            <td><?php echo number_format($trans->saldo_anterior, 2); ?> €</td>
                            <td>
                                <strong><?php echo number_format($trans->saldo_nuevo, 2); ?> €</strong>
                            </td>
                            <td>
                                <?php if ($trans->order_id): ?>
                                    <a href="<?php echo admin_url('post.php?post=' . $trans->order_id . '&action=edit'); ?>" 
                                       target="_blank" 
                                       class="button button-small">
                                        #<?php echo $trans->order_id; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($trans->descripcion); ?></td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($trans->fecha_transaccion)); ?>
                                <?php if ($trans->fecha_caducidad): ?>
                                    <br>
                                    <small class="text-muted">
                                        Caduca: <?php echo date('d/m/Y', strtotime($trans->fecha_caducidad)); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $total_pages = ceil($total_transactions / $per_page);
                        
                        if ($total_pages > 1) {
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $current_page,
                            ));
                        }
                        ?>
                    </div>
                </div>
                
                <?php else: ?>
                <div class="notice notice-info">
                    <p>No hay transacciones registradas.</p>
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <h4>Exportar datos:</h4>
                    <p>
                        <a href="<?php echo admin_url('admin-post.php?action=export_bono_credit_csv'); ?>" 
                           class="button button-primary">
                            <i class="dashicons dashicons-download"></i> Exportar CSV
                        </a>
                        
                        <button class="button" onclick="alert('Para exportar a Excel, descarga el CSV y ábrelo con Excel')">
                            <i class="dashicons dashicons-media-spreadsheet"></i> Exportar Excel
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Exportar CSV
add_action('admin_post_export_bono_credit_csv', 'bono_export_credit_csv');

function bono_export_credit_csv() {
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos');
    }
    
    global $wpdb;
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=creditos_transacciones_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, array(
        'ID',
        'Usuario ID',
        'Nombre Usuario',
        'Email',
        'Tipo',
        'Monto (€)',
        'Saldo Anterior (€)',
        'Saldo Nuevo (€)',
        'Pedido ID',
        'Descripción',
        'Fecha Transacción',
        'Fecha Caducidad'
    ));
    
    // Datos
    $transactions = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}credito_transacciones ORDER BY fecha_transaccion DESC"
    );
    
    foreach ($transactions as $trans) {
        $user = get_userdata($trans->user_id);
        
        fputcsv($output, array(
            $trans->id,
            $trans->user_id,
            $user ? $user->display_name : '',
            $user ? $user->user_email : '',
            $trans->tipo,
            number_format($trans->monto, 2, '.', ''),
            number_format($trans->saldo_anterior, 2, '.', ''),
            number_format($trans->saldo_nuevo, 2, '.', ''),
            $trans->order_id,
            $trans->descripcion,
            $trans->fecha_transaccion,
            $trans->fecha_caducidad
        ));
    }
    
    fclose($output);
    exit;
}