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

		$this->assertNotEmpty( mediatheque_get_personal_avatar( $um_id, '48' ) );
	}
}
