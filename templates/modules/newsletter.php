<?php if (have_rows('newsletter_blocks', 'option')) : ?>
    <section class="newsletter-container">
        <div class="container">
            <?php while (have_rows('newsletter_blocks', 'option')) : the_row(); ?>
                <div class="newsletter-text-block">
                    <div class="inner">
                        <i class="fa fa-long-arrow-alt-right"></i>
                        <p><?php the_sub_field('newsletter_blocks_text'); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
            <?= do_shortcode('[gravityform id=3 title=false description=false ajax=true]'); ?>
            <div class="clearfix"></div>
        </div>
    </section>
<?php endif; ?>
