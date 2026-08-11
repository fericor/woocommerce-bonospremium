<?php
/**
 * Template Name: Formulario Recibir ofertas
 * Envía a info@bonospremium.com (configurable en functions.php)
 * Félix 11/08: rediseñado como la web original de bonospremium
 * (solo campo Email + botón Suscribirme + texto legal).
 */
get_header(); ?>

<main class="bp-main-content">
    <div class="bp-container bp-page-content">
        <div class="bp-contact-page">
            <div class="bp-contact-header">
                <h1 class="bp-page-title">Recibir puntualmente nuevas ofertas</h1>
            </div>
            <div class="bp-contact-body">
                <?php bp_form_success('ofertas'); ?>
                <form id="bp-form-ofertas" class="bp-form" method="post">
                    <input type="hidden" name="bp_form_submit" value="ofertas" />
                    <?php wp_nonce_field('bp_form_ofertas', 'bp_form_nonce'); ?>
                    <?php bp_form_field('email', 'email', 'Email'); ?>
                    <p class="bp-form-row">
                        <button type="submit" class="bp-form-submit">Suscribirme</button>
                    </p>
                </form>
                <p class="bp-form-legal">** Tu correo será registrado para uso exclusivo de BonosPremium, no cedemos a terceras entidades tu información. Te enviaremos de manera regular nuestras mejores experiencias, promociones y descuentos exclusivos para suscriptores.</p>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
