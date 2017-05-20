<?php
/**
 * Users Functions tests.
 */

/**
 * @group users
 */
class MediaTheque_Users_Tests extends WP_UnitTestCase {
	protected $mediatheque_factory;

	public function setUp() {
		parent::setUp();

		$this->mediatheque_factory = new MediaTheque_UnitTest_Factory;
	}

	/**
	 * @group folder
	 */
	public function test_mediatheque_delete_user_data() {
		$user_id = $this->factory->user->create();

		if ( is_multisite() ) {
			$blog_id = $this->factory->blog->create( array(
				'user_id' => $user_id,
			) );

			switch_to_blog( $blog_id );
		}

		$user_media_ids = array();

		$user_media_ids[] = $this->mediatheque_factory->user_media_file->create( array(
			'file'        => DIR_TESTDATA . '/images/waffles.jpg',
			'post_author' => $user_id,
		) );

		$f_id = $this->mediatheque_factory->user_media_folder->create( array(
			'post_author' => $user_id,
			'post_status' => 'private',
		) );
		$user_media_ids[] = $f_id;

		$user_media_ids[] = $this->mediatheque_factory->user_media_file->create( array(
			'post_author' => $user_id,
			'post_parent' => $f_id,
		) );

		// Delete/Remove from blog the user
		wp_delete_user( $user_id );
		$post_args = array(
			'include'   => $user_media_ids,
			'post_type' => 'user_media',
		);

		$is_main_site = mediatheque_is_main_site();

		if ( ! $is_main_site ) {
			switch_to_blog( get_current_network_id() );
		}

		$posts = get_posts( $post_args );

		if ( ! $is_main_site ) {
			switch_to_blog( $blog_id );
		}

		if ( ! is_multisite() ) {
			$this->assertTrue( 0 === count( $posts ) );
		} else {
			$this->assertFalse( 0 === count( $posts ), 'Removing a user from a blog should not remove user media' );

			restore_current_blog();

			wpmu_delete_user( $user_id );

			$posts = get_posts( $post_args );
			$this->assertTrue( 0 === count( $posts ), 'Deleting a user from a network should remove user media' );
		}
	}
}
