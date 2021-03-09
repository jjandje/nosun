<?php while ( have_posts() ) {
	the_post();
	get_template_part( 'templates/content', 'page' );

	if ( is_account_page() ) {
		the_content();
	}
}

