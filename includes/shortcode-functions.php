<?php
// shortcode-functions.php
if (!defined('ABSPATH')) exit;

class Woo_Search_Shortcode {
    
    public static function init() {
        add_shortcode('woo_buscador_responsive', [__CLASS__, 'render_search_shortcode']);
        add_action('wp_ajax_woo_ajax_search', [__CLASS__, 'ajax_search_products']);
        add_action('wp_ajax_nopriv_woo_ajax_search', [__CLASS__, 'ajax_search_products']);
    }
    
    public static function render_search_shortcode($atts) {
        ob_start();
        ?>
        
<div class="woo-search-shortcode-container">
	<!-- Versión Desktop -->
	<div class="woo-search-desktop">
		<div class="woo-search-wrapper">
			<!-- ICONO A LA IZQUIERDA -->
			<button class="woo-search-btn" type="button" aria-label="Buscar" id="woo-search-trigger">
				<svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
					<path d="M19.13 18.16l-4.52-4.52a7.85 7.85 0 1 0-1.06 1.06l4.52 4.52a.75.75 0 0 0 1.06-1.06zM2.5 8.57a6.07 6.07 0 1 1 12.14 0 6.07 6.07 0 0 1-12.14 0z"/>
				</svg>
			</button>

			<input type="text" 
				   class="woo-search-input" 
				   id="woo-search-input-desk"
				   placeholder="Buscar..."
				   autocomplete="off"
				   data-nonce="<?php echo wp_create_nonce('woo_search_nonce'); ?>">

			<div class="woo-search-results" id="woo-results-desk"></div>
		</div>
	</div>

	<!-- Versión Móvil -->
	<div class="woo-search-mobile">
		<button class="woo-mobile-toggle" id="woo-mobile-toggle" aria-label="Abrir buscador">
			<svg width="28" height="28" viewBox="0 0 20 20" fill="currentColor">
				<path d="M19.13 18.16l-4.52-4.52a7.85 7.85 0 1 0-1.06 1.06l4.52 4.52a.75.75 0 0 0 1.06-1.06zM2.5 8.57a6.07 6.07 0 1 1 12.14 0 6.07 6.07 0 0 1-12.14 0z"/>
			</svg>
		</button>

		<div class="woo-mobile-overlay" id="woo-mobile-overlay"></div>

		<div class="woo-mobile-panel" id="woo-mobile-panel">
			<div class="woo-mobile-header">
				<button class="woo-close-search" id="woo-close-search">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
						<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
					</svg>
				</button>
				<h3>Buscar Productos</h3>
			</div>
			<div class="woo-mobile-body">
				<!-- Icono para móvil -->
				<div class="search-icon-mobile">
					<svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
						<path d="M19.13 18.16l-4.52-4.52a7.85 7.85 0 1 0-1.06 1.06l4.52 4.52a.75.75 0 0 0 1.06-1.06zM2.5 8.57a6.07 6.07 0 1 1 12.14 0 6.07 6.07 0 0 1-12.14 0z"/>
					</svg>
				</div>

				<input type="text" 
					   class="woo-search-input" 
					   id="woo-search-input-mob"
					   placeholder="Escribe para buscar..."
					   autocomplete="off"
					   data-nonce="<?php echo wp_create_nonce('woo_search_nonce'); ?>">
				<div class="woo-search-results" id="woo-results-mob"></div>
			</div>
		</div>
	</div>
</div>

        <?php
        return ob_get_clean();
    }
    
    public static function ajax_search_products() {
        // Verificar nonce
        check_ajax_referer('woo_search_nonce', 'nonce');
        
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $results = [];
        
        if (strlen($search_term) >= 2) {
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                's' => $search_term,
                'posts_per_page' => 10,
                'orderby' => 'relevance'
            ];
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    global $product;
                    
                    $results[] = [
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'url' => get_permalink(),
                        'price' => $product ? $product->get_price_html() : '',
                        'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
                    ];
                }
                wp_reset_postdata();
            }
        }
        
        wp_send_json_success($results);
    }
}

// Inicializar
Woo_Search_Shortcode::init();