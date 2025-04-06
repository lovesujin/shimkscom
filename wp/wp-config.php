<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'superuser_cc' );

/** Database username */
define( 'DB_USER', 'superuser' );

/** Database password */
define( 'DB_PASSWORD', '##tladbswls1214##' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'M1{9L=1zL:|NSFRxWsFx<nB;1EA]Y[L#Hf4XU*CL$R@9Wsjo#|^NYeHyC(sQC;R)' );
define( 'SECURE_AUTH_KEY',  ']aKb&`bXCupxyBDGJ.2tRNz7_p3N2Fi,VRTN#r9U>H)ks0Dn!NZ(Fv^VBdVSz|2Y' );
define( 'LOGGED_IN_KEY',    'o>o9lBAuQ}qKB`Ve/%Nb$ x/ne:<VNr:]W0#aoe6{Nese@z@JuiYI<++hT1Ns}iw' );
define( 'NONCE_KEY',        '.A4|wIYzu<[DT8>h.!O_&$Ozf2[ZWdWl9o!wX/h$e (3B2C#1l8vPa<s&-&N{XV,' );
define( 'AUTH_SALT',        'n&adTW2X<-*1KPMcAJ.-flZKJrXKd,.. ]Z[[sAV:uh/M!UqN<6fjqY5]1>SU:mk' );
define( 'SECURE_AUTH_SALT', '.XYcw{-w]A9$&J[sv<(t8RJrOP=,q~@{*wjuo|BSa0tDQ&z^wkJyVf_X;5iZ]ztD' );
define( 'LOGGED_IN_SALT',   'UYRm>;^% py3WlpoG`/1}Ft7T}+Om1Mg15;Ock1u4n7Ox$MiXYod|R6XZNDQjhY!' );
define( 'NONCE_SALT',       '= l $8jC0pg9@_zDxc7sUJK4Y0yX?1p1s4f>5BQa6a@4#KhF^M<d3a}#DwgdwSl&' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'player_wp_root_wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
