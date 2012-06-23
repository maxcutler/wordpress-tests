<?php

include_once( ABSPATH . WPINC . '/class-IXR.php' );
include_once( ABSPATH . WPINC . '/class-wp-xmlrpc-server.php' );

class WP_XMLRPC_UnitTestCase extends WP_UnitTestCase {
	protected $myxmlrpcserver;

	function setUp() {
		parent::setUp();

		add_filter( 'pre_option_enable_xmlrpc', '__return_true' );

		$this->myxmlrpcserver = new wp_xmlrpc_server();
	}

	function tearDown() {

		remove_filter( 'pre_option_enable_xmlrpc', '__return_true' );

		parent::tearDown();
	}
}
