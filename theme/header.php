<?php
/**
 * Header template
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php get_template_part('loader'); ?>
<?php wp_body_open(); ?>

<header class="bp-header">
    <div class="bp-header-top">
        <div class="bp-container bp-header-inner">
            <div class="bp-header-left">
                <button class="bp-menu-toggle" aria-label="Menú">
                    <span></span><span></span><span></span>
                </button>
                <button class="bp-back-btn" aria-label="Volver">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <nav class="bp-user-nav">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'user-menu',
                        'container'      => false,
                        'menu_class'     => 'bp-user-nav-menu',
                        'depth'          => 2,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </nav>
            </div>

            <div class="bp-logo">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/logo_rectangulo.png" alt="BonosPremium" class="bp-logo-img" />
                </a>
            </div>

            <div class="bp-header-right">
                <div class="bp-header-search-desktop">
                    <?php if (class_exists('WooCommerce')): ?>
                        <form role="search" method="get" class="bp-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                            <input type="search" placeholder="Buscar ofertas..." value="<?php echo get_search_query(); ?>" name="s" />
                            <input type="hidden" name="post_type" value="product" />
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (class_exists('WooCommerce')): ?>
                <?php
                // Link del corazón -> lista de deseos (página wishlist del plugin smart-wishlist /favoritos/)
                $bp_wishlist_url = function_exists('wlfmc_get_wishlist_url') ? wlfmc_get_wishlist_url() : home_url('/favoritos/');
                ?>
                <a href="<?php echo esc_url($bp_wishlist_url); ?>" class="bp-header-action bp-wishlist-icon" title="Lista de deseos">
                    <i class="far fa-heart"></i>
                </a>
                <?php endif; ?>
                <button class="bp-header-action bp-search-toggle bp-search-mobile" title="Buscar">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <!-- Search overlay -->
        <div class="bp-search-overlay">
            <div class="bp-search-overlay-inner">
                <?php if (class_exists('WooCommerce')): ?>
                    <form role="search" method="get" class="bp-search-form bp-search-form-overlay" action="<?php echo esc_url(home_url('/')); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                        <input type="search" placeholder="Buscar ofertas..." value="<?php echo get_search_query(); ?>" name="s" autofocus />
                        <input type="hidden" name="post_type" value="product" />
                        <button type="button" class="bp-search-close"><i class="fas fa-times"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<nav class="bp-nav">
    <div class="bp-container">
        <?php
        wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'bp-nav-menu',
            'fallback_cb'    => 'bp_lz_fallback_menu',
            'depth'          => 1,
        ]);
        ?>
    </div>
</nav>

<?php
// Fallback menu si no hay menú asignado
function bp_lz_fallback_menu() {
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
    if (!empty($categories)) {
        echo '<ul class="bp-nav-menu">';
        echo '<li><a href="' . get_permalink(wc_get_page_id('shop')) . '">Todo</a></li>';
        foreach ($categories as $cat) {
            echo '<li><a href="' . get_term_link($cat) . '">' . $cat->name . '</a></li>';
        }
        echo '</ul>';
    }
}
