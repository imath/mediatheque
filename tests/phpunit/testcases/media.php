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
}
