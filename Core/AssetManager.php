<?php

namespace Nova\Core;

use function add_action;
use function wp_register_style;
use function wp_register_script;
use function wp_enqueue_script;
use function get_template_directory_uri;
use function get_template_directory;
use function is_admin;

/**
 * Class AssetManager.
 *
 * @package Nova
 * @author "eddo81 <eduardo_jonnerstig@live.com>"
 */
class AssetManager {
	/**
	 * Control variable to store the asset manifest.
	 *
	 * @var array
	 */
	private $asset_manifest = [];

	/**
	 * Control variable to store registered scripts.
	 *
	 * @var array
	 */
	private $registered_scripts = [];

	/**
	 * Control variable to store registered styles.
	 *
	 * @var array
	 */
	private $registered_styles = [];

	/**
	 * Control variable to store preloaded assests.
	 *
	 * @var string
	 */
	private $preloaded_assests = '';

	/**
	 * Constructor
	 *
	 * @param string $asset_manifest_path The uri of the theme's asset_manifest.json file.
	 * @return self
	 */
	public function __construct( string $asset_manifest_path ) {

		if ( file_exists( $asset_manifest_path ) ) {
			$this->asset_manifest = json_decode( file_get_contents( $asset_manifest_path ), true );
		}

		// Init asset loader.
		$this->init_asset_loader();

		// Actions.
		add_action( 'wp_head', function () {
			$preloaded_assets = $this->preloaded_assests ?? '';

			if ( $preloaded_assets ) {
				echo $preloaded_assets;
			}
		}, 1000);
	}

	/**
	 * Registers theme scripts and styles and adds resource hints to theme fonts.
	 *
	 * @return void
	 */
	private function init_asset_loader() : void {
		foreach ( $this->get_theme_assets() as $asset_type => $assets ) {

			foreach ( $assets as $asset ) {

				if ( 'preload' !== $asset['resource_hint'] ) {
					continue;
				}

				$href                = get_template_directory_uri() . $asset['path'];
				$file_time           = filemtime( get_template_directory() . $asset['path'] );
				$crossorigin         = ( array_key_exists( 'crossorigin', $asset ) && ( true === $asset['crossorigin'] ) );
				$resource_attributes = [$asset['resource_hint'], $asset['mime'], $href, $asset_type, $crossorigin];

				switch ( $asset_type ) {
					case 'font':
						$this->set_resource_hint( ...$resource_attributes );
						break;

					case 'style':
						if ( 'preload' === $asset['resource_hint'] ) {
							wp_register_style($asset['name'], $href, false, $file_time);
						}

						$this->registered_styles[ $asset['name'] ] = $resource_attributes;
						break;

					case 'script':
						$this->registered_scripts[ $asset['name'] ] = $resource_attributes;
						wp_register_script( $asset['name'], $href, [], $file_time, false );
						break;
				}
			}
		}
	}

	/**
	 * Enqueue theme scripts and styles and add matching resource hints.
	 *
	 * @param string $handle The name - ***Handle*** - of the theme ***script*** or ***style*** file to enqueue.
	 * @return void
	 */
	public function enqueue_theme_scripts_and_styles( string $handle ) : void {
		$handle = strtolower( $handle );

		add_action( 'wp_enqueue_scripts', function () use ( $handle ) {
			if ( array_key_exists( $handle, $this->registered_scripts ) ) {
				wp_enqueue_script( $handle );
				$this->set_resource_hint( ...$this->registered_scripts[ $handle ] );
			}

			if ( array_key_exists( $handle, $this->registered_styles ) ) {
				wp_enqueue_style( $handle );
				$this->set_resource_hint( ...$this->registered_styles[ $handle ] );
			}
		}, 100);
	}

	/**
	 * Render a <link> tag with the preload attribute for each theme asset (resource hint).
	 *
	 * @param string $rel The relationship attribute of the linked resource. Set to either ***preload*** or ***prefetch*** for preloading purposes.
	 * @param string $mime The mimetype of the resource.
	 * @param string $href The URL of the link.
	 * @param string $as The resource type e.g ***script***, ***font***, ***style*** etc.
	 * @param bool   $cors Set to true if resource is hosted on a diffrent domain.
	 * @return void
	 */
	public function set_resource_hint( string $rel, string $mime, string $href, string $as, bool $cors = false ) : void {
		if ( WP_DEBUG || is_admin() ) {
			return;
		}

		$rel                      = ( $rel ) ? "rel=\"{$rel}\"" : null;
		$mime                     = ( $mime ) ? "type=\"{$mime}\"" : null;
		$href                     = ( $href ) ? "href=\"{$href}\"" : null;
		$as                       = ( $as ) ? "as=\"{$as}\"" : null;
		$crossorigin              = ( true === $cors ) ? 'crossorigin ' : null;
		$this->preloaded_assests .= "<link {$rel} {$mime} {$href} {$as} {$crossorigin}>";
	}

	/**
	 * Returns an array of objects which hold descriptive metadata about the theme's assets.
	 *
	 * @param string $key Specifies which asset type - **script**, **style** or ***font*** - that is to be retuned. Providing an empty string as an argument will return all asset types.
	 * @return array
	 */
	public function get_theme_assets( string $key = '' ) : array {
		return ( array_key_exists( $key, $this->asset_manifest ) ) ? $this->asset_manifest[ $key ] : $this->asset_manifest;
	}

	/**
	 * Returns an array of asset names - ***handles*** - for all registered theme ***scripts*** and ***styles***.
	 *
	 * @param string $asset_type Specifies which asset type - ***script*** or ***style*** - that is to be retuned.
	 * @return array
	 */
	public function get_registered_asset_names( string $asset_type = '' ) : array {
		$registered_asset_names = [];

		if ( 'script' === $asset_type ) {
			$registered_asset_names = array_keys( $this->registered_scripts );
		}

		if ( 'style' === $asset_type ) {
			$registered_asset_names = array_keys( $this->registered_styles );
		}

		return $registered_asset_names;
	}
}
