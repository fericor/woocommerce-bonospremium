<?php
/**
 * Custom archive-product.php for WooCommerce
 * BonosPremium Theme - Sin títulos ni descripciones de WooCommerce
 */
get_header(); ?>

<main class="bp-main-content">
    <div class="bp-container">
        <?php
        // Get current category name for the heading
        $current_cat = '';
        $current_cat_name = '';
        if (is_product_category()) {
            $cat = get_queried_object();
            $current_cat = $cat->slug;
            $current_cat_name = $cat->name;
        }
        ?>
        
        <?php if (woocommerce_product_loop()) : ?>
            <?php woocommerce_product_loop_start(); ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php wc_get_template_part('content', 'product'); ?>
            <?php endwhile; ?>
            <?php woocommerce_product_loop_end(); ?>
            <?php
            // Load more wrap with category data
            global $wp_query;
            if ($wp_query->max_num_pages > 1) :
            ?>
            <div class="bp-load-more-wrap" 
                 data-page="1" 
                 data-max="<?php echo $wp_query->max_num_pages; ?>" 
                 data-category="<?php echo esc_attr($current_cat); ?>">
                <div class="bp-load-more-spinner" style="display:none;">
                    <span class="bp-spinner"></span> Cargando...
                </div>
            </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="woocommerce-no-products-found">
				<div class="no-products-message">
					<span class="no-products-icon">🔍</span>
					<h2><?php esc_html_e( 'No se encontraron productos', 'tutema' ); ?></h2>
					<p><?php esc_html_e( 'Lo sentimos, pero no hay productos que coincidan con tu búsqueda o filtros.', 'tutema' ); ?></p>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="button return-to-shop">
						<?php esc_html_e( 'Volver a la tienda', 'tutema' ); ?>
					</a>
				</div>
			</div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
