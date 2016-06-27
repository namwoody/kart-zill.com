<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'kartzill');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'D7Hpk2lRsx');

/** MySQL hostname */
define('DB_HOST', '127.0.0.1');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

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
define('AUTH_KEY',         'aI =-[4,r[PJ0m.=1+Pylf1+]A9dS ~V@uuNCJ8!32;(-<]pwLiWLy&WW*`#++P5');
define('SECURE_AUTH_KEY',  'd9 ]lq>|g8/Q5B@1V[Pg5&w7DQZ|5;L}ee#LE1`7n`jf34No@.]aWy{z=%Jb^4/)');
define('LOGGED_IN_KEY',    'P-#w$)|gT@+Cv }dF$.O5|[yjTMrY!>tlc!+koZ}X&3l&ck`Val)7j_-4p|_:F#d');
define('NONCE_KEY',        '=v_7hK;NRQn=o.b~eO.1>FM.5TV%FsXp+o^! KU7<yp-Vg_-HuwvAL94oF-mYG0u');
define('AUTH_SALT',        '~^d}/z_Tv1.,HT1Y:I]OOG~V:8<<kOx~OO>*dSG3&/[|BO]HbJy|kJl4YU-|CCx[');
define('SECURE_AUTH_SALT', '^Iu<u @H,?Fbl%[ZK;MIf$axv-B!h9$s%).2H`liA|vKuOe9ZM<8fzBMQI}vmXCW');
define('LOGGED_IN_SALT',   'kR-P[Aq74S3Sb8%7O|9q8$1AZRPn[apb;+;p3aici0fZI)8hAU3DsTR I_-?|, Z');
define('NONCE_SALT',       'i2&KioFM;$l-H~,I^vxHb#o)A.s|N(yi}.fGuqAa-R9-+$=/mOi+x%+%:-ZiJrW ');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
