<?php
/**
 * Template Name: Contact
 */
?>

<div class="page-header-image" style="background-image: url(<?= get_stylesheet_directory_uri(); ?>/dist/images/contact-header.png);">
	<h1><?= get_the_title(); ?></h1>
</div>
<div class="contact-wrapper">
	<div class="container">
		<h2 class="page-title-border">Kunnen wij je helpen?</h2>
		<p style="display: block; text-align: center">Gebruik onderstaand formulier om een berichtje te sturen</p>
		<div class="contact-wrapper__form">
            <!--[if lte IE 8]>
            <script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2-legacy.js"></script>
            <![endif]-->
            <script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2.js"></script>
            <script>
                hbspt.forms.create({
                    portalId: "4965403",
                    formId: "38a41d85-f198-4969-a65b-7d1c33068107"
                });
            </script>
		</div>
	</div>
	<?php if ( have_rows( 'flex_contact' ) ):
		while ( have_rows( 'flex_contact' ) ) : the_row();
			if ( get_row_layout() == 'flex_contact_faq' ) : ?>
				<div class="contact-wrapper__faq">
					<div class="container">
						<h2><?php the_sub_field( 'flex_contact_faq_title' ); ?></h2>
						<?php if ( get_sub_field( 'flex_contact_faq_button_url' ) ): ?>
							<a href="<?php the_sub_field( 'flex_contact_faq_button_url' ); ?>" class="btn btn--yellow btn--arrow-blue"><?php the_sub_field( 'flex_contact_faq_button_text' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif;
		endwhile;
	endif; ?>
	<div class="contact-wrapper__company">
		<div class="container">
			<h2>
				Gegevens
			</h2>
			<div class="contact-wrapper__column">
				<p>
					<b>noSun Reizen BV</b><br />
					<?php the_field( 'company_postbus', 'option' ); ?><br />
					<?php the_field( 'company_zipcode', 'option' ); ?> <?php the_field( 'company_city', 'option' ); ?>
					<br />
					<br />
					<a href="mailto:<?php the_field( 'company_mail', 'option' ); ?>" title="Mail ons"><i class="far fa-envelope"></i> <?php the_field( 'company_mail', 'option' ); ?>
					</a><br />
					<a href="tel:<?php the_field( 'company_phone', 'option' ); ?>"><i class="fas fa-phone"></i> <?php the_field( 'company_phone', 'option' ); ?>
					</a><br />
					<span class="small"><?php the_field( 'company_available', 'option' ); ?></span>
				</p>

				<?php the_field( 'company_lat', 'option' ); ?>
				<?php the_field( 'company_long', 'option' ); ?>
			</div>
			<div class="contact-wrapper__column">
				<p>
					<b>Bezoekadres</b><br />
					<span>noSun reizen BV</span><br />
					<?php the_field( 'company_v_street', 'option' ); ?><br />
					<?php the_field( 'company_v_zipcode', 'option' ); ?> <?php the_field( 'company_v_city', 'option' ); ?>
					<br />
					<br />
					<?php the_field( 'company_kvk', 'option' ); ?><br />
					<?php the_field( 'company_btw', 'option' ); ?><br />
					<?php the_field( 'company_iban', 'option' ); ?><br />
				</p>
			</div>
		</div>
	</div>

	<div id="nosun-map"></div>
</div>