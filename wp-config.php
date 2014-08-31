<?php
define('WP_HOME','http://acstestwp.azurewebsites.net');
define('WP_SITEURL','http://acstestwp.azurewebsites.net');



/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache

/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */



define( 'WP_MAX_MEMORY_LIMIT', '256M' );
define('DB_NAME', 'cdb_0bdb0e66cc');

/** MySQL database username */
define('DB_USER', 'b5b26b6a60b0f5');

/** MySQL database password */
define('DB_PASSWORD', 'd0e6f612');

/** MySQL hostname */
define('DB_HOST', "ap-cdbr-azure-east-c.cloudapp.net");

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'Q(-TWE0^_++9d}:{TLw|X)y4DxS1]YTMe!+T-XI6X|RNr8<i812nbk>FJ/)-{H j');
define('SECURE_AUTH_KEY',  'P6qhFtr[%K4 E:c<ZCRd+lkY=Io-/CL^(=S)kpj3FFzA?[O+9sqnQ28^M6}Lq;u7');
define('LOGGED_IN_KEY',    'j2 &SNpl]&ZfwQMg<0V3oIWE9)Yh@ZS;-ftwkbn<%Y$G,|o{}kMcOtbs}_^7)f7U');
define('NONCE_KEY',        'G~c9hle)UL{w]n<|9Ag(?Dz[oWl4NHq2,P_MmRS06]J%WK-%U7vEZHXy.l&I-*pT');
define('AUTH_SALT',        'E?@|y> )+vl>%n8^NY6pU7#OMX7FA/q)wl ( tb+`{/`$H27eM=>k(SQQ9?JD|Om');
define('SECURE_AUTH_SALT', 'Iz+6*s>h/t}gZ`bjD- +ZT(H(U_FZ|k$H/K^-OE{q@1WGkI IclPK/?edjaZb4T-');
define('LOGGED_IN_SALT',   '!n`TN;FHD:=+{j-+$>{Z#!H /_!~>MzElo-_A uTo3Z_~5|-#|AsVOS-6#e&?do:');
define('NONCE_SALT',       'li|i|YmzkNN,kj?I|Ke{#:GH_J,-~J2m+#J|v_3-~[05v-Fi36%PLR{~C2k7vy.)');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'o5gbc_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);


/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

?>
