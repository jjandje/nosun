<?php /** @noinspection SqlNoDataSourceInspection */

namespace Vazquez\NosunAssumaxConnector\Api;

use Exception;
use stdClass;

/**
 * Holds all functionality dealing with Assumax attachments.
 *
 * @link       https://vazquez.nl
 * @since      2.0.0
 * @package    Nosun_Assumax_Api
 * @subpackage Nosun_Assumax_Api/includes
 * @author     Chris van Zanten <chris@vazquez.nl>
 */
class Attachments implements ILoadable {
    /**
     * Checks whether or not the image already exists, if not, it downloads it and creates a new attachment for it.
     * Should the image already exist, then the title and the meta data will be updated.
     * Creates a new id that is a combination of the provided Assumax id, prefix and parent id to make an id that is
     * as unique as possible.
     *
     * @param string $imageURL The URL at which the image can be downloaded.
     * @param string $fileName The basename of a file (without the path, but with extension).
     * @param string $assumaxId The of the image as found in Assumax.
     * @param string $prefix The prefix used to discern the type of image that is being handled.
     * @param string $title Title of the image.
     * @param string $parentId Post Id of the parent post which holds this image.
     * @param string $altText [Optional] If an empty string is provided, it will be set equal to the title.
     * @param string $index The index of the image that defines the order in which it is displayed among other images.
     * @param string $modified Some images may hav a modified field which specifies when the last modification was.
     * @return false | string The attachment id when the image has been handled successfully, false if otherwise.
     */
    private static function handle_image($imageURL, $fileName, $assumaxId, $prefix, $title, $parentId, $altText = '', $index = '0', $modified = '') {
        if (empty($imageURL) || empty($fileName) || empty($assumaxId) || empty($title) || empty($parentId)) {
            error_log("[Nosun_Assumax_Api_Attachments->handle_image]: One of the required image data fields isn't set.");
            return false;
        }
        // Create the unique id combination.
        if (empty($prefix)) $uniqueId = "{$assumaxId}-{$parentId}";
        else $uniqueId = "{$prefix}-{$assumaxId}-{$parentId}";
        $altText = !empty($altText) ? $altText : $title;
        // Check if an attachment already exists with this id.
        global $wpdb;
        $query = "SELECT ID FROM {$wpdb->posts} JOIN {$wpdb->postmeta} ON post_id = ID AND meta_key = '_assumax_id' 
                    AND meta_value = '{$uniqueId}' WHERE post_type = 'attachment' LIMIT 1;";
        $attachmentId = $wpdb->get_var($query);
        if (empty($attachmentId)) {
            // Attachment doesn't exist, create a new one.
            $attachmentId = self::download_image($imageURL, $fileName, $title, $uniqueId, $parentId);
            if (empty($attachmentId)) {
                error_log("[Nosun_Assumax_Api_Attachments->handle_product_image]: Could not download the image with Assumax Id: {$uniqueId}.");
                return false;
            }
        } else {
            // Check if the modified field is set and if so compare it to the one in the _image_modified meta field.
            if (!empty($modified)) {
                $currentModified = get_post_meta($attachmentId, '_image_modified', true);
                if ($currentModified !== $modified) {
                    // Download the new version of the image.
                    $attachmentId = self::download_image($imageURL, $fileName, $title, $uniqueId, $parentId);
                    if (empty($attachmentId)) {
                        error_log("[Nosun_Assumax_Api_Attachments->handle_product_image]: Could not download the image with Assumax Id: {$uniqueId}.");
                        return false;
                    }
                }
                update_post_meta($attachmentId, '_image_modified', $modified);
            } else {
                // Attachment exists, update the title.
                $args = [
                    'ID' => $attachmentId,
                    'post_title' => $title
                ];
                $attachmentId = wp_update_post($args);
                if (is_wp_error($attachmentId) || $attachmentId === 0) {
                    error_log("[Nosun_Assumax_Api_Attachments->handle_product_image]: Could not update the attachment with Id: {$attachmentId}. Parent: {$parentId}.");
                    return false;
                }
            }
        }
        // Update the meta fields.
        update_post_meta($attachmentId, '_wp_attachment_image_alt', $altText);
        update_post_meta($attachmentId, '_image_index', $index);
        // All is well, return true.
        return $attachmentId;
    }

