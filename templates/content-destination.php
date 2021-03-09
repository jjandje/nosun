<div class="trip-blocks__trip trip-blocks__trip--small">
	<a class="trip-blocks__image " href="<?= get_the_permalink(); ?>">
        <?php         $imageId = get_post_meta(get_the_ID(), '_thumbnail_id', true);
        $imageAlt = get_post_meta($imageId, '_wp_attachment_image_alt', true);
        if (empty($imageAlt)) $imageAlt = get_the_title();
        ?>
		<img src="<?php echo get_the_post_thumbnail_url( get_the_ID(), 'popular-trip-thumb' ); ?>" alt="<?= $imageAlt; ?>">
		<span class="image-title"><?= get_the_title(); ?></span>
	</a>
</div>
