<?php
global $accommodation;

/** @var WP_Post $accommodation */
if (!empty($accommodation)): ?>
    <div class="clearfix"></div>
    <ul>
        <?php
        $images = get_field('accommodation_images', $accommodation->ID);
        if (!empty($images)):
            foreach ($images as $imageField):
                if (empty($imageField['image'])) continue;
                ?>
                    <li>
                        <a href="<?= wp_get_attachment_image_url($imageField['image'], 'full'); ?>"
                           data-lightbox="accommodations"><img
                                src="<?= wp_get_attachment_image_url($imageField['image'], 'woocommerce_thumbnail'); ?>"
                                alt="">
                        </a>
                    </li>
                <?php
            endforeach;
        endif;
        ?>
    </ul>
    <div class="clearfix"></div>
    <div class="acco_desc">
        <div class="title">
            <strong><?php the_field('accommodation_title', $accommodation->ID); ?></strong>
        </div>
        <?php if (!empty($accommodation->post_content)): ?>
            <p><strong>Omschrijving: </strong><?= $accommodation->post_content ?></p>
        <?php endif; ?>
        <?php $rooms = get_field('accommodation_rooms', $accommodation->ID); ?>
        <?php if (!empty($rooms)): ?>
            <p><strong>Slaapsituatie: </strong><?= $rooms; ?></p>
        <?php endif; ?>
    </div>
    <div class="clearfix"></div>
<?php endif; ?>
