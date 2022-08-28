<?php
define('WP_CACHE', true); // WP-Optimize Cache
//Begin Really Simple SSL session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple SSL
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'voicescity' );
/** Database username */
define( 'DB_USER', 'voicescity' );
/** Database password */
define( 'DB_PASSWORD', 'Arif@semi2020' );
/** Database hostname */
define( 'DB_HOST', 'localhost' );
/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );
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
define( 'AUTH_KEY',          '9pNU%&2Ir$.^8.9^oQ0oD<6x,5P@Zd#-feZK3]DVjPMf6x`5w2VLmBM@96#0$W>`' );
define( 'SECURE_AUTH_KEY',   '`c]*^i$rXuj}k;`G@><3l+;4o/7~Kr170o`MDYb$<^UZReO4cuFyJ`?>tT#!N2$H' );
define( 'LOGGED_IN_KEY',     'zWF}Q!WpDq-]pJ{[wfD)&@xkh*|X}l0(|J<Yunvj7izwTuLHw3Bsfn% {.:+5mC ' );
define( 'NONCE_KEY',         '[1Ud8V~@az Y9TwuBY#0InOkB.hR15s=*Ok<tcf4gdH<gLt`$~F$]X4q;x1<Y5#`' );
define( 'AUTH_SALT',         'mOSO[4$*znCgR_b7Lymxs]E8tyvYN/yJm;bCo8EPbp(PE;wl-lZHnU?o}Em216c*' );
define( 'SECURE_AUTH_SALT',  '<.?{|2{9T_+E F>f*=)QrnA$Z3d&W^dq(-WQaaq2tw#AHJfg&_aEJteXyww:i*z{' );
define( 'LOGGED_IN_SALT',    ' UD2byyu})Pi&(H%-QAf)QjPZT6V],zGyV<-3(X{f/G|su.czCxf=BQY<6Z35Io*' );
define( 'NONCE_SALT',        ']e,s<otWHk1SUmNXlc7ecasI9W4|NI`9SE`g!MKs+bz#GQXQ91#Bze!&x:*d~0f8' );
define( 'WP_CACHE_KEY_SALT', 'LQ;71uy4.(etHsF;< _,QC&JLcE0B>H5g`!>Yy:;Tp=1VoK_xHd*`j2 BDKzk{0^' );
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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