<?php
/**
 * FileName  class-tcb-symbol-element-abstract.php.
 *
 * @project  : thrive-visual-editor
 * @company  : BitStone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TCB_Symbol_Element_Abstract
 */
abstract class TCB_Symbol_Element_Abstract extends TCB_Cloud_Template_Element_Abstract {

	/**
	 * Config for symbol
	 *
	 * @var string
	 */
	public $_cfg_code = '__CONFIG_post_symbol__';


	/**
	 * This is only a placeholder element
	 *
	 * @return bool
	 */
	public function is_placeholder() {
		return true;
	}

	/**
	 * Component and control config
	 *
	 * @return array
	 */
	public function own_components() {
		return array();
	}

	/**
	 * Element category that will be displayed in the sidebar
	 *
	 * @return string
	 */
	public function category() {
		return $this->get_thrive_basic_label();
	}

	/**
	 * HTML layout of the element for when it's dragged in the canvas
	 *
	 * @return string
	 */
	public function html() {
		return $this->html_placeholder( sprintf( __( 'Insert %s', 'thrive-cb' ), $this->name() ) );
	}

	/**
	 * Returns the HTML placeholder for an element (contains a wrapper, and a button with icon + element name)
	 *
	 * @param string $title Optional. Defaults to the name of the current element
	 *
	 * @return string
	 */
	public function html_placeholder( $title = null ) {
		if ( empty( $title ) ) {
			$title = $this->name();
		}

		return tcb_template( 'elements/section-placeholder', array(
			'icon'       => $this->icon(),
			'class'      => 'thrv_symbol ' . 'thrv_' . $this->tag(),
			'title'      => $title,
			'extra_attr' => 'data-shortcode=thrive_' . $this->tag() . ' data-selector="' . '.thrv_symbol' . '.thrv_' . $this->tag() . '" data-tcb-elem-type="' . $this->tag() . '" data-element-name="' . esc_attr( $this->name() ) . '"',
		), true );
	}

	/**
	 * Make sure that a symbol title is provided and it's unique
	 *
	 * @param array $args
	 *
	 * @return bool|WP_Error
	 */
	public function ensure_title( $args ) {

		/* If the title is not set, just throw the error */
		if ( ! isset( $args['post_title'] ) ) {
			return new WP_Error( 'rest_cannot_create_post', __( 'Sorry, you are not allowed to create symbols without title' ), array( 'status' => 409 ) );
		}

		$post = get_page_by_title( $args['post_title'], OBJECT, TCB_Symbols_Post_Type::SYMBOL_POST_TYPE );

		if ( $post && $post->post_status !== 'trash' ) {
			return new WP_Error( 'rest_cannot_create_post', __( 'Sorry, you are not allowed to create global elements with the same title' ), array( 'status' => 409 ) );
		}

		return true;
	}

	/**
	 * Get all symbols
	 */
	public function get_all( $args ) {
		$result   = array();
		$defaults = array(
			'post_type'      => TCB_Symbols_Post_Type::SYMBOL_POST_TYPE,
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		);

		//get symbols from a specific category
		if ( isset( $args['category_name'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => TCB_Symbols_Taxonomy::SYMBOLS_TAXONOMY,
					'field'    => 'slug',
					'terms'    => $args['category_name'],
				),
			);
			unset( $args['category_name'] );
		}

		//exclude symbols from categories
		if ( isset( $args['category__not_in'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => TCB_Symbols_Taxonomy::SYMBOLS_TAXONOMY,
					'field'    => 'term_id',
					'terms'    => $args['category__not_in'],
					'operator' => 'NOT IN',
				),
			);

			unset( $args['category__not_in'] );
		}

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Add the possibility for other plugins to change the arguments for getting the symbols
		 *
		 * @param array $args
		 */
		$args                     = apply_filters( 'tcb_get_symbols_args', $args );

		$symbols = get_posts( $args );

		if ( is_wp_error( $symbols ) ) {
			return new WP_Error( 'query_error', __( 'Error when retrieving symbols', 'thrive-cb' ) );
		}

		foreach ( $symbols as $symbol ) {
			$result['local'][ $symbol->ID ] = $this->prepare_symbol( $symbol );
		}

		//if we have templates in the cloud get them
		//else just return the local results
		if ( $this->has_cloud_templates() ) {
			//get the templates from the cloud
			$cloud_items = $this->get_cloud_templates();

			if ( is_wp_error( $cloud_items ) ) {
				return $cloud_items;
			}

			$result['cloud'] = $cloud_items;
		} else {
			$result = $result['local'];
		}
		/**
		 * Change the symbols array returned
		 *
		 * @param array $result
		 */
		$result = apply_filters( 'tcb_get_symbols_response', $result );

		return $result;
	}

