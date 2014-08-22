<?php
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
define('DB_NAME', 'acswordAKTVCRln9');

/** MySQL database username */
define('DB_USER', 'b039394634dcdf');

/** MySQL database password */
define('DB_PASSWORD', '09b58e8f7344ae6');

/** MySQL hostname */
define('DB_HOST', "us-cdbr-azure-east-a.cloudapp.net");

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
define('AUTH_KEY',         'DmPsk|uSQQ*|~[=55e[Fy?v-,2+4Y4]PqRz0%|&4pX[#uuxt&t?;WgX`37qhU(}/');
define('SECURE_AUTH_KEY',  'dS[[V-nR`:~2.T&zcSCZV-o]>{{8QW]+|MsWPAGwIHC(_VaG`19RMZ57D#&Cm4,.');
define('LOGGED_IN_KEY',    'p-yU-yYubB+CL_`m@8.hmp`Q(,~d_F>.xEih&U>#@:|A?m.q+&&yuVf|o}uZGKJ&');
define('NONCE_KEY',        'Uq4qyBRai7jt19t{%1VLhP>Csx9upp^8U;|U5+lf|aX[o[JVc9zHl$ri,D0xek9V');
define('AUTH_SALT',        '8mpP@?c mhD+c8|Lu+rP@K[jMEPwP3!5?uOKsXkp. mH?%-?UE0<7/$I7(a75}r6');
define('SECURE_AUTH_SALT', '8*oO9I:-s&O-sLBs++O1Ff%|<hlP?~3JTsy&m,np.(V =~9{*|f&5rGK:E@[)gK4');
define('LOGGED_IN_SALT',   'aSwBkd<|Qb_-*sLx9fDC)dQ@suC2P)YOwUt:xY-_K5*>m3AnK*[3~5-iDqz_CE,U');
define('NONCE_SALT',       'st)`,Qd6/mV4a]f8>UL*n=S|&GalEEV}Lt{wc1p}La=oq20J2MBPuO@Rob!B:^ZE');

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

/* Multisite */

define( 'WP_ALLOW_MULTISITE', true );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

?>
