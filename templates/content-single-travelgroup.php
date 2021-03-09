<?php
use lib\controllers\Template;
use Roots\Sage\Assets;
use Roots\Sage\Helpers;
use Vazquez\NosunAssumaxConnector\Api\TravelGroups as TravelGroupAPI;
use lib\controllers\TravelGroup;

$subGroups = TravelGroupAPI::get_user_subgroups(get_the_ID());
$accessType = 'none';
if (!empty($subGroups['subgroups'])) {
    $accessType = $subGroups['access_type'];
}
get_template_part( 'templates/page-header-regular' );

if ($accessType !== 'none'):
    $today = Helpers::today();
    foreach ($subGroups['subgroups'] as $subGroup):
        $participants = TravelGroup::get_participant_information(get_the_ID(), $subGroup);
        $trip = get_field('travelgroup_trip');
        $tripStartDate = get_field('trip_start_date', $trip);
        $tripEndDate = get_field('trip_end_date', $trip);
        $template = get_field('trip_template', $trip);
        $tripWelcomeText = get_field('travelgroup_welcometext');
        $templateDestination = Template::get_primary_destination($template); ?>
        <div class="container travelgroup chatContainer" data-subgroup="<?= $subGroup; ?>" data-travelgroup="<?= get_the_ID(); ?>">
            <div class="chatSummary">
                <h2><i class="fas fa-users"></i> Deelnemers<?php if ($accessType !== 'customer') echo " groep {$subGroup}"; ?></h2>
                <ul>
                    <?php foreach ($participants['customers'] as $assumaxId => $customer): ?>
                        <?php
                        $isCurrentUser = false;
                        $idString = '';
                        if (!empty($customer['user_id'])) {
                            $isCurrentUser = get_current_user_id() === intval($customer['user_id']);
                            $idString = "id=\"customer_{$customer['user_id']}\"";
                        }
                        ?>
                        <li <?= $idString; ?> data-is-current-user="<?= $isCurrentUser ? 1 : 0; ?>">
                            <?php if (!empty($customer['profile_image'])):
                                echo wp_get_attachment_image($customer['profile_image'], 'thumbnail', false, array('style' => 'max-width: 96px;max-height: 96px;'));
                            else: ?>
                                <img src="<?= Assets\asset_path('images/no-profile-image.png'); ?>"
                                     style="max-width: 96px;max-height: 96px;"
                                     alt="Geen profielfoto"/>
                            <?php endif; ?>
                            <?php  ?>
                            <div class="chatSummary__user">
                                <span class="chatSummary__name"><?= $customer['nickname']; ?><?php if ($isCurrentUser) echo " (Jij)"; ?></span>
                                <?php if (!empty($customer['date_of_birth']) && !empty($today)):
                                    $birthDate = Helpers::create_local_datetime($customer['date_of_birth']);
                                    $age = $birthDate->diff($today); ?>
                                    <span class="chatSummary__age"><?= $age->y; ?> jaar</span>
                                <?php endif; ?>
                            </div>
                            <div class="clearfix"></div>
                        </li>
                    <?php endforeach; ?>

                    <?php foreach ($participants['guides'] as $assumaxId => $guide): ?>
                        <?php
                        $isCurrentUser = false;
                        $idString = '';
                        if (!empty($guide['user_id'])) {
                            $isCurrentUser = get_current_user_id() === intval($guide['user_id']);
                            $idString = "id=\"tourguide_{$guide['user_id']}\"";
                        }
                        ?>
                        <li <?= $idString; ?> data-is-current-user="<?= $isCurrentUser ? 1 : 0; ?>">
                            <?php if (!empty($guide['profile_image'])):
                                echo wp_get_attachment_image($guide['profile_image'], 'thumbnail', false, array('style' => 'max-width: 96px;max-height: 96px;'));
                            else: ?>
                                <img src="<?= Assets\asset_path('images/no-profile-image.png'); ?>"
                                     style="max-width: 96px;max-height: 96px;"
                                     alt="Geen profielfoto"/>
                            <?php endif; ?>
                            <?php $isCurrentUser = get_current_user_id() === intval($guide['user_id']) ?>
                            <div class="chatSummary__user">
                                <span class="chatSummary__name"><?= $guide['nickname']; ?><?php if ($isCurrentUser) echo " (Jij)"; ?></span>
                                <?php if (!empty($guide['date_of_birth']) && !empty($today)):
                                    $birthDate = Helpers::create_local_datetime($guide['date_of_birth']);
                                    $age = $birthDate->diff($today); ?>
                                    <span class="chatSummary__age"><?= $age->y; ?> jaar</span>
                                <?php endif; ?>
                            </div>
                            <span class="chatSummary__guide">Reisbegeleider</span>
                            <div class="clearfix"></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="border"></div>
                <div class="tour-information__row">
                    <h2><i class="fas fa-plane"></i> Reisinformatie</h2>
                    <p style="font-weight: bold;font-size: 17px;"><?= get_the_title($trip); ?></p>
                    <table class="tour-information__tour">
                        <tbody>
                        <?php if (!empty($tripStartDate)): ?>
                        <tr>
                            <td>
                                Reisdatum:
                            </td>
                            <td>
                                <strong><?= date_i18n('d-m-Y', strtotime($tripStartDate)); ?></strong>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($tripEndDate)): ?>
                        <tr>
                            <td>
                                Terugkomstdatum:
                            </td>
                            <td>
                                <strong><?= date_i18n('d-m-Y', strtotime($tripEndDate)); ?></strong>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($templateDestination)): ?>
                        <tr>
                            <td>
                                Bestemming:
                            </td>
                            <td>
                                <strong><?= $templateDestination->name; ?></strong>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>
                                Aantal dagen:
                            </td>
                            <td><strong><?= get_field('trip_num_days', $trip); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="chatWrapper">
                <div class="chatWrapper-inner">
                    <div class="chat-container">
						<?php if($tripWelcomeText) : ?>
						<div class="message message--nosun" id="#message-0">
							<div class="message__profile">
								<img src="<?= Assets\asset_path('images/no-profile-image.png'); ?>"
									 style="max-width: 96px;max-height: 96px;"
									 alt="Geen profielfoto"/>
								<span>noSun</span>
							</div>
							<div class="inner"><?php echo $tripWelcomeText; ?></div>
						</div>
						<?php endif; ?>
					</div>
                </div>

                <div class="form nosun-form ajaxChat">
                    <!-- <div class="loading"><i class="fas fa-circle-notch fa-spin"></i> Een moment geduld...</div>-->
                    <textarea name="message" placeholder="Typ hier je bericht..."></textarea>
                    <input type="submit" class="btn btn--blue btn--padding" value="Verzenden">
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <div id="chatMessagePlaceholder" data-nonce="<?= wp_create_nonce('travelgroups'); ?>" data-no-image="<?= Assets\asset_path('images/no-profile-image.png'); ?>">
        <div class="message">
            <div class="message__profile">
                <span></span>
            </div>
            <div class="inner">
                <span></span>
            </div>
        </div>
        <div class="clearfix"></div>
        <div id="noProfileImage"><?= '<img src="' . Assets\asset_path( 'images/no-profile-image.png' ) . '"  />'; ?></div>
    </div>
<?php else: ?>
    <div class="container">
        <div class="message">
            <div class="inner" style="color: #a94442; background-color: #f2dede; border-color: #ebccd1;">
                <i class="fa fa-times"></i> <span>Je hebt geen toegang tot deze reisgroep.</span>
            </div>
        </div>
    </div>
<?php endif; ?>
