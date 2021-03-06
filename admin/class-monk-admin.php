<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      0.1.0
 *
 * @package    Monk
 * @subpackage Monk/Admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Monk
 * @subpackage Monk/Admin
 */
class Monk_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $monk    The ID of this plugin.
	 */
	private $monk;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The default language of the plugin.
	 *
	 * @since    0.7.0
	 * @access   private
	 * @var      string    $default_language    The default language of the plugin.
	 */
	private $default_language;

	/**
	 * The active languages of the plugin.
	 *
	 * @since    0.7.0
	 * @access   private
	 * @var      array $active_languages The active languages of the plugin.
	 */
	private $active_languages;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since  0.1.0
	 * @param  string $monk             The name of the plugin.
	 * @param  string $version          The version of this plugin.
	 * @param  string $default_language The default language of the plugin.
	 * @param  array  $active_languages The active languages of the plugin.
	 * @return  void
	 */
	public function __construct( $monk, $version, $default_language, $active_languages ) {
		$this->monk             = $monk;
		$this->version          = $version;
		$this->default_language = $default_language;
		$this->active_languages = $active_languages;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function enqueue_styles() {
		wp_enqueue_style( strtolower( $this->monk ), plugin_dir_url( __FILE__ ) . 'css/monk-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'monk-widgets', plugin_dir_url( __FILE__ ) . 'css/monk-widgets.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'monk-flags', plugin_dir_url( __FILE__ ) . 'css/monk-flags.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->monk, plugin_dir_url( __FILE__ ) . 'js/monk-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->monk, 'monk', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		));
	}

	/**
	 * This function validate the get_current_screen calls.
	 *
	 * @since    0.4.1
	 *
	 * @return mixed
	 */
	private function get_current_screen() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( is_a( $screen, 'WP_Screen' ) && ! empty( $screen ) ) {
				return $screen;
			}
		}

		return false;
	}

	/**
	 * Handle redirects to setup page after install.
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_activation_redirect() {
		global $pagenow;
		if ( 'plugins.php' === $pagenow ) {
			$monk_settings_notice = get_option( 'monk_settings_notice', false );
			$activate_multi       = filter_input( INPUT_GET, 'activate-multi' );
			$activate_multi       = isset( $activate_multi ) ? true : false;

			if ( get_transient( '_monk_redirect' ) && ! $activate_multi && $monk_settings_notice ) {
				wp_safe_redirect( admin_url( 'admin.php?page=monk' ) );
				exit();
			}
			delete_transient( '_monk_redirect' );
		}
	}

	/**
	 * Function to register the settings page of the plugin
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_add_menu_page() {
		add_menu_page(
			__( 'Monk Settings', 'monk' ),
			'Monk',
			'manage_options',
			'monk',
			array( $this, 'monk_settings_render' ),
			'dashicons-translation',
			3
		);
	}

	/**
	 * Function to create a section for the Monk General Options in the administration menu
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_options_init() {
		$action = filter_input( INPUT_GET, 'action' );

		switch ( $action ) {
			case 'monk_tools':
				add_settings_section(
					'monk_tools',
					__( 'Tools', 'monk' ),
					array( $this, 'monk_tools_description' ),
					'monk_settings'
				);
				break;
			case 'monk_options':
				add_settings_section(
					'monk_options',
					__( 'Options', 'monk' ),
					array( $this, 'monk_options_description' ),
					'monk_settings'
				);
				break;
			case 'monk_general':
			default:
				add_settings_section(
					'monk_general_settings',
					__( 'General Settings', 'monk' ),
					array( $this, 'monk_general_settings_render' ),
					'monk_settings'
				);
				break;
		}

		register_setting( 'monk_settings', 'monk_default_language' );
		add_settings_field(
			'monk_default_language',
			__( 'Default site language', 'monk' ),
			array( $this, 'monk_default_language_render' ),
			'monk_settings',
			'monk_general_settings'
		);

		register_setting( 'monk_settings', 'monk_active_languages' );
		add_settings_field(
			'monk_active_languages',
			__( 'Add new translation', 'monk' ),
			array( $this, 'monk_active_languages_render' ),
			'monk_settings',
			'monk_general_settings'
		);

		register_setting( 'monk_settings', 'monk_set_language_to_elements' );
		add_settings_field(
			'monk_set_language_to_elements',
			__( 'Set default language to all posts and terms without language', 'monk' ),
			array( $this, 'monk_set_elements_language_render' ),
			'monk_settings',
			'monk_tools'
		);

		register_setting( 'monk_settings', 'monk_default_language_url' );
		add_settings_field(
			'monk_default_language_url',
			__( 'Show default language in URL', 'monk' ),
			array( $this, 'monk_default_language_url_render' ),
			'monk_settings',
			'monk_general_settings'
		);

		register_setting( 'monk_settings', 'monk_site_name' );
		add_settings_field(
			'monk_site_name',
			__( 'Site Title', 'monk' ),
			array( $this, 'monk_site_name_render' ),
			'monk_settings',
			'monk_options'
		);

		register_setting( 'monk_settings', 'monk_site_description' );
		add_settings_field(
			'monk_site_description',
			__( 'Tagline', 'monk' ),
			array( $this, 'monk_site_description_render' ),
			'monk_settings',
			'monk_options'
		);
	}

	/**
	 *  This is the callback for the monk_default_language_url element
	 *
	 * @since  0.4.0
	 *
	 * @return  void
	 */
	public function monk_default_language_url_render() {
		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-default-language-url-render.php';
	}

	/**
	 * This is the callback for the monk_general_settings section
	 *
	 * Prints a description in the section
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_general_settings_render() {
		?>
		<p><?php esc_html_e( 'Here you can configure your language preferences.', 'monk' ); ?><br />
		<?php esc_html_e( 'Select a default language for your site and check the languages you will translate.', 'monk' ); ?></p>
		<?php
	}

	/**
	 * This function show tabs in Monk page
	 *
	 * @since    0.4.0
	 * @return  void
	 */
	public function monk_settings_tabs() {
		$url     = admin_url( 'admin.php?page=monk' );
		$action  = filter_input( INPUT_GET, 'action' );
		$general = '';
		$tools   = '';
		$options = '';

		switch ( $action ) {
			case 'monk_tools':
				$tools = 'nav-tab-active';
				break;
			case 'monk_options':
				$options = 'nav-tab-active';
				break;
			case 'monk_general':
			default:
				$general = 'nav-tab-active';
				break;
		}

		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-settings-tabs-render.php';
	}

	/**
	 * This is the callback for the monk_tools section
	 *
	 * Prints a description in the section
	 *
	 * @since    0.4.0
	 * @return  void
	 */
	public function monk_tools_description() {
		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-tools-description.php';
	}

	/**
	 * This is the callback for the monk_options section
	 *
	 * Prints a description in the section
	 *
	 * @since    0.5.0
	 * @return  void
	 */
	public function monk_options_description() {
		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-options-description.php';
	}

	/**
	 * Function to render checkbox field to set default language to all posts and terms
	 *
	 * Callback for the monk_set_language_to_elements element
	 *
	 * @since    0.4.0
	 * @return  void
	 */
	public function monk_set_elements_language_render() {
		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-set-elements-language-render.php';
	}

	/**
	 * Function to render the site name to translate.
	 *
	 * Callback for the monk_site_name element
	 *
	 * @since    0.5.0
	 * @return  void
	 */
	public function monk_site_name_render() {
		$monk_languages   = monk_get_available_languages();
		$site_name        = get_option( 'blogname' );
		$default_language = $this->default_language;
		$default_slug     = $monk_languages[ $default_language ]['slug'];
		$active_languages = $this->active_languages;

		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-site-name-render.php';
	}

	/**
	 * Function to render the site description to translate.
	 *
	 * Callback for the monk_site_description element
	 *
	 * @since    0.5.0
	 * @return  void
	 */
	public function monk_site_description_render() {
		$monk_languages   = monk_get_available_languages();
		$site_description = get_option( 'blogdescription' );
		$default_language = $this->default_language;
		$default_slug     = $monk_languages[ $default_language ]['slug'];
		$active_languages = $this->active_languages;

		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-site-description-render.php';
	}

	/**
	 * Function to render the select field, callback for the monk_default_language element
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_default_language_render() {
		$default_language = $this->default_language;
		$languages        = get_available_languages();
		$args             = array(
			'id'        => 'monk-default-language',
			'name'      => 'monk_default_language',
			'selected'  => $default_language,
			'languages' => $languages,
		);

		wp_dropdown_languages( $args );
	}

	/**
	 * Function to render the checkbox field, callback for the monk_active_languages element
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function monk_active_languages_render() {
		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-active-languages-render.php';
	}

	/**
	 * Function to render the admin settings page for the plugin
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_settings_render() {
		$this->monk_settings_tabs();
		$action  = filter_input( INPUT_GET, 'action' );
		$the_tab = false;

		switch ( $action ) {
			case 'monk_tools':
				$form_id = 'monk-tools-form';
				$the_tab = 'tools';
				break;
			case 'monk_options':
				$form_id = 'monk-options-form';
				$the_tab = 'options';
				break;
			case 'monk_general':
			default:
				$form_id = 'monk-general-form';
				$the_tab = 'general';
				break;
		}

		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-settings-render.php';
	}

	/**
	 * Function to create the main language meta box for posts
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_post_meta_box() {
		add_meta_box(
			'monk_post_meta_box_field',
			__( 'Language', 'monk' ),
			array( $this, 'monk_post_meta_box_field_render' ),
			'',
			'side',
			'high',
			''
		);
	}

	/**
	 * Function to make the view for the post monk meta box
	 *
	 * @param    object $post Post object.
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_post_meta_box_field_render( $post ) {
		global $current_screen;
		$monk_languages        = monk_get_available_languages();
		$monk_id               = get_post_meta( $post->ID, '_monk_post_translations_id', true );
		$post_default_language = get_post_meta( $post->ID, '_monk_post_language', true );
		$site_default_language = $this->default_language;
		$active_languages      = $this->active_languages;

		if ( empty( $monk_id ) ) {
			$monk_id = filter_input( INPUT_GET, 'monk_id' );
			if ( isset( $monk_id ) ) {
				$monk_id = sanitize_text_field( wp_unslash( $monk_id ) );
			} else {
				$monk_id = $post->ID;
			}
		}

		$lang = filter_input( INPUT_GET, 'lang' );
		if ( isset( $lang ) ) {
			$lang = sanitize_text_field( wp_unslash( $lang ) );
		} else {
			$lang = $site_default_language;
		}

		$monk_translations      = get_option( 'monk_post_translations_' . $monk_id, false );
		$monk_translation_url   = admin_url( 'post-new.php' );
		$is_available_languages = false;

		foreach ( $active_languages as $language ) {
			if ( ! array_key_exists( $language, (array) $monk_translations ) ) {
				$is_available_languages = true;
			}
		}

		wp_nonce_field( basename( __FILE__ ), 'monk_post_meta_box_nonce' );

		if ( '' === $post_default_language ) {
			$selected = $monk_languages[ $site_default_language ]['english_name'];
		} else {
			$selected = $post_default_language;
		}

		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-post-meta-box-field-render.php';
	}

	/**
	 * Function to save data from the monk post meta box
	 *
	 * @param   string $post_id ID of the post.
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_save_post_meta_box( $post_id ) {
		$monk_post_meta_box_nonce = filter_input( INPUT_POST, 'monk_post_meta_box_nonce' );
		if ( ! isset( $monk_post_meta_box_nonce ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $monk_post_meta_box_nonce ) ), basename( __FILE__ ) ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$monk_languages = monk_get_available_languages();

		$active_languages = $this->active_languages;
		$current_language = get_post_meta( $post_id, '_monk_post_language', true );
		$post_language    = filter_input( INPUT_POST, 'monk_post_language' );

		if ( ! $current_language ) {
			$current_language = sanitize_text_field( wp_unslash( $post_language ) );
		}

		$language          = '';
		$post_translations = array(
			$current_language => $post_id,
		);

		if ( isset( $post_language ) && ! empty( $post_language ) ) {
			$language = sanitize_text_field( wp_unslash( $post_language ) );

			if ( in_array( $language, $active_languages, true ) ) {
				update_post_meta( $post_id, '_monk_post_language', $language );
			}
		}

		$monk_id = filter_input( INPUT_POST, 'monk_id' );

		if ( isset( $monk_id ) ) {
			$monk_id = sanitize_text_field( wp_unslash( $monk_id ) );
		} else {
			$monk_id = get_post_meta( $post_id, '_monk_post_translations_id', true );
		}

		$option_name       = 'monk_post_translations_' . $monk_id;
		$post_translations = get_option( $option_name );

		if ( ! empty( $post_translations ) ) {
			if ( array_key_exists( $current_language, $post_translations ) ) {
				if ( $post_id === $post_translations[ $current_language ] && $language ) {
					unset( $post_translations[ $current_language ] );
					$post_translations[ $language ] = $post_id;
				}
			} else {
				$post_translations[ $current_language ] = $post_id;
			}
		} else {
			$monk_id           = $post_id;
			$post_translations = array(
				$current_language => $post_id,
			);
		}

		foreach ( $post_translations as $lang_code => $id ) {
			if ( ! array_key_exists( $lang_code, $monk_languages ) ) {
				unset( $post_translations[ $lang_code ] );
			}
		}

		update_post_meta( $post_id, '_monk_post_translations_id', $monk_id );
		update_option( 'monk_post_translations_' . $monk_id, $post_translations );

		$post_terms            = get_the_terms( $post_id, 'category' );
		$default_post_category = get_term( get_option( 'default_category' ) );

		if ( is_object( $post_terms[0] ) && is_object( $default_post_category ) ) {
			if ( 1 >= count( $post_terms ) && $default_post_category->term_id === $post_terms[0]->term_id ) {
				$default_post_category           = get_term( get_option( 'default_category' ) );
				$default_category_translations   = get_option( 'monk_term_translations_' . $default_post_category->term_id, array() );
				$default_category_translation_id = array_key_exists( $language, $default_category_translations ) ? $default_category_translations[ $language ] : false;

				wp_set_post_terms( $post_id, array( $default_category_translation_id ), 'category' );
			}
		}
	}

	/**
	 * Function to return a post from the trash
	 * and reinsert it into its original translation group
	 *
	 * @param   string $post_id ID of the post.
	 *
	 * @since    0.5.2
	 * @return  void
	 */
	public function monk_untrash_post( $post_id ) {
		$language                       = get_post_meta( $post_id, '_monk_post_language', true );
		$monk_id                        = get_post_meta( $post_id, '_monk_post_translations_id', true );
		$post_translations              = get_option( 'monk_post_translations_' . $monk_id, array() );
		$post_translations[ $language ] = $post_id;

		update_option( 'monk_post_translations_' . $monk_id, $post_translations );
	}

	/**
	 * Function that erases the data stored by the plugin when post is deleted permanently.
	 *
	 * @param   string $post_id ID of the post, page or post_type to be deleted.
	 *
	 * @since    0.1.0
	 * @return  void
	 */
	public function monk_delete_post_data( $post_id ) {
		$monk_id           = get_post_meta( $post_id, '_monk_post_translations_id', true );
		$post_lang         = get_post_meta( $post_id, '_monk_post_language', true );
		$post_translations = get_option( 'monk_post_translations_' . $monk_id, false );

		if ( isset( $post_translations ) && $post_translations ) {
			unset( $post_translations[ $post_lang ] );
			if ( empty( $post_translations ) ) {
				delete_option( 'monk_post_translations_' . $monk_id );
			} else {
				update_option( 'monk_post_translations_' . $monk_id, $post_translations );
			}
		} else {
			delete_option( 'monk_post_translations_' . $monk_id );
		}
	}

	/**
	 * Add parameters to filter by meta_key.
	 *
	 * @since  0.1.0
	 * @param object $query Object Query.
	 * @return  void
	 */
	public function monk_admin_posts_filter( $query ) {
		global $mode;
		if ( ! is_admin() || ( 'attachment' === $query->get( 'post_type' ) && 'list' !== $mode ) || 'nav_menu_item' === $query->get( 'post_type' ) ) {
			return;
		}

		$default_language = $this->default_language;
		$active_languages = $this->active_languages;
		$filter           = filter_input( INPUT_GET, 'monk_language_filter' );
		$language         = $filter;
		$screen           = $this->get_current_screen();

		if ( ! is_customize_preview() && $screen && 'nav-menus' === $screen->base ) {
			$menu_id  = filter_input( INPUT_GET, 'menu' ) ? filter_input( INPUT_GET, 'menu' ) : get_user_option( 'nav_menu_recently_edited' );
			$language = get_term_meta( $menu_id, '_monk_menu_language', true );
			$language = empty( $language ) ? $default_language : $language;
		}

		if ( $query->is_search() ) {
			if ( empty( $filter ) ) {
				return;
			} else {
				$language = $filter;
			}
		}

		if ( isset( $filter ) && empty( $filter ) ) {
			return;
		}

		if ( is_customize_preview() || $language === $default_language || ! in_array( $language, $active_languages, true ) ) {
			$meta_query = array(
				'relation' => 'OR', // Optional, defaults to "AND".
				array(
					'key'   => '_monk_post_language',
					'value' => $default_language,
				),
				array(
					'key'     => '_monk_post_language',
					'compare' => 'NOT EXISTS',
				),
			);
		} else {
			$meta_query = array(
				array(
					'key'   => '_monk_post_language',
					'value' => $language,
				),
			);
		}

		$old_meta_query = $query->get( 'meta_query' );

		if ( is_array( $old_meta_query ) ) {
			$meta_query = array_merge( $old_meta_query, $meta_query );
		}
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Function to filter the query inside the category meta box using the languages
	 *
	 * @param   array $args       Array of arguments.
	 * @param   array $taxonomies Array of taxonomies.
	 *
	 * @since    0.1.0
	 * @return  array $args.
	 */
	public function monk_admin_terms_filter( $args, $taxonomies ) {
		if ( ! is_admin() ) {
			return $args;
		}
		$old_meta_query = $args['meta_query'];
		$screen         = $this->get_current_screen();

		if ( is_customize_preview() ) {
			$language = $this->default_language;

			$meta_query         = array(
				array(
					'relation' => 'OR', // Optional, defaults to "AND".
					array(
						'key'   => '_monk_menu_language',
						'value' => $language,
					),
					array(
						'key'     => '_monk_menu_language',
						'compare' => 'NOT EXISTS',
					),
				),
				array(
					'relation' => 'OR', // Optional, defaults to "AND".
					array(
						'key'   => '_monk_term_language',
						'value' => $language,
					),
					array(
						'key'     => '_monk_term_language',
						'compare' => 'NOT EXISTS',
					),
				),
			);
			$args['meta_query'] = $meta_query;
		}

		if ( ! is_customize_preview() && $screen ) {

			if ( ( 'edit' === $screen->parent_base && 'post' === $screen->base )
				|| ( 'nav-menus' === $screen->base )
				|| ( 'edit-tags' === $screen->base ) ) {

				$active_languages = $this->active_languages;
				$default_language = $this->default_language;

				if ( 'nav-menus' === $screen->base ) {
					$menu_id  = filter_input( INPUT_GET, 'menu' ) ? filter_input( INPUT_GET, 'menu' ) : get_user_option( 'nav_menu_recently_edited' );
					$language = get_term_meta( $menu_id, '_monk_menu_language', true );
					$language = empty( $language ) ? $default_language : $language;

					$relation = array(
						'key'   => '_monk_term_language',
						'value' => $language,
					);
				} elseif ( 'edit-tags' === $screen->base ) {
					$term_language = filter_input( INPUT_GET, 'lang' );
					$language      = empty( $term_language ) ? $default_language : $term_language;

					$relation = array(
						'key'   => '_monk_term_language',
						'value' => $language,
					);
				} else {
					$post_id       = get_the_id();
					$post_language = sanitize_text_field( get_post_meta( $post_id, '_monk_post_language', true ) );
					$url_language  = filter_input( INPUT_GET, 'lang' ) ? filter_input( INPUT_GET, 'lang' ) : $default_language;
					$language      = empty( $post_language ) ? $url_language : $post_language;

					$relation = array(
						'key'   => '_monk_term_language',
						'value' => $language,
					);
				}

				if ( isset( $relation ) && 'all' === $relation['value'] ) {
					return $args;
				}

				if ( isset( $relation ) ) {
					$meta_query = array(
						'relation' => 'OR', // Optional, defaults to "AND".
						$relation,
						array(
							'key'     => '_monk_term_language',
							'compare' => 'NOT EXISTS',
						),
					);

					$args['meta_query'] = $meta_query;
				}
			} // End if().
		} // End if().

		if ( is_array( $old_meta_query ) ) {
			$args['meta_query'] = array_merge( $old_meta_query, $args['meta_query'] );
		}
		return $args;
	}

	/**
	 * Add select filter
	 *
	 * @since  0.1.0
	 * @return  void
	 */
	public function monk_admin_languages_selector() {
		$screen   = $this->get_current_screen();
		$class    = wp_count_posts( $screen->post_type );
		$has_post = false;
		$is_trash = 'trash' === filter_input( INPUT_GET, 'post_status' ) ? true : false;

		foreach ( $class as $type => $number ) {
			if ( ( 'trash' !== $type && 'auto-draft' !== $type && 0 !== $number && ! $is_trash ) || ( 'trash' === $type && $is_trash && 0 !== $number ) ) {
				$has_post = true;
			}
		}

		if ( $has_post ) {
			require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/monk-language-filter.php';
		}
	}

	/**
	 * Include styles related to Customize options
	 *
	 * @since  0.1.0
	 * @param array $title Title of the column.
	 * @return array $title
	 */
	public function monk_language_column_head( $title ) {
		$title['languages'] = __( 'Language', 'monk' );
		return $title;
	}

	/**
	 * Include styles related to Customize options.
	 *
	 * @since  0.1.0
	 * @param string $column_name Title of the column.
	 * @param string $post_id    Post id.
	 * @return  void
	 */
	public function monk_language_column_content( $column_name, $post_id ) {
		if ( 'languages' === $column_name ) {
			$monk_languages = monk_get_available_languages();

			$monk_language        = get_post_meta( $post_id, '_monk_post_language', true );
			$slug                 = ! empty( $monk_language ) ? $monk_languages[ $monk_language ]['slug'] : '';
			$monk_translations_id = get_post_meta( $post_id, '_monk_post_translations_id', true );
			$monk_translations    = get_option( 'monk_post_translations_' . $monk_translations_id, false );
			$default_language     = $this->default_language;
			$base_url             = admin_url( 'post.php?action=edit' );
			$active_languages     = $this->active_languages;
			$post_type            = get_query_var( 'post_type' );
			$post_type            = isset( $post_type ) && ! empty( $post_type ) ? sanitize_text_field( wp_unslash( $post_type ) ) : false;
			$available_languages  = false;
			$post_url             = add_query_arg( array(
				'post' => $post_id,
			), $base_url );

			if ( ! $monk_translations_id ) {
				$monk_translations_id = $post_id;
			}

			if ( 'post' !== $post_type ) {
				$new_url = add_query_arg( array(
					'post_type' => $post_type,
					'monk_id'   => $monk_translations_id,
				), admin_url( 'post-new.php' ) );
			} else {
				$new_url = add_query_arg( array(
					'monk_id' => $monk_translations_id,
				), admin_url( 'post-new.php' ) );
			}

			foreach ( $active_languages as $language ) {
				if ( ! $monk_language || $monk_translations && ! array_key_exists( $language, $monk_translations ) ) {
					$available_languages = true;
				}
			}

			require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/monk-language-column.php';
		} // End if().
	}

	/**
	 * Add select term language
	 *
	 * @since  0.1.0
	 * @return  void
	 */
	public function monk_custom_taxonomy_field() {
		$monk_languages   = monk_get_available_languages();
		$languages        = $this->active_languages;
		$taxonomies       = get_taxonomies();
		$default_language = $this->default_language;

		require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-monk-language-term.php';
	}

	/**
	 * Saves the term language on a new term creation
	 *
	 * @since  0.1.0
	 *
	 * @param   int    $term_id Id of the term.
	 * @param   int    $tt_id Term taxonomy ID.
	 * @param   string $taxonomy Taxonomy slug.
	 * @return  void
	 */
	public function monk_create_term_meta( $term_id, $tt_id, $taxonomy ) {
		$monk_language = filter_input( INPUT_POST, 'monk_language' );

		if ( ! empty( $monk_language ) ) {

			$active_languages  = $this->active_languages;
			$language          = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'monk_language' ) ) );
			$is_menu           = 'nav_menu' === $taxonomy ? 'menu' : 'term';
			$monk_id           = 'menu' === $is_menu ? filter_input( INPUT_GET, 'monk_id' ) : filter_input( INPUT_POST, 'monk_id' );
			$term_translations = array(
				$language => $term_id,
			);

			if ( isset( $monk_id ) ) {
				$monk_id           = sanitize_text_field( wp_unslash( $monk_id ) );
				$term_translations = get_option( 'monk_' . $is_menu . '_translations_' . $monk_id, array() );

				if ( in_array( $language, $active_languages, true ) && ( ! array_key_exists( $language, $term_translations ) || empty( $term_translations ) ) ) {
					add_term_meta( $term_id, '_monk_' . $is_menu . '_language', $language, true );

					if ( false !== $term_translations ) {
						add_term_meta( $term_id, '_monk_' . $is_menu . '_translations_id', $monk_id, true );
						$term_translations[ $language ] = $term_id;
						update_option( 'monk_' . $is_menu . '_translations_' . $monk_id, $term_translations );
					} else {
						add_term_meta( $term_id, '_monk_' . $is_menu . '_translations_id', $term_id, true );
						update_option( 'monk_' . $is_menu . '_translations_' . $term_id, $term_translations );
					}
				}
			} else {
				add_term_meta( $term_id, '_monk_' . $is_menu . '_language', $language, true );
				add_term_meta( $term_id, '_monk_' . $is_menu . '_translations_id', $term_id, true );
				update_option( 'monk_' . $is_menu . '_translations_' . $term_id, $term_translations );
			}
		}
	}

	/**
	 * Update term language
	 *
	 * @since  0.1.0
	 * @param int $term_id  Term ID.
	 * @param int $taxonomy Taxonomy slug.
	 * @return  void
	 */
	public function monk_update_term_meta( $term_id, $taxonomy ) {

		$new_language              = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'monk_language' ) ) );
		$is_menu                   = 'nav_menu' === $taxonomy ? 'menu' : 'term';
		$current_language          = get_term_meta( $term_id, '_monk_' . $is_menu . '_language', true );
		$monk_term_translations_id = get_term_meta( $term_id, '_monk_' . $is_menu . '_translations_id', true );
		$new_language              = $new_language && ! empty( $new_language ) ? $new_language : $current_language;

		update_term_meta( $term_id, '_monk_' . $is_menu . '_language', $new_language );

		if ( ! $monk_term_translations_id ) {
			update_term_meta( $term_id, '_monk_' . $is_menu . '_translations_id', $term_id );
			$monk_term_translations_id = $term_id;
		}

		$monk_term_translations = get_option( 'monk_' . $is_menu . '_translations_' . $monk_term_translations_id, array() );

		if ( ! array_key_exists( $new_language, $monk_term_translations ) ) {
			if ( ! empty( $monk_term_translations ) ) {
				unset( $monk_term_translations[ $current_language ] );
			}

			$monk_term_translations[ $new_language ] = $term_id;
			update_option( 'monk_' . $is_menu . '_translations_' . $monk_term_translations_id, $monk_term_translations );
		}
	}

	/**
	 * Function that erases the data stored by the plugin when term is deleted.
	 *
	 * @since 0.1.0
	 *
	 * @param  int $term_id  Term ID.
	 * @param  int $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function monk_delete_term_meta( $term_id, $taxonomy ) {

		$is_menu                   = 'nav_menu' === $taxonomy ? 'menu' : 'term';
		$term_language             = get_term_meta( $term_id, '_monk_' . $is_menu . '_language', true );
		$monk_term_translations_id = get_term_meta( $term_id, '_monk_' . $is_menu . '_translations_id', true );
		$option_name               = 'monk_' . $is_menu . '_translations_' . $monk_term_translations_id;
		$monk_term_translations    = get_option( $option_name, array() );

		if ( isset( $monk_term_translations ) && $monk_term_translations ) {
			unset( $monk_term_translations[ $term_language ] );
			if ( empty( $monk_term_translations ) ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $monk_term_translations );
			}
		} else {
			delete_option( $option_name );
		}
	}

	/**
	 * Add select term language inside edit page
	 *
	 * @since  0.1.0
	 * @param Object $term Object term.
	 * @return  void
	 */
	public function monk_edit_custom_taxonomy_field( $term ) {
		$monk_languages            = monk_get_available_languages();
		$monk_language             = get_term_meta( $term->term_id, '_monk_term_language', true );
		$monk_term_translations_id = get_term_meta( $term->term_id, '_monk_term_translations_id', true );
		$option_name               = 'monk_term_translations_' . $monk_term_translations_id;
		$languages                 = $this->active_languages;
		$monk_term_translations    = get_option( $option_name, array() );

		require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-monk-language-update-term.php';
	}

	/**
	 * Add column content
	 *
	 * @since  0.1.0
	 * @param string $content    The content.
	 * @param string $column_name Title of the column.
	 * @param int    $term_id    Id of the term.
	 * @return  void
	 */
	public function monk_taxonomy_language_column_content( $content, $column_name, $term_id ) {
		if ( 'languages' === $column_name ) :
			$monk_languages            = monk_get_available_languages();
			$taxonomies                = get_taxonomies();
			$monk_language             = get_term_meta( $term_id, '_monk_term_language', true );
			$monk_term_translations_id = get_term_meta( $term_id, '_monk_term_translations_id', true );
			$languages                 = $this->active_languages;
			$monk_term_translations    = get_option( 'monk_term_translations_' . $monk_term_translations_id, array() );
			$default_language          = $this->default_language;
			$available_languages       = false;
			$post_type                 = 'none';
			$current_taxonomy          = filter_input( INPUT_GET, 'taxonomy' );

			foreach ( $languages as $language ) {
				if ( ! $monk_language || ( $monk_term_translations && ! array_key_exists( $language, $monk_term_translations ) ) ) {
					$available_languages = true;
				}
			}

			foreach ( $taxonomies as $taxonomy ) {
				if ( isset( $current_taxonomy ) ) {
					if ( $current_taxonomy === $taxonomy ) {
						$base_url = admin_url( 'term.php?taxonomy=' . $taxonomy );
						$new_url  = add_query_arg( array(
							'monk_id' => $monk_term_translations_id,
						), admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) );
					}
				}
			}

			require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/monk-language-column.php';
		endif;
	}

	/**
	 * Add term translation meta field.
	 *
	 * @since  0.1.0
	 * @param object $term Term object.
	 * @return  void
	 */
	public function monk_term_translation_meta_field( $term ) {
		$monk_languages            = monk_get_available_languages();
		$monk_language             = get_term_meta( $term->term_id, '_monk_term_language', true );
		$languages                 = $this->active_languages;
		$taxonomies                = get_taxonomies();
		$monk_term_translations_id = get_term_meta( $term->term_id, '_monk_term_translations_id', true );
		$monk_term_translations    = get_option( 'monk_term_translations_' . $monk_term_translations_id, array() );
		$available_languages       = false;
		$taxonomy                  = filter_input( INPUT_GET, 'taxonomy' );

		if ( isset( $taxonomy ) ) {
			$taxonomy = sanitize_text_field( wp_unslash( $taxonomy ) );
			if ( in_array( $taxonomy, $taxonomies ) ) {
				$base_url             = admin_url( 'edit-tags.php?taxonomy=' . $taxonomy );
				$base_url_translation = admin_url( 'term.php?taxonomy=' . $taxonomy );
			}
		}

		if ( ! is_array( $monk_term_translations ) ) {
			$monk_term_translations = array( $monk_term_translations );
		}

		foreach ( $languages as $language ) {
			if ( ! array_key_exists( $language, $monk_term_translations ) ) {
				$available_languages = true;
			}
		}

		require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-monk-term-translation.php';
	}

	/**
	 * Function to display a notice on plugin activation
	 *
	 * This function gets the user to the configuration page
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function monk_activation_notice() {
		$monk_settings_notice = get_option( 'monk_settings_notice', false );

		if ( $monk_settings_notice ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-monk-notice-render.php';
		}
	}

	/**
	 * Function to create a new media translation.
	 *
	 * @return void
	 * @since  0.2.0
	 */
	public function monk_add_attachment_translation() {
		$monk_id         = filter_input( INPUT_POST, 'monk_id' );
		$current_post_id = filter_input( INPUT_POST, 'current_post_id' );
		$lang            = filter_input( INPUT_POST, 'lang' );
		$attach_path     = get_attached_file( $current_post_id );
		$attach_url      = wp_get_attachment_url( $monk_id );

		$filetype      = wp_check_filetype( basename( $attach_path ), null );
		$wp_upload_dir = wp_upload_dir();

		$attachment = array(
			'guid'           => $attach_url,
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $attach_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id                         = wp_insert_attachment( $attachment, $attach_path );
		$monk_attach_translations          = get_option( 'monk_post_translations_' . $monk_id, false );
		$monk_attach_translations[ $lang ] = $attach_id;

		update_option( 'monk_post_translations_' . $monk_id, $monk_attach_translations );
		if ( $lang ) {
			update_post_meta( $attach_id, '_monk_post_language', $lang );
		}
		update_post_meta( $attach_id, '_monk_post_translations_id', $monk_id );

		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$attach_data = wp_generate_attachment_metadata( $attach_id, $attach_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		$monk_attach_url = admin_url( 'post.php' );
		$language_url    = add_query_arg( array(
			'post'    => $attach_id,
			'action'  => 'edit',
			'lang'    => $lang,
			'monk_id' => $monk_id,
		), $monk_attach_url );

		wp_send_json_success( $language_url );
	}

	/**
	 * Function to render input type select in medias modal.
	 *
	 * @param  object $post_id       Post object.
	 * @param  string $language_code Current post language.
	 * @return string $monk_attach_options
	 * @since  0.2.0
	 */
	public function monk_language_selector_render( $post_id, $language_code = false ) {
		$monk_languages = monk_get_available_languages();

		$monk_id             = get_post_meta( $post_id, '_monk_post_translations_id', true );
		$language            = get_post_meta( $post_id, '_monk_post_language', true );
		$slug                = $monk_languages[ $language ]['slug'];
		$active_languages    = $this->active_languages;
		$default_language    = $this->default_language;
		$post_translations   = get_option( 'monk_post_translations_' . $monk_id, false );
		$post                = filter_input( INPUT_GET, 'post' );
		$is_modal            = ! isset( $post ) ? true : false;
		$post_type           = get_post_type( $post_id );
		$available_languages = false;

		foreach ( $active_languages as $lang ) {
			if ( ( $post_translations && ! array_key_exists( $lang, $post_translations ) ) ) {
				$available_languages = true;
			}
		}

		ob_start();
		if ( 'attachment' === $post_type && $is_modal ) {
			if ( $language_code ) {
				require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-monk-language-selector-render.php';
			} else {
				require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-monk-language-name-render.php';
			}
			$monk_attach_options = ob_get_contents();
			ob_end_clean();

			if ( ! $monk_attach_options ) {
				$monk_attach_options = __( 'No translations available', 'monk' );
			}
			return $monk_attach_options;
		}
	}

	/**
	 * Function to save attachment language, monk id and translations array when media is uploaded on modal.
	 *
	 * @param  object $post        Post object.
	 * @param  array  $attachment  Form fields array.
	 * @return array  $post        Post object.
	 * @since  0.2.0
	 */
	public function monk_fields_to_save( $post, $attachment ) {
		$active_languages = $this->active_languages;
		$referer          = wp_parse_url( $_SERVER['HTTP_REFERER'] );

		if ( 'upload.php' !== substr( strrchr( $referer['path'], '/' ), 1 ) ) {
			if ( isset( $attachment['language'] ) ) {
				if ( is_array( $active_languages ) ) {
					if ( in_array( $attachment['language'], $active_languages ) ) {
						update_post_meta( $post['ID'], '_monk_post_language', $attachment['language'] );
						update_option( 'monk_post_translations_' . $post['ID'], array(
							$attachment['language'] => $post['ID'],
						) );
						update_post_meta( $post['ID'], '_monk_post_translations_id', $post['ID'] );
						$attachment['language'] = get_post_meta( $post['ID'], '_monk_post_language', true );
					}
				} else {
					if ( $attachment['language'] === $active_languages ) {
						update_post_meta( $post['ID'], '_monk_post_language', $attachment['language'] );
						update_option( 'monk_post_translations_' . $post['ID'], array(
							$attachment['language'] => $post['ID'],
						) );
						update_post_meta( $post['ID'], '_monk_post_translations_id', $post['ID'] );
						$attachment['language'] = get_post_meta( $post['ID'], '_monk_post_language', true );
					}
				}
			}
		}
		return $post;
	}

	/**
	 * Function to display meta fields in medias modal on grid mode.
	 *
	 * @param  array  $form_fields Form fields array.
	 * @param  object $post        Post object.
	 * @return array  $form_fields Form fields array.
	 * @since  0.2.0
	 */
	public function monk_attachment_meta_box( $form_fields, $post ) {
		$monk_languages = monk_get_available_languages();

		$post_id           = $post->ID;
		$language          = get_post_meta( $post_id, '_monk_post_language', true );
		$new_post_language = monk_get_url_args( 'lang' );
		$requested_post_id = filter_input( INPUT_POST, 'post_id' );
		$post_language     = isset( $requested_post_id ) ? get_post_meta( $requested_post_id, '_monk_post_language', true ) : '';
		$default_language  = $this->default_language;
		$requested_post    = filter_input( INPUT_POST, 'post' );
		$is_modal          = ! isset( $requested_post ) ? true : false;
		$post_type         = $post->post_type;
		$is_translatable   = true;

		if ( $language ) {
			$lang_code = $language;
			$language  = $this->monk_language_selector_render( $post_id );
		} elseif ( ! $language && $post_language ) {
			$lang_code = $post_language;
			$language  = $monk_languages[ $post_language ]['english_name'];
		} elseif ( ! $language && $new_post_language ) {
			$lang_code = $new_post_language;
			$language  = $monk_languages[ $new_post_language ]['english_name'];
		} else {
			$lang_code = $default_language;
			$language  = $monk_languages[ $default_language ]['english_name'];
		}

		$referer = wp_parse_url( $_SERVER['HTTP_REFERER'] );

		if ( 'upload.php' !== substr( strrchr( $referer['path'], '/' ), 1 ) ) {
			$is_translatable = false;
		}

		if ( $is_translatable && ! get_post_meta( $post_id, '_monk_post_language', true ) ) {
			$language = '-';
		}

		if ( ! $is_translatable && ! get_post_meta( $post_id, '_monk_post_language', true ) ) {
			$language = $this->monk_language_selector_render( $post_id );
		}

		if ( 'attachment' === $post_type && $is_modal ) {
			$form_fields['language'] = array(
				'input' => 'html',
				'html'  => $language,
				'label' => __( 'Language', 'monk' ),
			);
			if ( $is_translatable ) {
				$form_fields['translate'] = array(
					'input' => 'html',
					'html'  => $this->monk_language_selector_render( $post_id, $lang_code ),
					'label' => __( 'Translate', 'monk' ),
				);
			}
		}
		return $form_fields;
	}

	/**
	 * This function shows a message if widget needs activation.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function monk_widget_message() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-monk-widget-notice.php';
	}

	/**
	 * This function is helper to monk_delete_attachment_file function.
	 *
	 * @param  int $post_id Id of the post.
	 * @since  0.2.0
	 * @return void
	 */
	public function monk_delete_attachment( $post_id ) {
		global $monk_id;
		$monk_id = get_post_meta( $post_id, '_monk_post_translations_id', true );
	}

	/**
	 * This function prevents that the media file to be deleted if attachment has translations.
	 *
	 * @param  string $file The path of media file.
	 * @since  0.2.0
	 * @return string $file
	 */
	public function monk_delete_attachment_file( $file ) {
		global $monk_id;
		$monk_translations = get_option( 'monk_post_translations_' . $monk_id, array() );

		if ( 1 <= count( $monk_translations ) ) {
			$file = '';
		}

		return $file;
	}

	/**
	 * This function sets the view mode to list when the user first accesses the media list page.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	public function define_view_mode() {
		global $current_screen;

		$is_first_access = get_option( 'monk_first_media_list_access', false );

		if ( 'upload' === $current_screen->base && $is_first_access ) {
			delete_option( 'monk_first_media_list_access' );
			$url = add_query_arg( 'mode', 'list', admin_url( $current_screen->base . '.php' ) );
			wp_safe_redirect( $url, 303 );
			exit();
		}
	}

	/**
	 * This function filter attachments by meta_key in post edit page.
	 *
	 * @since  0.2.0
	 * @param object $query Object Query.
	 * @return  void
	 */
	public function medias_modal_filter( $query ) {
		$post_id = filter_input( INPUT_POST, 'post_id' );
		$action  = filter_input( INPUT_POST, 'action' );

		if ( is_admin() && ( isset( $post_id ) && '0' !== $post_id ) && isset( $action ) ) {

			$default_language = $this->default_language;
			$language         = get_post_meta( $post_id, '_monk_post_language', true );

			if ( empty( $language ) ) {
				$lang_from_url = monk_get_url_args( 'lang' );

				if ( ! empty( $lang_from_url ) ) {
					$language = $lang_from_url;
				} else {
					$language = $default_language;
				}
			}

			if ( 'query-attachments' === $action ) {
				if ( $language !== $default_language ) {
					$query->set( 'meta_key', '_monk_post_language' );
					$query->set( 'meta_value', $language );
				} else {
					$meta_query_args = array(
						'relation' => 'OR', // Optional, defaults to "AND".
						array(
							'key'   => '_monk_post_language',
							'value' => $default_language,
						),
						array(
							'key'     => '_monk_post_language',
							'compare' => 'NOT EXISTS',
						),
					);

					$query->set( 'meta_query', $meta_query_args );
				}
			}
		} // End if().
	}

	/**
	 * Adds a language filter to the terms pages
	 *
	 * There are no hooks to use here, so we create the
	 * components using the admin_footer action and move them to the right location
	 *
	 * @since    0.3.0
	 *
	 * @return void
	 */
	public function monk_add_term_language_filter() {
		$screen = $this->get_current_screen();
		if ( $screen ) {
			if ( 'edit-tags' !== $screen->base || ( 'edit-tags' === $screen->base && 'post_tag' === $screen->taxonomy ) ) {
				return;
			}
		}
		$monk_languages = monk_get_available_languages();
		$languages      = get_option( 'monk_active_languages', array() );
		$url            = monk_get_current_url();
		$action_url     = add_query_arg( 'lang', '', $url );

		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-term-language-selector-render.php';
	}

	/**
	 * Adds new menu components to allow their translation
	 *
	 * There are no hooks to use in the menu edit page, so we create the
	 * components using the admin_footer action and move them to the right location
	 *
	 * @since    0.3.0
	 *
	 * @return void
	 */
	public function monk_add_menu_translation_fields() {
		$screen = $this->get_current_screen();
		if ( $screen ) {
			if ( 'nav-menus' !== $screen->base || 'locations' === filter_input( INPUT_GET, 'action' ) ) {
				return;
			}
		}

		$active_languages = $this->active_languages;
		$default_language = $this->default_language;

		if ( '0' === filter_input( INPUT_GET, 'menu' ) ) {
			$monk_id = filter_input( INPUT_GET, 'monk_id' );
			if ( $monk_id ) {
				$menu_translations = get_option( 'monk_menu_translations_' . $monk_id, array() );
			} else {
				$menu_translations = array();
			}
			require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-new-menu-fields-render.php';
		} else {
			$menu                = filter_input( INPUT_GET, 'menu' );
			$menu_id             = empty( $menu ) || 'delete' === filter_input( INPUT_GET, 'action' ) ? get_user_option( 'nav_menu_recently_edited' ) : $menu;
			$monk_id             = get_term_meta( $menu_id, '_monk_menu_translations_id', true );
			$monk_id             = empty( $monk_id ) ? $menu_id : $monk_id;
			$menu_language       = get_term_meta( $menu_id, '_monk_menu_language', true );
			$menu_translations   = get_option( 'monk_menu_translations_' . $monk_id, array() );
			$new_translation_url = admin_url( 'nav-menus.php?action=edit&menu=0&monk_id=' . $monk_id );
			$translation_counter = 0;

			foreach ( $active_languages as $code ) {
				if ( $menu_translations && array_key_exists( $code, $menu_translations ) ) {
					$translation_counter = $translation_counter++;
				}
			}

			require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-menu-translation-fields-render.php';
		}
	}

	/**
	 * Change nav_menu page components to improve the user experience
	 *
	 * There are no hooks to use in the menu edit page, so we create the
	 * components using the admin_footer action and move them to the right location
	 *
	 * @since    0.3.0
	 *
	 * @return void
	 */
	public function monk_change_nav_menu_fields() {
		$screen = $this->get_current_screen();
		if ( $screen && 'nav-menus' !== $screen->base ) {
			return;
		}

		$args             = array(
			'hide_empty' => false,
		);
		$nav_menus        = get_terms( 'nav_menu', $args );
		$monk_ids         = array();
		$menu             = filter_input( INPUT_GET, 'menu' );
		$current_id       = empty( $menu ) || 'delete' === filter_input( INPUT_GET, 'action' ) ? get_user_option( 'nav_menu_recently_edited' ) : filter_input( INPUT_GET, 'menu' );
		$registered_menus = get_registered_nav_menus();
		$menus            = get_nav_menu_locations();
		$current_menus    = get_theme_mod( 'nav_menu_locations' );
		$default_language = $this->default_language;

		foreach ( $nav_menus as $nav_menu ) {
			$menu_id = $nav_menu->term_id;
			$monk_id = get_term_meta( $menu_id, '_monk_menu_translations_id', true );

			if ( ! in_array( $monk_id, $monk_ids ) ) {
				if ( ! empty( $monk_id ) ) {
					$monk_ids[] = (int) $monk_id;
				} else {
					$monk_ids[] = $menu_id;
				}
			}
		}
		require_once plugin_dir_path( __FILE__ ) . '/partials/admin-monk-select-menu-to-edit-render.php';
	}

	/**
	 * Download language packages according to active languages
	 *
	 * Update active and default languages.
	 *
	 * @param  array $active_languages Active languages array.
	 *
	 * @since    0.4.0
	 *
	 * @return array
	 */
	public function monk_save_language_packages( $active_languages ) {
		require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

		foreach ( $active_languages as $language ) {
			if ( 'en_US' !== $language ) {
				$download_pack = wp_download_language_pack( $language );

				if ( $download_pack ) {
					$response[] = true;
				} else {
					$response[] = false;
				}
			}
		}

		if ( in_array( false, $response, true ) ) {
			$response = false;
		} else {
			$response = true;
		}

		return $response;
	}

	/**
	 * Create 'Uncategorized' translations according to active languages
	 *
	 * @param  array $active_languages Active languages array.
	 *
	 * @since    0.5.0
	 *
	 * @return bool
	 */
	public function create_uncategorized_translations( $active_languages ) {
		global $monk_uncategorized_translations;

		$default_post_category = get_term( get_option( 'default_category' ) );

		foreach ( $active_languages as $language ) {
			$has_term = get_term_by( 'name', $monk_uncategorized_translations[ $language ], 'category' );
			if ( ! $has_term ) {
				$uncategorized_term = get_term_by( 'id', 1, 'category' );
				if ( $uncategorized_term && $uncategorized_term->term_id === $default_post_category->term_id ) {
					$new_term = wp_insert_term( $monk_uncategorized_translations[ $language ], 'category' );
					if ( ! is_wp_error( $new_term ) ) {
						$term_translations[ $language ] = $new_term['term_id'];
						update_term_meta( $new_term['term_id'], '_monk_term_language', $language );
						update_term_meta( $new_term['term_id'], '_monk_term_translations_id', $default_post_category->term_id );
					}
				}
			} else {
				$term_translations[ $language ] = $has_term->term_id;
			}
		}

		$monk_old_translations = get_option( 'monk_term_translations_' . $default_post_category->term_id, array() );

		if ( $monk_old_translations === $term_translations ) {
			$response = true;
		} else {
			$response = update_option( 'monk_term_translations_' . $default_post_category->term_id, $term_translations );
		}

		return $response;
	}

	/**
	 * Download language packages according to active languages
	 *
	 * Update active and default languages.
	 *
	 * @since    0.4.0
	 *
	 * @return void
	 */
	public function monk_save_general_form_settings() {
		if ( check_ajax_referer( '_monk_save_general_settings', '_monk_save_general_settings', false ) ) {
			$active_languages = filter_input( INPUT_POST, 'monk_active_languages', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$response         = array();

			$response[] = $this->monk_save_language_packages( $active_languages ) ? true : false;
			$response[] = $this->create_uncategorized_translations( $active_languages ) ? true : false;

			wp_send_json_success( $response );
		} else {
			wp_send_json_error();
		} // End if().
	}

	/**
	 * Function to set default language to all posts and term without language
	 *
	 * @since    0.4.0
	 *
	 * @return void
	 */
	public function monk_set_language_to_elements() {
		if ( check_ajax_referer( '_monk_tools', false, false ) ) {
			$monk_set_language_to_elements = filter_input( INPUT_POST, 'monk_set_language_to_elements' );

			global $wpdb;
			$default_language = $this->default_language;
			$post_types       = get_post_types( array(
				'public'   => true,
				'_builtin' => false,
			), 'names', 'OR');

			$i = 0;
			foreach ( $post_types as $post_type ) {
				if ( 0 === $i ) {
					$posts_where = "( post_type = '$post_type'";
					$i++;
				} else {
					$posts_where .= " OR post_type = '$post_type'";
				}
			}
			$posts_where .= ')';

			$post_ids = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE $posts_where AND NOT EXISTS ( SELECT post_id FROM $wpdb->postmeta WHERE $wpdb->posts.ID=$wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_monk_post_language' )", ARRAY_A ); // WPCS: unprepared SQL OK.

			$term_ids = $wpdb->get_results( "SELECT term_id FROM $wpdb->terms
				WHERE NOT EXISTS ( SELECT term_id FROM $wpdb->termmeta
				WHERE $wpdb->terms.term_id=$wpdb->termmeta.term_id
				AND $wpdb->termmeta.meta_key = '_monk_term_language' )", ARRAY_A );

			if ( is_array( $post_ids ) && ! empty( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					$set_language          = $wpdb->insert( 'wp_postmeta', array(
						'post_id'    => intval( $post_id['ID'] ),
						'meta_key'   => '_monk_post_language',
						'meta_value' => $default_language,
					));
					$set_monk_id           = $wpdb->insert( 'wp_postmeta', array(
						'post_id'    => intval( $post_id['ID'] ),
						'meta_key'   => '_monk_post_translations_id',
						'meta_value' => intval( $post_id['ID'] ),
					));
					$value                 = array(
						$default_language => intval( $post_id['ID'] ),
					);
					$set_translation_array = $wpdb->insert( 'wp_options', array(
						'option_name'  => 'monk_post_translations_' . intval( $post_id['ID'] ),
						'option_value' => maybe_serialize( $value ),
						'autoload'     => 'yes',
					));
				}

				$response[] = $set_monk_id && $set_language ? true : false;
			}

			if ( is_array( $term_ids ) && ! empty( $term_ids ) ) {
				foreach ( $term_ids as $term_id ) {
					$set_language          = $wpdb->insert( 'wp_termmeta', array(
						'term_id'    => intval( $term_id['term_id'] ),
						'meta_key'   => '_monk_term_language',
						'meta_value' => $default_language,
					));
					$set_monk_id           = $wpdb->insert( 'wp_termmeta', array(
						'term_id'    => intval( $term_id['term_id'] ),
						'meta_key'   => '_monk_term_translations_id',
						'meta_value' => intval( $term_id['term_id'] ),
					));
					$value                 = array(
						$default_language => intval( $term_id['term_id'] ),
					);
					$set_translation_array = $wpdb->insert( 'wp_options', array(
						'option_name'  => 'monk_term_translations_' . $term_id['term_id'],
						'option_value' => maybe_serialize( $value ),
						'autoload'     => 'yes',
					));
				}
				$response[] = $set_monk_id && $set_language ? true : false;
			}
			wp_send_json_success();
		} else {
			wp_send_json_error();
		} // End if().
	}

	/**
	 * Function to save site options and your translations
	 *
	 * @since    0.5.0
	 *
	 * @return void
	 */
	public function monk_save_site_options() {
		if ( check_ajax_referer( '_monk_site_options', false, false ) ) {
			$default_language = $this->default_language;
			$active_languages = $this->active_languages;
			$default_blogname = filter_input( INPUT_POST, 'blogname' );
			$default_blogdesc = filter_input( INPUT_POST, 'blogdescription' );

			update_option( 'blogname', $default_blogname );
			update_option( 'blogdescription', $default_blogdesc );

			foreach ( $active_languages as $lang ) {
				if ( $lang !== $default_language ) {
					$blogname_value = filter_input( INPUT_POST, 'monk_' . $lang . '_blogname' );
					$blogdesc_value = filter_input( INPUT_POST, 'monk_' . $lang . '_blogdescription' );
					if ( ! empty( $blogname_value ) ) {
						update_option( 'monk_' . $lang . '_blogname', $blogname_value );
					}
					if ( ! empty( $blogdesc_value ) ) {
						update_option( 'monk_' . $lang . '_blogdescription', $blogdesc_value );
					}
				}
			}
			wp_send_json_success();
		} else {
			wp_send_json_error();
		} // End if().
	}

	/**
	 * Function that creates a shortcode on posts to retrieve a link
	 * for one of its translations.
	 *
	 * @since    0.5.0
	 *
	 * @param    array $atts The user defined data.
	 * @return string $translation_link
	 */
	public function monk_language_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'text'     => '',
				'language' => '',
				'class'    => '',
			), $atts
		);

		$base_id      = get_post_meta( get_queried_object_id(), '_monk_post_translations_id', true );
		$translations = get_option( 'monk_post_translations_' . $base_id, false );

		if ( ! empty( $translations[ $atts['language'] ] ) ) {
			$translation      = $translations[ $atts['language'] ];
			$translation_link = '<a href="' . esc_attr( get_permalink( $translation ) ) . '" class="' . esc_attr( $atts['class'] ) . '">' . esc_html( $atts['text'] ) . '</a>';
			return $translation_link;
		} else {
			return;
		}
	}

	/**
	 * Function to filter comments by their post language.
	 *
	 * @uses the comments_clauses filter
	 *
	 * @since    0.7.0
	 *
	 * @param    array $clauses Array with the query pieces to be filtered.
	 * @return  array $clauses
	 */
	public function monk_admin_filter_comment_clauses( $clauses ) {
		$screen = $this->get_current_screen();
		$lang   = filter_input( INPUT_GET, 'lang' );

		if ( ! is_admin() || 'edit-comments' !== $screen->base || 'all' === $lang ) {
			return $clauses;
		}

		global $wpdb;

		$default_language = get_option( 'monk_default_language', false );
		$lang             = empty( $lang ) ? $default_language : $lang;
		$replace          = array( '(', ')' );
		$comment_status   = str_replace( $replace, '', $clauses['where'] );
		$comment_status   = '(' . $comment_status . ')';
		$not_exists       = '';

		if ( $default_language === $lang ) {
			$not_exists = " ( SELECT ID FROM $wpdb->posts WHERE ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE $wpdb->postmeta.meta_key = '_monk_post_language' ) AND comment_count > 0 ) or comment_post_id IN ";
		}

		$clauses['where'] = 'comment_post_id in ' . $not_exists . " ( select ID FROM $wpdb->posts WHERE ID IN ( select post_id FROM $wpdb->postmeta WHERE $wpdb->postmeta.meta_key = '_monk_post_language' AND $wpdb->postmeta.meta_value = '$lang' ) AND comment_count > 0 ) AND " . $comment_status;

		$clauses['orderby'] = str_replace( 'wp_comments.', '', $clauses['orderby'] );

		return $clauses;
	}

	/**
	 * Function to add a language selector to comments admin page.
	 *
	 * @uses the restrict_manage_comments action
	 *
	 * @since    0.7.0
	 *
	 * @return  void
	 */
	public function monk_admin_add_comments_language_selector() {
		$comment_status = filter_input( INPUT_GET, 'comment_status' );
		$comment_status = 'all' === $comment_status ? 'total_comments' : $comment_status;
		$comment_status = empty( $comment_status ) ? 'total_comments' : $comment_status;
		$comment_class  = wp_count_comments();

		foreach ( $comment_class as $status => $number ) {

			if ( $comment_status === $status && intval( $number ) > 0 ) {
				$monk_languages = monk_get_available_languages();
				$languages      = get_option( 'monk_active_languages', array() );
				$url_language   = filter_input( INPUT_GET, 'lang' );

				require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-monk-comments-language-selector-render.php';
				break;
			}
		}
	}
}
