<?php
// Index - fallback template
get_header(); ?>
<main class="bp-main-content">
    <div class="bp-container">
        <?php
        if (class_exists('WooCommerce') && (is_shop() || is_product_category() || is_product_tag() || is_product())) {
            woocommerce_content();
        } elseif (have_posts()) {
            while (have_posts()) {
                the_post();
                the_content();
            }
        }
        ?>
    </div>
</main>
<?php get_footer(); ?>