    /**
     * Checks whether or not the product image already exists, if not, it downloads it and creates a new attachment for it.
     * Should the image already exist, then the title and the meta data will be updated.
     * Product images are not prefixed.
     *
     * @param stdClass $productImageData ImageData as provided by the Assumax API.
     * @param string $parentId The post id of the product parent template post which this product image belongs to.
     * @return false | string The attachment id when the image has been handled successfully, false if otherwise.
     */
    public static function handle_product_image($productImageData, $parentId) {
        if (empty($productImageData) || empty($productImageData->Title) || empty($productImageData->Image)
            || empty($productImageData->FileName) || empty($productImageData->ImageId)) {
            error_log("[Nosun_Assumax_Api_Attachments->handle_product_image]: One of the required image data fields isn't set. Parent: {$parentId}.");
            return false;
        }
        $altText = !empty($productImageData->Alt) ? $productImageData->Alt : $productImageData->Title;
        $index = !empty($productImageData->Index) ? $productImageData->Index : '0';
        return self::handle_image($productImageData->Image, $productImageData->FileName,
            $productImageData->ImageId, "", $productImageData->Title, $parentId, $altText, $index);
    }

    /**
     * Checks whether or not the product day image already exists, if not, it downloads it and creates a new attachment for it.
     * Should the image already exist, then the title and the meta data will be updated.
     * Product day images are prefixed with a 'd'.
     *
     * @param stdClass $dayImageData ProductDay data as provided by the Assumax API.
     * @param string $parentId The post id of the product parent template post which this product image belongs to.
     * @return false | string The attachment id when the image has been handled successfully, false if otherwise.
     */
    public static function handle_product_day_image($dayImageData, $parentId) {
        if (empty($dayImageData) || empty($dayImageData->Image) || empty($dayImageData->ImageFileName) || empty($dayImageData->ImageId)) {
            error_log("[Nosun_Assumax_Api_Attachments->handle_product_day_image]: One of the required image data fields isn't set. Parent: {$parentId}.");
            return false;
        }
        // The ImageTitle field is optional. Use the regular Title when it's empty.
        $imageTitle = !empty($dayImageData->ImageTitle) ? $dayImageData->ImageTitle : $dayImageData->Title;
        $altText = !empty($dayImageData->ImageAlt) ? $dayImageData->ImageAlt : $imageTitle;
        return self::handle_image($dayImageData->Image, $dayImageData->ImageFileName,
            $dayImageData->ImageId, "d", $imageTitle, $parentId, $altText, isset($dayImageData->Modified) ? $dayImageData->Modified : '');
    }

    /**
     * Checks whether or not the banner or profile image already exists, if not, it downloads it and creates a new attachment for it.
     * Should the image already exist, then the title and the meta data will be updated.
     * Profile images are prefixed with a 'p' and banner images are prefixed with a 'b'.
     *
     * @param stdClass $imageData ProfileImage or BannerImage data as provided by the Assumax API.
     * @param string $parentId The post id of the product parent template post which this product image belongs to.
     * @param bool $isProfileImage When true, the image is a profile image, when false it is a banner image instead.
     * @return false | string The attachment id when the image has been handled successfully, false if otherwise.
     */
    public static function handle_profile_banner_image($imageData, $parentId, $isProfileImage = true) {
        if (empty($imageData) || empty($imageData->Title) || empty($imageData->Image)
            || empty($imageData->FileName) || empty($imageData->Id)) {
            error_log("[Nosun_Assumax_Api_Attachments->handle_profile_banner_image]: One of the required image data fields isn't set. Parent: {$parentId}.");
            return false;
        }
        $altText = !empty($imageData->Alt) ? $imageData->Alt : $imageData->Title;
        return self::handle_image($imageData->Image, $imageData->FileName,
            $imageData->Id, $isProfileImage ? "p" : "b", $imageData->Title, $parentId, $altText);
    }

    /**
     * Checks whether or not the guide image already exists, if not, it downloads it and creates a new attachment for it.
     * Should the image already exist, then the title and the meta data will be updated.
     * Guide images are prefixed with a 'g'.
     *
     * @param stdClass $guideImageData GuideImage data as provided by the Assumax API.
     * @param string $parentId The post id of parent tourguide post which this guide image belongs to.
     * @return false | string The attachment id when the image has been handled successfully, false if otherwise.
     */
    public static function handle_guide_image($guideImageData, $parentId) {
        if (empty($guideImageData) || empty($guideImageData->Title) || empty($guideImageData->Image)
            || empty($guideImageData->FileName) || empty($guideImageData->ImageId)) {
            error_log("[Nosun_Assumax_Api_Attachments->handle_guide_image]: One of the required image data fields isn't set. Parent: {$parentId}.");
            return false;
        }
        $altText = !empty($guideImageData->Alt) ? $guideImageData->Alt : $guideImageData->Title;
        $modified = !isset($guideImageData->Modified) ? '' : $guideImageData->Modified;
        return self::handle_image($guideImageData->Image, $guideImageData->FileName,
            $guideImageData->ImageId, "g", $guideImageData->Title, $parentId, $altText, '0', $modified);
    }

