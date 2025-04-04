<?php

/**
 * Log file to know more about users website environment.
 * helps in debugging and providing support.
 *
 * @package    LoginPress
 * @since      1.0.19
 * @version    3.0.0
 */

class LoginPress_Log_Info {

	/**
	 * Returns the plugin & system information.
	 *
	 * @access public
	 * @package LoginPress
	 * @since 1.0.19
	 * @version 3.0.0
	 * @return string
	 */
	public static function get_sysinfo() {

		global $wpdb;
		$loginpress_setting = get_option( 'loginpress_setting' );
		$loginpress_captcha = get_option( 'loginpress_captcha_settings' );
		$loginpress_config  = get_option( 'loginpress_customization' );
		$session_expiration = ( isset( $loginpress_setting['session_expiration'] ) && '0' != $loginpress_setting['session_expiration'] ) ? $loginpress_setting['session_expiration'] . ' Minute' : 'Not Set';
		$login_order        = isset( $loginpress_setting['login_order'] ) ? $loginpress_setting['login_order'] : 'Default';
		$customization      = isset( $loginpress_config ) ? print_r( $loginpress_config, true ) : 'No customization yet';
		$lostpassword_url   = isset( $loginpress_setting['lostpassword_url'] ) ? $loginpress_setting['lostpassword_url'] : 'Off';

		if ( version_compare( $GLOBALS['wp_version'], '5.9', '>=' ) && ! empty( get_available_languages() ) ) {
			$lang_switcher = isset( $loginpress_setting['enable_language_switcher'] ) ? $loginpress_setting['enable_language_switcher'] : 'Off';
		}
		$pci_compliance        = isset( $loginpress_setting['enable_pci_compliance'] ) ? $loginpress_setting['enable_pci_compliance'] : 'Off';
		$_loginpassword_url    = ( $lostpassword_url == 'on' ) ? 'WordPress Default' : 'WooCommerce Custom URL';
		$loginpress_uninstall  = isset( $loginpress_setting['loginpress_uninstall'] ) ? $loginpress_setting['loginpress_uninstall'] : 'Off';
		$disable_default_style = (bool) apply_filters( 'loginpress_disable_default_style', false );
		$enable_password_reset = isset( $loginpress_setting['enable_password_reset'] ) ? $loginpress_setting['enable_password_reset'] : 'Off';

		$html = '### Begin System Info ###' . "\n\n";

		// Basic site info
		$html  = '-- WordPress Configuration --' . "\n\n";
		$html .= 'Site URL:                 ' . site_url() . "\n";
		$html .= 'Home URL:                 ' . home_url() . "\n";
		$html .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";
		$html .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
		$html .= 'Language:                 ' . get_locale() . "\n";
		$html .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . "\n";
		$html .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? ( WP_DEBUG ? 'Enabled' : 'Disabled' ) : 'Not set' ) . "\n";
		$html .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";

		/**
		 * Add a filter to disable the LoginPress default template style.
		 *
		 * @since 1.6.4
		 */
		if ( $disable_default_style ) {
			$html .= "\n" . '-- *LoginPress Default Style is disabled by using Hook* --' . "\n";
		}

		// Plugin Configuration
		$html .= "\n" . '-- LoginPress Configuration --' . "\n\n";
		$html .= 'Plugin Version:           ' . LOGINPRESS_VERSION . "\n";
		$html .= 'Expiration:           	' . $session_expiration . "\n";
		$html .= 'Login Order:              ' . ucfirst( $login_order ) . "\n";
		$html .= 'PCI Compliance:           ' . ucfirst( $pci_compliance ) . "\n";
		$html .= 'Force Password Reset:     ' . ucfirst( $enable_password_reset ) . "\n";

		if ( class_exists( 'WooCommerce' ) ) {
			$html .= 'Lost Password URL:        ' . $_loginpassword_url . "\n";
		}

		/**
		 * Add a filter to disable the LoginPress default template style.
		 *
		 * @since 1.6.4
		 */
		if ( $disable_default_style ) {
			$html .= "\n" . '-- *LoginPress Default Style is disabled by using Hook* --' . "\n";
		}

		/**
		 * Add option to remove language switcher option
		 *
		 * @since 1.5.13
		 */
		if ( version_compare( $GLOBALS['wp_version'], '5.9', '>=' ) && ! empty( get_available_languages() ) ) {
			$html .= 'Language Switcher:        ' . ucfirst( $lang_switcher ) . "\n";
		}
		$html .= 'Uninstallation:       	  ' . $loginpress_uninstall . "\n";
		$html .= 'Total Customized Fields:  ' . count( $loginpress_config ) . "\n";
		$html .= 'Customization Detail:     ' . $customization . "\n";

