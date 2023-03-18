<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Safe_Media_Delete
 */

// $_tests_dir = getenv( 'WP_TESTS_DIR' );

// if ( ! $_tests_dir ) {
// 	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
// }

// // Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
// $_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
// if ( false !== $_phpunit_polyfills_path ) {
// 	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
// }

// if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
// 	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
// 	exit( 1 );
// }

// // Give access to tests_add_filter() function.
// require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
// function _manually_load_plugin() {
// 	require dirname( dirname( __FILE__ ) ) . '/safe-media-delete.php';
// }
// tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
// add_action( 'plugins_loaded', 'load_safe_media_delete', 0 );
// function load_safe_media_delete() {
// 	$smd_path = dirname( __FILE__ );
// 	if ( file_exists( $smd_path . '/safe-media-delete.php' ) ) {
// 		include( $smd_path . '/safe-media-delete.php' );
// 	}
// }

// // Start up the WP testing environment.
// // require "{$_tests_dir}/includes/bootstrap.php";
// if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
// 	require getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/bootstrap.php';
// } else {
// 	require '../../../../tests/phpunit/includes/bootstrap.php';
// }
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'safe-media-delete/safe-media-delete.php' ),
);

if ( file_exists( dirname( __FILE__ ) . '/../vendor/autoload.php' ) ) {
	include dirname( __FILE__ ) . '/../vendor/autoload.php';
}

if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	require getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/bootstrap.php';
} else {
	require '../../../../tests/phpunit/includes/bootstrap.php';
}