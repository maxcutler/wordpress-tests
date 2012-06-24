<?php
require_once dirname( __FILE__ ) . '/factory.php';
require_once dirname( __FILE__ ) . '/trac.php';

class WP_UnitTestCase extends PHPUnit_Framework_TestCase {

	var $factory;

	function setUp() {
		global $wpdb;
		$wpdb->suppress_errors = false;
		$wpdb->show_errors = true;
		$wpdb->db_connect();
		ini_set('display_errors', 1 );
		$this->factory = new WP_UnitTest_Factory;
		$this->clean_up_global_scope();
		$this->start_transaction();
	}

	function tearDown() {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
	}

	function clean_up_global_scope() {
		wp_cache_flush();
		$_GET = array();
		$_POST = array();
	}

	function start_transaction() {
		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );
		$wpdb->query( 'START TRANSACTION;' );
	}

	function assertWPError( $actual, $message = '' ) {
		$this->assertTrue( is_wp_error( $actual ), $message );
	}

	function assertEqualFields( $object, $fields ) {
		foreach( $fields as $field_name => $field_value ) {
			if ( $object->$field_name != $field_value ) {
				$this->fail();
			}
		}
	}

	function assertDiscardWhitespace( $expected, $actual ) {
		$this->assertEquals( preg_replace( '/\s*/', '', $expected ), preg_replace( '/\s*/', '', $actual ) );
	}

	function checkAtLeastPHPVersion( $version ) {
		if ( version_compare( PHP_VERSION, $version, '<' ) ) {
			$this->markTestSkipped();
		}
	}

	function go_to( $url ) {
		// note: the WP and WP_Query classes like to silently fetch parameters
		// from all over the place (globals, GET, etc), which makes it tricky
		// to run them more than once without very carefully clearing everything
		$_GET = $_POST = array();
		foreach (array('query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow') as $v) {
			if ( isset( $GLOBALS[$v] ) ) unset( $GLOBALS[$v] );
		}
		$parts = parse_url($url);
		if (isset($parts['scheme'])) {
			$req = $parts['path'];
			if (isset($parts['query'])) {
				$req .= '?' . $parts['query'];
				// parse the url query vars into $_GET
				parse_str($parts['query'], $_GET);
			} else {
				$parts['query'] = '';
			}
		}
		else {
			$req = $url;
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset($_SERVER['PATH_INFO']);

		wp_cache_flush();
		unset($GLOBALS['wp_query'], $GLOBALS['wp_the_query']);
		$GLOBALS['wp_the_query'] =& new WP_Query();
		$GLOBALS['wp_query'] =& $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] =& new WP();

		// clean out globals to stop them polluting wp and wp_query
		foreach ($GLOBALS['wp']->public_query_vars as $v) {
			unset($GLOBALS[$v]);
		}
		foreach ($GLOBALS['wp']->private_query_vars as $v) {
			unset($GLOBALS[$v]);
		}

		$GLOBALS['wp']->main($parts['query']);
	}

	// as it suggests: delete all posts and pages
	function _delete_all_posts() {
		global $wpdb;

		$all_posts = $wpdb->get_col("SELECT ID from {$wpdb->posts}");
		if ($all_posts) {
			foreach ($all_posts as $id)
				wp_delete_post( $id, true );
		}
	}

	/**
	 * Skips the current test if there is open WordPress ticket with id $ticket_id
	 */
	function knownWPBug($ticket_id) {
		if ( ! TrackTickets::isTracTicketClosed('http://core.trac.wordpress.org', $ticket_id ) ) {
			$this->markTestSkipped( sprintf( 'WordPress Ticket #%d is not fixed', $ticket_id ) );
		}
	}

	/**
	 * Skips the current test if there is open unit tests ticket with id $ticket_id
	 */
	function knownUTBug($ticket_id) {
		if ( ! TrackTickets::isTracTicketClosed( 'http://unit-tests.trac.wordpress.org', $ticket_id ) ) {
			$this->markTestSkipped( sprintf( 'Unit Tests Ticket #%d is not fixed', $ticket_id ) );
		}
	}

	/**
	 * Skips the current test if there is open WordPress MU ticket with id $ticket_id
	 */
	function knownMUBug($ticket_id) {
		if ( ! TrackTickets::isTracTicketClosed ('http://trac.mu.wordpress.org', $ticket_id ) ) {
			$this->markTestSkipped( sprintf( 'WordPress MU Ticket #%d is not fixed', $ticket_id ) );
		}
	}

	/**
	 * Skips the current test if there is open plugin ticket with id $ticket_id
	 */
	function knownPluginBug($ticket_id) {
		if ( ! TrackTickets::isTracTicketClosed( 'http://dev.wp-plugins.org', $ticket_id ) ) {
			$this->markTestSkipped( sprintf( 'WordPress Plugin Ticket #%d is not fixed', $ticket_id ) );
		}
	}

}
