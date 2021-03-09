<?php global $ratings;
if (!empty($ratings)):
?>
<section class="single-trip__ratings">
	<h2 class="regular-title">Wat andere reizigers zeggen...</h2>
	<div class="rating-slider">
		<?php foreach ($ratings as $rating): ?>
            <?php if (empty($rating['name'])) continue; ?>
			<div class="rating-slide">
				<span><b><?= $rating['name']; ?> - <?= $rating['age'] ? $rating['age'] . ' jaar - ' : ''; ?></b><?= $rating['date']; ?></span>
				<p><?= $rating['message']; ?></p>
				<div class="rating-list-container">
					<ul class="rating-list">
						<?php
						$countStars = $rating['score'];
						if (is_numeric($countStars)):
                            $emptyStars = 5 - $countStars;
                            for ( $x = 1; $x <= $countStars; $x ++ ) : ?>
                                <li><i class="fas fa-star"></i></li>
                            <?php endfor; ?>

                            <?php for ( $x = 1; $x <= $emptyStars; $x ++ ) : ?>
                                <li><i class="far fa-star"></i></li>
                            <?php endfor; ?>
                        <?php endif; ?>
					</ul>
				</div>
				<div class="clearfix"></div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>
