<?php
/*
Plugin Name: WPSiteSync for Divi
Plugin URI: http://wpsitesync.com
Description: Extension for WPSiteSync for Content that provides the ability to Sync Divi theme and Divi Builder plugin content and settings within the WordPress admin.
Author: WPSiteSync
Author URI: http://wpsitesync.com
Version: 1.0
Text Domain: wpsitesync-divi

The PHP code portions are distributed under the GPL license. If not otherwise stated, all
images, manuals, cascading stylesheets and included JavaScript are NOT GPL.
*/

if (!class_exists('WPSiteSync_Divi')) {
	/*
	 * @package WPSiteSync_Divi
	 * @author WPSiteSync
	 */

	class WPSiteSync_Divi
	{
		private static $_instance = NULL;
		private $_api_request = NULL;
		private $_api = NULL;

		const PLUGIN_NAME = 'WPSiteSync for Divi';
		const PLUGIN_VERSION = '1.0';
		const PLUGIN_KEY = 'c51144fe92984ecb07d30e447c39c27a';
		// TODO: this needs to be updated to 1.3.3 before releasing
		const REQUIRED_VERSION = '1.3.2';                                    // minimum version of WPSiteSync required for this add-on to initialize

		private function __construct()
		{
			add_action('spectrom_sync_init', array($this, 'init'));
			if (is_admin())
				add_action('wp_loaded', array($this, 'wp_loaded'));
		}

		/**
		 * Retrieve singleton class instance
		 *
		 * @since 1.0.0
		 * @static
		 * @return null|WPSiteSync_Divi
		 */
		public static function get_instance()
		{
			if (NULL === self::$_instance)
				self::$_instance = new self();
			return self::$_instance;
		}

		/**
		 * Callback for Sync initialization action
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function init()
		{
			add_filter('spectrom_sync_active_extensions', array($this, 'filter_active_extensions'), 10, 2);

//			if (!WPSiteSyncContent::get_instance()->get_license()->check_license('sync_divi', self::PLUGIN_KEY, self::PLUGIN_NAME))
//				return;

			// check for minimum WPSiteSync version
			if (is_admin() && version_compare(WPSiteSyncContent::PLUGIN_VERSION, self::REQUIRED_VERSION) < 0 && current_user_can('activate_plugins')) {
				add_action('admin_notices', array($this, 'notice_minimum_version'));
				return;
			}

			if (is_admin() && SyncOptions::is_auth()) {
				$this->load_class('diviadmin');
				SyncDiviAdmin::get_instance();
			}

			add_filter('spectrom_sync_allowed_post_types', array($this, 'allowed_post_types'));
			add_filter('spectrom_sync_tax_list', array($this, 'add_taxonomies'), 10, 1);
			add_filter('spectrom_sync_api_request_action', array($this, 'api_request'), 20, 3); // called by SyncApiRequest
			add_filter('spectrom_sync_api', array($this, 'api_controller_request'), 10, 3); // called by SyncApiController
			add_filter('spectrom_sync_upload_media_allowed_mime_type', array($this, 'filter_allowed_mime_type'), 10, 2);
			add_filter('spectrom_sync_api_push_content', array($this, 'filter_push_content'), 10, 2);
			add_action('spectrom_sync_push_content', array($this, 'handle_push'), 10, 3);
			add_action('spectrom_sync_media_processed', array($this, 'media_processed'), 10, 3);
			add_action('spectrom_sync_api_request_response', array($this, 'api_response'), 10, 3); // called by SyncApiRequest->api()

			add_filter('spectrom_sync_error_code_to_text', array($this, 'filter_error_codes'), 10, 2);
			add_filter('spectrom_sync_notice_code_to_text', array($this, 'filter_notice_codes'), 10, 2);
		}

		/**
		 * Checks the API request if the action is to pull/push the settings
		 *
		 * @param array $args The arguments array sent to SyncApiRequest::api()
		 * @param string $action The API requested
		 * @param array $remote_args Array of arguments sent to SyncApiRequest::api()
		 * @return array The modified $args array, with any additional information added to it
		 */
		public function api_request($args, $action, $remote_args)
		{
			$this->_get_api_request();
			$args = $this->_api_request->api_request($args, $action, $remote_args);

			return $args;
		}

		/**
		 * Handles the requests being processed on the Target from SyncApiController
		 *
		 * @param type $return
		 * @param type $action
		 * @param SyncApiResponse $response
		 * @return bool $response
		 */
		public function api_controller_request($return, $action, SyncApiResponse $response)
		{
			$this->_get_api_request();
			$return = $this->_api_request->api_controller_request($return, $action, $response);

			return $return;
		}

		/**
		 * Handles the request on the Source after API Requests are made and the response is ready to be interpreted
		 *
		 * @param string $action The API name, i.e. 'push' or 'pull'
		 * @param array $remote_args The arguments sent to SyncApiRequest::api()
		 * @param SyncApiResponse $response The response object after the API request has been made
		 */
		public function api_response($action, $remote_args, $response)
		{
			$this->_get_api_request();
			$this->_api_request->api_response($action, $remote_args, $response);
		}

		/**
		 * Callback for filtering the post data before it's sent to the Target. Here we check for additional data needed.
		 * @param array $data The data being Pushed to the Target machine
		 * @param SyncApiRequest $apirequest Instance of the API Request object
		 * @return array The modified data
		 */
		public function filter_push_content($data, $apirequest)
		{
			$this->_get_api_request();
			$data = $this->_api_request->filter_push_content($data, $apirequest);

			return $data;
		}

		/**
		 * Handles the processing of Push requests in response to an API call on the Target
		 * @param int $target_post_id The post ID of the Content on the Target
		 * @param array $post_data The array of post content information sent via the API request
		 * @param SyncApiResponse $response The response object used to reply to the API call
		 */
		public function handle_push($target_post_id, $post_data, SyncApiResponse $response)
		{
			$this->_get_api_request();
			$this->_api_request->handle_push($target_post_id, $post_data, $response);
		}

		/**
		 * Filter the allowed mime type in upload_media
		 *
		 * @since 1.0.0
		 * @param $default
		 * @param $img_type
		 * @return string
		 */
		public function filter_allowed_mime_type($default, $img_type)
		{
			if (in_array($img_type['type'], get_allowed_mime_types())) {
				return TRUE;
			}

			return $default;
		}

		/**
		 * Callback for 'spectrom_sync_media_processed', called from SyncApiController->upload_media()
		 * @param int $target_post_id The Post ID of the Content being pushed
		 * @param int $attach_id The attachment's ID
		 * @param int $media_id The media id
		 */
		public function media_processed($target_post_id, $attach_id, $media_id)
		{
			$this->_get_api_request();
			$this->_api_request->media_processed($target_post_id, $attach_id, $media_id);
		}

		/**
		 * Add Divi related post types to allowed post types
		 *
		 * @since 1.0.0
		 * @param array $post_types Currently allowed post types
		 * @return array The merged post types
		 */
		public function allowed_post_types($post_types)
		{
			$post_types[] = 'project';
			$post_types[] = 'et_pb_layout';
			return $post_types;
		}

		/**
		 * Adds Divi related taxonomies to the list of available taxonomies for Syncing
		 *
		 * @param array $tax Array of taxonomy information to filter
		 * @return array The taxonomy list, with additional taxonomies added to it
		 */
		public function add_taxonomies($tax)
		{
			$divi_taxonomies = array(
				'layout_category' => get_taxonomy('layout_category'),
				'layout_type' => get_taxonomy('layout_type'),
				'scope' => get_taxonomy('scope'),
				'module_width' => get_taxonomy('module_width'),
				'project_category' => get_taxonomy('project_category'),
				'project_tag' => get_taxonomy('project_tag'),
			);
			$tax = array_merge($tax, $divi_taxonomies);
			return $tax;
		}

		/**
		 * Converts numeric error code to message string
		 * @param string $message Error message
		 * @param int $code The error code to convert
		 * @return string Modified message if one of WPSiteSync Divi's error codes
		 */
		public function filter_error_code($message, $code)
		{
			$this->_get_api_request();
			switch ($code) {
			case SyncDiviApiRequest::ERROR_DIVI_SETTINGS_NOT_FOUND:
				$message = __('Divi Settings were not found', 'wpsitesync-divi');
				break;
			case SyncDiviApiRequest::ERROR_DIVI_ROLES_NOT_FOUND:
				$message = __('Divi Roles were not found', 'wpsitesync-divi');
				break;
			}
			return $message;
		}

		/**
		 * Converts numeric error code to message string
		 * @param string $message Error message
		 * @param int $code The error code to convert
		 * @return string Modified message if one of WPSiteSync Divi's error codes
		 */
		public function filter_notice_code($message, $code)
		{
			$this->_get_api_request();
			switch ($code) {
			case SyncDiviApiRequest::NOTICE_DIVI:
				$message = __('', 'wpsitesync-divi');
				break;
			}
			return $message;
		}

		/**
		 * Retrieve a single copy of the SyncApiRequest class
		 * @return SyncApiRequest instance of the class
		 */
		public function get_api()
		{
			if (NULL === $this->_api) {
				$this->_api = new SyncApiRequest();
			}
			return $this->_api;
		}

		/**
		 * Retrieve a single copy of the SyncDiviApiRequest class
		 * @return SyncDiviApiRequest instance of the class
		 */
		private function _get_api_request()
		{
			if (NULL === $this->_api_request) {
				$this->load_class('diviapirequest');
				$this->_api_request = new SyncDiviApiRequest();
			}
			return $this->_api_request;
		}

		/**
		 * Loads a specified class file name and optionally creates an instance of it
		 *
		 * @since 1.0.0
		 * @param $name Name of class to load
		 * @param bool $create TRUE to create an instance of the loaded class
		 * @return bool|object Created instance of $create is TRUE; otherwise FALSE
		 */
		public function load_class($name, $create = FALSE)
		{
			if (file_exists($file = dirname(__FILE__) . '/classes/' . strtolower($name) . '.php')) {
				require_once($file);
				if ($create) {
					$instance = 'Sync' . $name;
					return new $instance();
				}
			}
			return FALSE;
		}

		/**
		 * Return reference to asset, relative to the base plugin's /assets/ directory
		 *
		 * @since 1.0.0
		 * @param string $ref asset name to reference
		 * @static
		 * @return string href to fully qualified location of referenced asset
		 */
		public static function get_asset($ref)
		{
			$ret = plugin_dir_url(__FILE__) . 'assets/' . $ref;
			return $ret;
		}

		/**
		 * Callback for the 'wp_loaded' action. Used to display admin notice if WPSiteSync for Content is not activated
		 */
		public function wp_loaded()
		{
			if (!class_exists('WPSiteSyncContent', FALSE) && current_user_can('activate_plugins')) {
				if (is_admin())
					add_action('admin_notices', array($this, 'notice_requires_wpss'));
				return;
			}
		}

		/**
		 * Display admin notice to install/activate WPSiteSync for Content
		 */
		public function notice_requires_wpss()
		{
			$this->_show_notice(sprintf(__('WPSiteSync for Divi requires the main <em>WPSiteSync for Content</em> plugin to be installed and activated. Please <a href="%1$s">click here</a> to install or <a href="%2$s">click here</a> to activate.', 'wpsitesync-divi'),
				admin_url('plugin-install.php?tab=search&s=wpsitesync'),
				admin_url('plugins.php')), 'notice-warning');
		}

		/**
		 * Display admin notice to upgrade WPSiteSync for Content plugin
		 */
		public function notice_minimum_version()
		{
			$this->_show_notice(sprintf(__('WPSiteSync for Divi requires version %1$s or greater of <em>WPSiteSync for Content</em> to be installed. Please <a href="2%s">click here</a> to update.', 'wpsitesync-divi'),
				self::REQUIRED_VERSION,
				admin_url('plugins.php')), 'notice-warning');
		}

		/**
		 * Helper method to display notices
		 * @param string $msg Message to display within notice
		 * @param string $class The CSS class used on the <div> wrapping the notice
		 * @param boolean $dismissable TRUE if message is to be dismissable; otherwise FALSE.
		 */
		private function _show_notice($msg, $class = 'notice-success', $dismissable = FALSE)
		{
			// TODO: refactor to use Sync Core function
			echo '<div class="notice ', $class, ' ', ($dismissable ? 'is-dismissible' : ''), '">';
			echo '<p>', $msg, '</p>';
			echo '</div>';
		}

		/**
		 * Adds the WPSiteSync for Divi add-on to the list of known WPSiteSync extensions
		 *
		 * @param array $extensions The list of extensions
		 * @param boolean TRUE to force adding the extension; otherwise FALSE
		 * @return array Modified list of extensions
		 */
		public function filter_active_extensions($extensions, $set = FALSE)
		{
			if ($set || WPSiteSyncContent::get_instance()->get_license()->check_license('sync_divi', self::PLUGIN_KEY, self::PLUGIN_NAME))
				$extensions['sync_divi'] = array(
					'name' => self::PLUGIN_NAME,
					'version' => self::PLUGIN_VERSION,
					'file' => __FILE__,
				);
			return $extensions;
		}
	}
}

// Initialize the extension
WPSiteSync_Divi::get_instance();

// EOF
