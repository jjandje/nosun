<footer class="site-footer">
	<img class="logo-wave" src="<?= get_stylesheet_directory_uri(); ?>/dist/images/logo-wave.svg" alt="wave logo artikel">
	<div class="container">
		<div class="chat-box">
			<?php if(!is_front_page()) : ?><span class="chat-box__title">Stel live je vraag <br/>aan Tjalyna!</span><?php endif; ?>
			<a href="" onclick="parent.LC_API.open_chat_window({source:'minimized'})" class="btn btn--yellow live-chat">Chat live met ons</a>
			<!-- Start of LiveChat Code -->
			<script type="text/javascript">
                var __lc = {};
                __lc.license = 3983961;
                (function() {
                    var lc = document.createElement('script'); lc.type = 'text/javascript'; lc.async = true;
                    lc.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'cdn.livechatinc.com/tracking.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(lc, s);
                })();
			</script>

			<!-- End of LiveChat Code -->
		</div>

		<div class="footer-text">
			<h2><?php the_field('option_footer_title', 'option'); ?></h2>
			<?php the_field('option_footer_text', 'option'); ?>
		</div>

		<div class="row">

			<div class="footer-column">
				<a href="<?= get_site_url(); ?>" class="site-footer__brand"><img src="<?= get_stylesheet_directory_uri(); ?>/dist/images/logo-white.svg" alt="noSun"></a>
				<span class="footer-column__title">noSun reizen BV</span>
				<ul>
					<li><?php the_field( 'company_v_street', 'option' ); ?></li>
					<li><?php the_field( 'company_v_zipcode', 'option' ); ?> <?php the_field( 'company_v_city', 'option' ); ?></li>
					<li><a href="tel:<?php the_field( 'company_phone', 'option' ); ?>"><?php the_field( 'company_phone', 'option' ); ?></a></li>
					<li><a href="<?= site_url('/contact/'); ?>" class="footer-column__read-more">Alle contact informatie</a></li>
				</ul>
			</div>
			<div class="footer-column">
				<span class="footer-column__title">Bestemmingen</span>
				<?php
				if ( has_nav_menu( 'footer_menu_trips' ) ) :
					wp_nav_menu( [
						'theme_location' => 'footer_menu_trips',
						'menu_id'        => '',
						'menu_class'     => 'footer-menu',
						'container'      => ''
					] );
				endif;
				?>
			</div>
			<div class="footer-column">
				<span class="footer-column__title">Reistypes</span>
				<?php
				if ( has_nav_menu( 'footer_menu_nosun' ) ) :
					wp_nav_menu( [
						'theme_location' => 'footer_menu_nosun',
						'menu_id'        => '',
						'menu_class'     => 'footer-menu',
						'container'      => ''
					] );
				endif;
				?>
				<span class="footer-column__title">Sitemap</span>
				<?php
				if ( has_nav_menu( 'footer_menu_account' ) ) :
					wp_nav_menu( [
						'theme_location' => 'footer_menu_account',
						'menu_id'        => '',
						'menu_class'     => 'footer-menu',
						'container'      => ''
					] );
				endif;
				?>
			</div>

			<div class="footer-column">
				<div class="subscribe">
					<span class="footer-column__title footer-column__title--striped">noNieuwsbrief</span>
          <?php the_field('option_footer_newsletter_text', 'option'); ?>

					<?= do_shortcode( '[gravityform id=3 title=false description=false ajax=true]' ); ?>
				</div>
				<div class="subscribe subscribe--mobile">
					<span class="footer-column__title">noNieuwsbrief</span>

					<?= do_shortcode( '[gravityform id=3 title=false description=false ajax=true]' ); ?>
				</div>
			</div>
		</div>
	</div>
</footer>

