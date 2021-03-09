<?php
global $ratings;
if (!empty($ratings)):
    $numRatings = 0;
    $totalScore = 0;
    foreach ($ratings as $rating) {
        if (is_numeric($rating['score'])) {
            $numRatings++;
            $totalScore += $rating['score'];
        }
    }
    if ($numRatings > 0):
        $average = $totalScore / $numRatings;
?>
        <div class="rating-list-container">
            <a href="<?= get_permalink(); ?>#beoordelingen">
                <ul class="rating-list">
                    <?php
                    $emptyStars = 5 - $average;
                    for ($x = 1; $x <= $average; $x++) : ?>
                        <li><i class="fas fa-star"></i></li>
                    <?php endfor; ?>
                    <?php for ($x = 1; $x <= $emptyStars; $x++) : ?>
                        <li><i class="far fa-star"></i></li>
                    <?php endfor; ?>
                </ul>
                <span class="rating-title"><?= $numRatings; ?> beoordeling<?= $numRatings === 1 ? '' : 'en'; ?></span>
            </a>
        </div>
    <?php endif; ?>
<?php endif; ?>