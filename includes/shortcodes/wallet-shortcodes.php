<?php
// Shortcode para mostrar el wallet completo del usuario
function bono_wallet_shortcode($atts) {
    // Solo para usuarios logueados
    if (!is_user_logged_in()) {
        return '<div class="alert alert-info">Debes iniciar sesión para ver tu saldo.</div>';
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    // Obtener saldo
    $saldo = 0;
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
        $user_id
    ));
    
    if ($result) {
        $saldo = floatval($result->saldo);
    }
    
    // Obtener créditos próximos a caducar
    $proximos_caducar = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}credito_transacciones 
         WHERE user_id = %d 
         AND tipo = 'credito' 
         AND fecha_caducidad IS NOT NULL 
         AND fecha_caducidad >= CURDATE()
         ORDER BY fecha_caducidad ASC 
         LIMIT 5",
        $user_id
    ));
    
    // Obtener historial reciente
    $historial = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}credito_transacciones 
         WHERE user_id = %d 
         ORDER BY fecha_transaccion DESC 
         LIMIT 10",
        $user_id
    ));
    
    ob_start();
    ?>
    <div class="bono-wallet-container">
        <!-- Tarjeta de Saldo -->
        <div class="bono-wallet-card">
            <div class="bono-wallet-header">
                <h3 style="color: #ffffff;"><i class="fas fa-wallet"></i> Mi Saldo Disponible</h3>
            </div>
            <div class="bono-wallet-balance">
                <span class="bono-balance-amount"><?php echo number_format($saldo, 2); ?> €</span>
                <p class="bono-balance-info">Puedes usar este saldo en tus compras</p>
            </div>
            
            <?php if (!empty($proximos_caducar)): ?>
            <div class="bono-wallet-alerts">
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i>
                    <strong>Tienes créditos por caducar:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($proximos_caducar as $credito): 
                            $dias_restantes = floor((strtotime($credito->fecha_caducidad) - time()) / (60 * 60 * 24));
                        ?>
                        <li>
                            <?php echo number_format($credito->monto, 2); ?> € - 
                            Caduca: <?php echo date('d/m/Y', strtotime($credito->fecha_caducidad)); ?>
                            <span class="badge bg-<?php echo $dias_restantes <= 3 ? 'danger' : 'warning'; ?>">
                                <?php echo $dias_restantes; ?> días
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Historial -->
        <div class="bono-wallet-history mt-4">
            <h4><i class="fas fa-history"></i> Historial Reciente</h4>
            <?php if (!empty($historial)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Descripción</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $trans): 
                            $tipo_class = $trans->tipo == 'credito' ? 'text-success' : 'text-danger';
                            $tipo_icon = $trans->tipo == 'credito' ? 'fa-arrow-down' : 'fa-arrow-up';
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($trans->fecha_transaccion)); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $trans->tipo == 'credito' ? 'success' : 'danger'; ?>">
                                    <i class="fas <?php echo $tipo_icon; ?>"></i>
                                    <?php echo ucfirst($trans->tipo); ?>
                                </span>
                            </td>
                            <td class="<?php echo $tipo_class; ?>">
                                <?php echo ($trans->tipo == 'credito' ? '+' : '-') . number_format($trans->monto, 2); ?> €
                            </td>
                            <td><?php echo esc_html($trans->descripcion); ?></td>
                            <td><?php echo number_format($trans->saldo_nuevo, 2); ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted">No hay transacciones registradas.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .bono-wallet-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .bono-wallet-card {
            background: linear-gradient(135deg, #009cdc, #157ad5);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 15px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3);
        }
        
        .bono-wallet-header h3 {
            margin: 0 0 20px 0;
            font-size: 1.5rem;
        }
        
        .bono-wallet-header i {
            margin-right: 10px;
        }
        
        .bono-wallet-balance {
            text-align: center;
            margin: 30px 0;
        }
        
        .bono-balance-amount {
            font-size: 3.5rem;
            font-weight: bold;
            display: block;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .bono-balance-info {
            opacity: 0.9;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        
        .bono-wallet-alerts {
            margin-top: 25px;
        }
        
        .bono-wallet-alerts .alert {
            padding: 10px;
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
        }
        
        .bono-wallet-alerts .alert i {
            margin-right: 10px;
        }
        
        .bono-wallet-alerts ul {
            padding-left: 20px;
        }
        
        .bono-wallet-alerts li {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .bono-wallet-history {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .bono-wallet-history h4 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #4361ee;
            padding-bottom: 10px;
        }
        
        .bono-wallet-history h4 i {
            color: #4361ee;
            margin-right: 10px;
        }
        
        .badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('bono_wallet', 'bono_wallet_shortcode');

// Shortcode para mostrar solo el saldo
function bono_wallet_balance_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_icon' => 'yes',
        'label' => 'Saldo disponible:'
    ), $atts);
    
    if (!is_user_logged_in()) {
        return '';
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
        $user_id
    ));
    
    $saldo = $result ? floatval($result->saldo) : 0;
    
    $output = '<div class="bono-wallet-balance-shortcode">';
    
    if ($atts['show_icon'] === 'yes') {
        $output .= '<i class="fas fa-wallet" style="color: #4361ee; margin-right: 8px;"></i>';
    }
    
    $output .= '<span class="bono-wallet-label">' . esc_html($atts['label']) . '</span> ';
    $output .= '<span class="bono-wallet-amount" style="font-weight: bold; color: #28a745;">';
    $output .= number_format($saldo, 2) . ' €</span>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('bono_wallet_balance', 'bono_wallet_balance_shortcode');