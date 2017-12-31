<?php
/**
 * Rest API tests.
 */

/**
 * @group rest
 */
class MediaTheque_Rest_Tests extends WP_Test_REST_Controller_Testcase {
	protected $rb;
	protected $admin_id;
	protected $user_id;
	protected $cap;
	protected $current_user_id;
	protected $controller;
	protected $user_media_ids = array();
	protected $term_id = 0;

	public function setUp() {
		parent::setUp();

		$this->rb       = 'user-media';
		$this->admin_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

		if ( is_multisite() ) {
			grant_super_admin( $this->admin_id );
		}

		$this->user_id = $this->factory->user->create( array(
			'role' => 'subscriber',
		) );

		$this->cap = '';
		$this->current_user_id = get_current_user_id();
		$this->controller = new MediaTheque_REST_Controller( 'user_media' );

		$attrs = array(
			'post_type' => 'user_media',
		);

		$this->term_id = mediatheque_get_user_media_type_id( 'mediatheque-directory' );

		$this->user_media_ids[] = $this->factory->post->create( array_merge( $attrs, array(
			'post_author' => $this->admin_id,
			'post_status' => 'publish',
			'post_title'  => 'Public directory',
		) ) );

		$post_ID = reset( $this->user_media_ids );
		wp_set_post_terms( $post_ID, array( $this->term_id ), 'user_media_types' );

		$this->user_media_ids[] = $this->factory->post->create( array_merge( $attrs, array(
			'post_author' => $this->user_id,
			'post_status' => 'private',
			'post_title'  => 'Private directory',
		) ) );

		$post_ID = end( $this->user_media_ids );
		wp_set_post_terms( $post_ID, array( $this->term_id ), 'user_media_types' );
	}

	public function tearDown() {
		$this->cap = '';
		wp_set_current_user( $this->current_user_id );
		$this->controller = null;

		foreach ( $this->user_media_ids as $user_media_id ) {
			wp_delete_post( $user_media_id, true );
		}

		parent::tearDown();
	}

	public function get_cap( $caps, $cap ) {
		if ( in_array( $cap, mediatheque_get_all_caps(), true ) ) {
			$this->cap = $cap;
		}

		return $caps;
	}

	protected function set_post_data( $args = array() ) {
		$defaults = array(
			'title'       => 'User Media Title',
			'status'      => 'publish',
			'post_status' => 'publish',
			'author'      => get_current_user_id(),
			'type'        => 'user_media',
		);

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Routes
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wp/v2/' . $this->rb, $routes );
	}

	public function test_create_items_permissions_check() {
		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		wp_set_current_user( 0 );
		$request = new WP_REST_Request( WP_REST_Server::CREATABLE, '/wp/v2/' . $this->rb );
		$check   = $this->controller->create_item_permissions_check( $request );

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertErrorResponse( 'rest_cannot_create', $check );
		$this->assertTrue( 'create_user_uploads' === $this->cap );
	}

	public function test_context_param() {}

	public function test_get_items() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/' . $this->rb );
		$response = $this->server->dispatch( $request );
		$results  = $response->get_data();

		$public_user_media = wp_list_filter( $results, array( 'status' => 'publish' ) );
		$this->assertTrue( 1 === count( $public_user_media ) );

		$term_id = reset( $results[0]['user_media_types'] );
		$this->assertTrue( $this->term_id === $term_id );

