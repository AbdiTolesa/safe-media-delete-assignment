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
		'id'               => 'smd_term_edit',
		'object_types'     => array( 'term' ),
		'taxonomies'       => array_values( $taxonomies ),
		'new_term_section' => true,
	) );

	$cmb_term->add_field( array(
		'name' => esc_html__( 'Term Image', 'safe-media-delete' ),
		'desc' => esc_html__( 'Image for the term', 'safe-media-delete' ),
		'id'   => 'smd_term_image',
		'type' => 'file',
	) );
}

add_filter( 'wp_prepare_attachment_for_js', function( $response, $attachment ) {
    $can_be_deleted = get_image_deletable_status( $attachment->ID )['status'];
    if ( ! $can_be_deleted ) {
        $response['nonces']['delete'] = false;
    }
    return $response;
}, 10, 2 );

function get_image_deletable_status( $attachment_id ) {
    $can_be_deleted = true;
    $objects_using_image = get_objects_using_image( $attachment_id );

    if ( ! empty( $objects_using_image['terms']) || ! empty( $objects_using_image['posts'] ) ) {
        $can_be_deleted = false;
    }

    return array( 'status' => $can_be_deleted, 'linked_objects' => $objects_using_image );
}

function get_objects_using_image( $attachment_id ) {
    $can_be_deleted = true;
    $linked_posts = array();

    return array(
        'terms' => get_terms_using_image( $attachment_id ),
        'posts' => get_posts_using_image( $attachment_id ),
    );
}

function get_posts_using_image( $attachment_id ) {
    $url = wp_get_attachment_url( $attachment_id );
    $url_parts = explode( '.', $url );
    $ext = $url_parts[ count( $url_parts ) - 1 ];
    $url = str_replace( '.' . $ext, '', $url );

    $posts_linked_to_image = array();
    $posts_with_attachments = get_posts_attachment_data();
    foreach ( $posts_with_attachments as $post ) {
        if ( $post->meta_value == $attachment_id || ! empty( preg_match( '#\w*(<!-- wp:image {"id":' . $attachment_id . '[^>]*>)\w*#', $post->post_content ) ) || ! empty( preg_match( '!(\w*<img [^>]*src="' . $url . '[^>]*>\w*)!', $post->post_content ) ) ) {
            if ( false === array_search( $post->ID, array_column( $posts_linked_to_image, 'ID' ) ) ) {
                $posts_linked_to_image[] = $post;
            }
        }
    }

    return $posts_linked_to_image;
}

function get_terms_linked_to_images() {
    if ( get_transient( 'terms_linked_to_images' ) ) {
        return get_transient( 'terms_linked_to_images' );
    }

    global $wpdb;
    $terms_linked_to_images = $wpdb->get_results( "SELECT t.term_id, t_meta.meta_value from {$wpdb->prefix}terms as t INNER JOIN {$wpdb->prefix}termmeta as t_meta ON t.term_id = t_meta.term_id WHERE t_meta.meta_key='smd_term_image_id' AND t_meta.meta_value!=''" );

    set_transient( 'terms_linked_to_images', $terms_linked_to_images, 10 );

    return $terms_linked_to_images;
}

function get_terms_using_image( $attachment_id ) {
    $terms_linked_to_image = array();

    $terms_linked_to_images = get_terms_linked_to_images();

    foreach ( $terms_linked_to_images as $term ) {
        if ( $term->meta_value == $attachment_id ) {
            $terms_linked_to_image[] = $term;
        }
    }

    return $terms_linked_to_image;
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
    if ( ! get_image_deletable_status( $post->ID )['status'] ) {
        unset( $actions['delete'] );
    }
    return $actions;
}, 10, 3);

// Used in image edit page
add_filter( 'user_has_cap', function( $allcaps, $caps, $args ) {
    if ( ! isset( $args[2] ) || $args[0] !== 'delete_post' ) {
        return $allcaps;
    }
    $post = get_post( $args[2] );
    if ( $post->post_type !== 'attachment' ) {
        return $allcaps;
    }
    if ( ! get_image_deletable_status( $post->ID )['status'] ) {
        $allcaps['delete_posts'] = false;
    }

    return $allcaps;
}, 10, 3 );


// Prevent used image from being deleted via bulk delete
add_filter( 'pre_delete_attachment', function( $delete, $post ) {
    if ( ! get_image_deletable_status( $post->ID )['status'] ) {
        return false;
    }
    return $delete;
}, 10, 2 );

