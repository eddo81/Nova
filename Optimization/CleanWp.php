<?php

namespace Nova\Optimization;

/**
 * Class CleanWp.
 *
 * @package Soil
 *
 * Fork of https://roots.io/plugins/soil/
 */
class CleanWp {
	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			return;
		}

		add_action('after_setup_theme', function() {

			if ( current_theme_supports( 'clean-up-markup' ) === true ) {
				$this->clean_up_markup();
			}

			if ( current_theme_supports( 'disable-asset-versioning' ) === true ) {
				$this->disable_asset_versioning();
			}

			if ( current_theme_supports( 'disable-trackbacks-pingbacks' ) === true ) {
				$this->disable_trackbacks_pingbacks();
			}

			if ( current_theme_supports( 'scripts-to-footer' ) === true ) {
				$this->scripts_to_footer();
			}

			if ( current_theme_supports( 'nice-search' ) === true ) {
				$this->nice_search();
			}

		}, 100);
	}

	/**
	 * Clean up markup.
	 * Remove unnecessary <link> tags.
	 * Remove inline CSS and JS from WP emoji support.
	 * Remove inline CSS used by Recent Comments widget.
	 * Remove inline CSS used by posts with galleries.
	 * Remove self-closing tag.
	 * Clean up language_attributes() used in <html> tag.
	 * Clean up output of stylesheet <link> tags.
	 * Clean up output of <script> tags.
	 * Add and remove body_class() classes.
	 * Wrap embedded media as suggested by Readability.
	 * Remove unnecessary self-closing tags.
	 * Don't return the default description in the RSS feed if it hasn't been changed.
	 *
	 * @return void
	 */
	private function clean_up_markup() : void {

		add_action( 'init', function() {
			remove_action( 'wp_head', 'feed_links_extra', 3 );
			add_action( 'wp_head', 'ob_start', 1, 0 );
			add_action( 'wp_head', function () {
				$pattern = '/.*' . preg_quote( esc_url( get_feed_link( 'comments_' . get_default_feed() ) ), '/' ) . '.*[\r\n]+/';
				echo preg_replace( $pattern, '', ob_get_clean() );
			}, 3, 0 );
			remove_action( 'wp_head', 'rsd_link' );
			remove_action( 'wp_head', 'wlwmanifest_link' );
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
			remove_action( 'wp_head', 'wp_generator' );
			remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
			remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
			add_filter( 'use_default_gallery_style', '__return_false' );
			add_filter( 'emoji_svg_url', '__return_false' );
			add_filter( 'show_recent_comments_widget_style', '__return_false' );
			wp_deregister_script( 'wp-embed' );
		});

		add_filter( 'the_generator', '__return_false' );

		add_filter( 'language_attributes', function() {
			$attributes = [];
			if ( is_rtl() === true ) {
				$attributes[] = 'dir="rtl"';
			}
			$lang = get_bloginfo( 'language' );
			if ( $lang ) {
				$attributes[] = "lang=\"$lang\"";
			}
			$output = implode( ' ', $attributes );
			$output = apply_filters( 'soil/language_attributes', $output );
			return $output;
		} );

		add_filter( 'style_loader_tag', function( $input ) {
			preg_match_all( "!<link rel='stylesheet'\s?(id='[^']+')?\s+href='(.*)' type='text/css' media='(.*)' />!", $input, $matches );
			if ( empty($matches[2] ) ) {
				return $input;
			}
			$media = '' !== $matches[3][0] && 'all' !== $matches[3][0] ? ' media="' . $matches[3][0] . '"' : '';
			return '<link rel="stylesheet" href="' . $matches[2][0] . '"' . $media . '>' . "\n";
		} );

		add_filter( 'script_loader_tag', function( $input ) {
			$input = str_replace( "type='text/javascript' ", '', $input );
			return $input;
		} );

		add_filter( 'body_class', function( $classes ) {
			if ( is_single() || is_page() && ! is_front_page() ) {
				if ( ! in_array( basename( get_permalink() ), $classes, true ) ) {
					$classes[] = basename( get_permalink() );
				}
			}
			$home_id_class  = 'page-id-' . get_option( 'page_on_front' );
			$remove_classes = [
				'page-template-default',
				$home_id_class,
			];
			$classes        = array_diff( $classes, $remove_classes );
			return $classes;
		});

		add_filter( 'embed_oembed_html', function( $cache ) {
			return '<div class="entry-content-asset">' . $cache . '</div>';
		} );

		$filters = ['get_avatar', 'comment_id_fields', 'post_thumbnail_html'];

		foreach ( $filters as $filter ) {
			add_filter( $filter, function( $input ) {
				return str_replace( ' />', '>', $input );
			});
		}

		add_filter( 'get_bloginfo_rss', function( $bloginfo ) {
			$default_tagline = 'Just another WordPress site';
			return ( $bloginfo === $default_tagline ) ? '' : $bloginfo;
		} );
	}

	/**
	 * Remove version query string from all styles and scripts.
	 *
	 * @return void
	 */
	private function disable_asset_versioning() : void {
		if ( WP_DEBUG ) {
			return;
		}

		$filters = ['script_loader_src', 'style_loader_src'];

		foreach ( $filters as $filter ) {
			add_filter( $filter, function( $src ) {
				return $src ? esc_url( remove_query_arg( 'ver', $src ) ) : false;
			}, 15, 1 );
		}
	}

	/**
	 * Disables trackbacks/pingbacks.
	 *
	 * Disable pingback XMLRPC method.
	 * Remove pingback header.
	 * Kill trackback rewrite rule.
	 * Kill bloginfo('pingback_url').
	 *
	 * @return void
	 */
	private function disable_trackbacks_pingbacks() : void {

		add_filter( 'xmlrpc_methods', function ( $methods ) {
			unset( $methods['pingback.ping'] );
			return $methods;
		}, 10, 1);

		add_filter( 'wp_headers', function( $headers ) {
			if ( isset( $headers['X-Pingback'] ) ) {
				unset( $headers['X-Pingback'] );
			}
			return $headers;
		}, 10, 1 );

		add_filter( 'rewrite_rules_array', function( $rules ) {
			foreach ( $rules as $rule => $rewrite ) {
				if ( preg_match( '/trackback\/\?\$$/i', $rule ) ) {
					unset( $rules[ $rule ] );
				}
			}
			return $rules;
		});

		add_filter( 'bloginfo_url', function( $output, $show ) {
			if ( 'pingback_url' === $show ) {
				$output = '';
			}
			return $output;
		}, 10, 2 );

		add_action('xmlrpc_call', function( $action ) {
			if ( 'pingback.ping' === $action ) {
				wp_die( 'Pingbacks are not supported', 'Not Allowed!', ['response' => 403] );
			}
		});
	}

	/**
	 * Moves all scripts to wp_footer action.
	 *
	 * @return void
	 */
	private function scripts_to_footer() : void {

		add_action( 'wp_enqueue_scripts', function() {
			remove_action( 'wp_head', 'wp_print_scripts' );
			remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
			remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );
		});
	}

	/**
	 * Redirects search results from /?s=query to /search/query/, converts %20 to +.
	 *
	 * @return void
	 */
	private function nice_search() : void {

		add_filter( 'wpseo_json_ld_search_url', function( $url ) {
			return str_replace( '/?s=', __( '/search/', '' ), $url );
		} );

		add_action( 'template_redirect', function() {
			global $wp_rewrite;
			if ( ! isset( $wp_rewrite ) || ! is_object( $wp_rewrite ) || ! $wp_rewrite->get_search_permastruct() ) {
				return;
			}
			$search_base = $wp_rewrite->search_base;
			if ( is_search() && ! is_admin() && strpos( $_SERVER['REQUEST_URI'], "/{$search_base}/" ) === false && strpos( $_SERVER['REQUEST_URI'], '&') === false ) {
				wp_redirect( get_search_link() );
				exit();
			}
		});
	}
}
