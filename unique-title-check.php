<?php
/**
 * Plugin Name: Unique Title Check
 * Plugin URI: http://buddydevelopers.com
 * Description: Checks if the title of a post, page or custom post type is unique and warn the editor if not before post submission.
 * Author: buddydevelopers
 * Version: 1.1
 * Author URI: http://www.buddydevelopers.com/
 * License: GPL-2.0+
 * Text Domain: buddy-utc
 * Domain Path:       /languages
 *
 * @package BuddyUTC
 * @subpackage BuddyUTC\Backend
 */

namespace BUDDY_UTC_Check;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action(
	'plugins_loaded',
	array( Buddy_Unique_Title_Check::get_instance(), 'action_call' )
);
/**
 * Plugin code for title checker management.
 * *********************************************
 * Admin side post, page, custom post type title checker functionality of the plugin.
 *
 * @package BuddyUTC
 * @subpackage BuddyUTC\Backend
 * @author     buddydevelopers <buddydevelopers@gmail.com>
 */
class Buddy_Unique_Title_Check {


	/**
	 * Single ton pattern instance reuse.
	 * ************************************
	 *
	 * @access  private
	 *
	 * @var object  $_instance class instance.
	 */
	private static $_instance;


	/**
	 * Get class instance in singleton way.
	 * ***************************************
	 *
	 * @return object Buddy_Unique_Title_Check::$_instance
	 */
	public static function get_instance() {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Initialize the class and set its properties.
	 * *******************************************
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Function to call WP action and filters on class instance creation.
	 * *********************************************************************
	 *
	 * @since    1.0.0
	 */
	public function action_call() {
		add_action( 'admin_enqueue_scripts', array( $this, 'utc_enqueue_scripts' ), 2000 );
		// Since we believe Post title will not be unique if post slug get create on post autosave, so buddy disable it.
		add_action( 'wp_print_scripts', array( $this, 'utc_disable_autosave' ) );
		add_action( 'wp_ajax_utc_check', array( $this, 'unique_title_check_callback' ) );
	}

	/**
	 * Function to Include script and style file required for plugin.
	 * **************************************************************
	 * For now It will include only script file in admin side
	 *
	 * @since    1.0.0
	 */
	public function utc_enqueue_scripts() {
		 wp_enqueue_script( 'utc_script',  plugin_dir_url( __FILE__ ) . 'js/utc-script.js', array( 'jquery' ) );
		 // Localize the script with new data.
		$utc_nonce = wp_create_nonce( 'XXnonceXtoXfuckXhackerXX' );
		wp_localize_script( 'utc_script', 'utc_nonce', $utc_nonce );
	}

	/**
	 * Function to disable autosave feature of post to maintain unique post title before post publish
	 * ***************************************************************************************************
	 *
	 * @since    1.0.0
	 */
	public function utc_disable_autosave() {
		wp_deregister_script( 'autosave' );
	}

	/**
	 * Function do a DB query to check if post title exist already.
	 * **************************************************************
	 *
	 * @since    1.0.0
	 */
	public function unique_title_check_callback() {

		// Verify the ajax request.
		check_ajax_referer( 'XXnonceXtoXfuckXhackerXX', 'ajaxnonce' );

		$title = sanitize_text_field( $_POST['post_title'] );
		$post_id = intval( $_POST['post_id'] );
		$post_type = sanitize_text_field( $_POST['post_type'] );

		// Show no warning, when the title is empty.
		if ( empty( $title ) ) {
			return;
		}

		// Build the necessary args for the initial uniqueness check.
		$args = array(
			'post__not' => $post_id,
			'post_type'    => $post_type,
			'post_title'   => $title,
		);

		$response = $this->check_uniqueness( $args );
		echo wp_json_encode( $response );

		die();

	}

	/**
	 * Check for the uniqueness of the post.
	 *
	 * @param array|string $args The post query arguments array.
	 *
	 * @return array The status and message for the response
	 */
	public function check_uniqueness( $args ) {
		global $wpdb;
		$statement = "SELECT post_title FROM {$wpdb->posts} WHERE post_type = %s
					AND post_title = %s AND ID != %d AND post_status = %s";
		extract( $args );
		$query = $wpdb->prepare( $statement, $post_type, $post_title, $post__not, 'publish' );
		$post_found = $wpdb->get_results( $query );
		$posts_count = count( $post_found );
		if ( empty( $posts_count ) ) {
			$response = array(
				'message' => __( 'The chosen title is unique.', 'unique-title-checker' ),
				'status'  => 'updated',
			);
		} else {
			$response = array(
				'message' => sprintf( _n( 'There is 1 %2$s with the same title!', 'There are %1$d other %3$s with the same title!', $posts_count, 'unique-title-checker' ), $posts_count, $post_type_singular_name, $post_type_name ),
				'status'  => 'error',
			);
		}

		return $response;
	}
}