		// Pro Plugin Configuration
		if ( class_exists( 'LoginPress_Pro' ) ) {

			$captchas_enabled  = isset( $loginpress_captcha['enable_captchas'] ) ? $loginpress_captcha['enable_captchas'] : 'off';
			$type_recaptcha    = ( 'off' !== $captchas_enabled && isset( $loginpress_captcha['captchas_type'] ) && $loginpress_captcha['captchas_type'] == 'type_recaptcha' ) ? 'on' : 'off';
			$type_hcaptcha     = ( 'off' !== $captchas_enabled && isset( $loginpress_captcha['captchas_type'] ) && $loginpress_captcha['captchas_type'] == 'type_hcaptcha' ) ? 'on' : 'off';
			$type_cloudflare   = ( 'off' !== $captchas_enabled && isset( $loginpress_captcha['captchas_type'] ) && $loginpress_captcha['captchas_type'] == 'type_cloudflare' ) ? 'on' : 'off';
			$enable_force      = ( isset( $loginpress_setting['force_login'] ) ) ? $loginpress_setting['force_login'] : 'Off';
			$loginpress_preset = get_option( 'customize_presets_settings', true );
			$license_key       = LoginPress_Pro::get_registered_license_status();

			$html .= "\n" . '-- LoginPress Pro Configuration --' . "\n\n";
			$html .= 'Plugin Version:           ' . LOGINPRESS_PRO_VERSION . "\n";
			$html .= 'LoginPress Template:      ' . $loginpress_preset . "\n";
			$html .= 'License Status:           ' . $license_key . "\n";
			$html .= 'Force Login:              ' . $enable_force . "\n";
			$html .= 'Google Recaptcha Status:  ' . $type_recaptcha . "\n";

			if ( 'off' !== $type_recaptcha ) {
				$site_key          = ( isset( $loginpress_captcha['site_key'] ) ) ? $loginpress_captcha['site_key'] : 'Not Set';
				$secret_key        = ( isset( $loginpress_captcha['secret_key'] ) ) ? $loginpress_captcha['secret_key'] : 'Not Set';
				$captcha_theme     = ( isset( $loginpress_captcha['captcha_theme'] ) ) ? $loginpress_captcha['captcha_theme'] : 'Light';
				$captcha_language  = ( isset( $loginpress_captcha['captcha_language'] ) ) ? $loginpress_captcha['captcha_language'] : 'English (US)';
				$captcha_enable_on = ( isset( $loginpress_captcha['captcha_enable'] ) ) ? $loginpress_captcha['captcha_enable'] : 'Not Set';
				$cap_type          = (isset( $loginpress_captcha['recaptcha_type'] )) ? $loginpress_captcha['recaptcha_type'] : 'v2-robot';
				if ( $cap_type == 'v2-invisible' ){
					$site_key          = ( isset( $loginpress_captcha['site_key_v2_invisible'] ) ) ? $loginpress_captcha['site_key_v2_invisible'] : 'Not Set';
					$secret_key        = ( isset( $loginpress_captcha['secret_key_v2_invisible'] ) ) ? $loginpress_captcha['secret_key_v2_invisible'] : 'Not Set';
				}
				else if ($cap_type == 'v3'){
					$site_key          = ( isset( $loginpress_captcha['site_key_v3'] ) ) ? $loginpress_captcha['site_key_v3'] : 'Not Set';
					$secret_key        = ( isset( $loginpress_captcha['secret_key_v3'] ) ) ? $loginpress_captcha['secret_key_v3'] : 'Not Set';
				}
				$html .= 'Recaptcha Site Key:        ' . LoginPress_Pro::mask_license( $site_key ) . "\n";
				$html .= 'Recaptcha Secret Key:      ' . LoginPress_Pro::mask_license( $secret_key ) . "\n";
				$html .= 'Recaptcha Type:            ' . $cap_type . "\n";
				$html .= 'Recaptcha Theme Used:      ' . $captcha_theme . "\n";
				$html .= 'Recaptcha Language Used:   ' . $captcha_language . "\n";
				if ( is_array( $captcha_enable_on ) ) {
					foreach ( $captcha_enable_on as $key ) {
						$html .= 'Recaptcha Enable On:       ' . ucfirst( str_replace( '_', ' ', $key ) ) . "\n";
					}
				}
			}

			$html .= 'hCaptcha Status:          ' . $type_hcaptcha . "\n";

			if ( 'off' !== $type_hcaptcha ) {
				$site_key          = ( isset( $loginpress_captcha['hcaptcha_site_key'] ) ) ? $loginpress_captcha['hcaptcha_site_key'] : 'Not Set';
				$secret_key        = ( isset( $loginpress_captcha['hcaptcha_secret_key'] ) ) ? $loginpress_captcha['hcaptcha_secret_key'] : 'Not Set';
				$captcha_theme     = ( isset( $loginpress_captcha['hcaptcha_theme'] ) ) ? $loginpress_captcha['hcaptcha_theme'] : 'Light';
				$captcha_language  = ( isset( $loginpress_captcha['hcaptcha_language'] ) ) ? $loginpress_captcha['hcaptcha_language'] : 'English (US)';
				$captcha_enable_on = ( isset( $loginpress_captcha['hcaptcha_enable'] ) ) ? $loginpress_captcha['hcaptcha_enable'] : 'Not Set';
				$hcaptcha_type     = (isset( $loginpress_captcha['hcaptcha_type'] )) ? $loginpress_captcha['hcaptcha_type'] : 'normal';
				
				$html .= 'hCaptcha Site Key:        ' . LoginPress_Pro::mask_license( $site_key ) . "\n";
				$html .= 'hCaptcha Secret Key:      ' . LoginPress_Pro::mask_license( $secret_key ) . "\n";
				$html .= 'hCaptcha Type:            ' . $hcaptcha_type . "\n";
				$html .= 'hCaptcha Theme Used:      ' . $captcha_theme . "\n";
				$html .= 'hCaptcha Language Used:   ' . $captcha_language . "\n";
				if ( is_array( $captcha_enable_on ) ) {
					foreach ( $captcha_enable_on as $key ) {
						$html .= 'hCaptcha Enable On:       ' . ucfirst( str_replace( '_', ' ', $key ) ) . "\n";
					}
				}
			}

			$html .= 'Cloudflare Turnstile Status: ' . $type_cloudflare . "\n";

			if ( 'off' !== $type_cloudflare ) {
				$site_key          = ( isset( $loginpress_captcha['site_key_cf'] ) ) ? $loginpress_captcha['site_key_cf'] : 'Not Set';
				$secret_key        = ( isset( $loginpress_captcha['secret_key_cf'] ) ) ? $loginpress_captcha['secret_key_cf'] : 'Not Set';
				$captcha_theme     = ( isset( $loginpress_captcha['cf_theme'] ) ) ? $loginpress_captcha['cf_theme'] : 'Light';
				$captcha_enable_on = ( isset( $loginpress_captcha['captcha_enable_cf'] ) ) ? $loginpress_captcha['captcha_enable_cf'] : 'Not Set';

				$html .= 'Turnstile Site Key:        ' . LoginPress_Pro::mask_license( $site_key ) . "\n";
				$html .= 'Turnstile Secret Key:      ' . LoginPress_Pro::mask_license( $secret_key ) . "\n";
				$html .= 'Turnstile Theme Used:      ' . $captcha_theme . "\n";
				if ( is_array( $captcha_enable_on ) ) {
					foreach ( $captcha_enable_on as $key ) {
						$html .= 'Turnstile Enable On:       ' . ucfirst( str_replace( '_', ' ', $key ) ) . "\n";
					}
				}
			}
		}
		// Server Configuration
		$html .= "\n" . '-- Server Configuration --' . "\n\n";
		$html .= 'Operating System:         ' . php_uname( 's' ) . "\n";
		$html .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$html .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";

