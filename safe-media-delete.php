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
		'desc' => esc_html__( 'Image for the term', 'safe-media-delete' ),
		'id'   => 'safe-media-delete_term_image',
		'type' => 'file',
	) );
}

add_filter( 'wp_prepare_attachment_for_js', function( $response, $attachment ) {
    $can_be_deleted = image_is_safe_to_delete( $attachment );
    if ( ! $can_be_deleted ) {
        $response['nonces']['delete'] = false;
    }
    return $response;
}, 10, 2 );

function image_is_safe_to_delete( $attachment ) {
    $can_be_deleted = true;
    $linked_posts = array();

    $url = wp_get_attachment_url( $attachment->ID );
    $url_parts = explode( '.', $url );
    $ext = $url_parts[ count( $url_parts ) - 1 ];
    $url = str_replace( '.' . $ext, '', $url );

    $posts_with_attachments = get_posts_attachment_data();
    foreach ( $posts_with_attachments as $post ) {
        if ( $post->meta_value == $attachment->ID || strpos( $post->post_content, $url ) !== false ) {
            $can_be_deleted = false;
            $linked_posts[ $attachment->ID ] = $post->ID;
        }
    }

    return $can_be_deleted;
}

function get_posts_attachment_data() {
    global $wpdb;
    if ( get_transient( 'posts_with_attachments' ) ) {
        return get_transient( 'posts_with_attachments' );
    }
    $posts_with_attachments = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_content, p_meta.meta_value from {$wpdb->prefix}posts as p INNER JOIN {$wpdb->prefix}postmeta as p_meta ON p.ID = p_meta.post_id WHERE p.post_content LIKE %s OR p_meta.meta_key='_thumbnail_id'", '%src="' . wp_upload_dir()['baseurl'] . '%' ) );
    set_transient( 'posts_with_attachments', $posts_with_attachments, 10 );
    return $posts_with_attachments;
}

// Can be used in media list page row options
add_filter( 'media_row_actions', function( $actions, $post, $detached ) {
    if ( ! image_is_safe_to_delete( $post ) ) {
        unset( $actions['delete'] );
    }
    return $actions;
}, 10, 3);

// Used in image edit page
add_filter( 'user_has_cap', function( $allcaps, $caps, $args ) {
    if ( ! isset( $args[2] ) || $args[0] !== 'delete_post' || ! is_attachment( $args[2] )) {
        return $allcaps;
    }

    if ( ! image_is_safe_to_delete( get_post( $args[2] ) ) ) {
        $allcaps['delete_posts'] = false;
    }

    return $allcaps;
}, 10, 3 );


// Prevent used image from being deleted via bulk delete
add_filter( 'pre_delete_attachment', function( $delete, $post ) {
    if ( ! image_is_safe_to_delete( $post ) ) {
        return false;
    }
    return $delete;
}, 10, 2 );
// function wpse_312694_restrict_page_deletion( $caps, $cap, $user_id, $args ) {
//     // error_log( 'args::' . print_r( $args, true ) );
//     if ( ! isset( $args[0] ) ) {
//         return $caps;
//     }
//     $post_id = $args[0];
    
//     if ( $cap === 'delete_post' && $post_id === 5698 ) {
//         // $caps[] = 'do_not_allow';
//     }
    
//     return $caps;
// }
// add_filter( 'map_meta_cap', 'wpse_312694_restrict_page_deletion', 10, 4 );

