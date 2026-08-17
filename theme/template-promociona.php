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
                <h1 class="bp-page-title"><?php echo bp_form_titulo('promociona'); ?></h1>
            </div>
            <div class="bp-contact-body">
                <?php // bp_form_intro('promociona'); ?>
                <?php bp_form_success('promociona'); ?>
                <form id="bp-form-promociona" class="bp-form" method="post">
                    <input type="hidden" name="bp_form_submit" value="promociona" />
					<p><strong>Datos de contacto</strong></p>
                    <?php wp_nonce_field('bp_form_promociona', 'bp_form_nonce'); ?>
                    <?php bp_form_field('text', 'nombre', 'Nombre'); ?>
                    <?php bp_form_field('text', 'apellidos', 'Apellidos'); ?>
                    <?php bp_form_field('tel', 'telefono', 'Teléfono', false); ?>
                    <?php bp_form_field('email', 'email', 'Email'); ?>
					<p><strong>Datos del Negocio</strong></p>
                    <?php bp_form_field('text', 'negocio', 'Nombre'); ?>
                    <?php // bp_form_field('text', 'direccion', 'Dirección', false); ?>
					<?php
						$opciones = array(
							'Tenerife' => 'Tenerife',
							'Gran Canaria' => 'Gran Canaria',
							'Fuerteventiura' => 'Fuerteventura',
							'Madrid' => 'Madrid'
						);
						bp_form_select('tienda', 'Lugar del negocio', $opciones, 'Tenerife', true, 'class="mi-select"');
					?>
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
