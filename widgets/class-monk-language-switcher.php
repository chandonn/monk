<?php
/**
 * Monk Language Switcher.
 *
 * @since      0.1.0
 *
 * @package    Monk
 * @subpackage Monk/Widgets
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class responsible for create Monk_Language_Switcher widget.
 *
 * @package    Monk
 * @subpackage Monk/Widgets
 */
class Monk_Language_Switcher extends WP_Widget {

	/**
	 * Sets up the widgets classname and description.
	 *
	 * @since  0.1.0
	 */
	public function __construct() {
		$widget_options = array(
			'classname'   => 'monk_language_switcher',
			'description' => __( 'Switch between site translations.', 'monk' ),
			);
		parent::__construct( 'monk_language_switcher', __( 'Language Switcher', 'monk' ), $widget_options );
	}

	/**
	 * Outputs the content of the front-end side
	 *
	 * @since  0.1.0
	 * @param array $args     Args.
	 * @param array $instance The widget options.
	 */
	public function widget( $args, $instance ) {
		global $monk_languages;
		$switchable_languages = array();
		$title                = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Languages', 'monk' );
		$flag                 = ! empty( $instance['flag'] ) ? true : false;
		$active_languages     = get_option( 'monk_active_languages' );
		$current_language     = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( get_query_var( 'lang' ) ) ) : $monk_languages[ get_option( 'monk_default_language' ) ]['slug'];
		$default_language     = get_option( 'monk_default_language', false );
		if ( is_front_page() || is_post_type_archive() || is_date() || is_404() || is_author() ) {
			$current_url = monk_get_current_url();

			foreach ( $active_languages as $lang_code ) {
				if ( $lang_code !== $current_language ) {
					$switchable_languages[ $lang_code ] = add_query_arg( 'lang', esc_attr( $monk_languages[ $lang_code ]['slug'], 'monk' ), $current_url );
				}
			}
		}

		if ( is_singular() ) {
			$monk_post_translations_id = get_post_meta( get_the_id(), '_monk_post_translations_id', true );
			$monk_total_translations   = get_option( 'monk_post_translations_' . $monk_post_translations_id, false );

			if ( get_post_meta( get_the_id(), '_monk_post_language', true ) ) {
				$current_language = $monk_languages[ get_post_meta( get_the_id(), '_monk_post_language', true ) ]['slug'];
				$current_locale   = get_post_meta( get_the_id(), '_monk_post_language', true );
			} else {
				$current_language = $monk_languages[ $default_language ]['slug'];
				$current_locale   = $default_language;
			}

			if ( is_array( $monk_total_translations ) ) {
				foreach ( $monk_total_translations as $lang_code => $post_id ) {
					if ( in_array( $lang_code, $active_languages ) || $monk_languages[ $lang_code ]['slug'] === $current_language ) {
						$monk_translations[ $lang_code ] = $post_id;
					}
				}
			} else {
				$monk_translations[ $current_locale ] = get_the_id();
			}

			if ( $monk_translations ) {
				foreach ( $monk_translations as $lang_code => $post_id ) {
					if ( $lang_code !== $current_language ) {
						$switchable_languages[ $lang_code ] = get_permalink( $post_id );
					}
				}
			} else {
				$current_language                       = $monk_languages[ get_option( 'monk_default_language', false ) ]['slug'];
				$monk_translations[ $current_language ] = get_the_id();
				foreach ( $monk_translations as $lang_code => $post_id ) {
					if ( $lang_code !== $current_language ) {
						$switchable_languages[ $lang_code ] = get_permalink( $post_id );
					}
				}
			}
		}

		if ( is_archive() && ( is_category() || is_tag() ) ) {
			$monk_term_translations_id = get_term_meta( get_queried_object_id(), '_monk_term_translations_id', true );
			$monk_total_translations   = get_option( 'monk_term_translations_' . $monk_term_translations_id, false );

			if ( get_term_meta( get_queried_object_id(), '_monk_term_language', true ) ) {
				$current_language = $monk_languages[ get_term_meta( get_queried_object_id(), '_monk_term_language', true ) ]['slug'];
				$current_locale   = get_term_meta( get_queried_object_id(), '_monk_term_language', true );
			} else {
				$current_language = $monk_languages[ $default_language ]['slug'];
				$current_locale   = $default_language;
			}

			if ( is_array( $monk_total_translations ) ) {
				foreach ( $monk_total_translations as $lang_code => $post_id ) {
					if ( in_array( $lang_code, $active_languages ) || $monk_languages[ $lang_code ]['slug'] === $current_language ) {
						$monk_translations[ $lang_code ] = $post_id;
					}
				}
			} else {
				$monk_translations[ $current_locale ] = get_the_id();
			}

			if ( $monk_translations ) {
				foreach ( $monk_translations as $lang_code => $term_id ) {
					if ( $lang_code !== $current_language ) {
						$switchable_languages[ $lang_code ] = get_term_link( $term_id );
					}
				}
			} else {
				$current_language                       = $monk_languages[ get_option( 'monk_default_language', false ) ]['slug'];
				$monk_translations[ $current_language ] = get_queried_object_id();

				foreach ( $monk_translations as $lang_code => $term_id ) {
					if ( $lang_code !== $current_language ) {
						$switchable_languages[ $lang_code ] = get_term_link( $term_id );
					}
				}
			}
		}

		require plugin_dir_path( dirname( __FILE__ ) ) . 'widgets/partials/public-monk-language-switcher.php';
	}

	/**
	 * Outputs the options form on admin side
	 *
	 * @since  0.1.0
	 * @param array $instance The widget options.
	 * @return  void
	 */
	public function form( $instance ) {
		require plugin_dir_path( dirname( __FILE__ ) ) . 'widgets/partials/admin-monk-language-switcher.php';
	}

	/**
	 * Process widget options on save
	 *
	 * @since  0.1.0
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 * @return array $instance
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['flag']        = ! empty( $new_instance['flag'] ) ? true : false;

		return $instance;
	}
}
