<?php
// Añadir pestaña de crédito en "Mi cuenta" de WooCommerce
add_filter('woocommerce_account_menu_items', 'bono_add_wallet_account_tab', 40);

function bono_add_wallet_account_tab($menu_items) {
    $menu_items = array_slice($menu_items, 0, 5, true) +
        array('bono-wallet' => __('Mi Crédito', 'bonospremium')) +
        array_slice($menu_items, 5, NULL, true);
    
    return $menu_items;
}

// Registrar el endpoint
add_action('init', 'bono_add_wallet_endpoint');

function bono_add_wallet_endpoint() {
    add_rewrite_endpoint('bono-wallet', EP_ROOT | EP_PAGES);
}

// Añadir contenido a la pestaña
add_action('woocommerce_account_bono-wallet_endpoint', 'bono_wallet_tab_content');

function bono_wallet_tab_content() {
    echo do_shortcode('[bono_wallet]');
}

// Flush rewrite rules
register_activation_hook(__FILE__, 'bono_wallet_flush_rewrite_rules');

function bono_wallet_flush_rewrite_rules() {
    bono_add_wallet_endpoint();
    flush_rewrite_rules();
}

// Añadir saldo en el dashboard de "Mi cuenta"
add_action('woocommerce_account_dashboard', 'bono_add_balance_to_dashboard');

function bono_add_balance_to_dashboard() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT saldo FROM {$wpdb->prefix}usuario_creditos WHERE user_id = %d", 
        $user_id
    ));
    
    $saldo = $result ? floatval($result->saldo) : 0;
    
    ?>
    <div class="bono-dashboard-balance">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-wallet text-primary me-2"></i>
                    Mi Crédito
                </h5>
                <p class="card-text">
                    <span class="fs-4 text-success fw-bold"><?php echo number_format($saldo, 2); ?> €</span>
                    <br>
                    <small class="text-muted">Saldo disponible para tus compras</small>
                </p>
                <a href="<?php echo wc_get_account_endpoint_url('bono-wallet'); ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-history me-1"></i> Ver historial
                </a>
            </div>
        </div>
    </div>
    <style>
    .bono-dashboard-balance {
        margin: 20px 0;
    }
    .bono-dashboard-balance .card {
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }
    </style>
    <?php
}