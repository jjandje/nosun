<?php
use Roots\Sage\Assets;

if (have_rows('option_vlogs', 'option')) : ?>
    <section class="vlogs">
        <div class="container">
            <?php $i = 0;
            while (have_rows('option_vlogs', 'option')) : the_row(); ?>
                <?php $i++;
                preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", get_sub_field('vlog_youtube_url'), $matches);
                $youtube_code = trim($matches[1]);
                $image = wp_get_attachment_image_src(get_sub_field('vlog_image'), 'large')[0]; ?>
                <a class="vlog" href="#youtube_modal_<?php echo $i; ?>">
                    <div class="vlog__inner">
                        <div class="image" style="background-image: url('<?php echo $image; ?>');">
                            <img src="<?= Assets\asset_path('images/Playknop_noSun.svg'); ?>" alt="Afspelen"
                                 class="play-button">
                        </div>
                        <span class="title">VLOG <i>-</i> <?php the_sub_field('vlog_title'); ?></span>
                        <span><?php the_sub_field('vlog_subtitle'); ?></span>
                    </div>
                </a>

                <div class="remodal" data-remodal-id="youtube_modal_<?php echo $i; ?>">
                    <button data-remodal-action="close" class="remodal-close"></button>
                    <div class="videoWrapper">
                        <iframe width="560" height="349"
                                src="http://www.youtube.com/embed/<?= $youtube_code; ?>?rel=0&hd=1" frameborder="0"
                                allowfullscreen></iframe>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
<?php endif; ?>
