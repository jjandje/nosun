<?php
/**
 * Edit account form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use lib\controllers\User;
?>
<div class="nosun-account row-padding">
	<div class="container">
		<?php
        $editAccountData = User::get_user_edit_account_data();

        do_action( 'woocommerce_before_edit_account_form' ); ?>

		<form class="woocommerce-EditAccountForm edit-account nosun-form" action="" method="post">

			<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

			<?php if ( isset( $_GET[ 'profile_image_updated' ] ) ): ?>
				<div class="message">
					<div class="inner">
						<i class="fa fa-user" aria-hidden="true"></i> Profielfoto is bijgewerkt.
					</div>
				</div>
				<div class="clearfix"></div>
			<?php endif; ?>

            <?php if (!empty($editAccountData)): ?>
                <ul class="edit-account__column">
                    <li>
                        <h3>Persoonsgegevens</h3>
                        <label for="account_first_name">Alle voornamen volgens paspoort
                            <span class="required">*</span></label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" required value="<?= $editAccountData['customer']->FirstName; ?>" />
                    </li>
                    <li>
                        <label for="account_display_name"><?php esc_html_e( 'Roepnaam', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" value="<?= $editAccountData['customer']->NickName; ?>" />
                    </li>
                    <li>
                        <label for="account_last_name"><?php esc_html_e( 'Achternaam', 'woocommerce' ); ?>
                            <span class="required">*</span></label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" required value="<?= $editAccountData['customer']->LastName; ?>" />
                    </li>
                    <li>
                        <label for="account_email"><?php esc_html_e( 'Email adres', 'woocommerce' ); ?>
                            <span class="required">*</span></label>
                        <input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" required value="<?= $editAccountData['customer']->EmailAddress; ?>" />
                    </li>
                    <li>
                        <label for="dateofbirth"><?php _e( "Geboortedatum" ); ?>
                            <span class="required">*</span></label>
                        <input type="text" name="dateofbirth" id="dateofbirth" required value="<?= empty($editAccountData['customer']->DateOfBirth) ? '' : date("d-m-Y", strtotime($editAccountData['customer']->DateOfBirth)); ?>" class="woocommerce-Input input-text" placeholder="dd-mm-jjjj" />
                    </li>
                    <li>
                        <label for="phonenumber"><?php _e( "Telefoonnummer" ); ?>
                            <span class="required">*</span></label>
                        <input type="text" name="phonenumber" id="phonenumber" required value="<?= $editAccountData['customer']->PhoneNumber; ?>" class="regular-text phonenumber" />
                    </li>
                    <li>
                        <label for="gender"><?php _e( 'Geslacht' ); ?>
                            <span class="required">*</span></label>
                        <select name="gender" id="gender" required>
                            <option value="">
                                Selecteer uw keuze
                            </option>
                            <option value="0" <?= $editAccountData['customer']->Sex === 0 ? 'selected' : ''; ?>>
                                Man
                            </option>
                            <option value="1" <?= $editAccountData['customer']->Sex === 1 ? 'selected' : ''; ?>>
                                Vrouw
                            </option>
                        </select>
                    </li>
                    <li>
                        <label for="nationality"><?php _e( "Nationaliteit" ); ?></label>
                        <input type="text" name="nationality" id="nationality" value="<?= $editAccountData['customer']->Nationality; ?>" class="regular-text" /><br />
                    </li>
                    <li>
                        <label for="dietary_wishes"><?php _e( "Voedselallergie en dieetwensen" ); ?></label>
                        <textarea name="dietary_wishes" id="dietary_wishes" class="regular-text"><?= $editAccountData['customer']->DietaryWishes; ?></textarea>
                    </li>
                    <li>
                        <label for="note"><?php _e( "Notitie" ); ?></label>
                        <textarea name="note" id="note" class="regular-text"><?= $editAccountData['customer']->Note; ?></textarea>
                    </li>
                </ul>
                <ul class="edit-account__column no-left-padding">
                    <?php
                    $i = 0;
                    foreach ($editAccountData['documents'] as $postId => $document):
                        $encryptedDocumentId = -1;
                        if ($document !== false) {
                            $key = Key::loadFromAsciiSafeString(NOSUN_CRYPTO_KEY);
                            $encryptedDocumentId = Crypto::encrypt(strval($postId), $key);
                        } ?>
                    <li>
                        <h3>Document <?= $i+1; ?></h3>
                        <input type="hidden" name="documents[<?= $i; ?>][id]" value="<?= $encryptedDocumentId; ?>" />
                        <label for="document-type-<?= $i; ?>"><?php _e( "Document type" ); ?></label>
                        <select class="form-control" name="documents[<?= $i; ?>][type]" id="document-type-<?= $i; ?>">
                            <option disabled="" value="-1" selected="">Selecteer een optie a.u.b.</option>
                            <?php foreach ($editAccountData['document_types'] as $value => $label):
                                $selected = ($document->DocumentType == $value) ? 'selected' : ''; ?>
                                <option <?= $selected; ?> value="<?= $value; ?>"><?= $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </li>
                    <li>
                        <label for="document-number-<?= $i; ?>"><?php _e( "Documentnummer" ); ?></label>
                        <input type="text" name="documents[<?= $i; ?>][number]" id="document-number-<?= $i; ?>" value="<?= $document->Title; ?>" class="regular-text" />
                    </li>
                    <li>
                        <label for="document-expires-<?= $i; ?>"><?php _e( "Document geldig tot" ); ?></label>
                        <input type="text" name="documents[<?= $i; ?>][expires]" id="document-expires-<?= $i; ?>" value="<?= date('d-m-Y', strtotime($document->Expires)); ?>" class="regular-text birthdate" placeholder="dd-mm-jjjj" />
                    </li>
                    <li>
                        <label for="document-country-<?= $i; ?>"><?php _e( "Land van afgifte" ); ?></label>
                        <select class="form-control" name="documents[<?= $i; ?>][country]" id="document-country-<?= $i; ?>">
                            <option disabled="" value="-1" selected="">Selecteer een land a.u.b.</option>
                            <?php foreach ($editAccountData['document_countries'] as $value => $label ):
                                $selected = ($document->CountryId == $value) ? 'selected' : ''; ?>
                                <option <?= $selected; ?> value="<?= $value; ?>"><?= $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </li>
                    <li>
                        <label for="document-city-<?= $i; ?>"><?php _e( "Plaats van afgifte" ); ?></label>
                        <input type="text" name="documents[<?= $i; ?>][city]" id="document-city-<?= $i; ?>" value="<?= $document->City; ?>" class="regular-text" />
                    </li>
                    <?php
                    $i++;
                    endforeach;
                    ?>
                    <li>
                        <h3>Profielfoto</h3>
                        <?php
                        $profileImage = get_field('profile_image', 'user_' . $user->ID);
                        if ($profileImage) print wp_get_attachment_image($profileImage, 'thumbnail');
                        ?>
                        <div class="clearfix"></div>
                        <input type="file" name="profile_image" id="profile_image" class="inputfile">
                        <br><small>Maximale uploadgrootte: 1MB</small>
                        <input type="hidden" name="post_id" id="post_id" value="55" />
                        <?php wp_nonce_field( 'profile_image', 'profile_image_nonce' ); ?>
                        <div class="clearfix"></div>
                        <button type="button" class="button btn btn--blue btn--padding" style="margin-top: 10px; display: none;" id="upload_profile_image">Opslaan</button>&nbsp;&nbsp;<i class="fa fa-spinner fa-spin fa-fw profile_image_loader" style="display: none;"></i>
                    </li>
                </ul>

                <div class="clearfix"></div>

                <ul class="edit-account__column">
                    <li>
                        <h3>Adresgegevens</h3>
                        <label for="billing_address_1">Straatnaam</label>
                        <input id="billing_address_1" name="billing_address_1" value="<?= $editAccountData['customer']->Street; ?>" class="input required" />
                    </li>
                    <li>
                        <label for="billing_house_number">Huisnummer en toevoeging</label>
                        <div class="clearfix"></div>
                        <input id="billing_house_number" name="billing_house_number" value="<?= $editAccountData['customer']->StreetNumber; ?>" class="input required" />
                    </li>
                </ul>

                <ul class="edit-account__column no-left-padding">
                    <li>
                        <h3>&nbsp;</h3>
                        <label for="billing_postcode">Postcode</label>
                        <input id="billing_postcode" name="billing_postcode" value="<?= $editAccountData['customer']->PostalCode; ?>" class="input postcode required" minlength="4" />
                    </li>
                    <li>

                        <label for="billing_city">Plaats</label>
                        <input id="billing_city" name="billing_city" value="<?= $editAccountData['customer']->City; ?>" class="input required" />
                    </li>
                    <li style="display: none;">
                        <label for="billing_country">Land</label>
                        <input id="billing_country" name="billing_country" value="Nederland" class="input required" />
                    </li>
                </ul>
                <div class="clearfix"></div>

                <ul class="edit-account__column">
                    <li>
                        <h3>Gegevens thuisblijver</h3>
                        <label for="emergencycontactname"><?php _e( "Naam thuisblijver" ); ?></label>
                        <input type="text" name="emergencycontactname" id="emergencycontactname" value="<?= $editAccountData['customer']->EmergencyContactName; ?>" class="regular-text" /><br />
                    </li>
                    <li>
                        <label for="emergencycontactphone"><?php _e( "Telefoonnummer thuisblijver" ); ?></label>
                        <input type="text" name="emergencycontactphone" id="emergencycontactphone" value="<?= $editAccountData['customer']->EmergencyContactPhone; ?>" class="regular-text" />
                    </li>
                </ul>

                <div class="clearfix"></div>
                <?php do_action( 'woocommerce_edit_account_form' ); ?>
                <br>
                <div class="password-wrapper">
                    <h3>Wachtwoord wijzigen</h3>
                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
                        <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" />
                    </p>
                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
                        <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" />
                    </p>
                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="password_2"><?php esc_html_e( 'Confirm new password', 'woocommerce' ); ?></label>
                        <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" />
                    </p>

                    <div class="clear"></div>
                    <p>
                        <?php wp_nonce_field( 'save_account_details' ); ?>
                        <button type="submit" class="woocommerce-Button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
                        <i class="fa fa-refresh fa-spin fa-fw loading"></i>
                        <input type="hidden" name="action" value="save_account_details" />
                    </p>
                </div>

                <?php do_action( 'woocommerce_edit_account_form_end' ); ?>
            <?php endif; ?>
		</form>

		<?php do_action( 'woocommerce_after_edit_account_form' ); ?>

	</div>

</div>

