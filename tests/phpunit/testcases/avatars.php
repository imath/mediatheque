<?php
/**
 * Avatars tests.
 */

/**
 * @group avatars
 */
class MediaTheque_Avatars_Tests extends WP_UnitTestCase {
	protected $mediatheque_factory;
	protected $user_media_ids = array();

	public function setUp() {
		parent::setUp();

		$this->mediatheque_factory = new MediaTheque_UnitTest_Factory;
	}

	public function tearDown() {
		foreach ( $this->user_media_ids as $um_id ) {
			mediatheque_delete_media( $um_id );
		}

		parent::tearDown();
	}

	public function test_mediatheque_get_personal_avatar() {
		$um_id = $this->mediatheque_factory->user_media_file->create();
		$this->user_media_ids[] = $um_id;

		$this->assertNotEmpty( mediatheque_get_personal_avatar( $um_id, 48 ) );
	}

	public function test_mediatheque_get_avatar_data() {
		$user_id = $this->factory->user->create();

		$um_id = $this->mediatheque_factory->user_media_file->create( array(
			'post_author' => $user_id,
			'file'        => DIR_TESTDATA . '/images/waffles.jpg',
		) );

		$this->user_media_ids[] = $um_id;

		$user = get_user_by( 'id', $user_id );
		$user->personal_avatar = $um_id;
		$avatar_url = mediatheque_get_personal_avatar( $um_id, 96 );

		$this->assertSame( get_avatar_url( $user, array( 'size' => 96 ) ), $avatar_url );
	}

	/**
	 * @group ms-required
	 * @group multisite
	 */
	public function test_mediatheque_get_avatar_data_from_site() {
		$user_id = $this->factory->user->create();

		$um_id = $this->mediatheque_factory->user_media_file->create( array(
			'post_author' => $user_id,
		) );

		$this->user_media_ids[] = $um_id;

		$blog_id = $this->factory->blog->create( array(
			'user_id' => $user_id,
		) );

		switch_to_blog( $blog_id );

		$user = get_user_by( 'id', $user_id );
		$user->personal_avatar = $um_id;
		$avatar_url_from_site = get_avatar_url( $user, array( 'size' => 24 ) );

		restore_current_blog();

		$avatar_url = mediatheque_get_personal_avatar( $um_id, 24 );
		$this->assertSame( $avatar_url_from_site, $avatar_url );
	}
}
