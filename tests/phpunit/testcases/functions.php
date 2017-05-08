<?php
/**
 * Functions tests.
 */

/**
 * @group functions
 */
class MediaTheque_Functions_Tests extends WP_UnitTestCase {

	/**
	 * @group multisite
	 * @group ms-required
	 */
	public function test_mediatheque_get_upload_dir() {
		$dir = mediatheque_get_upload_dir();

		$user_id = $this->factory->user->create();

		$blog_id = $this->factory->blog->create( array(
			'user_id' => $user_id,
		) );

		switch_to_blog( $blog_id );

		unset( mediatheque()->upload_dir );
		$dir_from_site = mediatheque_get_upload_dir();

		restore_current_blog();

		$this->assertSame( $dir, $dir_from_site );
	}
}