		$private_user_media = wp_list_filter( $results, array( 'status' => 'private' ) );
		$this->assertTrue( 0 === count( $private_user_media ) );
	}

	public function test_get_item() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/' . $this->rb . '/' . $this->user_media_ids[0] );
		$response = $this->server->dispatch( $request );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_private_item_cant() {
		wp_set_current_user( 0 );

		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/' . $this->rb . '/' . $this->user_media_ids[1] );
		$response = $this->server->dispatch( $request );

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertErrorResponse( 'rest_forbidden', $response );
		$this->assertTrue( 'read_user_upload' === $this->cap );
	}

	public function test_get_private_item_can() {
		wp_set_current_user( $this->user_id );

		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/' . $this->rb . '/' . $this->user_media_ids[1] );
		$response = $this->server->dispatch( $request );

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( 'read_user_upload' === $this->cap );
	}

	public function test_create_item() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( WP_REST_Server::CREATABLE, '/wp/v2/' . $this->rb );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_post_data( array(
			'action' => 'mkdir_user_media',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$user_media = $response->get_data();

		$this->assertNotEmpty( $user_media['id'] );

		$term_id = reset( $user_media['user_media_types'] );
		$this->assertTrue( $this->term_id === $term_id );

		//clean up
		mediatheque_delete_dir( $user_media['id'] );
	}

	public function test_create_private_item() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( WP_REST_Server::CREATABLE, '/wp/v2/' . $this->rb );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_post_data( array(
			'action'      => 'mkdir_user_media',
			'post_status' => 'private',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$user_media = $response->get_data();

		$this->assertNotEmpty( $user_media['id'] );

		$term_id = reset( $user_media['user_media_types'] );
		$this->assertTrue( $this->term_id === $term_id );

		//clean up
		mediatheque_delete_dir( $user_media['id'] );
	}

	public function test_update_item() {
		wp_set_current_user( $this->user_id );

		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/' . $this->rb . '/' . $this->user_media_ids[1] );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_post_data( array(
			'status' => 'private',
			'title'  => 'Edited User Media',
		) );

		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );

		$edited = $response->get_data();
		$this->assertTrue( 'Edited User Media' === $edited['title']['raw'] );
		$this->assertTrue( 'publish_user_uploads' === $this->cap );
	}

	public function test_update_item_cant() {
		wp_set_current_user( $this->user_id );

		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/' . $this->rb . '/' . $this->user_media_ids[0] );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_post_data( array(
			'title'  => 'Edited User Media',
		) );

		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertErrorResponse( 'rest_cannot_edit', $response );
		$this->assertTrue( 'edit_user_upload' === $this->cap );
	}

	public function test_update_item_can() {
		wp_set_current_user( $this->admin_id );

		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/' . $this->rb . '/' . $this->user_media_ids[1] );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_post_data( array(
			'status' => 'private',
			'title'  => 'Edited by admin User Media',
		) );

		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );

		$edited = $response->get_data();
		$this->assertTrue( 'Edited by admin User Media' === $edited['title']['raw'] );
		$this->assertTrue( 'publish_user_uploads' === $this->cap );
	}

	public function test_delete_item() {
		$user_media_id = $this->factory->post->create( array(
			'post_author' => $this->user_id,
			'post_status' => 'publish',
			'post_title'  => 'Deleted directory',
			'post_type'   => 'user_media',
		) );

		wp_set_post_terms( $user_media_id, array( $this->term_id ), 'user_media_types' );

		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'DELETE', '/wp/v2/' . $this->rb . '/' . $user_media_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['deleted'] );
		$this->assertTrue( 'delete_user_upload' === $this->cap );
	}

	public function test_delete_item_cant() {
		$user_media_id = $this->factory->post->create( array(
			'post_author' => $this->admin_id,
			'post_status' => 'publish',
			'post_title'  => 'Deleted directory',
			'post_type'   => 'user_media',
		) );

		wp_set_post_terms( $user_media_id, array( $this->term_id ), 'user_media_types' );

		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'DELETE', '/wp/v2/' . $this->rb . '/' . $user_media_id );
		$response = $this->server->dispatch( $request );

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertErrorResponse( 'rest_cannot_delete', $response );
		$this->assertTrue( 'delete_user_upload' === $this->cap );
	}

	public function test_delete_item_can() {
		$user_media_id = $this->factory->post->create( array(
			'post_author' => $this->user_id,
			'post_status' => 'publish',
			'post_title'  => 'Deleted directory',
			'post_type'   => 'user_media',
		) );

		wp_set_post_terms( $user_media_id, array( $this->term_id ), 'user_media_types' );

		add_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'DELETE', '/wp/v2/' . $this->rb . '/' . $user_media_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		remove_filter( 'mediatheque_map_meta_caps', array( $this, 'get_cap' ), 10, 2 );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['deleted'] );
		$this->assertTrue( 'delete_user_upload' === $this->cap );
	}

	public function test_prepare_item_for_edit_response() {
		$user_media_id = $this->factory->post->create( array(
			'post_author' => $this->user_id,
			'post_status' => 'publish',
			'post_title'  => 'User Media',
			'post_type'   => 'user_media',
		) );

		$url = mediatheque_get_download_url( $user_media_id );

		$p = $this->factory->post->create( array(
			'post_content' => $url,
		) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/' . $this->rb . '/' . $user_media_id );
		$params = array(
			'user_media_edit' => true,
		);
		$request->set_query_params( $params );
		$response = $this->server->dispatch( $request );
		$response = rest_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$user_media = $response->get_data();
		$posts = wp_list_pluck( $user_media['attached_posts'], 'id' );
		$post = reset( $posts );

		$this->assertSame( $p, $post );
	}

	/**
	 * @group ms-required
	 * @group multisite
	 */
	public function test_prepare_item_for_edit_response_from_site() {
		$user_media_id = $this->factory->post->create( array(
			'post_author' => $this->user_id,
			'post_status' => 'publish',
			'post_title'  => 'User Media',
			'post_type'   => 'user_media',
		) );

		$url = mediatheque_get_download_url( $user_media_id );

		$blog_id = $this->factory->blog->create( array(
			'user_id' => $this->user_id,
		) );

		switch_to_blog( $blog_id );

		$p = $this->factory->post->create( array(
			'post_content' => $url,
		) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/' . $this->rb . '/' . $user_media_id );
		$params = array(
			'user_media_edit' => true,
		);
		$request->set_query_params( $params );
		$response = $this->server->dispatch( $request );
		$response = rest_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$user_media = $response->get_data();
		$posts = wp_list_pluck( $user_media['attached_posts'], 'id' );
		$post = reset( $posts );

		$this->assertSame( $p, $post );

		restore_current_blog();
	}

	public function test_prepare_item() {}

	public function test_get_item_schema() {}

	/**
	 * Neutralize this check in parent class as we're using pretty links
	 */
	public function filter_rest_url_for_leading_slash( $url, $path ) {
		return $url;
	}
}
