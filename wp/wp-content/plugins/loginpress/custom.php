<?php

class LoginPress_Entities {

	/**
	 * Variable that Check for LoginPress Key.
	 *
	 * @var string
	 * @since 1.0.0
	 * @version 3.0.0
	 */
	public $loginpress_key;

	/**
	 * LoginPress template name.
	 *
	 * @since 1.6.4
	 * @var string LoginPress template name.
	 */
	public $loginpress_preset;

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->loginpress_key    = get_option( 'loginpress_customization' );
		$this->loginpress_preset = get_option( 'customize_presets_settings', true );
		$this->_hooks();
	}


	/**
	 * Hook into actions and filters
	 *
	 * @since 1.0.0
	 * @version 1.4.0
	 */
	private function _hooks() {

		add_filter( 'login_title', array( $this, 'login_page_title' ), 99 );
		add_filter( 'login_headerurl', array( $this, 'login_page_logo_url' ) );
		if ( version_compare( $GLOBALS['wp_version'], '5.2', '<' ) ) {
			add_filter( 'login_headertitle', array( $this, 'login_page_logo_title' ) );
		} else {
			add_filter( 'login_headertext', array( $this, 'login_page_logo_title' ) );
		}
		add_filter( 'login_errors', array( $this, 'login_error_messages' ) );
		add_filter( 'login_message', array( $this, 'change_welcome_message' ) );
		add_action( 'customize_register', array( $this, 'customize_login_panel' ) );
		add_action( 'login_footer', array( $this, 'login_page_custom_footer' ) );
		add_filter( 'site_icon_meta_tags', array( $this, 'login_page_custom_favicon' ), 1, 1 );
		add_action( 'login_head', array( $this, 'login_page_custom_head' ) );
		add_action( 'init', array( $this, 'redirect_to_custom_page' ) );
		add_action( 'init', array( $this, 'loginpress_lostpassword_url_changed' ) );
		add_action( 'admin_menu', array( $this, 'menu_url' ), 10 );
		add_filter( 'wp_login_errors', array( $this, 'remove_error_messages_in_wp_customizer' ), 10, 2 );
		add_action( 'login_enqueue_scripts', array( $this, 'loginpress_login_page_scripts' ) );

		if ( version_compare( $GLOBALS['wp_version'], '5.9', '>=' ) ) {
			add_filter( 'login_display_language_dropdown', array( $this, 'loginpress_language_switch' ) );
		}

		/**
		 * This function enqueues scripts and styles in the Customizer.
		 */
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'loginpress_customizer_js' ) );

		/**
		 * This function is triggered on the initialization of the Previewer in the Customizer.
		 * We add actions that pertain to the Previewer window here.
		 * The actions added here are triggered only in the Previewer and not in the Customizer.
		 *
		 * @since 1.0.23
		 */
		add_action( 'customize_preview_init', array( $this, 'loginpress_customizer_previewer_js' ) );
		add_filter( 'woocommerce_process_login_errors', array( $this, 'loginpress_woo_login_errors' ), 10, 3 );
	}

	/**
	 * Login Page Custom Favicon ( Overwrites the default meta tags if favicon is set from LoginPress ).
	 *
	 * @param array $meta_tags default meta tags for login page.
	 * @return array $meta_tags modified meta tags for login page.
	 *
	 * @since 3.0.0
	 */
	function login_page_custom_favicon( $meta_tags ) {
		$login_favicon = isset( $this->loginpress_key['login_favicon'] ) ? $this->loginpress_key['login_favicon'] : 'off';

		if ( has_site_icon() && ! empty( $login_favicon ) ) {

			/**
			 * If Login favicon is set then only show the favicon of login page else site icon will be shown.
			 */
			if ( 'off' != $login_favicon && function_exists( 'login_header' ) ) {
				unset( $meta_tags );
				$meta_tags[] = '<link rel="shortcut icon" href="' . $login_favicon . '" type="image/x-icon" />';
			}
		}
		return $meta_tags;
	}

	/**
	 * Login Page YouTube Video Background scripts.
	 *
	 * @since 3.0.0
	 */
	function loginpress_login_page_scripts() {

		$loginpress_customization = get_option( 'loginpress_customization' );
		$loginpress_yt_id         = isset( $loginpress_customization['yt_video_id'] ) && ! empty( $loginpress_customization['yt_video_id'] ) ? $loginpress_customization['yt_video_id'] : false;

		if ( $loginpress_yt_id ) {
			wp_enqueue_script( 'loginpress-yt-iframe', 'https://www.youtube.com/iframe_api' );
		}
	}

	/**
	 * Enqueue jQuery and use wp_localize_script.
	 *
	 * @since 1.0.9
	 * @version 3.0.0
	 */
	function loginpress_customizer_js() {

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'loginpress-customize-control', plugins_url( 'js/customize-controls.js', LOGINPRESS_ROOT_FILE ), array( 'jquery' ), LOGINPRESS_VERSION, true );

		/*
		 * Our Customizer script
		 *
		 * Dependencies: Customizer Controls script (core)
		 */
		wp_enqueue_script( 'loginpress-control-script', plugins_url( 'js/customizer.js', LOGINPRESS_ROOT_FILE ), array( 'customize-controls' ), LOGINPRESS_VERSION, true );

		// Get Background URL for use in Customizer JS.
		$user              = wp_get_current_user();
		$name              = empty( $user->user_firstname ) ? ucfirst( $user->display_name ) : ucfirst( $user->user_firstname );
		$loginpress_bg     = get_option( 'loginpress_customization' );
		$loginpress_st     = get_option( 'loginpress_setting' );
		$cap_type          = isset( $loginpress_st['recaptcha_type'] ) ? $loginpress_st['recaptcha_type'] : 'v2-robot'; // 1.2.1
		$loginpress_bg_url = $loginpress_bg['setting_background'] ? $loginpress_bg['setting_background'] : false;

		/**
		 * Included in version 1.2.0.
		 */
		if ( isset( $_GET['autofocus'] ) && $_GET['autofocus'] === 'loginpress_panel' ) {
			$loginpress_auto_focus = true;
		} else {
			$loginpress_auto_focus = false;
		}

		// Array for localize.
		$loginpress_localize = array(
			'admin_url'          => admin_url(),
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'plugin_url'         => plugins_url(),
			'login_theme'        => $this->loginpress_preset,
			'loginpress_bg_url'  => $loginpress_bg_url,
			'preset_nonce'       => wp_create_nonce( 'loginpress-preset-nonce' ),
			'attachment_nonce'   => wp_create_nonce( 'loginpress-attachment-nonce' ),
			'preset_loader'      => includes_url( 'js/tinymce/skins/lightgray/img/loader.gif' ),
			'autoFocusPanel'     => $loginpress_auto_focus,
			'recaptchaType'      => $cap_type,
			'filter_bg'          => apply_filters( 'loginpress_default_bg', '' ),
			'translated_strings' => array(
				'wrong_yt_id' => _x( 'Wrong YouTube Video ID', 'Wrong YouTube Video ID (Customizer)', 'loginpress' ),
			),
		);

		wp_localize_script( 'loginpress-customize-control', 'loginpress_script', $loginpress_localize );
	}

	/**
	 * This function is called only on the Previewer and enqueues scripts and styles.
	 * Our Customizer script
	 *
	 * Dependencies: Customizer Preview script (core)
	 *
	 * @since 1.0.23
	 */
	function loginpress_customizer_previewer_js() {

		wp_enqueue_style( 'loginpress-customizer-previewer-style', plugins_url( 'css/style-previewer.css', LOGINPRESS_ROOT_FILE ), array(), LOGINPRESS_VERSION );
		wp_enqueue_script( 'loginpress-customizer-previewer-script', plugins_url( 'js/customizer-previewer.js', LOGINPRESS_ROOT_FILE ), array( 'customize-preview' ), LOGINPRESS_VERSION, true );
	}

	/**
	 * Creates a method for setting and controlling LoginPress_Range_Control.
	 *
	 * @param object $wp_customize The WordPress Customize object.
	 * @param string $control The control name.
	 * @param string $default The default value.
	 * @param string $label The label for the control.
	 * @param array  $input_attr Additional input attributes.
	 * @param array  $unit The unit for the control value.
	 * @param int    $index The index of the control.
	 * @param int    $priority To set the Priority of the section.
	 *
	 * @return object The modified WordPress Customize object.
	 *
	 * @since 1.1.3
	 */
	function loginpress_range_setting( $wp_customize, $control, $default, $label, $input_attr, $unit, $section, $index, $priority = '' ) {

		$wp_customize->add_setting(
			"loginpress_customization[{$control[$index]}]",
			array(
				'default'           => $default[ $index ],
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			new LoginPress_Range_Control(
				$wp_customize,
				"loginpress_customization[{$control[$index]}]",
				array(
					'type'        => 'loginpress-range',
					'label'       => $label[ $index ],
					'section'     => $section,
					'priority'    => $priority,
					'settings'    => "loginpress_customization[{$control[$index]}]",
					'default'     => $default[ $index ],
					'input_attrs' => $input_attr[ $index ],
					'unit'        => $unit[ $index ],
				)
			)
		);
	}


	/** Creates a method for setting and controlling LoginPress_Group_Control.
	 *
	 * @param object $wp_customize The WordPress Customize object.
	 * @param string $control The control name.
	 * @param string $label The label for the control.
	 * @param string $section The section name.
	 * @param string $info_text The information text.
	 * @param int    $index The index of the control.
	 * @param int    $priority To set the Priority of the section.
	 *
	 * @return object The modified WordPress Customize object.
	 *
	 * @since 1.1.3
	 */
	function loginpress_group_setting( $wp_customize, $control, $label, $info_test, $section, $index, $priority = '' ) {

		$wp_customize->add_setting(
			"loginpress_customization[{$control[$index]}]",
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			)
		);

		$wp_customize->add_control(
			new LoginPress_Group_Control(
				$wp_customize,
				"loginpress_customization[{$control[$index]}]",
				array(
					'settings'  => "loginpress_customization[{$control[$index]}]",
					'label'     => $label[ $index ],
					'section'   => $section,
					'type'      => 'group',
					'info_text' => $info_test[ $index ],
					'priority'  => $priority,
				)
			)
		);
	}

	/** Creates a method for setting and controlling WP_Customize_Color_Control.
	 *
	 * @param object $wp_customize The WordPress Customize object.
	 * @param string $control The control name.
	 * @param string $label The label for the control.
	 * @param string $section The section name.
	 * @param int    $index The index of the control.
	 * @param int    $priority To set the Priority of the section.
	 *
	 * @return object The modified WordPress Customize object.
	 * @since 1.1.3
	 */
	function loginpress_color_setting( $wp_customize, $control, $label, $section, $index, $priority = '' ) {

		$wp_customize->add_setting(
			"loginpress_customization[{$control[$index]}]",
			array(
				// 'default'                => $form_color_default[$form_color],
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				"loginpress_customization[{$control[$index]}]",
				array(
					'label'    => $label[ $index ],
					'section'  => $section,
					'settings' => "loginpress_customization[{$control[$index]}]",
					'priority' => $priority,
				)
			)
		);
	}

	/**
	 * Creates a thematic break.
	 *
	 * @param object $wp_customize The WordPress Customize object.
	 * @param string $control The control name.
	 * @param string $section The section name.
	 * @param int    $index The index of the control.
	 * @param int    $priority To set the Priority of the section.
	 *
	 * @since 1.1.3
	 * @version 3.0.0
	 */
	function loginpress_hr_setting( $wp_customize, $control, $section, $index, $priority = '' ) {
		if ( isset( $control[ $index ] ) ) {
			$wp_customize->add_setting(
				"loginpress_customization[{$control[$index]}]",
				array(
					'sanitize_callback' => 'sanitize_text_field',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Misc_Control(
					$wp_customize,
					"loginpress_customization[{$control[$index]}]",
					array(
						'section'  => $section,
						'type'     => 'hr',
						'priority' => $priority,
					)
				)
			);
		}
	}

	/**
	 * Register plugin settings Panel in WP Customizer.
	 *
	 * @param $wp_customize The WordPress Customize object.
	 *
	 * @since 1.0.0
	 */
	public function customize_login_panel( $wp_customize ) {

		include LOGINPRESS_ROOT_PATH . 'classes/control-presets.php';

		include LOGINPRESS_ROOT_PATH . 'classes/controls/background-gallery.php';

		include LOGINPRESS_ROOT_PATH . 'classes/controls/range.php';

		include LOGINPRESS_ROOT_PATH . 'classes/controls/group.php';

		include LOGINPRESS_ROOT_PATH . 'classes/controls/radio-button.php';

		include LOGINPRESS_ROOT_PATH . 'classes/controls/miscellaneous.php';

		include LOGINPRESS_ROOT_PATH . 'include/customizer-strings.php';

		include LOGINPRESS_ROOT_PATH . 'include/customizer-validation.php';

		include LOGINPRESS_ROOT_PATH . 'classes/controls/spacing-contols.php'; // Adjust path as necessary

		if ( ! has_action( 'loginpress_pro_add_template' ) ) :
			include LOGINPRESS_ROOT_PATH . 'classes/class-loginpress-promo.php';
		endif;

		// =============================
		// = Panel for the LoginPress  =
		// =============================
		$wp_customize->add_panel(
			'loginpress_panel',
			array(
				'title'       => __( 'LoginPress', 'loginpress' ),
				'description' => __( 'Customize Your WordPress Login Page with LoginPress :)', 'loginpress' ),
				'priority'    => 30,
			)
		);

		/**
		 * Section for Presets.
		 *
		 * @since   1.0.9
		 * @version 3.0.3
		 */
		$wp_customize->add_section(
			'customize_presets',
			array(
				'title'       => __( 'Themes', 'loginpress' ),
				'description' => __( 'Choose Theme', 'loginpress' ),
				'priority'    => 1,
				'panel'       => 'loginpress_panel',
			)
		);

		$loginpress_default_theme = $this->loginpress_preset === true && ( empty( $this->loginpress_key ) && empty( $this->loginpress_setting ) ) ? 'minimalist' : 'default1';

		$wp_customize->add_setting(
			'customize_presets_settings',
			array(
				'default'    => $loginpress_default_theme,
				'type'       => 'option',
				// 'transport'  => 'postMessage',
				'capability' => 'manage_options',
			)
		);

		$loginpress_free_templates = array();
		$loginpress_theme_name     = array(
			'',
			'',
			__( 'Company', 'loginpress' ),
			__( 'Persona', 'loginpress' ),
			__( 'Corporate', 'loginpress' ),
			__( 'Corporate', 'loginpress' ),
			__( 'Startup', 'loginpress' ),
			__( 'Wedding', 'loginpress' ),
			__( 'Wedding #2', 'loginpress' ),
			__( 'Company', 'loginpress' ),
			__( 'Bikers', 'loginpress' ),
			__( 'Fitness', 'loginpress' ),
			__( 'Shopping', 'loginpress' ),
			__( 'Writers', 'loginpress' ),
			__( 'Persona', 'loginpress' ),
			__( 'Geek', 'loginpress' ),
			__( 'Innovation', 'loginpress' ),
			__( 'Photographers', 'loginpress' ),
			__( 'Animated Wapo', 'loginpress' ),
			__( 'Animated Wapo 2', 'loginpress' ),
		);

		// 1st template that is default
		$loginpress_free_templates['default1'] = array(
			'img'       => esc_url( apply_filters( 'loginpress_default_bg', plugins_url( 'img/minimalist.jpg', LOGINPRESS_PLUGIN_BASENAME ) ) ),
			'thumbnail' => esc_url( apply_filters( 'loginpress_default_bg', plugins_url( 'img/thumbnail/default-1.png', LOGINPRESS_ROOT_FILE ) ) ),
			'id'        => 'default1',
			'name'      => 'Default',
		);

		// 1st template that is default
			$loginpress_free_templates['minimalist'] = array(
				'img'       => esc_url( apply_filters( 'loginpress_default_bg', plugins_url( 'img/bg-default.jpg', LOGINPRESS_PLUGIN_BASENAME ) ) ),
				'thumbnail' => esc_url( apply_filters( 'loginpress_default_bg', plugins_url( 'img/thumbnail/free-minimalist.png', LOGINPRESS_ROOT_FILE ) ) ),
				'id'        => 'minimalist',
				'name'      => 'Minimalist',
			);

			// Loop through the templates.
			$_count = 2;
			while ( $_count <= 18 ) :

				$loginpress_free_templates[ "default{$_count}" ] = array(
					// 'img'       => plugins_url( 'img/bg.jpg', LOGINPRESS_ROOT_FILE ),
					'thumbnail' => plugins_url( "img/thumbnail/default-{$_count}.png", LOGINPRESS_ROOT_FILE ),
					'id'        => "default{$_count}",
					'name'      => $loginpress_theme_name[ $_count ],
					'pro'       => 'yes',
				);
				++$_count;
		endwhile;

			// 18th template for custom design.
			$loginpress_free_templates['default19'] = array(
				'img'       => plugins_url( 'loginpress/img/bg17.jpg', LOGINPRESS_ROOT_PATH ),
				'thumbnail' => plugins_url( 'loginpress/img/thumbnail/custom-design.png', LOGINPRESS_ROOT_PATH ),
				'id'        => 'default19',
				'name'      => __( 'Custom Design', 'loginpress' ),
				'link'      => 'yes',
			);
			$loginpress_templates                   = apply_filters( 'loginpress_pro_add_template', $loginpress_free_templates );

			$wp_customize->add_control(
				new LoginPress_Presets(
					$wp_customize,
					'customize_presets_settings',
					array(
						'section' => 'customize_presets',
						// 'label'   => __( 'Themes', 'loginpress' ),
						'choices' => $loginpress_templates,
					)
				)
			);

		// End of Presets.

		// =============================
		// = Section for Login Logo        =
		// =============================
		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'customize_logo_section', 8, 4 );
		$wp_customize->add_section(
			'customize_logo_section',
			array(
				'title'       => __( 'Logo', 'loginpress' ),
				'description' => __( 'Customize Your Logo Section', 'loginpress' ),
				'priority'    => 5,
				'panel'       => 'loginpress_panel',
			)
		);

		/**
		* [ Enable / Disable Logo Image with LoginPress_Radio_Control ]
		 *
		* @since 1.1.3
		*/

		$wp_customize->add_setting(
			'loginpress_customization[setting_logo_display]',
			array(
				'default'           => false,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);

		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[setting_logo_display]',
				array(
					'settings' => 'loginpress_customization[setting_logo_display]',
					'label'    => __( 'Disable Logo:', 'loginpress' ),
					'section'  => 'customize_logo_section',
					'priority' => 4,
					'type'     => 'ios', // light, ios, flat
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[setting_logo]',
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_image',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'loginpress_customization[setting_logo]',
				array(
					'label'    => __( 'Logo Image:', 'loginpress' ),
					'section'  => 'customize_logo_section',
					'priority' => 5,
					'settings' => 'loginpress_customization[setting_logo]',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_favicon]',
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_image',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'loginpress_customization[login_favicon]',
				array(
					'label'       => __( 'Login Favicon:', 'loginpress' ),
					'section'     => 'customize_logo_section',
					'description' => __( 'Add a custom favicon specific for your login page', 'loginpress' ),
					'priority'    => 30,
					'settings'    => 'loginpress_customization[login_favicon]',
				)
			)
		);

		/**
		 * [ Change CSS Properties Input fields with LoginPress_Range_Control ]
		 *
		 * @since 1.0.1
		 * @version 3.0.0
		 */

		$this->loginpress_range_setting( $wp_customize, $logo_range_control, $logo_range_default, $logo_range_label, $logo_range_attrs, $logo_range_unit, 'customize_logo_section', 0, 10 );
		$this->loginpress_range_setting( $wp_customize, $logo_range_control, $logo_range_default, $logo_range_label, $logo_range_attrs, $logo_range_unit, 'customize_logo_section', 1, 15 );
		$this->loginpress_range_setting( $wp_customize, $logo_range_control, $logo_range_default, $logo_range_label, $logo_range_attrs, $logo_range_unit, 'customize_logo_section', 2, 20 );

		/**
		 * Login Page meta and form logo options.
		 *
		 * @version 3.0.0
		 */
		if ( version_compare( $GLOBALS['wp_version'], '5.2', '<' ) ) {
			$loginpress_logo_title = __( 'Logo Hover Title:', 'loginpress' );
		} else {
			$loginpress_logo_title = __( 'Logo Title:', 'loginpress' );
		}
		$logo_control      = array( 'customize_logo_hover', 'customize_logo_hover_title', 'customize_login_page_title' );
		$logo_default      = array( '', '', '' );
		$logo_label        = array( __( 'Logo URL:', 'loginpress' ), $loginpress_logo_title, __( 'Login Page Title:', 'loginpress' ) );
		$logo_sanitization = array( 'esc_url_raw', 'wp_strip_all_tags', 'wp_strip_all_tags' );
		$logo_desc         = array( '', '', __( 'Login page title that is shown on WordPress login page.', 'loginpress' ) );

		$logo = 0;
		while ( $logo < 3 ) :

			$wp_customize->add_setting(
				"loginpress_customization[{$logo_control[$logo]}]",
				array(
					'default'           => $logo_default[ $logo ],
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => $logo_sanitization[ $logo ],
				)
			);

			$wp_customize->add_control(
				"loginpress_customization[{$logo_control[$logo]}]",
				array(
					'label'       => $logo_label[ $logo ],
					'section'     => 'customize_logo_section',
					'priority'    => 25,
					'settings'    => "loginpress_customization[{$logo_control[$logo]}]",
					'description' => $logo_desc[ $logo ],
				)
			);
			if ( 1 === $logo ) {
				$this->loginpress_hr_setting( $wp_customize, $close_control, 'customize_logo_section', 9, 25 );
				$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'customize_logo_section', 9, 25 );
			}
			++$logo;
		endwhile;

		// =============================
		// = Section for Background        =
		// =============================
		$wp_customize->add_section(
			'section_background',
			array(
				'title'       => __( 'Background', 'loginpress' ),
				'description' => '',
				'priority'    => 10,
				'panel'       => 'loginpress_panel',
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[setting_background_color]',
			array(
				// 'default'            => '#ddd5c3',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[setting_background_color]',
				array(
					'label'    => __( 'Background Color:', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 5,
					'settings' => 'loginpress_customization[setting_background_color]',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[loginpress_display_bg]',
			array(
				'default'           => true,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);

		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_background', 6, 6 );

		/**
		 * [Enable / Disable Background Image with LoginPress_Radio_Control]
		 *
		 * @since 1.0.1
		 * @version 1.0.23
		 */
		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[loginpress_display_bg]',
				array(
					'settings' => 'loginpress_customization[loginpress_display_bg]',
					'label'    => __( 'Enable Background Image?', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 10,
					'type'     => 'ios', // light, ios, flat
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[setting_background]',
			array(
				// 'default'            =>  plugins_url( 'img/bg.jpg', LOGINPRESS_ROOT_FILE ) ,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_image',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'loginpress_customization[setting_background]',
				array(
					'label'         => __( 'Background Image:', 'loginpress' ),
					'section'       => 'section_background',
					'priority'      => 15,
					'settings'      => 'loginpress_customization[setting_background]',
					'button_labels' => array(
						'select' => __( 'Select Image', 'loginpress' ),
					),
				)
			)
		);

		$wp_customize->add_setting( 'loginpress_customization[mobile_background]', array(
			'type'              => 'option',
			'capability'        => 'manage_options',
			'transport'         => 'postMessage',
			'sanitize_callback'	=> 'loginpress_sanitize_image'
		));

		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'loginpress_customization[mobile_background]', array(
			'label'			=> __( 'Mobile Background Image:', 'loginpress' ),
			'section'		=> 'section_background',
			'priority'		=> 17,
			'settings'		=> 'loginpress_customization[mobile_background]',
			'button_labels'	=> array(
				'select'	=> __( 'Select Image', 'loginpress' ),
			)
		)));

		/**
		 * [ Add Background Gallery ]
		 *
		 * @since 1.1.0
		 */
		$wp_customize->add_setting(
			'loginpress_customization[gallery_background]',
			array(
				'default'    => plugins_url( 'img/gallery/img-1.jpg', LOGINPRESS_ROOT_FILE ),
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			)
		);

		$loginpress_free_background = array();
		$loginpress_background_name = array(
			'',
			__( 'Company', 'loginpress' ),
			__( 'Persona', 'loginpress' ),
			__( 'Corporate', 'loginpress' ),
			__( 'Corporate', 'loginpress' ),
			__( 'Startup', 'loginpress' ),
			__( 'Wedding', 'loginpress' ),
			__( 'Wedding #2', 'loginpress' ),
			__( 'Company', 'loginpress' ),
			__( 'Bikers', 'loginpress' ),
		);

		// Loop through the backgrounds.
		$bg_count = 1;
		while ( $bg_count <= 9 ) :

			$thumbnail                                = plugins_url( "img/gallery/img-{$bg_count}.jpg", LOGINPRESS_ROOT_FILE );
			$loginpress_free_background[ $thumbnail ] = array(
				'thumbnail' => plugins_url( "img/thumbnail/gallery-img-{$bg_count}.jpg", LOGINPRESS_ROOT_FILE ),
				'id'        => $thumbnail,
				'name'      => $loginpress_background_name[ $bg_count ],
			);
			++$bg_count;
		endwhile;

		$loginpress_background = apply_filters( 'loginpress_pro_add_background', $loginpress_free_background );

		$wp_customize->add_control(
			new LoginPress_Background_Gallery_Control(
				$wp_customize,
				'loginpress_customization[gallery_background]',
				array(
					'section' => 'section_background',
					'label'   => __( 'Background Gallery:', 'loginpress' ),
					'choices' => $loginpress_background,
				)
			)
		);

		// @version 1.1.21
		$wp_customize->add_setting(
			'loginpress_customization[background_repeat_radio]',
			array(
				'default'    => 'no-repeat',
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			// 'sanitize_callback' => 'loginpress_sanitize_checkbox'
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[background_repeat_radio]',
			array(
				'label'    => __( 'Background Repeat:', 'loginpress' ),
				'section'  => 'section_background',
				'priority' => 20,
				'settings' => 'loginpress_customization[background_repeat_radio]',
				'type'     => 'select',
				'choices'  => array(
					'repeat'    => 'repeat',
					'repeat-x'  => 'repeat-x',
					'repeat-y'  => 'repeat-y',
					'no-repeat' => 'no-repeat',
					'initial'   => 'initial',
					'inherit'   => 'inherit',
				),
			)
		);

		// @version 1.1.21
		$wp_customize->add_setting(
			'loginpress_customization[background_position]',
			array(
				'default'           => 'center',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_select',
			)
		);
		$wp_customize->add_control(
			'loginpress_customization[background_position]',
			array(
				'settings' => 'loginpress_customization[background_position]',
				'label'    => __( 'Select Position:', 'loginpress' ),
				'section'  => 'section_background',
				'priority' => 25,
				'type'     => 'select',
				'choices'  => array(
					'left-top'      => 'left top',
					'left-center'   => 'left center',
					'left-bottom'   => 'left bottom',
					'right-top'     => 'right top',
					'right-center'  => 'right center',
					'right-bottom'  => 'right bottom',
					'center-top'    => 'center top',
					'center'        => 'center',
					'center-bottom' => 'center bottom',
				),
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[background_image_size]',
			array(
				'default'           => 'cover',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_select',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[background_image_size]',
			array(
				'label'    => __( 'Background Image Size: ', 'loginpress' ),
				'section'  => 'section_background',
				'priority' => 30,
				'settings' => 'loginpress_customization[background_image_size]',
				'type'     => 'select',
				'choices'  => array(
					'auto'    => 'auto',
					'cover'   => 'cover',
					'contain' => 'contain',
					'initial' => 'initial',
					'inherit' => 'inherit',
				),
			)
		);

		$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 7, 35 );
		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_background', 7, 35 );
		/**
		 * [Enable / Disable Background Video with LoginPress_Radio_Control]
		 *
		 * @since 1.1.22
		 */
		$wp_customize->add_setting(
			'loginpress_customization[loginpress_display_bg_video]',
			array(
				'default'           => false,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);

		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[loginpress_display_bg_video]',
				array(
					'settings' => 'loginpress_customization[loginpress_display_bg_video]',
					'label'    => __( 'Enable Background Video?', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 40,
					'type'     => 'ios', // light, ios, flat
				)
			)
		);

		/**
		 * Background Video Medium.
		 *
		 * @since 3.0.0
		 */
		$wp_customize->add_setting(
			'loginpress_customization[bg_video_medium]',
			array(
				'default'    => 'media',
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			// 'sanitize_callback'  => 'loginpress_sanitize_checkbox'
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[bg_video_medium]',
			array(
				'label'    => __( 'Medium', 'loginpress' ),
				'section'  => 'section_background',
				'priority' => 41,
				'settings' => 'loginpress_customization[bg_video_medium]',
				'type'     => 'radio',
				'choices'  => array(
					'media'   => __( 'Media', 'loginpress' ),
					'youtube' => __( 'YouTube', 'loginpress' ),
				),
			)
		);

		/**
		 * [Launch Background Video feature with WP_Customize_Media_Control]
		 *
		 * @since 1.1.22
		 */
		$wp_customize->add_setting(
			'loginpress_customization[background_video]',
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			// 'sanitize_callback'  => 'section_background'
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'loginpress_customization[background_video]',
				array(
					'label'         => __( 'Background Video:', 'loginpress' ),
					'section'       => 'section_background',
					'mime_type'     => 'video', // Required. Can be image, audio, video, application, text
					'priority'      => 45,
					'button_labels' => array(
						'select'       => __( 'Select Video', 'loginpress' ),
						'change'       => __( 'Change Video', 'loginpress' ),
						'default'      => __( 'Default', 'loginpress' ),
						'remove'       => __( 'Remove', 'loginpress' ),
						'frame_title'  => __( 'Select File', 'loginpress' ),
						'frame_button' => __( 'Choose File', 'loginpress' ),
					),
				)
			)
		);

		/**
		 * Field settings for the error message
		 *
		 * @since 3.0.0
		 */
		$wp_customize->add_setting(
			'loginpress_customization[background_video_error]',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		/**
		 * Field for Add a control for the error message
		 *
		 * @since 3.0.0
		 */
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'loginpress_customization[background_video_error]',
				array(
					'label'       => __( 'Error Message:', 'loginpress' ),
					'description' => 'Wrong Selection, Please select MP4, webm or ogg file only',
					'section'     => 'section_background',
					'priority'    => 46, // Place it after the video control
					'type'        => 'hidden', // Using hidden type to display only the description
				)
			)
		);
		/**
		 * Field for YouTube video ID.
		 *
		 * @since 3.0.0
		 */
		$wp_customize->add_setting(
			'loginpress_customization[yt_video_id]',
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[yt_video_id]',
			array(
				'label'       => __( 'ID of the YouTube video', 'loginpress' ),
				'description' => sprintf( 
					// translators: Live Preview not Supported
					__( 'YouTube video ID is correct though the Live Preview is not supported. The video on the %1$slogin page%2$s can be checked, once it is published.', 'loginpress' ), '<a href="' . wp_login_url() . '" target="_blank">', '</a>' ),
				'section'     => 'section_background',
				'priority'    => 46,
				'settings'    => 'loginpress_customization[yt_video_id]',
				'input_attrs' => array(
					'placeholder' => 'GMAwsHomJlE',
				),
			)
		);

		// @version 1.1.21
		$wp_customize->add_setting(
			'loginpress_customization[background_video_object]',
			array(
				'default'           => 'cover',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_select',
			)
		);
		$wp_customize->add_control(
			'loginpress_customization[background_video_object]',
			array(
				'settings' => 'loginpress_customization[background_video_object]',
				'label'    => __( 'Video Size:', 'loginpress' ),
				'section'  => 'section_background',
				'priority' => 50,
				'type'     => 'select',
				'choices'  => array(
					'fill'       => 'fill',
					'contain'    => 'contain',
					'cover'      => 'cover',
					'scale-down' => 'scale-down',
					'none'       => 'none',
				),
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[video_obj_position]',
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'wp_strip_all_tags',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[video_obj_position]',
			array(
				'label'       => __( 'Object Position:', 'loginpress' ),
				'section'     => 'section_background',
				'priority'    => 55,
				'settings'    => 'loginpress_customization[video_obj_position]',
				'input_attrs' => array(
					'placeholder' => __( '50% 50%', 'loginpress' ),
				),
			)
		);

		/**
		 * [Enable / Disable Background Video with LoginPress_Radio_Control]
		 *
		 * @since 1.1.22
		 */
		$wp_customize->add_setting(
			'loginpress_customization[background_video_muted]',
			array(
				'default'           => true,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);

		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[background_video_muted]',
				array(
					'settings' => 'loginpress_customization[background_video_muted]',
					'label'    => __( 'Muted Video?', 'loginpress' ),
					'section'  => 'section_background',
					'priority' => 60,
					'type'     => 'ios', // light, ios, flat
				)
			)
		);

		// =============================
		// = Section for Form Beauty    =
		// =============================
		$wp_customize->add_section(
			'section_form',
			array(
				'title'       => __( 'Customize Login Form', 'loginpress' ),
				'description' => '',
				'priority'    => 15,
				'panel'       => 'loginpress_panel',
			)
		);

		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_form', 2, 4 );

		/**
		 * [ Enable / Disable Form Background Image with LoginPress_Radio_Control ]
		 *
		 * @since 1.1.3
		 */

		$wp_customize->add_setting(
			'loginpress_customization[setting_form_display_bg]',
			array(
				'default'           => false,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);

		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[setting_form_display_bg]',
				array(
					'settings' => 'loginpress_customization[setting_form_display_bg]',
					'label'    => __( 'Enable Form Transparency:', 'loginpress' ),
					'section'  => 'section_form',
					'priority' => 5,
					'type'     => 'ios',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[setting_form_background]',
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_image',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'loginpress_customization[setting_form_background]',
				array(
					'label'    => __( 'Form Background Image:', 'loginpress' ),
					'section'  => 'section_form',
					'priority' => 6,
					'settings' => 'loginpress_customization[setting_form_background]',
				)
			)
		);

		$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 0, 7 );

		$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 0, 15 );
		$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 1, 20 );
		$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 2, 25 );
		$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 3, 30 );
		$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 4, 35 );
		// Add settings for padding and margin
		$wp_customize->add_setting(
			'loginpress_customization[padding]',
			array(
				'default'           => array(
					'top'    => 0,
					'left'   => 0,
					'right'  => 0,
					'bottom' => 0,
					'unit'   => 'px',
					'lock'   => 0,
				),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_text_field', // Update with appropriate sanitization function
			)
		);

		$wp_customize->add_control(
			new LoginPress_Spacing_Control(
				$wp_customize,
				'loginpress_customization-customize_form_padding_controls',
				array(
					'label'            => __( 'Padding', 'loginpress' ),
					'description'      => __( 'Set the padding values.', 'loginpress' ),
					'section'          => 'section_form',
					'settings'         => 'loginpress_customization[padding]',
					'is_margin'        => false, // For padding
					'priority'         => 40,
					'loginpresstarget' => 'customize-control-loginpress_customization-customize_form_padding',
				)
			)
		);
		$form_padding = 0;
		while ( $form_padding < 2 ) :

			$wp_customize->add_setting(
				"loginpress_customization[{$form_control[$form_padding]}]",
				array(
					'default'           => $form_default[ $form_padding ],
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => $form_sanitization[ $form_padding ],
				)
			);

			$wp_customize->add_control(
				"loginpress_customization[{$form_control[$form_padding]}]",
				array(
					'label'    => $form_label[ $form_padding ],
					'section'  => 'section_form',
					'priority' => 40,
					'settings' => "loginpress_customization[{$form_control[$form_padding]}]",
				)
			);

			++$form_padding;
		endwhile;

		$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 3, 41 );

		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_form', 0, 45 );
		// Add settings for margin
		$wp_customize->add_setting(
			'loginpress_customization[margin]',
			array(
				'default'           => array(
					'top'    => 0,
					'left'   => 0,
					'right'  => 0,
					'bottom' => 0,
					'unit'   => 'px',
					'lock'   => 0,
				),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_text_field', // Update with appropriate sanitization function
			)
		);

		// Add control for margin
		$wp_customize->add_control(
			new LoginPress_Spacing_Control(
				$wp_customize,
				'loginpress_customization-customize_form_margin',
				array(
					'label'            => __( 'Margin', 'loginpress' ),
					'description'      => __( 'Set the margin values.', 'loginpress' ),
					'section'          => 'section_form',
					'settings'         => 'loginpress_customization[margin]',
					'is_margin'        => true, // For margin
					'priority'         => 49, // Adjust priority as needed
					'loginpresstarget' => 'customize-control-loginpress_customization-textfield_margin',
				)
			)
		);

		$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 1, 50 );
		$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 2, 55 );

		$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 5, 60 );
		// textfield_radius.
		// $this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 6, 65 );
		// textfield_shadow.
		// $this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 7, 70 );
		// textfield_shadow_opacity.
		// $this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 8, 75 );

		/**
		* [ Enable / Disable Form Background Image with LoginPress_Radio_Control ]
		 *
		* @since 1.1.3
		*/

		// $wp_customize->add_setting( 'loginpress_customization[textfield_inset_shadow]', array(
		// 'default'        => false,
		// 'type'           => 'option',
		// 'capability'    => 'manage_options',
		// 'transport'      => 'postMessage'
		// ) );
		//
		// $wp_customize->add_control( new LoginPress_Radio_Control( $wp_customize, 'loginpress_customization[textfield_inset_shadow]', array(
		// 'settings'    => 'loginpress_customization[textfield_inset_shadow]',
		// 'label'       => __( 'Input Text Field Shadow Inset:', 'loginpress'),
		// 'section'     => 'section_form',
		// 'priority'      => 80,
		// 'type'        => 'ios',// light, ios, flat
		// ) ) );

		$input_padding = 2;
		while ( $input_padding < 3 ) :

			$wp_customize->add_setting(
				"loginpress_customization[{$form_control[$input_padding]}]",
				array(
					'default'           => $form_default[ $input_padding ],
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => $form_sanitization[ $input_padding ],
				)
			);

			$wp_customize->add_control(
				"loginpress_customization[{$form_control[$input_padding]}]",
				array(
					'label'    => $form_label[ $input_padding ],
					'section'  => 'section_form',
					'priority' => 85,
					'settings' => "loginpress_customization[{$form_control[$input_padding]}]",
				)
			);

			++$input_padding;
		endwhile;

		$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 4, 86 );
		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_form', 1, 90 );

		// $form_input_label = 3;
		// while ( $form_input_label < 5 ) :
		//
		// $wp_customize->add_setting( "loginpress_customization[{$form_control[$form_input_label]}]", array(
		// 'default'                => $form_default[$form_input_label],
		// 'type'                   => 'option',
		// 'capability'     => 'manage_options',
		// 'transport'     => 'postMessage'
		// ) );
		//
		// $wp_customize->add_control( "loginpress_customization[{$form_control[$form_input_label]}]", array(
		// 'label'                       => $form_label[$form_input_label],
		// 'section'                     => 'section_form',
		// 'priority'                   => 91,
		// 'settings'                => "loginpress_customization[{$form_control[$form_input_label]}]"
		// ) );
		//
		// $form_input_label++;
		// endwhile;

		$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 3, 95 );
		$this->loginpress_color_setting( $wp_customize, $form_color_control, $form_color_label, 'section_form', 4, 100 );

		// customize_form_label.
		$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 9, 105 );
		// remember_me_font_size.
		$this->loginpress_range_setting( $wp_customize, $form_range_control, $form_range_default, $form_range_label, $form_range_attrs, $form_range_unit, 'section_form', 10, 110 );
		$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_form', 5, 111 );

		// =============================
		// = Section for Forget Form    =
		// =============================
		$wp_customize->add_section(
			'section_forget_form',
			array(
				'title'       => __( 'Customize Forget Form', 'loginpress' ),
				'description' => '',
				'priority'    => 20,
				'panel'       => 'loginpress_panel',
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[forget_form_background]',
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_image',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'loginpress_customization[forget_form_background]',
				array(
					'label'    => __( 'Forget Form Background Image:', 'loginpress' ),
					'section'  => 'section_forget_form',
					'priority' => 5,
					'settings' => 'loginpress_customization[forget_form_background]',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[forget_form_background_color]',
			array(
				// 'default'                => '#FFF',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[forget_form_background_color]',
				array(
					'label'    => __( 'Forget Form Background Color:', 'loginpress' ),
					'section'  => 'section_forget_form',
					'priority' => 10,
					'settings' => 'loginpress_customization[forget_form_background_color]',
				)
			)
		);

		// =============================
		// = Section for Button Style  =
		// =============================
		$wp_customize->add_section(
			'section_button',
			array(
				'title'       => __( 'Button Beauty', 'loginpress' ),
				'description' => '',
				'priority'    => 25,
				'panel'       => 'loginpress_panel',
			)
		);

		$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 0, 5 );
		$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 1, 10 );
		$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 2, 15 );
		$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 3, 20 );
		$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 4, 25 );
		$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 5, 30 );
		$this->loginpress_color_setting( $wp_customize, $button_control, $button_label, 'section_button', 6, 35 );

		/**
		 * [ Change Button CSS Properties with LoginPress_Range_Control ]
		 *
		 * @since 1.0.1
		 * @version 3.0.0
		 */

		$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 0, 35 );
		$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 1, 40 );
		$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 2, 45 );
		$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 3, 50 );
		$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 4, 55 );
		$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 5, 60 );
		$this->loginpress_range_setting( $wp_customize, $button_range_control, $button_range_default, $button_range_label, $button_range_attrs, $button_range_unit, 'section_button', 6, 65 );

		// =============================
		// = Section for Error message =
		// =============================
		$wp_customize->add_section(
			'section_error',
			array(
				'title'       => __( 'Error Messages', 'loginpress' ),
				'description' => '',
				'priority'    => 30,
				'panel'       => 'loginpress_panel',
			)
		);

		$error = 0;
		while ( $error < 11 ) :

			$wp_customize->add_setting(
				"loginpress_customization[{$error_control[$error]}]",
				array(
					'default'           => $error_default[ $error ],
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_control(
				"loginpress_customization[{$error_control[$error]}]",
				array(
					'label'    => $error_label[ $error ],
					'section'  => 'section_error',
					'priority' => 5,
					'settings' => "loginpress_customization[{$error_control[$error]}]",
				)
			);

			++$error;
		endwhile;

		// =============================
		// = Section for Welcome message
		// =============================
		$wp_customize->add_section(
			'section_welcome',
			array(
				'title'       => __( 'Welcome Messages', 'loginpress' ),
				'description' => '',
				'priority'    => 35,
				'panel'       => 'loginpress_panel',
			)
		);

		$welcome = 0;
		while ( $welcome < 5 ) :

			$wp_customize->add_setting(
				"loginpress_customization[{$welcome_control[$welcome]}]",
				array(
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => $welcome_sanitization[ $welcome ],
				)
			);

			$wp_customize->add_control(
				"loginpress_customization[{$welcome_control[$welcome]}]",
				array(
					'label'       => $welcome_label[ $welcome ],
					'section'     => 'section_welcome',
					'priority'    => 5,
					'settings'    => "loginpress_customization[{$welcome_control[$welcome]}]",
					'input_attrs' => array(
						'placeholder' => $welcome_default[ $welcome ],
					),
				)
			);

			++$welcome;
		endwhile;

		$wp_customize->add_setting(
			'loginpress_customization[message_background_color]',
			array(
				// 'default'            => '#fff',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[message_background_color]',
				array(
					'label'    => __( 'Message Field Background Color:', 'loginpress' ),
					'section'  => 'section_welcome',
					'priority' => 30,
					'settings' => 'loginpress_customization[message_background_color]',
				)
			)
		);

		// =============================
		// = Section for Header message
		// =============================
		// $wp_customize->add_section(
		// 'section_head',
		// array(
		// 'title'                 => __( 'Header Message', 'loginpress' ),
		// 'description'   => '',
		// 'priority'         => 35,
		// 'panel'                 => 'loginpress_panel',
		// ));
		//
		// $wp_customize->add_setting( 'loginpress_customization[login_header_message]', array(
		// 'default'                   => 'Latest NEWS',
		// 'type'                         => 'option',
		// 'capability'               => 'edit_theme_options',
		// ));
		//
		// $wp_customize->add_control( 'login_header_message', array(
		// 'label'                         => __( 'Header Message:', 'loginpress' ),
		// 'section'                   => 'section_head',
		// 'priority'                 => 5,
		// 'settings'                 => 'loginpress_customization[login_header_message]',
		// ));
		//
		// $wp_customize->add_setting( 'loginpress_customization[login_header_message_link]', array(
		// 'default'                   => '#',
		// 'type'                         => 'option',
		// 'capability'               => 'edit_theme_options',
		// ));
		//
		// $wp_customize->add_control( 'login_header_message_link', array(
		// 'label'                         => __( 'Header Message Link:', 'loginpress' ),
		// 'section'                   => 'section_head',
		// 'priority'                 => 5,
		// 'settings'                 => 'loginpress_customization[login_header_message_link]',
		// ));
		//
		// $wp_customize->add_setting( 'loginpress_customization[login_head_color]', array(
		// 'default'                   => '#17a8e3',
		// 'type'                         => 'option',
		// 'capability'               => 'edit_theme_options',
		// ));
		//
		// $wp_customize->add_control(
		// new WP_Customize_Color_Control(
		// $wp_customize,
		// 'login_head_color',
		// array(
		// 'label'         => __( 'Header Text Color:', 'loginpress' ),
		// 'section'   => 'section_head',
		// 'priority' => 10,
		// 'settings' => 'loginpress_customization[login_head_color]'
		// )));
		//
		// $wp_customize->add_setting( 'loginpress_customization[login_head_color_hover]', array(
		// 'default'                    => '#17a8e3',
		// 'type'                         => 'option',
		// 'capability'               => 'edit_theme_options',
		// ));
		//
		// $wp_customize->add_control(
		// new WP_Customize_Color_Control(
		// $wp_customize,
		// 'login_head_color_hover',
		// array(
		// 'label'         => __( 'Header Text Hover Color:', 'loginpress' ),
		// 'section'   => 'section_head',
		// 'priority' => 15,
		// 'settings' => 'loginpress_customization[login_head_color_hover]'
		// )));
		//
		// $wp_customize->add_setting( 'loginpress_customization[login_head_font_size]', array(
		// 'default'                   => '13px;',
		// 'type'                         => 'option',
		// 'capability'               => 'edit_theme_options',
		// ));
		//
		// $wp_customize->add_control( 'login_head_font_size', array(
		// 'label'                         => __( 'Text Font Size:', 'loginpress' ),
		// 'section'                   => 'section_head',
		// 'priority'                 => 20,
		// 'settings'                 => 'loginpress_customization[login_head_font_size]',
		// ));
		//
		// $wp_customize->add_setting( 'loginpress_customization[login_head_bg_color]', array(
		// 'default'                    => '#17a8e3',
		// 'type'                         => 'option',
		// 'capability'               => 'edit_theme_options',
		// ));
		//
		// $wp_customize->add_control(
		// new WP_Customize_Color_Control(
		// $wp_customize,
		// 'login_head_bg_color',
		// array(
		// 'label'         => __( 'Header Background Color:', 'loginpress' ),
		// 'section'   => 'section_head',
		// 'priority' => 25,
		// 'settings' => 'loginpress_customization[login_head_bg_color]'
		// )));

		// =============================
		// = Section for Form Footer    =
		// =============================
		$wp_customize->add_section(
			'section_footer',
			array(
				'title'       => __( 'Form Footer', 'loginpress' ),
				'description' => '',
				'priority'    => 40,
				'panel'       => 'loginpress_panel',
			)
		);

		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_footer', 3, 4 );

		$wp_customize->add_setting(
			'loginpress_customization[footer_display_text]',
			array(
				'default'           => true,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);

		/**
		 * [Enable / Disable Footer Text with LoginPress_Radio_Control]
		 *
		 * @since 1.0.1
		 * @version 1.0.23
		 */
		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[footer_display_text]',
				array(
					'settings' => 'loginpress_customization[footer_display_text]',
					'label'    => __( 'Enable Footer Text:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 5,
					'type'     => 'ios', // light, ios, flat
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_footer_text]',
			array(
				'default'           => __( 'Lost your password?', 'loginpress' ),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[login_footer_text]',
			array(
				'label'    => __( 'Lost Password Text:', 'loginpress' ),
				'section'  => 'section_footer',
				'priority' => 10,
				'settings' => 'loginpress_customization[login_footer_text]',
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_footer_text_decoration]',
			array(
				'default'           => 'none',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_select',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[login_footer_text_decoration]',
			array(
				'settings' => 'loginpress_customization[login_footer_text_decoration]',
				'label'    => __( 'Select Text Decoration:', 'loginpress' ),
				'section'  => 'section_footer',
				'priority' => 15,
				'type'     => 'select',
				'choices'  => array(
					'none'         => 'none',
					'overline'     => 'overline',
					'line-through' => 'line-through',
					'underline'    => 'underline',
				),
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_footer_color]',
			array(
				// 'default'            => '#17a8e3',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[login_footer_color]',
				array(
					'label'    => __( 'Footer Text Color:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 20,
					'settings' => 'loginpress_customization[login_footer_color]',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_footer_color_hover]',
			array(
				// 'default'            => '#17a8e3',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[login_footer_color_hover]',
				array(
					'label'    => __( 'Footer Text Hover Color:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 25,
					'settings' => 'loginpress_customization[login_footer_color_hover]',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_footer_font_size]',
			array(
				'default'           => '13',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'absint',
			)
		);

		/**
		 * [ Change login_footer_font_size Input fields with LoginPress_Range_Control ]
		 *
		 * @since 1.0.1
		 * @version 1.0.23
		 */
		$wp_customize->add_control(
			new LoginPress_Range_Control(
				$wp_customize,
				'loginpress_customization[login_footer_font_size]',
				array(
					'type'        => 'loginpress-range',
					'label'       => __( 'Text Font Size:', 'loginpress' ),
					'section'     => 'section_footer',
					'settings'    => 'loginpress_customization[login_footer_font_size]',
					'default'     => '13',
					'priority'    => 30,
					'input_attrs' => array(
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px',
					),
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_footer_bg_color]',
			array(
				// 'default'            => '#17a8e3',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[login_footer_bg_color]',
				array(
					'label'    => __( 'Footer Background Color:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 35,
					'settings' => 'loginpress_customization[login_footer_bg_color]',
				)
			)
		);

		$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_footer', 0, 36 );

		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_footer', 4, 40 );

		$wp_customize->add_setting(
			'loginpress_customization[back_display_text]',
			array(
				'default'           => true,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);

		/**
		 * [Enable / Disable Footer Text with LoginPress_Radio_Control]
		 *
		 * @since 1.0.1
		 * @version 1.0.23
		 */
		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[back_display_text]',
				array(
					'settings' => 'loginpress_customization[back_display_text]',
					'label'    => __( 'Enable "Back to" Text:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 45,
					'type'     => 'ios', // light, ios, flat
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_back_text_decoration]',
			array(
				'default'           => 'none',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_select',

			)
		);

		$wp_customize->add_control(
			'loginpress_customization[login_back_text_decoration]',
			array(
				'settings' => 'loginpress_customization[login_back_text_decoration]',
				'label'    => __( '"Back to" Text Decoration:', 'loginpress' ),
				'section'  => 'section_footer',
				'priority' => 50,
				'type'     => 'select',
				'choices'  => array(
					'none'         => 'none',
					'overline'     => 'overline',
					'line-through' => 'line-through',
					'underline'    => 'underline',
				),
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_back_color]',
			array(
				// 'default'        => '#17a8e3',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[login_back_color]',
				array(
					'label'    => __( '"Back to" Text Color:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 55,
					'settings' => 'loginpress_customization[login_back_color]',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_back_color_hover]',
			array(
				// 'default'            => '#17a8e3',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[login_back_color_hover]',
				array(
					'label'    => __( '"Back to" Text Hover Color:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 60,
					'settings' => 'loginpress_customization[login_back_color_hover]',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_back_font_size]',
			array(
				'default'           => '13',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'absint',
			)
		);
		$wp_customize->add_setting(
			'loginpress_customization[loginpress_show_love]',
			array(
				'default'           => true,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);

		/**
		 * [ Change login_back_font_size Input fields with LoginPress_Range_Control ]
		 *
		 * @since 1.0.1
		 * @version 1.0.23
		 */
		$wp_customize->add_control(
			new LoginPress_Range_Control(
				$wp_customize,
				'loginpress_customization[login_back_font_size]',
				array(
					'type'        => 'loginpress-range',
					'label'       => __( '"Back to" Text Font Size:', 'loginpress' ),
					'section'     => 'section_footer',
					'settings'    => 'loginpress_customization[login_back_font_size]',
					'default'     => '13',
					'priority'    => 65,
					'input_attrs' => array(
						'min'    => 0,
						'max'    => 100,
						'step'   => 1,
						'suffix' => 'px',
					),
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_back_bg_color]',
			array(
				// 'default'            => '#17a8e3',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[login_back_bg_color]',
				array(
					'label'    => __( '"Back to" Background Color:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 70,
					'settings' => 'loginpress_customization[login_back_bg_color]',
				)
			)
		);

		$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_footer', 1, 71 );

		$this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_footer', 5, 72 );

		/**
		 * [Enable / Disable Footer Text with LoginPress_Radio_Control]
		 *
		 * @since 1.1.3
		 */
		$wp_customize->add_setting(
			'loginpress_customization[login_copy_right_display]',
			array(
				'default'           => false,
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'loginpress_sanitize_checkbox',
			)
		);
		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[login_copy_right_display]',
				array(
					'settings' => 'loginpress_customization[login_copy_right_display]',
					'section'  => 'section_footer',
					'priority' => 73,
					'type'     => 'ios', // light, ios, flat
					'label'    => __( 'Enable Copyright Note:', 'loginpress' ),
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[copyright_background_color]',
			array(
				'default'           => '#efefef',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[copyright_background_color]',
				array(
					'label'    => __( '"Copyright" Background Color:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 74,
					'settings' => 'loginpress_customization[copyright_background_color]',
				)
			)
		);

		// Form Footer Text Color
		$wp_customize->add_setting(
			'loginpress_customization[copyright_text_color]',
			array(
				'default'           => '#000000',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color', // validates 3 or 6 digit HTML hex color code.
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'loginpress_customization[copyright_text_color]',
				array(
					'label'    => __( '"Copyright" Text Color:', 'loginpress' ),
					'section'  => 'section_footer',
					'priority' => 75,
					'settings' => 'loginpress_customization[copyright_text_color]',
				)
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[login_footer_copy_right]',
			array(
				'default'           => sprintf( 
					// translators: Rights Reserved
					__( '© %1$s %2$s, All Rights Reserved.', 'loginpress' ), date( 'Y' ), get_bloginfo( 'name' ) ),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		// Show Some Love text Color
		// $wp_customize->add_setting( 'loginpress_customization[show_some_love_text_color]', array(
			// 'default'                     => '#17a8e3',
			// 'type'              => 'option',
			// 'capability'            => 'manage_options',
			// 'transport'         => 'postMessage',
			// 'sanitize_callback' => 'sanitize_hex_color' // validates 3 or 6 digit HTML hex color code.
		// ) );

		// $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'loginpress_customization[show_some_love_text_color]', array(
			// 'label'             => __( '"Show Some Love" Text Color:', 'loginpress' ),
			// 'section'           => 'section_footer',
			// 'priority'        => 76,
			// 'settings'        => 'loginpress_customization[show_some_love_text_color]'
		// ) ) );

		/**
		 * [Add Copyright string in the footer along with year]
		 *
		 * @version 1.5.4
		 */
		$wp_customize->add_setting(
			'loginpress_customization[login_footer_copy_right]',
			array(
				'default'           => sprintf( 
					// translators: Rights Reserved
					__( '© %1$s %2$s, All Rights Reserved.', 'loginpress' ), '$YEAR$', get_bloginfo( 'name' ) ),
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => 'wp_kses_post',
			)
		);
		$wp_customize->add_control(
			'loginpress_customization[login_footer_copy_right]',
			array(
				'label'       => __( 'Copyright Note:', 'loginpress' ),
				'description' => sprintf( 
					// translators: Copyright Note
					__( '%1$s will be replaced with the current year.', 'loginpress' ), '<code>$YEAR$</code>' ),
				'type'        => 'textarea',
				'section'     => 'section_footer',
				'priority'    => 77,
				'settings'    => 'loginpress_customization[login_footer_copy_right]',
			)
		);

		/**
		 * [Enable / Disable Footer Text with LoginPress_Radio_Control]
		 *
		 * @since 1.0.1
		 * @version 1.0.23
		 */
		$wp_customize->add_control(
			new LoginPress_Radio_Control(
				$wp_customize,
				'loginpress_customization[loginpress_show_love]',
				array(
					'settings' => 'loginpress_customization[loginpress_show_love]',
					'section'  => 'section_footer',
					'priority' => 80,
					'type'     => 'ios', // light, ios, flat
					'label'    => __( 'Show some Love. Please help others learn about this free plugin by placing small link in footer. Thank you very much!', 'loginpress' ),
				)
			)
		);

		/**
		 * [Love position on footer.]
		 *
		 * @since 1.1.3
		 */
		$wp_customize->add_setting(
			'loginpress_customization[show_love_position]',
			array(
				'default'    => 'right',
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			// 'sanitize_callback'  => 'loginpress_sanitize_checkbox'
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[show_love_position]',
			array(
				'label'    => __( 'Love Position:', 'loginpress' ),
				'section'  => 'section_footer',
				'priority' => 85,
				'settings' => 'loginpress_customization[show_love_position]',
				'type'     => 'radio',
				'choices'  => array(
					'left'  => __( 'Left', 'loginpress' ),
					'right' => __( 'Right', 'loginpress' ),
				),
			)
		);
		$this->loginpress_hr_setting( $wp_customize, $close_control, 'section_footer', 2, 90 );
		// $this->loginpress_group_setting( $wp_customize, $group_control, $group_label, $group_info, 'section_footer', 2, 90 );

		// =============================
		// = Section for Custom CSS/JS =
		// =============================
		$wp_customize->add_section(
			'loginpress_custom_css_js',
			array(
				'title'       => __( 'Custom CSS/JS', 'loginpress' ),
				'description' => '',
				'priority'    => 50,
				'panel'       => 'loginpress_panel',
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[loginpress_custom_css]',
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[loginpress_custom_css]',
			array(
				'label'       => __( 'Customize CSS:', 'loginpress' ),
				'type'        => 'textarea',
				'section'     => 'loginpress_custom_css_js',
				'priority'    => 5,
				'settings'    => 'loginpress_customization[loginpress_custom_css]',
				'description' => sprintf( 
					// translators: Customize CSS
					__( 'Custom CSS doesn\'t make effect live. For preview please save the setting and visit %1$s login%2$s page or after save refresh the customizer.', 'loginpress' ), '<a href="' . wp_login_url() . '"title="Login" target="_blank">', '</a>' ),
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[loginpress_custom_js]',
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[loginpress_custom_js]',
			array(
				'label'       => __( 'Customize JS:', 'loginpress' ),
				'type'        => 'textarea',
				'section'     => 'loginpress_custom_css_js',
				'priority'    => 10,
				'settings'    => 'loginpress_customization[loginpress_custom_js]',
				'description' => sprintf( 
					// translators: Customize JS
					__( 'Custom JS doesn\'t make effect live. For preview please save the setting and visit %1$s login%2$s page or after save refresh the customizer.', 'loginpress' ), '<a href="' . wp_login_url() . '"title="Login" target="_blank">', '</a>' ),
			)
		);
	}

	/**
	 * Manage the Login Footer Links.
	 *
	 * @since   1.0.0
	 * @version 3.0.0
	 */
	public function login_page_custom_footer() {

		/**
		 * Add brand position class.
		 *
		 * @since   1.1.3
		 * @version 3.0.0
		 */
		$position = ''; // Empty variable for storing position class.
		if ( $this->loginpress_key ) {
			if ( isset( $this->loginpress_key['show_love_position'] ) && $this->loginpress_key['show_love_position'] == 'left' ) {
				$position = ' love-position';
			}
		}

		/**
		 * Add functionality of disabling the templates of LoginPress.
		 *
		 * @since 1.6.4
		 */
		$disable_default_style = (bool) apply_filters( 'loginpress_disable_default_style', false );

		if ( $this->loginpress_preset === 'default1' && $disable_default_style ) {
			include LOGINPRESS_DIR_PATH . 'include/login-footer.php';
		}

		if ( empty( $this->loginpress_key ) || ( isset( $this->loginpress_key['loginpress_show_love'] ) && $this->loginpress_key['loginpress_show_love'] != '' ) ) {
			echo '<div class="loginpress-show-love' . $position . '">' . __( 'Powered by:', 'loginpress' ) . ' <a href="https://wpbrigade.com" target="_blank">LoginPress</a></div>';
		}

		echo '<div class="footer-wrapper">';
		echo '<div class="footer-cont">';

		if ( $this->loginpress_key ) {
			// do_action( 'loginpress_footer_wrapper' );
			do_action( 'loginpress_footer_menu' );

			if ( array_key_exists( 'login_copy_right_display', $this->loginpress_key ) && true == $this->loginpress_key['login_copy_right_display'] ) {

				/**
				 * Replace the "$YEAR$" with current year if and where found.
				 *
				 * @since 1.5.4
				 */
				if ( isset( $this->loginpress_key['login_footer_copy_right'] ) && ! empty( $this->loginpress_key['login_footer_copy_right'] ) && strpos( $this->loginpress_key['login_footer_copy_right'], '$YEAR$' ) !== false ) {
					$year = date( 'Y' );
					// Setting the value with current year and saving in the 'login_footer_copy_right' key
					$this->loginpress_key['login_footer_copy_right'] = str_replace( '$YEAR$', $year, $this->loginpress_key['login_footer_copy_right'] );
				}

				// Show a default value if not changed or show the changed text string for 'login_footer_copy_right'
				$footer_text = ( array_key_exists( 'login_footer_copy_right', $this->loginpress_key ) && ! empty( $this->loginpress_key['login_footer_copy_right'] ) ) ? $this->loginpress_key['login_footer_copy_right'] : sprintf( 
					// translators: Rights Reserved
					__( '© %1$s %2$s, All Rights Reserved.', 'loginpress' ), date( 'Y' ), get_bloginfo( 'name' ) );

				echo '<div class="copyRight">' . apply_filters( 'loginpress_footer_copyright', $footer_text ) . '</div>';
			}
		}
		echo '</div></div>';

		/**
		 * Include LoginPress script in footer.
		 *
		 * @since 1.2.2
		 */
		include LOGINPRESS_DIR_PATH . 'js/script-login.php';
	}

	/**
	 * Manage the Login Head
	 *
	 * @since 1.0.0
	 * @version 3.0.8
	 */
	public function login_page_custom_head() {

		$loginpress_setting = get_option( 'loginpress_setting' );
		$lostpassword_url   = isset( $loginpress_setting['lostpassword_url'] ) ? $loginpress_setting['lostpassword_url'] : 'off';

		add_filter( 'gettext', array( $this, 'change_lostpassword_message' ), 20, 3 );
		add_filter( 'gettext', array( $this, 'change_username_label' ), 20, 3 );
		// add_filter( 'gettext', array( $this, 'change_password_label' ), 20, 3 );
		// Include CSS File in header.
		if ( isset( $this->loginpress_key['loginpress_custom_js'] ) && ! empty( $this->loginpress_key['loginpress_custom_js'] ) ) { // 1.2.2
			wp_enqueue_script( 'jquery' );
		}

		/**
		 * Add functionality of disabling the templates of LoginPress.
		 *
		 * @since 1.6.4
		 */
		$disable_default_style = (bool) apply_filters( 'loginpress_disable_default_style', false );

		if ( ! $disable_default_style || 'default1' !== $this->loginpress_preset ) {
			include LOGINPRESS_DIR_PATH . 'css/style-presets.php';
			include LOGINPRESS_DIR_PATH . 'css/style-login.php';
		}

		do_action( 'loginpress_header_menu' );
		// do_action( 'loginpress_header_wrapper' );

		/**
		 * Filter for changing the lost password URL of lifter LMS plugin to default Lost Password URL of WordPress
		 * By using this filter, you can prevent the redirection of lost password to Lifter LMS's lost password page over lost password link.
		 *
		 * @param bool
		 *
		 * @since 1.5.3
		 */
		if ( apply_filters( 'loginpress_llms_lostpassword_url', false ) ) {
			remove_filter( 'lostpassword_url', 'llms_lostpassword_url', 10 );
		}

		if ( ! has_site_icon() && ! is_customize_preview() ) {
			$login_favicon = isset( $this->loginpress_key['login_favicon'] ) ? $this->loginpress_key['login_favicon'] : 'off';

			if ( 'off' != $login_favicon && function_exists( 'login_header' ) ) {
				echo '<link rel="shortcut icon" href="' . $login_favicon . '" />';
			}
		}
	}

	/**
	 * Filters the Languages select input activation on the login screen.
	 *
	 * @param bool $arg Whether to display the Languages select input on the login screen.
	 *
	 * @since 1.5.11
	 * @return bool
	 */
	function loginpress_language_switch( $arg ) {

		$loginpress_setting = get_option( 'loginpress_setting' );
		$language_switcher  = isset( $loginpress_setting['enable_language_switcher'] ) ? $loginpress_setting['enable_language_switcher'] : 'off';

		if ( 'off' === $language_switcher ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set Redirect Path of Logo
	 *
	 * @since   1.0.0
	 * @version 1.5.3
	 */
	public function login_page_logo_url() {

		if ( $this->loginpress_key && array_key_exists( 'customize_logo_hover', $this->loginpress_key ) && ! empty( $this->loginpress_key['customize_logo_hover'] ) ) {
			return __( $this->loginpress_key['customize_logo_hover'], 'loginpress' ); // @codingStandardsIgnoreLine.
		} else {
			return home_url();
		}
	}

	/**
	 * Remove the filter login_errors from woocommerce login form.
	 *
	 * @since   1.0.16
	 *
	 * @return errors
	 */
	function loginpress_woo_login_errors( $validation_error, $arg1, $arg2 ) {

		remove_filter( 'login_errors', array( $this, 'login_error_messages' ) );
		return $validation_error;
	}


	/**
	 * Set hover Title of Logo
	 *
	 * @since   1.0.0
	 * @version 1.5.3
	 *
	 * @return mixed
	 */
	public function login_page_logo_title() {

		if ( $this->loginpress_key && array_key_exists( 'customize_logo_hover_title', $this->loginpress_key ) && ! empty( $this->loginpress_key['customize_logo_hover_title'] ) ) {
			return __( $this->loginpress_key['customize_logo_hover_title'], 'loginpress' ); // @codingStandardsIgnoreLine.
		} else {
			return home_url();
		}
	}

	/**
	 * Set Errors Messages to Show off
	 *
	 * @param   $error
	 * @since   1.0.0
	 * @version 1.2.5
	 *
	 * @return string
	 */
	public function login_error_messages( $error ) {

		global $errors;

		if ( isset( $errors ) && apply_filters( 'loginpress_display_custom_errors', true ) ) {

			$error_codes = $errors->get_error_codes();

			if ( $this->loginpress_key ) {

				$invalid_username = array_key_exists( 'incorrect_username', $this->loginpress_key ) && ! empty( $this->loginpress_key['incorrect_username'] ) ? $this->loginpress_key['incorrect_username'] : sprintf( 
					// translators: Username not valid
					__( '%1$sError:%2$s Invalid Username.', 'loginpress' ), '<strong>', '</strong>' );

				$invalid_password = array_key_exists( 'incorrect_password', $this->loginpress_key ) && ! empty( $this->loginpress_key['incorrect_password'] ) ? $this->loginpress_key['incorrect_password'] : sprintf( 
					// translators: Password not valid
					__( '%1$sError:%2$s Invalid Password.', 'loginpress' ), '<strong>', '</strong>' );

				$empty_username = array_key_exists( 'empty_username', $this->loginpress_key ) && ! empty( $this->loginpress_key['empty_username'] ) ? $this->loginpress_key['empty_username'] : sprintf( 
					// translators: Username field empty
					__( '%1$sError:%2$s The username field is empty.', 'loginpress' ), '<strong>', '</strong>' );

				$empty_password = array_key_exists( 'empty_password', $this->loginpress_key ) && ! empty( $this->loginpress_key['empty_password'] ) ? $this->loginpress_key['empty_password'] : sprintf( 
					// translators: Password field empty
					__( '%1$sError:%2$s The password field is empty.', 'loginpress' ), '<strong>', '</strong>' );

				$invalid_email = array_key_exists( 'invalid_email', $this->loginpress_key ) && ! empty( $this->loginpress_key['invalid_email'] ) ? $this->loginpress_key['invalid_email'] : sprintf( 
					// translators: Incorrect email
					__( '%1$sError:%2$s The email address isn\'t correct..', 'loginpress' ), '<strong>', '</strong>' );

				$empty_email = array_key_exists( 'empty_email', $this->loginpress_key ) && ! empty( $this->loginpress_key['empty_email'] ) ? $this->loginpress_key['empty_email'] : sprintf( 
					// translators: Enter email
					__( '%1$sError:%2$s Please type your email address.', 'loginpress' ), '<strong>', '</strong>' );

				$username_exists = array_key_exists( 'username_exists', $this->loginpress_key ) && ! empty( $this->loginpress_key['username_exists'] ) ? $this->loginpress_key['username_exists'] : sprintf( 
					// translators: Username already taken
					__( '%1$sError:%2$s This username is already registered. Please choose another one.', 'loginpress' ), '<strong>', '</strong>' );

				$email_exists = array_key_exists( 'email_exists', $this->loginpress_key ) && ! empty( $this->loginpress_key['email_exists'] ) ? $this->loginpress_key['email_exists'] : sprintf( 
					// translators: Email already taken
					__( '%1$sError:%2$s This email is already registered, please choose another one.', 'loginpress' ), '<strong>', '</strong>' );

				$password_mismatch = array_key_exists( 'password_mismatch', $this->loginpress_key ) && ! empty( $this->loginpress_key['password_mismatch'] ) ? $this->loginpress_key['password_mismatch'] : sprintf( 
					// translators: Password mismatch
					__( '%1$sError:%2$s Passwords Don\'t match.', 'loginpress' ), '<strong>', '</strong>' );

				$invalidcombo = array_key_exists( 'invalidcombo_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['invalidcombo_message'] ) ? $this->loginpress_key['invalidcombo_message'] : sprintf( 
					// translators: Username or Password not vaild
					__( '%1$sError:%2$s Invalid username or email.', 'loginpress' ), '<strong>', '</strong>' );

				if ( in_array( 'invalid_username', $error_codes ) ) {
					return $invalid_username;
				}

				if ( in_array( 'incorrect_password', $error_codes ) ) {
					return $invalid_password;
				}

				if ( in_array( 'empty_username', $error_codes ) ) {
					return $empty_username;
				}

				if ( in_array( 'empty_password', $error_codes ) ) {
					return $empty_password;
				}

				// registration Form entries.
				if ( in_array( 'invalid_email', $error_codes ) ) {
					return $invalid_email;
				}

				if ( in_array( 'empty_email', $error_codes ) ) {
					return '</br>' . $empty_email;
				}

				if ( in_array( 'username_exists', $error_codes ) ) {
					return $username_exists;
				}

				if ( in_array( 'email_exists', $error_codes ) ) {
					return $email_exists;
				}

				// forget password entry.
				if ( in_array( 'invalidcombo', $error_codes ) ) {
					return $invalidcombo;
				}

				// Password mismatch custom error message.
				if ( in_array( 'password_mismatch', $error_codes ) ) {
					return $password_mismatch;
				}
			}
		}

		return $error;
	}

	/**
	 * Redirecting the Lost Password url to default lost post password page when Woocommerce is active
	 *
	 * @since 3.1.1
	 */
	function loginpress_reset_pass_url_in_notify() {
		$siteURL   = get_option( 'siteurl' );
		$login_url = wp_login_url();
		$login_url = explode( '/', $login_url );
		$path      = $login_url[3];
		return "{$siteURL}/{$path}?action=lostpassword";
	}

	/**
	 * Checks if the Lost password URL is enabled
	 *
	 * @since 3.1.1
	 */
	public function loginpress_lostpassword_url_changed() {
		$loginpress_setting = get_option( 'loginpress_setting' );
		$lostpassword_url   = isset( $loginpress_setting['lostpassword_url'] ) ? $loginpress_setting['lostpassword_url'] : 'off';

		if ( 'on' == $lostpassword_url ) {
			add_filter( 'lostpassword_url', array( $this, 'loginpress_reset_pass_url_in_notify' ), 11, 0 );
		}
	}

	/**
	 * Change Lost Password Text from Form
	 *
	 * @param   $text
	 * @since   1.0.0
	 * @version 3.0.0
	 *
	 * @return mixed
	 */
	public function change_lostpassword_message( $translated_text, $text, $domain ) {

		if ( is_array( $this->loginpress_key ) && array_key_exists( 'login_footer_text', $this->loginpress_key ) && $text == 'Lost your password?' && 'default' == $domain && trim( $this->loginpress_key['login_footer_text'] ) ) {

			return trim( __( $this->loginpress_key['login_footer_text'], 'loginpress' ) ); // @codingStandardsIgnoreLine.
		}

		return $translated_text;
	}

	/**
	 * Change Label of the Username from login Form.
	 *
	 * @param string $translated_text Translated text.
	 * @param string $text The text to translate.
	 * @param string $domain The text domain.
	 *
	 * @since 1.1.3
	 * @version 3.0.0
	 * @return string
	 */
	public function change_username_label( $translated_text, $text, $domain ) {

		$loginpress_setting = get_option( 'loginpress_setting' );

		if ( $loginpress_setting ) {

			$default = 'Username or Email Address';
			// $options = $this->loginpress_key;
			// $label   = array_key_exists( 'form_username_label', $options ) ? $options['form_username_label'] : '';
			$login_order = isset( $loginpress_setting['login_order'] ) ? $loginpress_setting['login_order'] : '';

			// If the option does not exist, return the text unchanged.
			if ( ! $loginpress_setting && $default === $text ) {
				return $translated_text;
			}

			// If options exist, then translate away.
			if ( $loginpress_setting && $default === $text ) {
				// Check if the option exists.
				if ( '' != $login_order && 'default' != $login_order ) {
					if ( 'username' == $login_order ) {
						$label = __( 'Username', 'loginpress' );
					} elseif ( 'email' == $login_order ) {
						$label = __( 'Email Address', 'loginpress' );
					} else {
						$label = __( 'Username or Email Address', 'loginpress' );
					}
					$translated_text = esc_html( $label );
				}
				$translated_text = esc_html( apply_filters( 'loginpress_username_label', $translated_text ) );
			}
		}
		return $translated_text;
	}
	/**
	 * Change Password Label from Form.
	 *
	 * @param  [type] $translated_text [description]
	 * @param  [type] $text            [description]
	 * @param  [type] $domain          [description]
	 * @since 1.1.3
	 *
	 * @return string
	 */
	public function change_password_label( $translated_text, $text, $domain ) {

		if ( $this->loginpress_key ) {
			$default = 'Password';
			$options = $this->loginpress_key;
			$label   = array_key_exists( 'form_password_label', $options ) ? $options['form_password_label'] : '';

			// If the option does not exist, return the text unchanged.
			if ( ! $options && $default === $text ) {
				return $translated_text;
			}

			// If options exist, then translate away.
			if ( $options && $default === $text ) {

				// Check if the option exists.
				if ( array_key_exists( 'form_password_label', $options ) ) {
					$translated_text = esc_html( $label );
				} else {
					return $translated_text;
				}
			}
		}
		return $translated_text;
	}

	/**
	 * Manage Welcome Messages
	 *
	 * @param   $message
	 * @since   1.0.0
	 * @version 1.5.3
	 *
	 * @return string
	 */
	public function change_welcome_message( $message ) {

		if ( $this->loginpress_key ) {

			// Check, User Logged out.
			if ( isset( $_GET['loggedout'] ) && true == $_GET['loggedout'] ) {

				if ( array_key_exists( 'logout_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['logout_message'] ) ) {

					$loginpress_message = __( $this->loginpress_key['logout_message'], 'loginpress' ); // @codingStandardsIgnoreLine.
				}
			}

			// Logged In messages.
			elseif ( isset( $_GET['action'] ) && 'lostpassword' == $_GET['action'] ) {

				if ( array_key_exists( 'lostpwd_welcome_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['lostpwd_welcome_message'] ) ) {

					$loginpress_message = __( $this->loginpress_key['lostpwd_welcome_message'], 'loginpress' ); // @codingStandardsIgnoreLine.
				}
			} elseif ( isset( $_GET['action'] ) && 'register' == $_GET['action'] ) {

				if ( array_key_exists( 'register_welcome_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['register_welcome_message'] ) ) {

					$loginpress_message = __( $this->loginpress_key['register_welcome_message'], 'loginpress' ); // @codingStandardsIgnoreLine.
				}
			}

			// @since 1.0.18
			// else if ( strpos( $message, __( "Enter your new password below." ) ) == true ) {
			//
			// if ( array_key_exists( 'reset_hint_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['reset_hint_message'] ) ) {
			//
			// $loginpress_message = $this->loginpress_key['reset_hint_message'];
			// }
			// }

			// @since 1.0.18
			elseif ( strpos( $message, __( 'Your password has been reset.' , 'loginpress' ) ) == true ) {

				// if ( array_key_exists( 'after_reset_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['after_reset_message'] ) ) {

				$loginpress_message = __( 'Your password has been reset.', 'loginpress' ) . ' <a href="' . esc_url( wp_login_url() ) . '">' . __( 'Log in' , 'loginpress' ) . '</a></p>';
				// }
			} elseif ( array_key_exists( 'welcome_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['welcome_message'] ) ) {

					$loginpress_message = __( $this->loginpress_key['welcome_message'], 'loginpress' ); // @codingStandardsIgnoreLine.
			}

			return ! empty( $loginpress_message ) ? "<p class='custom-message'>" . $loginpress_message . '</p>' : $message;
		}
	}

	/**
	 * Set WordPress login page title.
	 *
	 * @since   1.4.6
	 * @version 1.5.3

	 * @return  string
	 */
	public function login_page_title( $title ) {

		if ( $this->loginpress_key && array_key_exists( 'customize_login_page_title', $this->loginpress_key ) && ! empty( $this->loginpress_key['customize_login_page_title'] ) ) {
			return __( $this->loginpress_key['customize_login_page_title'], 'loginpress' ); // @codingStandardsIgnoreLine.
		} else {
			return $title;
		}
	}

	/**
	 * Hook to Redirect Page for Customize
	 *
	 * @since   1.1.3
	 * @version 3.2.1
	 */
	public function redirect_to_custom_page() {
		if ( ! empty( $_GET['page'] ) ) {

			if ( ( $_GET['page'] == 'abw' ) || ( $_GET['page'] == 'loginpress' ) ) {

				if ( is_multisite() ) { // if subdirectories are used in multisite.

					$loginpress_obj  = new LoginPress();
					$loginpress_page = $loginpress_obj->get_loginpress_page();

					$page = get_permalink( $loginpress_page );

					// Generate the redirect url.
					$url = add_query_arg(
						array(
							'autofocus[panel]' => 'loginpress_panel',
							'url'              => rawurlencode( $page ),
						),
						admin_url( 'customize.php' )
					);

					wp_safe_redirect( $url );

				} else {
					$login_url = wp_login_url();
					$site_url  = site_url();

					// Parse the URLs only once to avoid redundancy.
					$parsed_login_url = parse_url( $login_url );
					$parsed_site_url  = parse_url( $site_url );

					// Determine login path.
					$login_path   = isset( $parsed_login_url['path'] ) ? $parsed_login_url['path'] : '/wp-login.php';
					$subdirectory = isset( $parsed_site_url['path'] ) ? $parsed_site_url['path'] : '';

					// If the login path starts with the subdirectory, remove the subdirectory from it.
					if ( ! empty( $subdirectory ) && strpos( $login_path, $subdirectory ) === 0 ) {
						$login_path = substr( $login_path, strlen( $subdirectory ) );
					}

					$login_path = sanitize_text_field( rtrim( $login_path, '/' ) );

					// Redirect to the login page URL.
					wp_redirect( get_admin_url() . 'customize.php?url=' . esc_url( site_url( $login_path, 'login_post' ) ) . '&autofocus=loginpress_panel' );
					exit;
				}
			}
		}
	}

	/**
	 * Redirect to the Admin Panel After Closing LoginPress Customizer
	 *
	 * @since   1.0.0
	 * @version 3.0.6
	 * @return  null
	 */
	public function menu_url() {

		global $submenu;

		$parent = 'index.php';
		$page   = 'abw';

		// Create specific url for login view.
		$login_url  = wp_login_url();
		$parsed_url = parse_url( $login_url );
		$login_url  = isset( $parsed_url['path'] ) ? sanitize_text_field( $parsed_url['path'] ) : 'wp-login.php';
		$url        = add_query_arg(
			array(
				'url'    => esc_url( site_url( $login_url, 'login_post' ) ),
				'return' => admin_url( 'themes.php' ),
			),
			admin_url( 'customize.php' )
		);

		// If is Not Design Menu, return
		if ( ! isset( $submenu[ $parent ] ) ) {
			return null;
		}

		foreach ( $submenu[ $parent ] as $key => $value ) {
			// Set new URL for menu item
			if ( $page === $value[2] ) {
				$submenu[ $parent ][ $key ][2] = $url;
				break;
			}
		}
	}

	/**
	 * This function is removed the error messages in the customizer.
	 *
	 * @param  array  $errors      [description]
	 * @param  string $redirect_to [description]
	 * @since  1.2.0
	 * @version 3.0.6
	 */
	function remove_error_messages_in_wp_customizer( $errors, $redirect_to ) {

		if ( is_customize_preview() && version_compare( $GLOBALS['wp_version'], '5.2', '>=' ) ) {
			return new WP_Error( '', '' );
		}
		// If Logout message is set and not empty then remove the default logout message from WordPress.
		if ( isset( $this->loginpress_key ) && is_array( $this->loginpress_key ) && array_key_exists( 'logout_message', $this->loginpress_key ) && ! empty( $this->loginpress_key['logout_message'] ) ) {
			if ( isset( $_GET['loggedout'] ) && true == $_GET['loggedout'] && isset( $errors->errors['loggedout'] ) ) {
				unset( $errors->errors['loggedout'] );
			}
		}
		return $errors;
	}
}
