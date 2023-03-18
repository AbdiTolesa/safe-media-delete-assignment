<?php

/**
 * Contains tests for methods in SMD_Media_Delete_Handle.
 */
class test_SMD_Media_Delete_Handler extends WP_UnitTestCase {

	public function test_get_image_deletable_status() {
		$post = $this->factory->post->create_and_get();
		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/assets/test-attachment.jpg', $post->ID );

		set_post_thumbnail( $post, $attachment_id );
		$image_data = get_image_deletable_status( $attachment_id );
		$this->assertFalse( $image_data['status'] );
		$this->assertEquals( $image_data['linked_objects']['posts'][0]->ID, $post->ID);

		delete_post_thumbnail( $post );
		delete_transient( 'posts_with_attachments' );
		$image_data = get_image_deletable_status( $attachment_id );
		$this->assertTrue( $image_data['status'] );

		$post->post_content = wp_get_attachment_image( $attachment_id );
		wp_update_post( $post );
		delete_transient( 'posts_with_attachments' );
		$image_data = get_image_deletable_status( $attachment_id );
		$this->assertFalse( $image_data['status'] );

		$post->post_content = '';
		wp_update_post( $post );
		delete_transient( 'posts_with_attachments' );
		$image_data = get_image_deletable_status( $attachment_id );
		$this->assertTrue( $image_data['status'] );

		delete_transient( 'posts_with_attachments' );

		$term = $this->factory->term->create_and_get();
		add_term_meta( $term->term_id, 'smd_term_image_id', $attachment_id );
		$image_data = get_image_deletable_status( $attachment_id );
		$this->assertFalse( $image_data['status'] );

		delete_transient( 'terms_linked_to_images' );
		delete_term_meta( $term->term_id, 'smd_term_image_id' );
		$image_data = get_image_deletable_status( $attachment_id );
		$this->assertTrue( $image_data['status'] );
	}

	public function test_delete_image() {
		$post = $this->factory->post->create_and_get();
		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/assets/test-attachment.jpg', $post->ID );
		set_post_thumbnail( $post, $attachment_id );
		$deleted = wp_delete_attachment( $attachment_id );
		$this->assertFalse( $deleted );

		delete_post_thumbnail( $post );
		delete_transient( 'posts_with_attachments' );
		$deleted = wp_delete_attachment( $attachment_id );
		$this->assertIsObject( $deleted );
	}
}