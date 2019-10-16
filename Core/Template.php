<?php
namespace Nova\Core;

/**
 * Class Template.
 *
 * @package Nova
 * @author "eddo81 <eduardo_jonnerstig@live.com>"
 */
class Template {
	/**
	 * Control variable to store the root path of the template files.
	 *
	 * @var string
	 */
	private $root_uri;

	/**
	 * Private variable used to hold the file extension of the template files.
	 *
	 * @var string
	 */
	private $extension = '.php';

	/**
	 * Constructor.
	 *
	 * @param string $root_uri The root uri for the theme templates.
	 */
	public function __construct( string $root_uri = '' ) {
		$this->root_uri = ( ! empty( $root_uri ) ) ? rtrim( $root_uri, '/' ) . '/' : $root_uri;
	}

	/**
	 * Returns the template file as a string. Data is bound to the template via the optional ***$vars*** array.
	 * Each key of the associative ***$vars*** array will correspond to a variable that will be available in the template.
	 *
	 * @param string $template_path Path to template file.
	 * @param array  $vars Optional parameter used to pass in variables for use by the template.
	 * @return string
	 */
	public function get( string $template_path, array $vars = [] ) : string {
		$template_path = ( substr( $template_path, -4 ) !== $this->extension ) ? $template_path . $this->extension : $template_path;
		$template_file = $this->root_uri . $template_path;

		if ( ! file_exists( $template_file ) ) {
			return '';
		}

		ob_start();

		if ( ! empty ($vars ) ) {
			extract( $vars );
		}

		include $template_file;
		$data = ob_get_clean();
		return $data;
	}

	/**
	 * Like ***get_template_part()*** but lets you pass args to the template file.
	 * Args are available in the template as regular variables named after their corresponding key in the ***$vars*** array.
	 *
	 * @param string $template_path Path to template file.
	 * @param array  $vars Optional parameter used to pass in variables for use by the template.
	 * @return void
	 */
	public function render( string $template_path, array $vars = [] ) : void {
		echo $this->get( $template_path, $vars );
	}
}
