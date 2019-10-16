<?php
namespace Nova\Compatibility;

use function add_filter;

/**
 * Class ACF.
 *
 * @package Nova
 * @author "eddo81 <eduardo_jonnerstig@live.com>"
 */
class ACF {
	/**
	 * Control variable used when checking if ACF is active.
	 *
	 * @var bool
	 */
	private $is_acf_active = true;

	/**
	 * Control variable used to store options passed to the class.
	 *
	 * @var array
	 */
	private $options = [];

	/**
	 * Register default hooks and actions for WordPress
	 *
	 * @param array $options ACF options.
	 */
	public function __construct( array $options = [] ) {
		$defaults = [
			'acf_json_directory' => '',
			'nbsp_to_br'         => true,
		];

		$this->options       = array_merge( $defaults, $options );
		$this->is_acf_active = class_exists( 'acf' );

		if ( $this->is_acf_active ) {

			// Set Save and Load paths if ACF JSON directory is found.
			if ( file_exists($this->options['acf_json_directory']) ) {
				add_filter( 'acf/settings/save_json', [$this, 'acf_json_save_point'] );
				add_filter( 'acf/settings/load_json', [$this, 'acf_json_load_point'] );
			}

			// Enable the feature of replacing non-breaking white space with <br> tags in ACF content.
			if ( true === $this->options['nbsp_to_br'] ) {
				add_filter( 'acf_the_content', function( $content ) {
					$this->nbsp_to_br( $content );
				}, 20, 1 );
			}
		}
	}

	/**
	 * Set the save point for ACF json files.
	 *
	 * @param string $path path to the save point.
	 */
	public function acf_json_save_point( string $path ) : string {
		$path = $this->options['acf_json_directory'];
		return $path;
	}

	/**
	 * Set the loading point for ACF json files.
	 *
	 * @param array $paths paths of the load point.
	 */
	public function acf_json_load_point( array $paths ) : array {
		unset($paths[0]);
		$paths[] = $this->options['acf_json_directory'];
		return $paths;
	}

	/**
	 * Get field.
	 *
	 * @param string $selector The field name or field key.
	 * @param mixed  $post_id The post ID where the value is saved. Defaults to the current post.
	 * @param bool   $format_value Whether to apply formatting logic. Defaults to true.
	 */
	public function get_field( string $selector, $post_id = null, bool $format_value = true ) {
		global $post;
		$post_id = $post_id ?? $post->ID;
		$result  = null;

		if ( function_exists( 'get_field' ) === true ) {
			$result = get_field($selector, $post_id, $format_value);
		}

		return $result;
	}

	/**
	 * The field.
	 *
	 * @param string $selector The field name or field key.
	 * @param mixed  $post_id The post ID where the value is saved. Defaults to the current post.
	 * @param bool   $format_value Whether to apply formatting logic. Defaults to true.
	 */
	public function the_field( string $selector, $post_id = null, bool $format_value = true ) {

		if ( function_exists( 'the_field' ) === true ) {
			echo $this->get_field($selector, $post_id, $format_value);
		}
	}

	/**
	 * Replace non-breaking white space with <br> tag.
	 *
	 * @param string $content The contents to replace.
	 * @return string
	 */
	protected function nbsp_to_br( string $content ) : string {
		$content = force_balance_tags( $content );
		$content = preg_replace( '#<p>\s*+(<br\s*/*>)?\s*</p>#i', '<br>', $content );
		$content = preg_replace( '~\s?<p>(\s|&nbsp;)+</p>\s?~', '<br>', $content );
		return $content;
	}

	/**
	 * Get flexible content
	 *
	 * @param string   $flexible_content_key The name / key of the page builder.
	 * @param callable $callback Function to be called. The name of the flexible content layout will be passed as the first parameter. An accociative array with the rest of the data will be passed as the second parameter.
	 * @return void
	 */
	public function get_flexible_content( string $flexible_content_key = '', callable $callback ) : void {
		$content_blocks = $this->get_field( $flexible_content_key ) ?? [];

		foreach ( $content_blocks as $content_block ) {

			if ( array_key_exists( 'acf_fc_layout', $content_block ) === true && is_callable( $callback ) ) {
				$layout = array_shift( $content_block );
				$callback( $layout, $content_block );
			}
		}
	}
}
