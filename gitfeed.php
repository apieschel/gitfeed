<?php 

/**
 * @package Gitfeed
 */
/*
Plugin Name: Gitfeed
Plugin URI: https://github.com/apieschel/gitfeed
Description: Plugin for displaying a feed for your latest Git commits on a portfolio site.
Version: 1.0
Author: Alex Pieschel
Author URI: https://gtrsoftware.com
License: GPLv2 or later
Text Domain: gitfeed
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'Gitfeed' ) ) {
	/**
	 * Class Gitfeed
	 *
	 * Main Plugin class. Intializes the plugin.
	 */
	class Gitfeed {
		
		private static $instance = null;
	
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
		
		/**
		 * Gitfeed constructor.
		 */
		public function __construct() {		
			$this->includes();
			
			$this->init();
			
			$this->load_textdomain();	
		}
		
		/**
		 * Initialize the plugin.
		 */
		private function init() {
			// Initialize the plugin core.
			$this->api = new Github_API();
		}
		
		/**
		 * Load translations
		 */
		private function load_textdomain() {		
			load_plugin_textdomain('gitfeed', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Load needed files for the plugin
		 */
		private function includes() {
			if ( file_exists( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'env.php' ) ) {
				include_once 'env.php';
				add_action('init', gf_set_up_env);
			}
			
			if ( file_exists( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'class-api.php' ) ) {
				include_once 'class-api.php';
			}
		}		
	}
}

// Init the plugin and load the plugin instance for the first time.
add_action( 'plugins_loaded', array( 'Gitfeed', 'get_instance' ) );