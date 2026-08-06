<?php
/**
 * Template Name: Página de contacto
 * Plantilla para formularios de contacto, promociona negocio, recibir ofertas
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
            </div>
        </div>
    </div>
</main>

<style>
.bp-contact-page {
    max-width: 600px;
    margin: 0 auto;
    padding: 30px 0;
}
.bp-contact-body input[type="text"],
.bp-contact-body input[type="email"],
.bp-contact-body input[type="tel"],
.bp-contact-body textarea,
.bp-contact-body select {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: .95rem;
    background: #fff;
    transition: border-color .2s;
    margin-bottom: 15px;
    box-sizing: border-box;
    font-family: inherit;
}
.bp-contact-body input:focus,
.bp-contact-body textarea:focus {
    border-color: var(--bp-primary);
    outline: none;
}
.bp-contact-body textarea {
    min-height: 120px;
    resize: vertical;
}
.bp-contact-body input[type="submit"],
.bp-contact-body button[type="submit"],
.bp-contact-body .wpcf7-submit {
    width: 100%;
    background: var(--bp-primary);
    color: #fff;
    border: none;
    padding: 16px;
    border-radius: 12px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s;
}
.bp-contact-body input[type="submit"]:hover,
.bp-contact-body button[type="submit"]:hover,
.bp-contact-body .wpcf7-submit:hover {
    background: var(--bp-primary-dark);
    transform: translateY(-1px);
}
.bp-contact-body label {
    font-size: .85rem;
    font-weight: 600;
    color: #555;
    margin-bottom: 4px;
    display: block;
}
.wpcf7-form {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    border: 1px solid #f0f0f0;
}
.wpcf7-form p { margin-bottom: 0; }
.wpcf7-not-valid-tip { font-size: .8rem; color: #ff3b30; margin-top: -10px; margin-bottom: 10px; }
.wpcf7-response-output { margin: 20px 0 0 !important; border-radius: 10px !important; font-size: .9rem; }
@media (max-width: 768px) {
    .bp-contact-page { padding: 15px; }
    .wpcf7-form { padding: 20px; }
}
</style>

<?php get_footer(); ?>
