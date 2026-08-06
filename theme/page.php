<?php
// Page template
get_header(); ?>
<main class="bp-main-content">
    <div class="bp-container bp-page-content">
        <?php
        while (have_posts()) {
            the_post();
            the_content();
        }
        ?>
    </div>
</main>
<?php get_footer(); ?>
