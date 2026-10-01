<?php
/**
 * Custom single-product.php for BonosPremium Theme
 */
get_header(); ?>

<main class="bp-main-content">
    <div class="bp-container">
        <?php while (have_posts()) : the_post();
            global $product;
            $city                   = get_field('localidad') ?: get_post_meta(get_the_ID(), 'localidad', true);
            $nombre_establecimiento = get_field('nombre_establecimiento') ?: get_post_meta(get_the_ID(), 'nombre_establecimiento', true);
            $excerpt                = get_the_excerpt();
            // 25/08: la ficha usa el MISMO resolver que el loop (bp_resolver_precio_loop)
            // para que respete la variación por defecto / seleccionada en el producto.
            // Fallback: si el producto es simple o el resolver no está, usar get_sale_price().
            if (function_exists('bp_resolver_precio_loop')) {
                $resuelto_single = bp_resolver_precio_loop($product);
                $sale_price      = $resuelto_single['sale'];
                $regular_price   = $resuelto_single['regular'];
            } else {
                $regular_price = $product->get_regular_price();
                $sale_price    = $product->get_sale_price() ?: $regular_price;
            }
            // FIX 25/08 (Félix): en productos VARIABLE sin variaciones hijas,
            // get_sale_price()/get_regular_price() devuelven vacío → wc_price('') = 0€.
            // Fallback al precio directo (_price) que es el que sí está definido.
            if ($sale_price === '' || $sale_price === null || (float)$sale_price <= 0) {
                $sale_price = $product->get_price();
            }
            if ($regular_price === '' || $regular_price === null || (float)$regular_price <= 0) {
                $regular_price = $sale_price;
            }

            $direccion   = get_field('direccion') ?: get_post_meta(get_the_ID(), 'direccion', true);
            $telefono    = get_field('telefono') ?: get_post_meta(get_the_ID(), 'telefono', true);
            $condiciones = get_field('condiciones_generales') ?: get_post_meta(get_the_ID(), 'condiciones_generales', true);
            $mapa_lat = get_field('mapa_lat') ?: get_post_meta(get_the_ID(), 'mapa_lat', true);
            $mapa_lng = get_field('mapa_lng') ?: get_post_meta(get_the_ID(), 'mapa_lng', true);
            $mapa     = get_field('mapa') ?: get_post_meta(get_the_ID(), 'mapa', true);
            // LZ 26/08: si hay campos lat/lng (ACF free) se priorizan; si no, se usa el mapa antiguo (ACF Pro, array)
            if (!empty($mapa_lat) && !empty($mapa_lng)) {
                $mapa = array('lat' => $mapa_lat, 'lng' => $mapa_lng);
            } elseif (is_array($mapa) && isset($mapa['lat'], $mapa['lng'])) {
                $mapa = array('lat' => $mapa['lat'], 'lng' => $mapa['lng']);
            } else {
                $mapa = array(); // sin coordenadas → no se muestra mapa
            }
        ?>
        <div class="bp-single-product">
            <div class="bp-single-gallery">
                <div class="bp-slider">
                    <div class="bp-slider-track">
                        <?php
                        // Main product image (medium_large ~768px: el slider no necesita el original
                        // de 2-3MB — Félix 10/08, optimización de velocidad)
                        echo '<div class="bp-slide">' . $product->get_image('medium_large') . '</div>';
                        // Gallery images
                        $galleries = $product->get_gallery_image_ids();
                        if (!empty($galleries)) {
                            foreach ($galleries as $gid) {
                                echo '<div class="bp-slide">' . wp_get_attachment_image($gid, 'medium_large') . '</div>';
                            }
                        }
                        ?>
                    </div>
                    <div class="bp-slider-dots"></div>
                </div>
            </div>
            <div class="bp-single-summary">
                <!-- Barra sutil de compartir + mapa (27/08) — movida ENCIMA del título (03/09) -->
                <div class="bp-share-bar" role="toolbar" aria-label="Compartir y mapa">
                    <div class="bp-share-group">
                        <button type="button" class="bp-share-btn" data-share="whatsapp" aria-label="Compartir por WhatsApp" title="Compartir por WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                    </div>
                    <div class="bp-share-group bp-share-group-map">
                        <button type="button" class="bp-share-btn bp-share-btn-map" id="bp-btn-mapa-bonos" aria-label="Ver mapa de bonos" title="Ver todos los bonos en el mapa">
                            <i class="fas fa-map-marked-alt"></i> Mapa
                        </button>
                    </div>
                </div>

                <div class="bp-single-categories" style="display: none;">
                    <?php echo wc_get_product_category_list($product->get_id(), ', '); ?>
                </div>
                <h1 class="bp-single-title">
                    <?php echo esc_html($nombre_establecimiento ?: get_the_title()); ?>
                </h1>
                <h2 class="bp-single-name">
                    <?php echo esc_html(get_the_title()); ?>
                </h2>

                <div class="bp-single-price" id="bp-single-price">
                    <?php if ($regular_price && $regular_price != $sale_price) : ?>
                        <span class="bp-price-original"><?php echo wc_price($regular_price); ?></span>
                    <?php endif; ?>
                    <span class="bp-price-sale"><?php echo wc_price($sale_price); ?></span>
                </div>

                <div class="bp-single-cart">
                    <?php
                    // FIX 03/09: en productos SIMPLES el botón de compra es un enlace directo
                    // ?add-to-cart=ID (WooCommerce lo procesa vía GET y el filtro del tema
                    // redirige al checkout). NO depende del submit del form ni de JavaScript:
                    // evita que extensiones del navegador bloqueen el envío y que el botón
                    // "se quede en la misma página". Para VARIABLES se mantiene el form
                    // estándar (necesita seleccionar variación).
                    if ( $product && $product->is_type('simple') ) {
                        // Añadir a la URL actual del producto (como hace WooCommerce)
                        $url_add = esc_url_raw( add_query_arg('add-to-cart', $product->get_id(), get_permalink($product->get_id())) );
                        echo '<a href="' . $url_add . '" rel="nofollow" class="bp-comprar-btn button alt">Comprar</a>';
                    } else {
                        woocommerce_template_single_add_to_cart();
                    }
                    ?>
                </div>
            </div><!-- /.bp-single-summary -->

            <div class="bp-single-summary-rest">

                <h3 class="bp-section-title bp-color-primary">Tu experiencia</h3>
                <?php if (!empty($excerpt)) : ?>
                    <div class="bp-single-desc"><?php echo apply_filters('the_excerpt', $excerpt); ?></div>
                <?php endif; ?>

                <?php if (!empty($condiciones)) : ?>
                <!-- LZ 08/09: condiciones en texto normal (sin colapsable) -->
                <div class="bp-condiciones-panel">
                    <h3 class="bp-condiciones-title">Condiciones</h3>
                    <div class="bp-condiciones-body">
                        <?php echo wp_kses_post($condiciones); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($direccion) || !empty($telefono) || !empty($city)) : ?>
                <div class="bp-contact-info">
                    <?php if (!empty($direccion)) : ?>
                        <p class="bp-contact-item"><i class="fas fa-map-pin"></i> <?php echo esc_html($direccion); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($telefono)) : ?>
                        <p class="bp-contact-item"><i class="fas fa-phone"></i> <a href="tel:<?php echo esc_attr($telefono); ?>"><?php echo esc_html($telefono); ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($mapa['lat']) && !empty($mapa['lng'])) : ?>
                        <p class="bp-contact-item">
                            <i class="fas fa-directions"></i> 
                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($mapa['lat']); ?>,<?php echo esc_attr($mapa['lng']); ?>" target="_blank" rel="noopener">
                                ¿Cómo llegar hasta allí?
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($mapa['lat']) && !empty($mapa['lng'])) : ?>
                <!-- LZ 07/09: mapa del bono con Leaflet (sin iframe) → evita el error
                     cross-origin de openstreetmap.org con extensiones del navegador -->
                <div class="bp-map-wrap">
                    <div class="bp-bono-mapa" data-lat="<?php echo esc_attr($mapa['lat']); ?>" data-lng="<?php echo esc_attr($mapa['lng']); ?>" style="width:100%; height:250px; background:#f5f5f7;"></div>
                </div>
                <?php endif; ?>
            </div><!-- /.bp-single-summary-rest -->
        </div>
        <?php endwhile; ?>

        <!-- Modal mapa de bonos movido al FOOTER (global 03/09) para poder abrirlo
             desde el menú de WP en cualquier página. El botón #bp-btn-mapa-bonos
             de la barra superior sigue funcionando porque el modal vive en footer.php. -->
    </div>

        <?php
        /* Scroller horizontal: bonos relacionados por categoria */
        $related_max = bp_get_setting('related_max_products', 8);
        if ($related_max > 0) {
            $terms = wp_get_post_terms(get_the_ID(), 'product_cat', array('fields' => 'ids'));
            $q = new WP_Query(array(
                'post_type' => 'product',
                'posts_per_page' => $related_max,
                'post__not_in' => array(get_the_ID()),
                'tax_query' => array(array('taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $terms)),
                'meta_query' => array(array('key' => '_stock_status', 'value' => 'instock')),
                'orderby' => 'rand',
            ));
            if ($q->have_posts()) : ?>
            <div class="bp-related-scroller">
                <h3 class="bp-related-scroller-title">Descubre m\u00e1s bonos relacionados</h3>
                <div class="bp-related-scroller-track">
                    <?php while ($q->have_posts()) : $q->the_post(); global $product; ?>
                    <a href="<?php the_permalink(); ?>" class="bp-related-card">
                        <?php echo $product->get_image('medium', array('loading' => 'lazy')); ?>
                        <span class="bp-related-card-title"><?php echo esc_html(get_the_title()); ?></span>
                        <span class="bp-related-card-price"><?php echo $product->get_price_html(); ?></span>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php wp_reset_postdata(); endif;
        }
        ?>

</main>

<style>
.bp-condiciones-panel { }
.bp-condiciones-title { font-size: 25px; font-weight: 100; color: var(--bp-primary); }
.bp-condiciones-body {
    color: var(--bp-text-light); line-height: 1.5;
}
.bp-condiciones-body ul { text-align: justify; }
.bp-condiciones-body ul li { padding-bottom: 15px; }

/* ===== Barra sutil de compartir + mapa (27/08) ===== */
.bp-share-bar {
    display: none; /*flex;*/ align-items: center; justify-content: space-between; gap: 4px;
    padding: 0px; margin: -15px -15px 0px;
    border-top: 0px solid var(--bp-border, #eee);
    border-bottom: 0px solid var(--bp-border, #eee);
}
.bp-share-group { display: flex; align-items: center; gap: 4px; }
.bp-share-group-map { margin-left: auto; }
.bp-share-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border: none; background: transparent; font-weight: bold;
    color: var(--bp-text-light, #8a8a8e); font-size: 20px; cursor: pointer;
    border-radius: 50%; transition: background .2s, color .2s;
}
.bp-share-btn:hover { background: rgba(0,0,0,.05); color: var(--bp-primary, #039CDC); }
.bp-share-divider { width: 1px; height: 18px; background: var(--bp-border, #eee); margin: 0 6px; }
.bp-share-btn-map { width: auto; padding: 0 10px; margin-right: 10px; border-radius: 5px; font-size: 15px; font-weight: bold; gap: 5px; color: var(--bp-text-light, #8a8a8e); }
.bp-share-btn-map:hover { background: rgba(3,156,220,.08); }
</style>

<?php get_footer(); ?>
