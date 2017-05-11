<?php
/**
 * Media tests.
 */

/**
 * @group media
 */
class MediaTheque_Media_Tests extends WP_UnitTestCase {
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

	public function test_mediatheque_get_media_info_all() {
		$pdf_id = $this->mediatheque_factory->user_media_file->create( array(
			'file' => DIR_TESTDATA . '/images/wordpress-gsoc-flyer.pdf',
		) );
		$this->user_media_ids[] = $pdf_id;
		$pdf_info = mediatheque_get_media_info( $pdf_id, 'all' );

		$this->assertTrue( 'application/pdf' === $pdf_info['type'] );

		$img_id = $this->mediatheque_factory->user_media_file->create( array(
			'file' => DIR_TESTDATA . '/images/test-image.jpg',
		) );
		$this->user_media_ids[] = $img_id;
		$img_info = mediatheque_get_media_info( $img_id, 'all' );

		$this->assertTrue( 'image/jpeg' === $img_info['type'] );

		foreach ( array( 'ext', 'type', 'media_type', 'size' ) as $key ) {
			$this->assertArrayHasKey( $key, $pdf_info );
			$this->assertArrayHasKey( $key, $img_info );
		}
	}

	public function test_mediatheque_get_media_info_media_type() {
		$pdf_id = $this->mediatheque_factory->user_media_file->create( array(
			'file' => DIR_TESTDATA . '/images/wordpress-gsoc-flyer.pdf',
		) );
		$this->user_media_ids[] = $pdf_id;
		$pdf_info = mediatheque_get_media_info( $pdf_id );

		$this->assertTrue( 'document' === $pdf_info );

		$img_id = $this->mediatheque_factory->user_media_file->create( array(
			'file' => DIR_TESTDATA . '/images/test-image.jpg',
		) );
		$this->user_media_ids[] = $img_id;
		$img_info = mediatheque_get_media_info( $img_id );

		$this->assertTrue( 'image' === $img_info );
	}

	public function test_mediatheque_image_get_intermediate_size() {
		$user_id = $this->factory->user->create();

		$um_id = $this->mediatheque_factory->user_media_file->create( array(
			'file'        => DIR_TESTDATA . '/images/waffles.jpg',
			'post_author' => $user_id,
		) );
		$this->user_media_ids[] = $um_id;

		$up_dir = wp_get_upload_dir();
		$small  = mediatheque_image_get_intermediate_size( $um_id, array( 24, 24 ) );

		$this->assertTrue( file_exists( $up_dir['basedir'] . '/mediatheque/publish/' . $user_id . '/' . $small['file'] ) );

		$full = mediatheque_image_get_intermediate_size( $um_id, array() );
		$this->assertTrue( file_exists( $full['path'] ) );
	}

	public function test_mediatheque_get_post_by_slug() {
		$um_id   = $this->mediatheque_factory->user_media_file->create( array(
			'post_name'   => 'right-slug'
		) );

		$this->user_media_ids[] = $um_id;
		$user_media = mediatheque_get_post_by_slug( 'right-slug' );

		$this->assertEquals( $um_id, $user_media->ID );

		$user_media = mediatheque_get_post_by_slug( 'wrong-slug' );

		$this->assertEmpty( $user_media );
	}
}
