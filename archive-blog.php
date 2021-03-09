<?php use lib\controllers\Blog;

get_template_part('templates/page-header'); ?>
<section class="blog-blocks">
    <?php
    $terms = get_terms(array(
        'taxonomy' => 'blog_categorie',
        'hide_empty' => false,
    ));
    $queriedObject = get_queried_object();
    $termId = null;
    if ($queriedObject instanceof WP_Term) {
        $termId = $queriedObject->term_id;
    }
    if (!empty($terms)): ?>
        <div class="container">
            <div class="blog-filters" style="min-height: 100px;">
                <ul>
                    <li>
                        <a href="<?= get_post_type_archive_link('blog'); ?>"
                           class="<?= empty($termId) ? 'active' : ''; ?>">Alle</a>
                    </li>
                    <?php foreach ($terms as $term):
                        ?>
                        <li>
                            <a href="<?= get_term_link($term->term_id, 'blog_categorie'); ?>"
                               class="<?= $termId === $term->term_id ? 'active' : ''; ?>"><?= $term->name; ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if (have_posts()) : ?>
        <div class="container">
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('templates/content', get_post_type() != 'post' ? get_post_type() : get_post_format()); ?>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
    <div class="pagination-container">
        <div class="inner">
            <?php Blog::nosun_numeric_posts_nav(); ?>
        </div>
    </div>
</section>