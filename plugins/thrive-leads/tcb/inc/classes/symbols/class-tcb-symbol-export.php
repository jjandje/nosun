<?php

class TCB_Symbol_Export {

	/**
	 * @var array|null|WP_Post
	 */
	public $post;

	/**
	 * TCB_Symbol_Export constructor.
	 *
	 * @param $post
	 */
	public function __construct( $post ) {
		$this->post = get_post( $post );
	}

	/**
	 * Returns the data prepared for export
	 *
	 * @return array
	 */
	public function export() {

		$config                = $this->prepare_symbol_for_export();
		$config['tve_version'] = TVE_VERSION; /* just to be able to validate imports later on, if we'll need some kind of compatibility checks */

		$symbol_content = $config['content'];
		unset( $config['content'] ); // we do not need these in the config file,

		/* parse and replace all image urls (both from img src and background: url(...) */
		$symbol_images = $this->export_symbol_images( $symbol_content, $config );

		return array(
			'symbol_data'    => $config,
			'symbol_content' => $symbol_content,
			'image_map'      => $symbol_images,
		);

	}

	/**
	 * Gets all the data for a symbol, including the meta values
	 *
	 * @return array
	 */
	public function prepare_symbol_for_export() {

		/** @var TCB_Symbol_Element_Abstract $helper */
		$helper = tcb_elements()->element_factory( 'symbol' );

		$item_data = $helper->prepare_symbol( $this->post );

		return $item_data;
	}

	/**
	 * Process images from the content and css
	 *
	 * @param $symbol_content
	 * @param $config
	 *
	 * @return array
	 */
	public function export_symbol_images( & $symbol_content, & $config ) {
		$image_map = $this->export_parse_images( $symbol_content );

		/**
		 * User custom CSS styles - background images
		 */
		if ( ! empty( $config['css'] ) ) {
			$image_map = array_merge( $image_map, $this->export_parse_images( $config['css'] ) );
		}

		return $image_map;
	}

	/**
	 *
	 * parse the HTML content, search for local images (stored on the server) AND images used in inline styles, retrieve the image paths and copy them to the $dest folder for each encountered image
	 * it will also replace the image srcs in HTML to a list of keys
	 *
	 * @param string $symbol_content
	 *
	 * @return array an image map, image_key => info
	 */
	protected function export_parse_images( & $symbol_content ) {
		$site_url = str_replace( array( 'http://', 'https://', '//' ), '', site_url() );

		/* regular expression that finds image links that point to the current server */
		$image_regexp = '#(http://|https://|//)(' . preg_quote( $site_url, '#' ) . ')([^ "\']+?)(\.[png|gif|jpg|jpeg]+)#is';

		$image_map = array();

		if ( preg_match_all( $image_regexp, $symbol_content, $matches ) ) {

			foreach ( $matches[3] as $index => $src ) {
				$img_src    = $matches[2][ $index ] . $src . $matches[4][ $index ];
				$no_qstring = explode( '?', $img_src );
				$img_src    = $no_qstring[0];

				$server_path = $matches[3][ $index ] . $matches[4][ $index ];

				$full_image_path = untrailingslashit( ABSPATH ) . $server_path;
				if ( ! file_exists( $full_image_path ) ) {
					continue;
				}

				$replacement               = md5( $img_src );
				$image_map[ $replacement ] = array(
					'name' => basename( $img_src ),
					'path' => $full_image_path,
					'url'  => $matches[1][ $index ] . $img_src,
				);

				$symbol_content = str_replace( array(
					'https://' . $img_src,
					'http://' . $img_src,
					'//' . $img_src,
				), '{{img=' . $replacement . '}}', $symbol_content );
			}
		}

		return $image_map;
	}
}
