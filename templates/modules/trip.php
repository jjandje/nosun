<?php

use lib\controllers\Trip;
use Vazquez\NosunAssumaxConnector\Helpers; // @since 26-05-2020

//if (Trip::is_shown()): // @since 26-05-2020
$guides = Trip::get_guides();
$availability = Trip::get_availability();
$confirmed = get_field('trip_confirmed');
$startDate = get_field('trip_start_date');
$price = get_post_meta(get_the_ID(), '_price', true);
$ageGroups = Trip::get_age_groups();
$status = get_field('trip_status');

$today = Helpers::today(); // @since 26-05-2020
?>
<div class="data-block<?= $confirmed ? ' guaranteed' : ''; ?><?= $availability['status'] !== 'red' && ($today->format('Y-m-d') < $startDate) ? ' available' : ''; // @since 26-05-2020 ?>">
	<div class="inner">
		<div class="data-block__header">
			<span>Vertrekdatum: <b><?= date_i18n('j F Y', strtotime($startDate)); ?></b></span>
		</div>
		<div class="data-block__body">
			<span>Groepssamenstelling</span><br/>
			<?php if ($availability['status'] !== 'red' && ($today->format('Y-m-d') < $startDate)) : ?>
				<a href="#data_<?= get_the_ID(); ?>"><b>Bekijk samenstelling</b></a>
			<?php else: ?>
				<b><span>Niet beschikbaar</span></b>
			<?php endif; ?>
			<br/>
			<br/>
			<span>Beschikbaarheid</span><br/>
			<b>
				<?php if( ( $availability['status'] == 'red' && ($today->format('Y-m-d') > $startDate) || $availability['status'] == 'red' && $confirmed === false || $today->format('Y-m-d') > $startDate ) || ( $availability['status'] == 'red' && $confirmed === true && $status === 'Confirmed' )) : ?> <?php // @since 26-05-2020 ?>
					<span class="available available--red left"></span>
					<span class="text-red">Niet meer beschikbaar</span>
				<?php else : ?>
					<span class="available available--<?= $availability['status']; ?> left"></span>
					<span class="text-<?= $availability['status']; ?>"><?= $availability['text']; ?></span>
				<?php endif; ?>
			</b>
			<br/>
			<br/>
			<?php if (!empty($guides)): ?>
				<span>Reisbegeleiders</span><br/>
				<?php foreach ($guides as $guide): ?>
					<?php if (!empty($guide['url'])): ?><a href="<?= $guide['url']; ?>"><b><?= $guide['name']; ?></b></a>
					<?php else: ?><b><?= $guide['name']; ?></b>
					<?php endif; ?>
				<?php endforeach; ?>
				<br/>
			<?php else: ?>
				<span>&nbsp;</span><br/>
				<b>&nbsp;</b>
				<br/>
			<?php endif; ?>
			<?php if ($confirmed && $availability['status'] !== 'red' && ($today->format('Y-m-d') < $startDate)): ?> <?php // @since 26-05-2020; ?>
				<span class="guaranteed-departure">Gegarandeerd vertrek</span>
			<?php endif; ?>
			<span class="data-price">€<?= number_format_i18n($price); ?>,-</span>

			<a href="<?= get_the_permalink(); ?>" class="btn btn--pink btn--arrow-white<?= $availability['status'] == 'red' && ($today->format('Y-m-d') > $startDate) || $availability['status'] == 'red' || $today->format('Y-m-d') > $startDate ? ' btn--disable' : ''; ?>">Boek deze datum</a> <?php // @since 26-05-2020; ?>
		</div>
	</div>

	<?php if($availability['status'] !== 'red' && ($today->format('Y-m-d') < $startDate)) : ?> <?php // @since 26-05-2020; ?>
		<div class="remodal samenstelling" data-remodal-id="data_<?= get_the_ID(); ?>">
			<button data-remodal-action="close" class="remodal-close"></button>
			<span class="title"><?php echo get_the_title() ?></span>
			<span class="date">Vertrekdatum: <b><?= date_i18n('j F Y', strtotime($startDate)); ?></b></span>
			<div class="geslacht">
				<span class="title">Geslacht</span>
				<div class="inner">
					<?php if (empty($ageGroups)): ?>
						Nog geen informatie beschikbaar
					<?php else: ?>
						<div class="mannen">
							<span class="title">Mannen</span>
							<?php
							for ($i = 0; $i < $ageGroups['men_count']; $i++) {
								echo '<i class="fa fa-circle" aria-hidden="true"></i>';
							}
							?>
						</div>
						<div class="vrouwen">
							<span class="title">Vrouwen</span>
							<?php
							for ($i = 0; $i < $ageGroups['women_count']; $i++) {
								echo '<i class="fa fa-circle" aria-hidden="true"></i>';
							}
							?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="leeftijd">
				<span class="title">Leeftijd</span>
				<div class="inner">
					<?php if (empty($ageGroups)): ?>
						Nog geen informatie beschikbaar
					<?php else: ?>
						<div class="age2029">
							<span class="title">20 - 29</span>
							<?php
							if (key_exists('20-29', $ageGroups['men'])) {
								for ($i = 0; $i < $ageGroups['men']['20-29']; $i++) {
									echo '<img class="male" src="' . get_stylesheet_directory_uri() . '/dist/images/male.png"/>';
								}
							}
							if (key_exists('20-29', $ageGroups['women'])) {
								for ($i = 0; $i < $ageGroups['women']['20-29']; $i++) {
									echo '<img class="female" src="' . get_stylesheet_directory_uri() . '/dist/images/female.png"/>';
								}
							}
							?>
						</div>
						<div class="age3039">
							<span class="title">30 - 39</span>
							<?php
							if (key_exists('30-39', $ageGroups['men'])) {
								for ($i = 0; $i < $ageGroups['men']['30-39']; $i++) {
									echo '<img class="male" src="' . get_stylesheet_directory_uri() . '/dist/images/male.png"/>';
								}
							}
							if (key_exists('30-39', $ageGroups['women'])) {
								for ($i = 0; $i < $ageGroups['women']['30-39']; $i++) {
									echo '<img class="female" src="' . get_stylesheet_directory_uri() . '/dist/images/female.png"/>';
								}
							}
							?>
						</div>
						<div class="age4049">
							<span class="title">40 - 49</span>
							<?php
							if (key_exists('40-49', $ageGroups['men'])) {
								for ($i = 0; $i < $ageGroups['men']['40-49']; $i++) {
									echo '<img class="male" src="' . get_stylesheet_directory_uri() . '/dist/images/male.png"/>';
								}
							}
							if (key_exists('40-49', $ageGroups['women'])) {
								for ($i = 0; $i < $ageGroups['women']['40-49']; $i++) {
									echo '<img class="female" src="' . get_stylesheet_directory_uri() . '/dist/images/female.png"/>';
								}
							}
							?>
						</div>
						<div class="age5059">
							<span class="title">50 - 59</span>
							<?php
							if (key_exists('50-59', $ageGroups['men'])) {
								for ($i = 0; $i < $ageGroups['men']['50-59']; $i++) {
									echo '<img class="male" src="' . get_stylesheet_directory_uri() . '/dist/images/male.png"/>';
								}
							}
							if (key_exists('50-59', $ageGroups['women'])) {
								for ($i = 0; $i < $ageGroups['women']['50-59']; $i++) {
									echo '<img class="female" src="' . get_stylesheet_directory_uri() . '/dist/images/female.png"/>';
								}
							}
							?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="center">
				<?php if ($availability['status'] !== 'red' && ($today->format('Y-m-d') < $startDate)): ?> <?php // @since 26-05-2020; ?>
					<strong>Ga je met ons mee?</strong>
					<a href="<?= get_the_permalink(); ?>"
					   class="btn btn--pink btn--arrow-white">Boek nu</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
<?php // endif; @since 26-05-2020 ?>
