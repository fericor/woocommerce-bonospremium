<?php
/**
 * Template Name: Formulario Promociona tu negocio
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
                <?php bp_form_success('promociona'); ?>
                <form id="bp-form-promociona" class="bp-form" method="post">
                    <input type="hidden" name="bp_form_submit" value="promociona" />
                    <?php wp_nonce_field('bp_form_promociona', 'bp_form_nonce'); ?>
                    <?php bp_form_field('text', 'nombre', 'Nombre'); ?>
                    <?php bp_form_field('email', 'email', 'Email'); ?>
                    <?php bp_form_field('tel', 'telefono', 'Teléfono', false); ?>
                    <?php bp_form_field('text', 'negocio', 'Nombre del negocio'); ?>
                    <?php bp_form_field('text', 'web', 'Web / RRSS', false); ?>
                    <p class="bp-form-row">
                        <label for="mensaje">Cuéntanos sobre tu negocio <span class="bp-form-required">*</span></label>
                        <textarea name="mensaje" id="mensaje" placeholder="Describe tu negocio y lo que ofreces..." required></textarea>
                    </p>
                    <p class="bp-form-row">
                        <button type="submit" class="bp-form-submit">Enviar solicitud</button>
                    </p>
                </form>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
