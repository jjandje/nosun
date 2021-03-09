<header class="site-header">
	<div class="container">
		<div class="inner">
			<div class="header-menu">
				<?php
				if ( has_nav_menu( 'top_navigation' ) ) :
					wp_nav_menu( [
						'theme_location' => 'top_navigation',
						'menu_id'        => '',
						'menu_class'     => 'top-menu',
						'container'      => ''
					] );
				endif;
				?>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="inner">
			<div class="brand">
				<a href="<?= get_site_url(); ?>" title="Home"><img src="<?= get_stylesheet_directory_uri(); ?>/dist/images/logo.svg" alt="logo noSun Groepsreizen"></a>
				<span class="brand__quote">Outdoor groepsreizen <br />voor individuen</span>
			</div>
			<div class="header-cta">
				<span class="btn btn--blue"><?= get_field( 'company_phone', 'option' ); ?></span>
				<a href="<?php echo get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ); ?>" class="btn btn--blue">Mijn account</a>
				<?php if ( is_user_logged_in() ): ?>
					<a href="<?php echo wp_logout_url( home_url() ); ?>" class="btn btn--blue btn-logout" title="Afmelden"><i class="fas fa-sign-out-alt"></i></a>
				<?php endif; ?>
				<ul class="header-cta__som">
					<?php if ( get_field( 'option_social_media_twitter', 'option' ) ): ?>
						<li>
							<a href="<?php the_field( 'option_social_media_twitter', 'option' ); ?>" target="_blank" title="Volg ons op Twitter!"><i class="fab fa-twitter"></i></a>
						</li>
					<?php endif; ?>
					<?php if ( get_field( 'option_social_media_facebook', 'option' ) ): ?>
						<li>
							<a href="<?php the_field( 'option_social_media_facebook', 'option' ); ?>" target="_blank" title="Volg ons op Facebook!"><i class="fab fa-facebook-f"></i></a>
						</li>
					<?php endif; ?>
					<?php if ( get_field( 'option_social_media_instagram', 'option' ) ): ?>
						<li>
							<a href="<?php the_field( 'option_social_media_instagram', 'option' ); ?>" target="_blank" title="Volg ons op Instagram!"><i class="fab fa-instagram"></i></a>
						</li>
					<?php endif; ?>
					<?php if ( get_field( 'option_social_media_youtube', 'option' ) ): ?>
						<li>
							<a href="<?php the_field( 'option_social_media_youtube', 'option' ); ?>" target="_blank" title="Volg ons op YouTube!"><i class="fab fa-youtube"></i></a>
						</li>
					<?php endif; ?>
					<?php if ( get_field( 'option_social_media_linkedin', 'option' ) ): ?>
						<li>
							<a href="<?php the_field( 'option_social_media_linkedin', 'option' ); ?>" target="_blank" title="Volg ons op LinkedIN!"><i class="fab fa-linkedin"></i></a>
						</li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="menu-button menu-btn">
				<div class="hambergerIcon "></div>
			</div>
			<nav class="pushy pushy-left">
				<ul class="pushy__menu">
					<li>
					<a href="tel:<?= get_field( 'company_phone', 'option' ); ?>"><i class="fas fa-phone"></i></a>
					</li>
					<li><a href="<?php echo get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ); ?>"><i class="fas fa-user"></i></a>
					</li>
					<?php if ( is_user_logged_in() ): ?>
						<li><a href="<?php echo wp_logout_url( home_url() ); ?>" title="Afmelden"><i class="fas fa-sign-out-alt"></i></a></li>
					<?php endif; ?>
					<div class="clearfix"></div>
				</ul>

				<?php
				if ( has_nav_menu( 'mobile_navigation' ) ) :
					wp_nav_menu( [
						'theme_location' => 'mobile_navigation',
						'menu_id'        => 'mobileNav',
						'menu_class'     => '',
						'container'      => ''
					] );
				endif;
				?>

				<ul class="pushy__som">
					<?php if ( get_field( 'option_social_media_twitter', 'option' ) ): ?>
						<li>
							<a href="<?php the_field( 'option_social_media_twitter', 'option' ); ?>" target="_blank" title="Volg ons op Twitter!"><i class="fab fa-twitter"></i></a>
						</li>
					<?php endif; ?>
					<?php if ( get_field( 'option_social_media_facebook', 'option' ) ): ?>
						<li>
							<a href="<?php the_field( 'option_social_media_facebook', 'option' ); ?>" target="_blank" title="Volg ons op Facebook!"><i class="fab fa-facebook-f"></i></a>
						</li>
					<?php endif; ?>
					<?php if ( get_field( 'option_social_media_instagram', 'option' ) ): ?>
						<li>
							<a href="<?php the_field( 'option_social_media_instagram', 'option' ); ?>" target="_blank" title="Volg ons op Instagram!"><i class="fab fa-instagram"></i></a>
						</li>
					<?php endif; ?>
					<div class="clearfix"></div>
				</ul>

			</nav>
			<div class="clearfix"></div>
		</div>
	</div>
	<div class="sub-header">
		<div class="container">
			<div class="sub-header__nav">
				<?php
				if ( has_nav_menu( 'primary_navigation' ) ) :
					wp_nav_menu( [ 'theme_location' => 'primary_navigation', 'menu_class' => 'nav' ] );
				endif;
				?>
			</div>
		</div>
		<span class="mobile-quote">Outdoor groepsreizen voor individuen</span>
	</div>
</header>
