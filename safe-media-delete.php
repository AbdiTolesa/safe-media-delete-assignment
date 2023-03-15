<?php
/**
 * @author Abdi Tolessa
 *
 * Plugin Name: Safe Media Delete
 * Description: This plugin adds features that checks if a media is used as a Featured Image, in post content or term edit page and prevent it from being deleted if so.
 * Author:      Abdi Tolessa
 * Author URI:  https://abditsori.com
 * Version:     1.0
 * Text Domain: safe-media-delete
 *
 * @package SMD
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
require_once __DIR__ . '/cmb2/init.php';

add_action( 'cmb2_admin_init', 'add_terms_image_field' );

function add_terms_image_field() {
    $taxonomies = get_taxonomies();
	$cmb_term = new_cmb2_box( array(
		'id'               => 'safe-media-delete_term_edit',
		'object_types'     => array( 'term' ),
		'taxonomies'       => array_values( $taxonomies ),
		'new_term_section' => true,
	) );

	$cmb_term->add_field( array(
		'name' => esc_html__( 'Term Image', 'safe-media-delete' ),
		'desc' => esc_html__( 'field description (optional)', 'safe-media-delete' ),
		'id'   => 'safe-media-delete_term_avatar',
		'type' => 'file',
	) );
}