	/**
	 * Prepare symbol before listing in TAR
	 *
	 * @param WP_Post $symbol
	 *
	 * @return array
	 */
	public function prepare_symbol( $symbol ) {

		$content = TCB_Symbol_Template::render_content( array( 'id' => $symbol->ID ) );

		$symbol_data = array(
			'id'         => $symbol->ID,
			'content'    => $content,
			'post_title' => $symbol->post_title,
			'config'     => $this->_get_symbol_config( $symbol ),
			'css'        => $this->get_symbol_css( $symbol->ID ),
			'thumb_url'  => $this->get_thumb_path( $symbol->ID ),
		);

		//TODO Move this in the header class called with a filter
		$behaviour_on_scroll = get_post_meta( $symbol_data['id'], 'tcb_scroll_behaviour', true );
		if ( $behaviour_on_scroll !== '' ) {
			$symbol_data['behaviour_on_scroll'] = $behaviour_on_scroll;
		}

		/**
		 * Change symbol data before showing it in the list
		 *
		 * @param array $symbol_data
		 */
		$symbol_data = apply_filters( 'tcb_symbol_data_before_return', $symbol_data );

		return $symbol_data;
	}

	/**
	 * Get path for symbol thumbnail
	 *
	 * @param int $symbol_id
	 *
	 * @return string
	 */
	public function get_thumb_path( $symbol_id ) {
		$upload_dir = wp_upload_dir();
		$thumb_url  = trailingslashit( $upload_dir['baseurl'] ) . TCB_Symbols_Post_Type::SYMBOL_THUMBS_FOLDER . '/' . $symbol_id . '.png';
		$thumb_path = trailingslashit( $upload_dir['basedir'] ) . TCB_Symbols_Post_Type::SYMBOL_THUMBS_FOLDER . '/' . $symbol_id . '.png';

		if ( file_exists( $thumb_path ) ) {
			return $thumb_url;
		}

		return tve_editor_url( 'editor/css/images/symbol_placeholder.png' );
	}

	/**
	 * Get css for a certain symbol
	 *
	 * @param int $id
	 *
	 * @return mixed
	 */
	public function get_symbol_css( $id ) {
		$custom_css = get_post_meta( $id, 'tve_custom_css', true );

		return $custom_css;
	}

	/**
	 * Get config for symbol
	 *
	 * @param WP_Post $symbol
	 *
	 * @return string
	 */
	private function _get_symbol_config( $symbol ) {
		$encoded_config = tve_json_utf8_unslashit( json_encode( array( 'id' => ( string ) $symbol->ID ) ) );

		return $this->_cfg_code . $encoded_config . $this->_cfg_code;
	}

	/**
	 * Save a symbol changed from within the editor page
	 *
	 * @param array $symbol_data
	 *
	 * @return array|WP_Error
	 */
	public function edit_symbol( $symbol_data ) {

		if ( ! isset( $symbol_data['id'] ) ) {
			return new WP_Error( 'id_is_not_set', __( 'Missing symbol id', 'thrive-cb' ), array( 'status' => 500 ) );
		}

		update_post_meta( $symbol_data['id'], 'tve_updated_post', $symbol_data['content'] );
		update_post_meta( $symbol_data['id'], 'tve_custom_css', $symbol_data['css'] );

		$symbol = get_post( $symbol_data['id'] );

		return array( 'symbol' => $symbol );
	}

	/**
	 * Create symbol from content elements
	 *
	 * @param array $symbol_data
	 *
	 * @return array|int|WP_Error
	 */
	public function create_symbol( $symbol_data ) {
		$create_symbol_defaults = array(
			'post_type'   => TCB_Symbols_Post_Type::SYMBOL_POST_TYPE,
			'post_status' => 'publish',
		);

		$post_title         = str_replace( '\\', '', $symbol_data['symbol_title'] );
		$create_symbol_args = wp_parse_args( array( 'post_title' => $post_title ), $create_symbol_defaults );

		/**
		 * Add the possibility for other plugins to change the arguments for creating a symbol
		 *
		 * @param array $args
		 */
		$create_symbol_args = apply_filters( 'tcb_create_symbol_args', $create_symbol_args );

		/* Ensure that the title exists and it's unique */
		$check_title = $this->ensure_title( $create_symbol_args );

		if ( is_wp_error( $check_title ) ) {
			return $check_title;
		}

		$post_id = wp_insert_post( $create_symbol_args, true );

		//if something went wrong at insert, just return the error
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		/**
		 * After save actions: add to category and update meta ( html and css )
		 */
		$this->after_save( $post_id, $symbol_data );

		//return the newly created symbol for later use, if needed
		$symbol = get_post( $post_id );

		//prepare the symbol to be inserted in the page after a successful save
		$response = $this->prepare_symbol( $symbol );

		return $response;
	}

