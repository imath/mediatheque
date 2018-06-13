<?php
/**
 * Embed tests.
 */

/**
 * @group embed
 */
class MediaTheque_Embed_Tests extends WP_UnitTestCase {
	protected $mediatheque_factory;
	protected $user_media_ids = array();
	protected $oembed;

	public function setUp() {
		parent::setUp();

		$this->mediatheque_factory = new MediaTheque_UnitTest_Factory;

		require_once ABSPATH . WPINC . '/class-oembed.php';
		$this->oembed = _wp_oembed_get_object();
	}

	public function tearDown() {
		foreach ( $this->user_media_ids as $um_id ) {
			mediatheque_delete_media( $um_id );
		}

		parent::tearDown();
	}

	public function _filter_pre_oembed_result( $result ) {
		// Return false to prevent HTTP requests during tests.
		return $result ? $result : false;
	}

	public function test_mediatheque_oembed_user_media_id_for_image() {
		if ( ! file_exists( ABSPATH . WPINC . '/js/wp-embed.js' ) ) {
			$this->markTestSkipped( 'wp-embed.js is required for this test.' );
		}

		mediatheque()->user_media_oembeds = array();

		$img_id = $this->mediatheque_factory->user_media_file->create( array(
			'file' => DIR_TESTDATA . '/images/waffles.jpg',
		) );

		$this->user_media_ids[] = $img_id;
		$wp_embed_link = get_post_permalink( $img_id );

		$this->go_to( $wp_embed_link );

		add_filter( 'pre_oembed_result', array( $this, '_filter_pre_oembed_result' ) );

		$wp_embed      = $this->oembed->get_html( $wp_embed_link );

		$attached_link = add_query_arg( 'attached', true, $wp_embed_link );
		$img_embed     = $this->oembed->get_html( $attached_link );

		$this->assertNotEquals( $wp_embed, $img_embed );

		$full_src  = mediatheque_image_get_intermediate_size( $img_id, 'full' );
		$this->assertNotEmpty( strpos( $img_embed, $full_src['url'] ) );

		$attached_link = add_query_arg( 'size', 'thumbnail', $attached_link );
		$img_embed     = $this->oembed->get_html( $attached_link );

		$thumbnail_src  = mediatheque_image_get_intermediate_size( $img_id, 'thumbnail' );
		$this->assertNotEmpty( strpos( $img_embed, $thumbnail_src['url'] ) );

		$attached_link = add_query_arg( 'align', 'center', $attached_link );
		$img_embed     = $this->oembed->get_html( $attached_link );

		preg_match( '/class="(.*?)"/', $img_embed, $matches );
		$this->assertContains( 'aligncenter', explode( ' ', $matches[1] ) );

		remove_filter( 'pre_oembed_result', array( $this, '_filter_pre_oembed_result' ) );
	}

	public function test_mediatheque_oembed_user_media_id_for_document() {
		if ( ! file_exists( ABSPATH . WPINC . '/js/wp-embed.js' ) ) {
			$this->markTestSkipped( 'wp-embed.js is required for this test.' );
		}

		mediatheque()->user_media_oembeds = array();

		$doc_id = $this->mediatheque_factory->user_media_file->create( array(
			'file' => DIR_TESTDATA . '/images/wordpress-gsoc-flyer.pdf',
		) );

		$this->user_media_ids[] = $doc_id;
		$wp_embed_link = get_post_permalink( $doc_id );

		$this->go_to( $wp_embed_link );

		add_filter( 'pre_oembed_result', array( $this, '_filter_pre_oembed_result' ) );

		$wp_embed      = $this->oembed->get_html( $wp_embed_link );
		$download_url  = mediatheque_get_download_url( $doc_id );

		$attached_link  = add_query_arg( 'attached', true, $wp_embed_link );
		$document_embed = $this->oembed->get_html( $attached_link );

		remove_filter( 'pre_oembed_result', array( $this, '_filter_pre_oembed_result' ) );

		$this->assertNotEquals( $wp_embed, $document_embed );
		$this->assertNotEmpty( strpos( $document_embed, $download_url ) );
	}

	public function test_mediatheque_oembed_vanished_user_media() {
		mediatheque()->user_media_oembeds = array();

		$img_id = $this->mediatheque_factory->user_media_file->create( array(
			'file' => DIR_TESTDATA . '/images/waffles.jpg',
		) );

		$user_media_link = get_post_permalink( $img_id );
		$attached_link   = add_query_arg( 'attached', true, $user_media_link );

		$this->go_to( $user_media_link );

		// Media Vanished
		mediatheque_delete_media( $img_id );

		$reset_current = get_current_user_id();

		$user_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

		wp_set_current_user( $user_id );

		add_filter( 'pre_oembed_result', array( $this, '_filter_pre_oembed_result' ) );

		$vanished = $GLOBALS['wp_embed']->maybe_make_link( $attached_link );

		remove_filter( 'pre_oembed_result', array( $this, '_filter_pre_oembed_result' ) );

		preg_match( '/class="(.*?)"/', $vanished, $matches );
		$this->assertContains( 'mediatheque-vanished-media', explode( ' ', $matches[1] ) );

		wp_set_current_user( 0 );

		$vanished = $GLOBALS['wp_embed']->maybe_make_link( $attached_link );

		$this->assertSame( array( $user_media_link ), get_option( '_mediatheque_vanished_media' ) );

		delete_option( '_mediatheque_vanished_media' );

		if ( is_multisite() ) {
			$blog_id = $this->factory->blog->create( array(
				'user_id' => $user_id,
			) );

			switch_to_blog( $blog_id );

			$vanished = $GLOBALS['wp_embed']->maybe_make_link( $attached_link );

			$this->assertSame( array( $user_media_link ), get_option( '_mediatheque_vanished_media' ) );

			delete_option( '_mediatheque_vanished_media' );

			restore_current_blog();
		}

		wp_set_current_user( $reset_current );
	}
}