    /**
     * Checks whether or not the accommodation image already exists, if not, it downloads it and creates a new attachment for it.
     * Should the image already exist, then the title and the meta data will be updated.
     * Accommodation images are prefixed with an 'a'.
     *
     * @param stdClass $accommodationImage AccommodationImage as provided by the Assumax API.
     * @param string $parentId The post id of the parent accommodation post which this accommodation image belongs to.
     * @return false | string The attachment id when the image has been handled successfully, false if otherwise.
     */
    public static function handle_accommodation_image($accommodationImage, $parentId) {
        if (empty($accommodationImage) || empty($accommodationImage->Title) || empty($accommodationImage->Image)
            || empty($accommodationImage->FileName)) {
            error_log("[Nosun_Assumax_Api_Attachments->handle_accommodation_image]: One of the required image data fields isn't set. Parent: {$parentId}.");
            return false;
        }
        $altText = !empty($accommodationImage->Alt) ? $accommodationImage->Alt : $accommodationImage->Title;
        return self::handle_image($accommodationImage->Image, $accommodationImage->FileName,
            $accommodationImage->ImageId, "a", $accommodationImage->Title, $parentId, $altText);
    }

    /**
     * Downloads the image from the provided URL and creates a new attachment for it.
     *
     * @param string $url The relative url from which to download the image.
     * @param string $fileName The filename with extension that will be used to save the file with.
     * @param string $title The title of the image.
     * @param string $imageId The Id of the image as it can be found in Assumax.
     * @param string $parentId The post Id of the parent that holds this image.
     * @return string | null The attachment Id when the image has been download successfully or null if something
     * went wrong.
     */
    private static function download_image($url, $fileName, $title, $imageId, $parentId) {
        if (empty($url) || empty($fileName) || empty($imageId) || empty($parentId)) return null;
        try {
            $client = AssumaxClient::getInstance();
        } catch (Exception $exception) {
            error_log("[Nosun_Assumax_Api_Attachments->download_image]: ImageId: {$imageId}. {$exception->getMessage()}");
            return null;
        }
        // Download the image from the url and save it in a temporary folder under a unique filename.
        $imageContents = $client->get(strtolower($url), [], false, false);
        if (empty($imageContents)) {
            error_log("[Nosun_Assumax_Api_Attachments->download_image]: ImageId: {$imageId}. Could not obtain the image from the Assumax API.");
            return null;
        }
        $tempFilePath = plugin_dir_path(__FILE__) . "temp/" . wp_generate_uuid4() . ".jpg";
        $tempFile = fopen($tempFilePath, "w");
        if ($tempFile === false) {
            error_log("[Nosun_Assumax_Api_Attachments->download_image]: ImageId: {$imageId}. Could not open the file with path: '{$tempFilePath}' for writing.");
            return null;
        }
        $bytesWritten = fwrite($tempFile, $imageContents);
        if ($bytesWritten === false) {
            error_log("[Nosun_Assumax_Api_Attachments->download_image]: ImageId: {$imageId}. Could not write the image contents to the file with path: '{$tempFilePath}'.");
            return null;
        }
        if (!fclose($tempFile)) {
            error_log("[Nosun_Assumax_Api_Attachments->download_image]: ImageId: {$imageId}. Could not close the file with path: '{$tempFilePath}'.");
            return null;
        }
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        // Sideload the image to the correct folder.
        $fileType = $wpFileType = wp_check_filetype(basename($tempFilePath), null);
        $uploadFileName = $imageId . "-" . $fileName;
        $fileArgs = [
            'name' => $uploadFileName,
            'type' => $fileType,
            'tmp_name' => $tempFilePath,
            'error' => 0,
            'size' => $bytesWritten
        ];
        $overrides = [
            'test_form' => false,
            'test_size' => true
        ];
        $sideloadResults = wp_handle_sideload($fileArgs, $overrides);
        if (!empty($sideloadResults['error'])) {
            error_log("[Nosun_Assumax_Api_Attachments->download_image]: ImageId: {$imageId}. Could not sideload the image.");
            error_log(var_export($sideloadResults['error'], true));
            return null;
        }
        // Create a new attachment.
        $args = [
            'guid' => $sideloadResults['url'],
            'post_mime_type' => $sideloadResults['type'],
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        $attachmentId = wp_insert_attachment($args, $sideloadResults['file'], $parentId);
        if (is_wp_error($attachmentId) || $attachmentId === 0) {
            error_log("[Nosun_Assumax_Api_Attachments->download_image]: ImageId: {$imageId}. Could not create a new attachment.");
            return null;
        }
        // Generate and update attachment thumbnail metadata.
        $thumbnailMetaData = wp_generate_attachment_metadata($attachmentId, $sideloadResults['file']);
        if (empty($thumbnailMetaData)) {
            wp_delete_attachment($attachmentId, true);
            error_log("[Nosun_Assumax_Api_Attachments->download_image]: ImageId: {$imageId}. Could not create the attachment meta data.");
            return null;
        }
        wp_update_attachment_metadata($attachmentId, $thumbnailMetaData);
        // Set the _assumax_id meta field for this attachment.
        update_post_meta($attachmentId, '_assumax_id', $imageId);
        // Return the attachment id.
        return $attachmentId;
    }

    /**
     * Either creates a new folder or obtains an existing folder with the provided folderName.
     * All the attachments provided will then have a shortcut created in that folder should a shortcut not already exist.
     *
     * @param string $folderName The name of the folder where the attachments needs to have a shortcut created.
     * @param array $attachmentIds A list of attachment ids.
     * @param string $type One of the following options:
     *  - template
     *  - accommodation
     *  - guide
     */
    public static function create_rml_shortcuts($folderName, $attachmentIds, $type) {
        if (empty($folderName) || empty($attachmentIds) || empty($type)) {
            error_log("[Nosun_Assumax_Api_Attachments->create_rml_shortcuts]: Not all the required fields have a value set.");
            return;
        }
        // Get the correct root folder depending on the type.
        $rootFolder = [];
        if ($type === 'template') $rootFolder = wp_rml_create("Templates", _wp_rml_root(), 0, [], false, true);
        elseif ($type === 'accommodation') $rootFolder = wp_rml_create("Accommodaties", _wp_rml_root(), 0, [], false, true);
        elseif ($type === 'guide') $rootFolder = wp_rml_create("Medewerkers", _wp_rml_root(), 0, [], false, true);
        if (is_array($rootFolder)) {
            error_log("[Nosun_Assumax_Api_Attachments->create_rml_shortcuts]: An error occurred while trying to obtain the root folder for type: {$type}.");
            foreach ($rootFolder as $error) {
                error_log($error);
            }
            return;
        }
        /** @var int $rootFolder */
        $folderId = wp_rml_create($folderName, $rootFolder, 0, ["cre"], false, true);
        if (is_array($folderId)) {
            error_log("[Nosun_Assumax_Api_Attachments->create_rml_shortcuts]: An error occurred while trying to obtain the folder id for folder name: {$folderName}.");
            foreach ($folderId as $error) {
                error_log($error);
            }
            return;
        }
        // Recommended to call this function according to the documentation.
        wp_rml_structure_reset();
        // Move each of the attachments into the target folder.
        /** @var int $folderId */
        wp_rml_move($folderId, $attachmentIds, false, false);
    }

    /**
     * Obtains all the attachments that have a _assumax_id meta field set up and determines which ones are still in use.
     * Those that are no longer in use will be deleted.
     */
    public static function prune_assumax_attachments() {
        set_time_limit(600);
        global $wpdb;
        $query = "SELECT post_id FROM {$wpdb->postmeta} JOIN {$wpdb->posts} ON ID=post_id AND post_type='attachment' WHERE meta_key='_assumax_id';";
        $postIds = $wpdb->get_col($query);
        // Use a hashset to boost performance when deleting lots of array elements.
        $surplusAttachments = [];
        if (!empty($postIds)) {
            foreach ($postIds as $postId) {
                $surplusAttachments[$postId] = true;
            }
        }
        $query = "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_attachments';";
        $rows = $wpdb->get_col($query);
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $usedIds = maybe_unserialize($row);
                if (!empty($usedIds) && is_array($usedIds)) {
                    foreach ($usedIds as $usedId) {
                        if (key_exists($usedId, $surplusAttachments)) unset($surplusAttachments[$usedId]);
                    }
                }
            }
        }
        // Delete all the attachments for which there is still an id left in the surplusAttachments array.
        foreach ($surplusAttachments as $postId => $value) {
            wp_delete_attachment($postId, true);
        }
    }

    /**
     * @inheritDoc
     */
    public static function load($loader): void {
        $loader->add_action('api_prune_attachments_event', [self::class, 'prune_assumax_attachments']);
    }
}