	/**
	 * Actions taken if a symbols is successfully created
	 *
	 * @param int   $post_id
	 * @param array $symbol_data
	 */
	public function after_save( $post_id, $symbol_data ) {

		//if we are sending the category than assign the symbol to it
		$terms = isset( $symbol_data['term_id'] ) ? array( $symbol_data['term_id'] ) : array();
		wp_set_post_terms( $post_id, $terms, TCB_Symbols_Taxonomy::SYMBOLS_TAXONOMY );

		//if the insert was ok, update the meta attributes for the symbol
		update_post_meta( $post_id, 'tve_updated_post', $symbol_data['content'] );
		update_post_meta( $post_id, 'tve_custom_css', $symbol_data['css'] );

		if ( isset( $symbol_data['thumb_path'] ) ) {
			$this->copy_thumb( $symbol_data['thumb_path'], $post_id );
		}

	}

	/**
	 * Save css for elements with extra css. i.e call to action
	 * The css selectors are updated with proper thrv_symbol selectors
	 *
	 * @param array $data
	 *
	 * @return array|WP_Error
	 */
	public function save_extra_css( $data ) {

		if ( ! isset( $data['id'] ) ) {
			return new WP_Error( 'id_is_not_set', __( 'Missing symbol id', 'thrive-cb' ), array( 'status' => 500 ) );
		}

		update_post_meta( $data['id'], 'tve_custom_css', $data['css'] );

		$symbol = get_post( $data['id'] );

		return array( 'symbol' => $symbol );
	}

	/**
	 * Generate preview for the symbol
	 *
	 * @param int $post_id
	 *
	 * @return array|WP_Error
	 */
	public function generate_preview( $post_id ) {

		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );

		$moved_file = wp_handle_upload( $_FILES['preview_file'], array(
			'action'                   => TCB_Editor_Ajax::ACTION,
			'unique_filename_callback' => array( $this, 'get_preview_filename' ),
		) );

		remove_filter( 'upload_dir', array( $this, 'upload_dir' ) );

		if ( empty( $moved_file['url'] ) ) {
			return new WP_Error( 'file_not_saved', __( 'The file could not be saved', 'thrive-cb' ), array( 'status' => 500 ) );
		}

		$editor = wp_get_image_editor( $moved_file['file'] );

		$editor->save( $moved_file['file'] );

		return array(
			'file_path' => $moved_file['url'],
		);

	}

	/**
	 * Get the name for the thumbnail
	 * Prevent wordpress for creating a new file when it already exists in the uploads folder
	 *
	 * @param string $dir
	 * @param string $name
	 * @param string $ext
	 *
	 * @return mixed
	 */
	public function get_preview_filename( $dir, $name, $ext ) {
		return $name;
	}

	/**
	 * Get the upload directory where the file will be kept
	 *
	 * @param array $upload
	 *
	 * @return mixed
	 */
	public static function upload_dir( $upload ) {

		$sub_dir = '/' . TCB_Symbols_Post_Type::SYMBOL_THUMBS_FOLDER;

		$upload['path']   = $upload['basedir'] . $sub_dir;
		$upload['url']    = $upload['baseurl'] . $sub_dir;
		$upload['subdir'] = $sub_dir;

		return $upload;
	}

	/**
	 * @param array $args
	 *
	 * @return array|WP_Error
	 */
	public function get_cloud_templates( $args = array() ) {
		$result        = array();
		$cloud_headers = parent::get_cloud_templates( $args );

		if ( is_wp_error( $cloud_headers ) ) {
			return $cloud_headers;
		}

		//see how the cloud templates are returned when you have elements of that type or when you don't
		if ( isset( $cloud_headers[0] ) ) {
			foreach ( $cloud_headers as $cloud_header ) {
				$result[ $cloud_header['id'] ] = array(
					'id'         => $cloud_header['id'],
					'post_title' => $cloud_header['name'],
					'thumb_url'  => $cloud_header['thumb'],
					'from_cloud' => 1,
				);
			}
		}

		return $result;
	}

	/**
	 * Get path for symbol thumbnail
	 *
	 * @param int $old_path
	 * @param int $new_id
	 *
	 * @return bool
	 */
	public function copy_thumb( $old_path, $new_id ) {

		if ( strpos( $old_path, 'http' ) === false ) {
			$old_path = 'http:' . $old_path;
		}

		$upload_dir = wp_upload_dir();
		$new_path   = trailingslashit( $upload_dir['basedir'] ) . TCB_Symbols_Post_Type::SYMBOL_THUMBS_FOLDER . '/' . $new_id . '.png';

		return copy( $old_path, $new_path );
	}
}
