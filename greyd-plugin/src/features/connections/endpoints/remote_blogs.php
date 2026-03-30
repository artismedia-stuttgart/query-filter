<?php
/**
 * Endpoint 'remote_blogs'
 *
 * @link {{your-domain}}/wp-json/greyd/v1/gc/remote_blogs
 */
namespace Greyd\Connections\Endpoints;

use Greyd\Hub\Hub_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

new Remote_Blogs();
class Remote_Blogs extends \Greyd\Connections\Endpoint {

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->rest_base = 'remote_blogs';

		parent::__construct();
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_routes() {

		// base (get all blogs)
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => $this->method,
					'callback'            => array( $this, 'get_all_blogs' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

		// dump blog
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/dump/(?P<mode>[a-z]+)',
			array(
				array(
					'methods'             => $this->method,
					'callback'            => array( $this, 'dump_blog' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

		// connect blog
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/connect/(?P<mode>[a-z]+)/(?P<related>[^\/]+)',
			array(
				array(
					'methods'             => $this->method,
					'callback'            => array( $this, 'connect_blog' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		// disconnect blog
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/disconnect',
			array(
				array(
					'methods'             => $this->method,
					'callback'            => array( $this, 'disconnect_blog' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
		// get blog changes
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/changes/(?P<datetime>[^\/]+)',
			array(
				array(
					'methods'             => $this->method,
					'callback'            => array( $this, 'get_blog_changes' ),
					'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);
	}

	/**
	 * Endpoint callbacks
	 *
	 * @param WP_REST_Request $request
	 */
	public function get_all_blogs( $request ) {

		$blogs = null;
		$blogs = Hub_Helper::get_all_blogs();

		if ( empty( $blogs ) ) {
			return new \WP_Error( 'no_blogs', 'Blogs could not be found', array( 'status' => 404 ) );
		}

		return $this->respond( $blogs );
	}

	public function dump_blog( $request ) {

		$blogid = intval( $request['id'] );
		$mode   = $request['mode'];
		if ( ! in_array( $mode, array( 'files', 'db', 'plugins', 'themes', 'content', 'site' ) ) ) {
			return new \WP_Error( 'no_dump', 'Dump mode "' . $mode . '" is not valid', array( 'status' => 404 ) );
		}

		$clean = isset( $request['clean'] ) ? $request['clean'] : true;

		$url = null;
		$url = Hub_Helper::dump_blog( $blogid, $mode, $clean, true );

		if ( $url === false ) {
			return new \WP_Error( 'no_blog', 'Blog ' . $blogid . ' could not be found', array( 'status' => 404 ) );
		}

		if ( empty( $url ) ) {
			return new \WP_Error( 'dump_error', 'Blog ' . $blogid . ' dump "' . $mode . '" could not be created', array( 'status' => 404 ) );
		}

		return $this->respond( $url );
	}

	public function connect_blog( $request ) {

		$blogid  = intval( $request['id'] );
		$mode    = $request['mode'];
		$related = $request['related'];
		if ( ! in_array( $mode, array( 'live', 'stage' ) ) ) {
			return new \WP_Error( 'no_connect', 'Connect mode "' . $mode . '" is not valid', array( 'status' => 404 ) );
		}

		$result = null;
		$result = Hub_Helper::connect_blog( $blogid, $mode, rawurldecode( $related ) );

		if ( $result === null || $result === false ) {
			return new \WP_Error( 'no_connect', 'Blogs could not be connected', array( 'status' => 404 ) );
		}

		return $this->respond( $result );

	}

	public function disconnect_blog( $request ) {

		$blogid = intval( $request['id'] );

		$result = null;
		$result = Hub_Helper::disconnect_blog( $blogid );

		if ( $result === null || $result === false ) {
			return new \WP_Error( 'no_disconnect', 'Blogs could not be disconnected', array( 'status' => 404 ) );
		}

		return $this->respond( $result );

	}

	public function get_blog_changes( $request ) {

		$blogid   = intval( $request['id'] );
		$datetime = rawurldecode( $request['datetime'] );

		$result = null;
		$result = Hub_Helper::get_changes( $blogid, $datetime );

		if ( $result === null || $result === false ) {
			return new \WP_Error( 'no_changes', 'Blog changes could not be retrieved', array( 'status' => 404 ) );
		}

		return $this->respond( $result );

	}

	// /**
	// * Make this a public endpoint
	// */
	// public function permission_callback($request) {
	// dev: make public on localhost
	// if (strpos($request->get_header('host'), '.localhost') > 0) return true;
	// return false;
	// }
}
