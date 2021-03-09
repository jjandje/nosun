<?php
/**
 * FileName  class-tcb-symbol-template.php.
 *
 * @project  : thrive-visual-editor
 * @developer: Dragos Petcu
 * @company  : BitStone
 */

class TCB_Symbol_Template {

	/**
	 * Render the symbol content
	 *
	 * @param array $config
	 *
	 * @return mixed|string
	 */
	public static function render_content( $config = array(), $do_shortcodes = false ) {

		$symbol_id = ( ! empty( $config ) && isset( $config['id'] ) ) ? $config['id'] : get_the_ID();
		$content   = self::content( $symbol_id );

		/* prepare Events configuration */
		tve_parse_events( $content );

		if ( wp_doing_ajax() || $do_shortcodes ) {

			$content = do_shortcode( $content );

			//apply thrive shortcodes
			$content = tve_thrive_shortcodes( $content, true );

			/* render the content added through WP Editor (element: "WordPress Content") */
			$content = tve_do_wp_shortcodes( $content, is_editor_page() );
		}

		$content = tve_restore_script_tags( $content );

		$content = apply_filters( 'tcb_symbol_template', $content );

		return $content;
	}

	/**
	 * Include the start of the html content
	 */
	public static function body_open() {
		include TVE_TCB_ROOT_PATH . 'inc/views/symbols/symbol-body-open.php';
	}

	/**
	 * Include the end of the html content
	 */
	public static function body_close() {
		include TVE_TCB_ROOT_PATH . 'inc/views/symbols/symbol-body-close.php';
	}

	/**
	 * Get the content from the symbol
	 *
	 * @param int $symbol_id
	 *
	 * @return mixed|string
	 */
	public static function content( $symbol_id ) {
		$content = get_post_meta( intval( $symbol_id ), 'tve_updated_post', true );

		return $content;
	}

	public static function tcb_symbol_get_css( $config ) {
		$symbol_id  = ( ! empty( $config ) && isset( $config['id'] ) ) ? $config['id'] : 0;
		$symbol_css = trim( get_post_meta( $symbol_id, 'tve_custom_css', true ) );

		$css = "<style class='tve-symbol-custom-style'>" . $symbol_css . '</style>';

		return $css;
	}

	/**
	 * Render symbol shortcode content
	 *
	 * @param array $config
	 *
	 * @return string
	 */
	public static function symbol_render_shortcode( $config ) {
		$symbol_id = ( ! empty( $config ) && isset( $config['id'] ) ) ? $config['id'] : 0;

		$post = get_post( $symbol_id );
		if ( $post && $post->post_status === 'publish' ) {
			$content          = self::render_content( $config );
			$css              = self::tcb_symbol_get_css( $config );
			$scroll_behaviour = get_post_meta( $symbol_id, 'tcb_scroll_behaviour', true );

			if ( $scroll_behaviour !== '' ) {
				return "<div class='thrive-shortcode-html thrive-symbol-shortcode' data-scroll-behaviour='{$scroll_behaviour}' data-symbol-id='{$symbol_id}'>" . $css . $content . '</div>';
			}

			return "<div class='thrive-shortcode-html thrive-symbol-shortcode' data-symbol-id='{$symbol_id}'>" . $css . $content . '</div>';
		}

		return '';
	}

	/**
	 * Return class for symbol element on it's page
	 *
	 * @return array
	 */
	public static function get_edit_symbol_vars() {
		/**
		 * @var TCB_Symbols_Taxonomy
		 */
		global $tcb_symbol_taxonomy;
		$type = $tcb_symbol_taxonomy->get_symbol_type();

		return array(
			'css_class' => ( $type === 'headers' || $type === 'footers' ) ? 'thrv_' . substr( $type, 0, - 1 ) : '',
			'type'      => substr( $type, 0, - 1 ),
		);
	}

	/**
	 * Return the content for the header or footer for the themes templates
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public static function get_theme_section( $type ) {
		$section_id = Thrive_Template()->meta( $type );

		$section_content = ( empty( $section_id ) ) ? '' : TCB_Symbol_Template::symbol_render_shortcode( array( 'id' => $section_id ) );

		return $section_content;
	}
}