add_filter( 'attachment_fields_to_edit', function( $form_fields, $post ) {
    $image_data = get_image_deletable_status( $post->ID );
    $can_be_deleted = $image_data['status'];
    if ( $can_be_deleted ) {
        return $form_fields;
    }

    if ( ! empty( $image_data['linked_objects']['posts'] ) ) {
        $linked_posts_list = get_linked_objects_html( $image_data['linked_objects']['posts'], 'post' );
        $form_fields['linked_posts'] = array(
            'label' => 'Linked articles',
            'input' => 'html',
            'html' => $linked_posts_list,
        );
    }

    if ( ! empty( $image_data['linked_objects']['terms'] ) ) {
        $linked_terms_list = get_linked_objects_html( $image_data['linked_objects']['terms'], 'term' );
        $form_fields['linked_terms'] = array(
            'label' => 'Linked terms',
            'input' => 'html',
            'html' => $linked_terms_list,
        );
    }

    return $form_fields;
}, 10, 2 );

function get_linked_objects_html( $objects, $object_type ) {
    $html = '';
    foreach ( $objects as $object ) {
        if ( $object_type === 'post' ) {
            $target = get_edit_post_link( $object->ID );
            $id     = $object->ID;
        } else {
            $target = get_edit_tag_link( $object->term_id, 'category' );
            $id     = $object->term_id;
        }

        $html .= ' <a href="' . $target . '">' . $id . '</a>';
    }

    return $html;
}

add_filter( 'wp_required_field_message', '__return_false' );


function update_media_columns($columns) {
    $columns['attached_objects'] = __( 'Attached Objects' );
    return $columns;
} 
add_filter( 'manage_media_columns', 'update_media_columns' );
 
function attached_objects_row_cb( $column_name, $post_id ) {
    if ( $column_name !== 'attached_objects' ) {
        return '';
    }

    $image_data     = get_image_deletable_status( $post_id );
    $can_be_deleted = $image_data['status'];

    if ( $can_be_deleted ) {
        return '';
    }

    if ( ! empty( $image_data['linked_objects']['posts'] ) ) {
        echo _e( 'Articles', 'smd' ) . '<br>';
        echo get_linked_objects_html( $image_data['linked_objects']['posts'], 'post' );
        echo '<br>';
    }

    if ( ! empty( $image_data['linked_objects']['terms'] ) ) {
        echo _e( 'Terms', 'smd' ) . '<br>';
        echo get_linked_objects_html( $image_data['linked_objects']['terms'], 'term' );
    }
}
add_action( 'manage_media_custom_column', 'attached_objects_row_cb', 10, 2 );

// TASK 3
add_action( 'rest_api_init', function () {
    $args = array(
        'id' => array(
          'validate_callback' => function( $param, $request, $key ) {
            return is_numeric( $param );
          }
        ),
    );

    register_rest_route( 'assignment/v1', '/image/(?P<id>\d+)', array(
        array(
            'methods' => 'GET',
            'callback'=> 'get_image',
            'args'    => $args,
        ),
        array(
            'methods' => 'DELETE',
            'callback'=> 'delete_image',
            'args'    => $args,
        )
    ) );

} );

function get_image( $data ) {
    $post = get_post( $data['id'] );
    
    $response = array();
    $response['ID'] = $data['id'];
    $response['date'] = $post->post_date;
    $response['slug'] = get_attachment_link( $data['id'] );
    $response['type'] = $post->post_mime_type;
    $response['link'] = wp_get_attachment_url( $data['id'] );
    $response['alt_text'] = get_post_meta( $data['id'] , '_wp_attachment_image_alt', true );
    $linked_objects = get_objects_using_image( $data['id'] );
    $response['attached_objects'] = array(
        'posts' => array_column( $linked_objects['posts'], 'ID' ),
        'terms' => array_column( $linked_objects['terms'], 'ID' ),
    );

    return $response;
}

function delete_image( $data ) {
    $deleted = wp_delete_attachment( $data['id'] );
    if ( $deleted ) {
      return new WP_REST_Response( true, 200 );
    }
    return new WP_Error( 'cant-delete', __( 'Cannot delete image because it may be used in posts or tags.', 'smd' ), array( 'status' => 500 ) );
}



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

