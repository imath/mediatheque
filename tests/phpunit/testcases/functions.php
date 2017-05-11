<?php
/**
 * Functions tests.
 */

/**
 * @group functions
 */
class MediaTheque_Functions_Tests extends WP_UnitTestCase {
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

	/**
	 * @group download_url
	 */
	public function test_mediatheque_get_download_url_no_media() {
		$this->assertSame( '#', mediatheque_get_download_url( 0 ) );
	}

	/**
	 * @group download_url
	 */
	public function test_mediatheque_get_download_url_has_media() {
		$um_id = $this->mediatheque_factory->user_media_file->create( array( 'post_name' => 'foobar' ) );
		$this->user_media_ids[] = $um_id;

		$url       = parse_url( mediatheque_get_download_url( $um_id ), PHP_URL_PATH );
		$url_parts = explode( '/', trim( $url, '/' ) );

		$this->assertSame( array( mediatheque_get_root_slug(), 'foobar', mediatheque_get_download_rewrite_slug() ), $url_parts );
	}

	/**
	 * @group download_url
	 */
	public function test_mediatheque_get_download_url_globalized() {
		mediatheque()->user_media_link = 'globalized_url';

		$this->assertSame( 'globalized_url', mediatheque_get_download_url() );

		unset( mediatheque()->user_media_link );
	}

	/**
	 * @group multisite
	 * @group ms-required
	 */
	public function test_mediatheque_get_download_url_ms() {
		$user_id = $this->factory->user->create();
		$um_id   = $this->mediatheque_factory->user_media_file->create( array(
			'post_author' => $user_id,
			'post_name'   => 'bartaz'
		) );

		$this->user_media_ids[] = $um_id;

		$blog_id = $this->factory->blog->create( array(
			'user_id' => $user_id,
		) );

		switch_to_blog( $blog_id );

		$url       = parse_url( mediatheque_get_download_url( $um_id ), PHP_URL_PATH );
		$url_parts = explode( '/', trim( $url, '/' ) );

		restore_current_blog();

		$this->assertSame( array( mediatheque_get_root_slug(), 'bartaz', mediatheque_get_download_rewrite_slug() ), $url_parts );
	}
}
