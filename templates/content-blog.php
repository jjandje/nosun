<div class="blog-blocks__trip">
	<div class="inner">
		<div class="blog-blocks__image">
			<a href="<?= get_the_permalink(); ?>" title="<?= get_the_title(); ?>">
				<img src="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'popular-trip-thumb' ); ?>" alt="<?= get_the_title(); ?>">
			</a>
		</div>
		<ul class="blog-blocks__list">
			<li>
				<i class="fas fa-calendar-alt"></i> <?= get_the_date(); ?>
			</li>
			<li>
				<i class="fas fa-user"></i> <?= get_the_author(); ?>
			</li>
		</ul>

		<a href="<?= get_the_permalink(); ?>" title="<?= get_the_title(); ?>" class="blog-blocks__title"><?= get_the_title(); ?></a>
		<span class="blog-blocks__subtitle-blog"><?= get_field( 'blog_short_description' ); ?></span>

		<a href="<?= get_the_permalink(); ?>" class="btn btn--pink btn--arrow-white btn--absolute">Lees meer</a>
	</div>
</div>