<?php
/**
 * The test class for the singular post creation
 *
 * @package    Monk
 * @subpackage Monk/Post Translation Tests
 * @since      0.4.0
 */

/**
 * Tests the methods related to adding a language to single posts
 *
 * @since      0.4.0
 *
 * @package    Monk
 * @subpackage Monk/Post Translation Tests
 */
class Test_Add_Language_To_Post extends WP_UnitTestCase {

	/**
	 * The WordPress test factory object.
	 *
	 * @since    0.4.0
	 *
	 * @access   private
	 * @var      class    $factory    A reference for the WP_UnitTest_Factory class.
	 */
	private $factory;

	/**
	 * The Monk_Post_Translation object.
	 *
	 * @since    0.4.0
	 *
	 * @access   private
	 * @var      class    $post_object    A reference for the Monk_Post_Translation class.
	 */
	private $post_object;

	/**
	 * The post to use during the tests.
	 *
	 * @since    0.4.0
	 *
	 * @access   private
	 * @var      class    $post_id    The id to use across the class.
	 */
	private $post_id;

	/**
	 * Initializes the test and handles the class instances.
	 *
	 * @since    0.4.0
	 *
	 * @return void
	 */
	function setUp() {
		require_once( '../../includes/class-monk-post-translation.php' );
		require_once( 'wptests/lib/factory.php' );

		parent::setUp();
		$this->factory     = new WP_UnitTest_Factory;
		$this->post_id     = $this->factory->post->create();
		$this->post_object = new Monk_Post_Translation( $this->post_id );

	} // end setUp

	/**
	 * Tests the creation process of a post with its language.
	 *
	 * @since    0.4.0
	 *
	 * @return void
	 */
	public function test_object_instance() {

		// Tests if this object is an instance of .
		$this->assertNotEmpty( $this->post_object );

	} // end test_object_instance.
}