		$html .= 'Server Software:          ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

		// PHP configs... now we're getting to the important stuff
		$html .= "\n" . '-- PHP Configuration --' . "\n\n";
		// $html .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );
		$html .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
		$html .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
		$html .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
		$html .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
		$html .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
		$html .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

		// WordPress active themes
		$html    .= "\n" . '-- WordPress Active Theme --' . "\n\n";
		$my_theme = wp_get_theme();
		$html    .= 'Name:                     ' . $my_theme->get( 'Name' ) . "\n";
		$html    .= 'URI:                      ' . $my_theme->get( 'ThemeURI' ) . "\n";
		$html    .= 'Author:                   ' . $my_theme->get( 'Author' ) . "\n";
		$html    .= 'Version:                  ' . $my_theme->get( 'Version' ) . "\n";

		// WordPress active plugins
		$html          .= "\n" . '-- WordPress Active Plugins --' . "\n\n";
		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( ! in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}
			$html .= $plugin['Name'] . ': v(' . $plugin['Version'] . ")\n";
		}

		// WordPress inactive plugins
		$html .= "\n" . '-- WordPress Inactive Plugins --' . "\n\n";
		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}
			$html .= $plugin['Name'] . ': v(' . $plugin['Version'] . ")\n";
		}

		if ( is_multisite() ) {
			// WordPress Multisite active plugins
			$html          .= "\n" . '-- Network Active Plugins --' . "\n\n";
			$plugins        = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );
			foreach ( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );
				if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
					continue;
				}
				$plugin = get_plugin_data( $plugin_path );
				$html  .= $plugin['Name'] . ': v(' . $plugin['Version'] . ")\n";
			}
		}

		$html .= "\n" . '### End System Info ###';
		return $html;
	}
} // End of Class.
