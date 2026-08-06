<?php
/**
 * Template Name: Formulario Recibir ofertas
 * Envía a info@bonospremium.com (configurable en functions.php)
 */
get_header(); ?>

<main class="bp-main-content">
    <div class="bp-container bp-page-content">
        <div class="bp-contact-page">
            <div class="bp-contact-header">
                <h1 class="bp-page-title"><?php the_title(); ?></h1>
            </div>
            <div class="bp-contact-body">
                <?php the_content(); ?>
                <?php bp_form_success('ofertas'); ?>
                <form id="bp-form-ofertas" class="bp-form" method="post">
                    <input type="hidden" name="bp_form_submit" value="ofertas" />
                    <?php wp_nonce_field('bp_form_ofertas', 'bp_form_nonce'); ?>
                    <?php bp_form_field('text', 'nombre', 'Nombre'); ?>
                    <?php bp_form_field('email', 'email', 'Email'); ?>
                    <?php bp_form_field('text', 'ciudad', 'Ciudad'); ?>
                    <p class="bp-form-row">
                        <button type="submit" class="bp-form-submit">Quiero recibir ofertas</button>
                    </p>
                </form>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
