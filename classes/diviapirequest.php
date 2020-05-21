<?php

/*
 * Allows syncing of Divi content and settings between the Source and Target sites
 * 
 * The following represents the data structure used in Sync operations:
 *  $_push_data['divi-version']				Theme / Plugin version, via get_option('et_core_version')
 *  $_push_data['divi-settings']			Theme settings, via get_option('et_divi')
 *  $_push_data['divi-builder-settings']	Plugin settings, via get_option('et_divi_builder_plugin')
 *  $_push_data['divi-roles']				Role configurations, via get_option('et_pb_role_settings')
 * @package Sync
 * @author WPSiteSync
 */

/**
settings:
  et_core_version                         | 3.0.92
* et_divi
* et_divi_builder_plugin                  | a:5:{s:39:"static_cs
  et_pb_builder_options                   | a:10:{i:0;b:0;s:35:"
  et_pb_role_settings                     | a:4:{s:13:"administr
 */

/**
 * 
 */
class SyncDiviApiRequest extends SyncInput
{
	const ERROR_DIVI_SETTINGS_NOT_FOUND = 900;
	const ERROR_DIVI_ROLES_NOT_FOUND = 901;
	const ERROR_DIVI_VERSION_MISMATCH = 902;

	const NOTICE_NO_SETTINGS = 900;

	private $_api = NULL;					// SyncApiRequest instance used for API calls
	private $_push_data = array();			// data being constructed for push operations
	private $_tax_data = array();			// taxonomy data constructed for push operations
	private $_post_data;					// API data used in handle_push() / 'spectrom_sync_push_content' action

	private $_source = NULL;				// source domain for array walk updates
	private $_dest = NULL;					// destination domain for array walk updates

	/**
	 * Checks the API request if the action is to pull/push the settings
	 * @param array $args The arguments array sent to SyncApiRequest::api()
	 * @param string $action The API requested
	 * @param array $remote_args Array of arguments sent to SyncRequestApi::api()
	 * @return array The modified $args array, with any additional information added to it
	 */
	public function api_request($args, $action, $remote_args)
	{
SyncDebug::log(__METHOD__ . '() action=' . $action);

		// TODO: enable licensing before shipping
		if (!WPSiteSyncContent::get_instance()->get_license()->check_license('sync_divi', WPSiteSync_Divi::PLUGIN_KEY, WPSiteSync_Divi::PLUGIN_NAME)) {
SyncDebug::log(__METHOD__.'() no license');
###			return $args;
		}

###$push_data = array();
###$tax_data = array();
###$api = NULL;
		$this->_push_data = array();
		$this->_tax_data = array();
		$this->_api = NULL;

		switch ($action) {
		case 'pushdivisettings':
SyncDebug::log(__METHOD__ . '():'. __LINE__ . ' args=' . var_export($args, TRUE));
			$this->_push_data['pull'] = FALSE;
			// TODO: why is this being duplicated?
			$this->_push_data['site-key'] = $args['auth']['site_key'];

			$this->_push_data['source_domain'] = site_url();
			$this->_push_data['divi-version'] = $this->_get_divi_version();
			$this->_push_data['divi-settings'] = get_option('et_divi');
			$this->_push_data['divi-builder-settings'] = get_option('et_divi_builder_plugin');

			// if no settings found, return error
			if (FALSE === $this->_push_data['divi-settings'] && FALSE === $this->_push_data['divi-builder-settings']) {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' no settings found, return with error');
				return new WP_Error(self::ERROR_DIVI_SETTINGS_NOT_FOUND);
			}

			$this->_api = WPSiteSync_Divi::get_instance()->get_api();
			$this->_api->set_source_domain(site_url());		// set source domain; used in domain transpositions

			// call helper methods to build up the _push_data[] array for the API call
//			$this->_get_taxonomies($push_data, $tax_data);			###
			$this->_get_taxonomies();
//			$this->_get_media($push_data, $api);					###
			$this->_get_media();

			$args['push_data'] = $this->_push_data;
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' push_data=' . var_export($args['push_data'], TRUE));
			break;

		case 'pushdiviroles':
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' args=' . var_export($args, TRUE));
			$this->_push_data['pull'] = FALSE;

			$this->_push_data['divi-version'] = $this->_get_divi_version();
			$this->_push_data['divi-roles'] = get_option('et_pb_role_settings');

			// if no settings found, return error
			if (FALSE === $this->_push_data['divi-roles']) {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' no roles found, return with error');
				return new WP_Error(self::ERROR_DIVI_ROLES_NOT_FOUND);
			}

			$args['push_data'] = $this->_push_data;
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' push_data=' . var_export($args['push_data'], TRUE));
			break;
		}

		// return the filter value
		return $args;
	}

	/**
	 * Callback for 'spectrom_sync_api' action. Called from SyncApiController->__construct().
	 * Handles the Push requests being processed on the Target from SyncApiController
	 * @param boolean $return TRUE when API is handled; otherwise FALSE
	 * @param string $action Action name, like 'pushdivisettings' or 'pulldivirole'
	 * @param SyncApiResponse $response The response object for API calls
	 * @return bool $response Return TRUE to indicate that the API call was handled; otherwise FALSE.
	 */
	public function api_controller_request($return, $action, SyncApiResponse $response)
	{
SyncDebug::log(__METHOD__ . "() handling '{$action}' action");

		// TODO: enable licensing before shipping
		if (!WPSiteSyncContent::get_instance()->get_license()->check_license('sync_divi', WPSiteSync_Divi::PLUGIN_KEY, WPSiteSync_Divi::PLUGIN_NAME)) {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' no license');
###			return $return;
		}

		switch ($action) {
		case 'pushdivisettings':
			$this->_push_data = $this->post_raw('push_data', array());
SyncDebug::log(__METHOD__ . '() found push_data information: ' . var_export($this->_push_data, TRUE));

			// check version numbers
			$this->_check_strict_mode($response);

			// set source domain- needed for handling media operations
			WPSiteSync_Divi::get_instance()->get_api()->set_source_domain($this->_push_data['source_domain']);
SyncDebug::log(__METHOD__ . '() source domain: ' . var_export($this->_push_data['source_domain'], TRUE));

			if (empty($this->_push_data['divi-settings']) && empty($this->_push_data['divi-builder-settings'])) {
				$response->error_code(SyncDiviApiRequest::ERROR_DIVI_SETTINGS_NOT_FOUND);
				return TRUE;			// return, signaling that the API request was processed
			}

			$this->_source = SyncApiController::get_instance()->source;
			$this->_dest = site_url();

			// check for Divi Theme settings
			if (isset($this->_push_data['divi-settings']) && 0 !== count($this->_push_data['divi-settings'])) {
				$set = $this->_process_settings($this->_push_data['divi-settings']);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' saving Divi settings: ' . var_export($set, TRUE));
				if (!empty($set))
					update_option('et_divi', $set);

#				foreach ($this->_push_data['divi-settings'] as $setting_key => $settings) {
#					if (empty($settings))
#						continue;
#
#					switch ($setting_key) {
#					case 'divi_menupages':
#						// look up the Target page IDs using SyncModel->get_sync_data()
#						$model = new SyncModel();
#						foreach ($settings as $key => $page_id) {
#SyncDebug::log(__METHOD__ . '() found menu page: ' . var_export($page_id, TRUE));
#							$sync_data = $model->get_sync_data($page_id, $this->_push_data['site-key']);
#SyncDebug::log(__METHOD__ . '() found sync data: ' . var_export($sync_data, TRUE));
#							if (NULL === $sync_data) {
#								// unset array key
#								unset($settings[$key]);
#							} else {
#								// replace id with new id
#SyncDebug::log(__METHOD__ . '() replace with: ' . var_export($sync_data->target_content_id, TRUE));
#								$settings[$key] = $sync_data->target_content_id;
#							}
#						}
#						$this->_push_data['divi-settings']['divi_menupages'] = $settings;
#						break;
#
#					case 'divi_menucats':
#						// for each category ID, get the new category ID and replace it.
#						foreach ($settings as $key => $cat_id) {
#SyncDebug::log(__METHOD__ . '() found menu category: ' . var_export($cat_id, TRUE));
#							foreach ($this->_push_data['divi-categories']['hierarchical'] as $index => $cat) {
#								if ($cat_id === $cat['term_id']) {
#									$name = $this->_push_data['divi-categories']['hierarchical'][$index]['name'];
#SyncDebug::log(__METHOD__ . '() term name: ' . var_export($name, TRUE));
#									$term_index = $index;
#								}
#							}
#							$term = get_term_by('name', $name, 'category');
#
#							// add new term if it doesn't exist
#							if (FALSE === $term) {
#								$term_id = SyncApiController::get_instance()->process_hierarchical_term($this->_push_data['divi-categories']['hierarchical'][$term_index], $this->_push_data['divi-categories']['hierarchical']);
#							} else {
#								$term_id = $term->term_id;
#							}
#
#							if (0 !== $term_id) {
#SyncDebug::log(__METHOD__ . '() new term id: ' . var_export($term_id, TRUE));
#								$settings[$key] = $term_id;
#							} else {
#								unset($settings[$key]);
#							}
#						}
#						$this->_push_data['divi-settings']['divi_menucats'] = $settings;
#						break;
#					}
#				}
#
#				$this->_push_data['divi-settings'] = str_replace(
#					SyncApiController::get_instance()->source,
#					site_url(),
#					$this->_push_data['divi-settings']);
#
#SyncDebug::log(__METHOD__.'():' . __LINE__ . ' saving Divi settings: ' . var_export($this->_push_data['divi-settings'], TRUE));
#				if (FALSE !== $this->_push_data['divi-settings'])
#					update_option('et_divi', $this->_push_data['divi-settings']);
			}

			// check for Divi Plugin settings
			if (isset($this->_push_data['divi-builder-settings']) && 0 !== count($this->_push_data['divi-builder-settings'])) {
				$set = $this->_process_settings($this->_push_data['divi-builder-settings']);
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' saving Divi Plugin settings: ' . var_export($set, TRUE));
				if (!empty($set))
					update_option('et_divi_builder_plugin', $set);
			}

			$return = TRUE; // tell the SyncApiController that the request was handled
			break;

		case 'pushdiviroles':
			$this->_push_data = $this->post_raw('push_data', array());
SyncDebug::log(__METHOD__ . '() found push_data information: ' . var_export($this->_push_data, TRUE));

			// check version numbers
			$this->_check_strict_mode($response);

			if (empty($this->_push_data['divi-roles']) && FALSE !== $this->_push_data['divi-roles']) {
				$response->error_code(SyncDiviApiRequest::ERROR_DIVI_ROLES_NOT_FOUND);
				return TRUE;			// return, signaling that the API request was processed
			}

SyncDebug::log(__METHOD__.'():' . __LINE__ . ' saving Divi roles: ' . var_export($this->_push_data['divi-roles'], TRUE));
			update_option('et_pb_role_settings', $this->_push_data['divi-roles']);

			$return = TRUE; // tell the SyncApiController that the request was handled
			break;

		case 'pulldivisettings':
###$pull_data = array();
###$tax_data = array();
			$this->_push_data = array();
			$this->_tax_data = array();
			$this->_push_data['divi-version'] = $this->_get_divi_version();
			$this->_push_data['divi-settings'] = get_option('et_divi');
			$this->_push_data['divi-builder-settings'] = get_option('et_divi_builder_plugin');
			
			// check if empty and show notice
			if (FALSE === $this->_push_data['divi-settings'] && FALSE === $this->_push_data['divi-builder-settings'])
				$response->notice_code(self::NOTICE_NO_SETTINGS);

			$api = WPSiteSync_Divi::get_instance()->get_api();
			$api->set_source_domain(site_url());

###			$pull_data = $this->_get_taxonomies($pull_data, $tax_data);
			$this->_get_taxonomies();
###			$this->_get_media($pull_data, $api);
			$this->_get_media();

			$response->set('pull_data', $this->_push_data); // add all the post information to the ApiResponse object
			$response->set('site_key', SyncOptions::get('site_key'));
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response data=' . var_export($response, TRUE));

			$return = TRUE; // tell the SyncApiController that the request was handled
			break;

		case 'pulldiviroles':
###$pull_data = array();
			$this->_push_data = array();
			$this->_push_data['divi-version'] = $this->_get_divi_version();
			$this->_push_data['divi-roles'] = get_option('et_pb_role_settings');
			// TODO: check for empty settings and return error

			$response->set('pull_data', $this->_push_data); // add all the post information to the ApiResponse object
			$response->set('site_key', SyncOptions::get('site_key'));
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response data=' . var_export($response, TRUE));

			$return = TRUE; // tell the SyncApiController that the request was handled
			break;
		}

		return $return;
	}

	private function _process_settings($settings)
	{
		foreach ($settings as $setting_key => &$set) {
			if (empty($set))
				continue;

			switch ($setting_key) {
			case 'divi_menupages':
				// look up the Target page IDs using SyncModel->get_sync_data()
				$model = new SyncModel();
				foreach ($set as $key => $page_id) {
SyncDebug::log(__METHOD__ . '() found menu page: ' . var_export($page_id, TRUE));
					$sync_data = $model->get_sync_data($page_id, $this->_push_data['site-key']);
SyncDebug::log(__METHOD__ . '() found sync data: ' . var_export($sync_data, TRUE));
					if (NULL === $sync_data) {
						// unset array key
						unset($set[$key]);
					} else {
						// replace id with new id
SyncDebug::log(__METHOD__ . '() replace with: ' . var_export($sync_data->target_content_id, TRUE));
						$set[$key] = $sync_data->target_content_id;
					}
				}
				break;

			case 'divi_menucats':
				// for each category ID, get the new category ID and replace it.
				foreach ($set as $key => $cat_id) {
SyncDebug::log(__METHOD__ . '() found menu category: ' . var_export($cat_id, TRUE));
					foreach ($this->_push_data['divi-categories']['hierarchical'] as $index => $cat) {
						if ($cat_id === $cat['term_id']) {
							$name = $this->_push_data['divi-categories']['hierarchical'][$index]['name'];
SyncDebug::log(__METHOD__ . '() term name: ' . var_export($name, TRUE));
							$term_index = $index;
						}
					}
					$term = get_term_by('name', $name, 'category');

					// add new term if it doesn't exist
					if (FALSE === $term) {
						$term_id = SyncApiController::get_instance()->process_hierarchical_term($this->_push_data['divi-categories']['hierarchical'][$term_index], $this->_push_data['divi-categories']['hierarchical']);
					} else {
						$term_id = $term->term_id;
					}

					if (0 !== $term_id) {
SyncDebug::log(__METHOD__ . '() new term id: ' . var_export($term_id, TRUE));
						$set[$key] = $term_id;
					} else {
						unset($set[$key]);
					}
				}
				break;
			}
		}

		array_walk_recursive($settings, array($this, 'process_array_elem'));
#		$this->_push_data['divi-settings'] = str_replace(
#			SyncApiController::get_instance()->source,
#			site_url(),
#			$this->_push_data['divi-settings']);

		return $settings;
	}
	public function process_array_elem(&$elem)
	{
		if (is_string($elem))
			$elem = str_replace($this->_source, $this->_dest, $elem);
	}

	/**
	 * Handles the request on the Source after API Requests are made and the response is ready to be interpreted
	 * @param string $action The API name, i.e. 'push' or 'pull'
	 * @param array $remote_args The arguments sent to SyncApiRequest::api()
	 * @param SyncApiResponse $response The response object after the API request has been made
	 */
	public function api_response($action, $remote_args, $response)
	{
SyncDebug::log(__METHOD__ . "('{$action}')");

		switch ($action) {
		case 'pushdivisettings':
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' response from API request: ' . var_export($response, TRUE));
			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' no response->response element');
			}

SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' api response body=' . var_export($api_response, TRUE));

			// TODO: not needed unless error_code() is called
			if (0 === $response->get_error_code()) {
				$response->success(TRUE);
			}
			break;

		case 'pushdiviroles':
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' response from API request: ' . var_export($response, TRUE));

			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' no response->response element');
			}

SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' api response body=' . var_export($api_response, TRUE));

			// TODO: not needed unless error_code() is called
			if (0 === $response->get_error_code()) {
				$response->success(TRUE);
			}
			break;

		case 'pulldivisettings':
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' response from API request: ' . var_export($response, TRUE));

			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' no response->response element');
			}

SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' api response body=' . var_export($api_response, TRUE));

			if (NULL !== $api_response && isset($api_response->data->pull_data)) {
				$save_post = $_POST;

				// convert the pull data into an array
				$pull_data = json_decode(json_encode($api_response->data->pull_data), TRUE); // $response->response->data->pull_data;
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - pull data=' . var_export($pull_data, TRUE));
				$site_key = $api_response->data->site_key; // $pull_data->site_key;
				$target_url = SyncOptions::get('target');
				$pull_data['site_key'] = $site_key;
				$pull_data['pull'] = TRUE;

				$_POST['push_data'] = $pull_data;
				$_POST['action'] = 'pushdivisettings';
				$_POST['pull_media'] = $pull_data['pull_media'];

				$args = array(
					'action' => 'pushdivisettings',
					'parent_action' => 'pulldivisettings',
					'site_key' => $site_key,
					'source' => $target_url,
					'response' => $response,
					'auth' => 0,
				);

SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' creating controller with: ' . var_export($args, TRUE));
				$this->_push_controller = SyncApiController::get_instance($args);
				$this->_push_controller->dispatch();
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from controller');
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response=' . var_export($response, TRUE));

//				if (isset($_POST['pull_media'])) {
//SyncDebug::log(__METHOD__ . '() - found ' . count($_POST['pull_media']) . ' media items');
//					$this->_handle_media(intval($_POST['post_id']), $_POST['pull_media'], $response);
//				}

				$_POST = $save_post;

				// TODO: not needed unless error_code() is called
				if (0 === $response->get_error_code()) {
					$response->success(TRUE);
				}
			} else {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' no data found in Pull response ' . var_export($api_response, TRUE));
			}
			break;

		case 'pulldiviroles':
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' response from API request: ' . var_export($response, TRUE));
			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' no response->response element');
			}

SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' api response body=' . var_export($api_response, TRUE));

			if (NULL !== $api_response && isset($api_response->data->pull_data)) {
				$save_post = $_POST;

				// convert the pull data into an array
				$pull_data = json_decode(json_encode($api_response->data->pull_data), TRUE); // $response->response->data->pull_data;
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - pull data=' . var_export($pull_data, TRUE));
				$site_key = $api_response->data->site_key; // $pull_data->site_key;
				$target_url = SyncOptions::get('target');
				$pull_data['site_key'] = $site_key;
				$pull_data['pull'] = TRUE;

				$_POST['push_data'] = $pull_data;
				$_POST['action'] = 'pushdiviroles';

				$args = array(
					'action' => 'pushdiviroles',
					'parent_action' => 'pulldiviroles',
					'site_key' => $site_key,
					'source' => $target_url,
					'response' => $response,
					'auth' => 0,
				);

SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' creating controller with: ' . var_export($args, TRUE));
				$this->_push_controller = SyncApiController::get_instance($args);
				$this->_push_controller->dispatch();
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from controller');
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response=' . var_export($response, TRUE));

				$_POST = $save_post;

				if (0 === $response->get_error_code()) {
					$response->success(TRUE);
				}
			} else {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' no data found in Pull response ' . var_export($api_response, TRUE));
			}
			break;
		}
	}

	/**
	 * Callback for 'spectrom_sync_media_processed', called from SyncApiController->upload_media()
	 * @param int $target_post_id The Post ID of the Content being pushed
	 * @param int $attach_id The attachment's ID
	 * @param int $media_id The media id
	 */
	public function media_processed($target_post_id, $attach_id, $media_id)
	{
SyncDebug::log(__METHOD__ . "({$target_post_id}, {$attach_id}, {$media_id}):" . __LINE__ . ' post= ' . var_export($_POST, TRUE));

		// if Divi gallery, update the id
		$gallery = $this->post_int('divi_gallery', 0);

		if (1 === $gallery) {
SyncDebug::log(__METHOD__ . ' processing gallery');
			$old_attach_id = $this->post_int('attach_id', 0);
			$target_post = get_post($target_post_id);
			if (NULL !== $target_post) {
				$content = $target_post->post_content;
				$content = preg_replace("/(gallery_ids=.*){$old_attach_id}(.*\")/", "\${1}{$media_id}\${2}", $content);
				wp_update_post(array('post_id' => $target_post_id, 'post_content' => $content));
			} else {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' could not find target post ' . $target_post_id);
				$resp = SyncApiController::get_instance()->get_api_response();
				$resp->error_code(SyncApiRequest::ERROR_POST_NOT_FOUND, $target_post_id);
			}
			return;
		}
	}

	/**
	 * Callback used to add additional fields to the data being sent with an image upload
	 * @param array $fields An array of data fields being sent with the image in an 'upload_media' API call
	 * @return array The modified media data
	 */
	public function filter_upload_media_fields_gallery($fields)
	{
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' media fields= ' . var_export($fields, TRUE));
		$fields['divi_gallery'] = 1;
		return $fields;
	}

	/**
	 * Callback for 'spectrom_sync_api_push_content'. Handles processing of Divi data for Push operations
	 * @param array $data The data being Pushed to the Target machine
	 * @param SyncApiRequest $apirequest Instance of the API Request object
	 * @return array The modified data
	 */
	public function filter_push_content($data, SyncApiRequest $apirequest)
	{
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' processing');

		$apirequest->set_source_domain(site_url());
		$push_tax_data = array();

		$post_id = 0;
		if (isset($data['post_id']))							// present on Push operations
			$post_id = abs($data['post_id']);
		else if (isset($data['post_data']['ID']))				// present on Pull operations
			$post_id = abs($data['post_data']['ID']);
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' post id=' . $post_id);

		$content = $data['post_data']['post_content'];

		// add all taxonomies to push data
		if (preg_match_all('/include_categories="(?P<ids>\w+[^"]*)"/i', $content, $matches)) {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' found include_categories match: ' . var_export($matches, TRUE));
			$ids = array();
			foreach ($matches['ids'] as $match) {
				$ids = array_unique(array_merge($ids, array_map('abs', explode(',', str_replace(' ', '', $match)))));
			}

SyncDebug::log(__METHOD__.'():' . __LINE__ . ' found ' . count($ids) . ' items in categories: ' . implode(',', $ids));
			foreach ($ids as $category) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' including cat=' . var_export($category, TRUE));
				$term_data = get_term($category);

				$tax_name = $term_data->taxonomy;
				$push_tax_data['hierarchical'][] = $term_data;
				$parent = abs($term_data->parent);
				while (0 !== $parent) {
					$term = get_term_by('id', $parent, $tax_name, OBJECT);
					$push_tax_data['lineage'][$tax_name][] = $term;
					$parent = $term->parent;
				}
			}
		}

		// add taxonomy data to the collection of Push data
		if (!empty($push_tax_data))
			$data['post_data']['divi-categories'] = $push_tax_data;


		// add gallery media to queue
		if (preg_match_all('/gallery_ids="(?P<ids>\w+[^"]*)"/i', $content, $matches)) {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' found gallery_ids match: ' . var_export($matches, TRUE));
			$ids = array();
			foreach ($matches['ids'] as $match) {
				$ids = array_unique(array_merge($ids, array_map('abs', explode(',', str_replace(' ', '', $match)))));
			}

SyncDebug::log(__METHOD__.'():' . __LINE__ . ' found ' . count($ids) . ' items in gallery: ' . implode(',', $ids));
			foreach ($ids as $id) {
SyncDebug::log(__METHOD__ . '() calling send_media for=' . var_export($id, TRUE));
				$url = wp_get_attachment_image_url($id, 'full');
				add_filter('spectrom_sync_upload_media_fields', array($this, 'filter_upload_media_fields_gallery'), 10, 1);
				$apirequest->send_media($url, $post_id, 0, $id);
				remove_filter('spectrom_sync_upload_media_fields', array($this, 'filter_upload_media_fields_gallery'));
			}
		}

		// add media to queue
		// TODO: is this handling the unlimited # with suffixes? If so, remove the @todo note
		// @todo 'bg_img_1' - can have unlimited # with suffix # increasing
		if (preg_match_all('/(src|mp4|webm|audio|image_url|image|url)="(?P<src>\w+[^"]*)"/i', $content, $matches)) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' matches=' . var_export($matches, TRUE));
			foreach (array_unique($matches['src']) as $src) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' src=' . var_export($src, TRUE));
				if (!empty($src)) {
					if (parse_url($src, PHP_URL_HOST) === parse_url(site_url(), PHP_URL_HOST)) {
SyncDebug::log(__METHOD__ . '() calling send_media for=' . var_export($src, TRUE));
						$image_id = attachment_url_to_postid($src);
						$apirequest->send_media($src, $post_id, 0, $image_id);
					}
				}
			}
		}

SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' filtered push data=' . var_export($data, TRUE));

		return $data;
	}

	/**
	 * Called via the 'spectrom_sync_push_content' action. Handles fix up of data on the Target after SyncApiController->push() has finished processing Content.
	 * @param int $target_post_id The post ID being created/updated via API call
	 * @param array $post_data Post data sent via API call
	 * @param SyncApiResponse $response Response instance
	 */
	public function handle_push($target_post_id, $post_data, $response)
	{
SyncDebug::log(__METHOD__ . "({$target_post_id})");

		$target_post = get_post($target_post_id);
		if (NULL !== $target_post) {
			$content = $target_post->post_content;
			$this->_post_data = $post_data;

			// replace taxonomy ids
			$content = preg_replace_callback('/include_categories="(?P<ids>\w+[^"]*)"/i', array($this, 'process_taxonomies_callback'), $content);
			if (NULL !== $content) {
				wp_update_post(array('post_id' => $target_post_id, 'post_content' => $content));
			}
		} else {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' could not find target post ' . $target_post_id);
			$resp = SyncApiController::get_instance()->get_api_response();
			$resp->error_code(SyncApiRequest::ERROR_POST_NOT_FOUND, $target_post_id);
		}
	}

	/**
	 * Process Taxonomies. Callback for the preg_replace_callback() call
	 * @param array $match The array of matches found via the regular expression
	 * @return int
	 */
	public function process_taxonomies_callback($matches)
	{
		$ids = explode(',', $matches['ids']);

		foreach ($ids as $key => $cat_id) {
SyncDebug::log(__METHOD__ . '() found category: ' . var_export($cat_id, TRUE));
			foreach ($this->_post_data['divi-categories']['hierarchical'] as $index => $cat) {
				if ($cat_id === $cat['term_id']) {
					$name = $this->_post_data['divi-categories']['hierarchical'][$index]['name'];
SyncDebug::log(__METHOD__ . '() term name: ' . var_export($name, TRUE));
					$term_index = $index;
				}
			}
			$term = get_term_by('name', $name, 'category');

			// add new term if it doesn't exist
			if (FALSE === $term) {
				$term_id = SyncApiController::get_instance()->process_hierarchical_term($this->_post_data['divi-categories']['hierarchical'][$term_index], $this->_post_data['divi-categories']['hierarchical']);
			} else {
				$term_id = abs($term->term_id);
			}

			if (0 !== $term_id) {
SyncDebug::log(__METHOD__ . '() new term id: ' . var_export($term_id, TRUE));
				$ids[$key] = $term_id;
			} else {
				unset($ids[$key]);
			}

		}

		$ids = implode(',', $ids);

		return str_replace($matches['ids'], $ids, $matches[0]);
	}

	/**
	 * Get Taxonomy information that is referenced via the ['divi_menucats'] data in the Divi settings
	 */
	private function _get_taxonomies()
	{
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' divi-settings=' . var_export($this->_push_data['divi-settings'], TRUE));
		if (array_key_exists('divi_menucats', $this->_push_data['divi-settings'])) {
			$tax_data = array();
			$categories = NULL;
			if (isset($this->_push_data['divi-settings']['divi_menucats']))
				$categories = $this->_push_data['divi-settings']['divi_menucats'];
			if (isset($this->_push_data['divi-plugin-setting']['divi_menucats']))
				$categories = $this->_push_data['divi-plugin-setting']['divi_menucats'];

SyncDebug::log(__METHOD__.'():' . __LINE__ . ' categories: ' . var_export($categories, TRUE));
			if (NULL !== $categories) {
				foreach ($categories as $category) {
					$term_data = get_term($category, 'category');
					if (NULL !== $term_data && !is_error($term_data)) {
						$tax_name = $term_data->taxonomy;
						$tax_data['hierarchical'][] = $term_data;
						$parent = abs($term_data->parent);
						while (0 !== $parent) {
							$term = get_term_by('id', $parent, $tax_name, OBJECT);
							$tax_data['lineage'][$tax_name][] = $term;
							$parent = abs($term->parent);
						}
					}
				}
				if (!empty($tax_data))
					$this->_push_data['divi-categories'] = $tax_data;
			}
		}
	}
/*	private function _get_taxonomies($push_data, $tax_data)
	{
		if (array_key_exists('divi_menucats', $push_data['divi-settings'])) {
			$categories = $push_data['divi-settings']['divi_menucats'];
			foreach ($categories as $category) {
				$term_data = get_term($category, 'category');
				$tax_name = $term_data->taxonomy;
				$tax_data['hierarchical'][] = $term_data;
				$parent = $term_data->parent;
				while (0 !== $parent) {
					$term = get_term_by('id', $parent, $tax_name, OBJECT);
					$tax_data['lineage'][$tax_name][] = $term;
					$parent = $term->parent;
				}
			}
			$push_data['divi-categories'] = $tax_data;
		}
		return $push_data;
	} */

	/**
	 * Collects any references to media items in the data and uses the SyncApiRequest->send_media() method to add them to the API call.
	 */
	private function _get_media(/*$push_data, $api*/)
	{
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' found push data=' . var_export($this->_push_data, TRUE));
		if (array_key_exists('divi_468_image', $this->_push_data['divi-settings'])) {
			$image = $this->_push_data['divi-settings']['divi_468_image'];
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' found image=' . var_export($image, TRUE));

			if (!empty($image)) {
				if (parse_url($image, PHP_URL_HOST) === parse_url(site_url(), PHP_URL_HOST)) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' calling send_media for=' . var_export($image, TRUE));
					$image_id = attachment_url_to_postid($image);
					$this->_api->send_media($image, 0, 0, $image_id);
				}
			}
		}

		if (array_key_exists('divi_favicon', $this->_push_data['divi-settings'])) {
			$image = $this->_push_data['divi-settings']['divi_favicon'];
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' found image=' . var_export($image, TRUE));

			if (!empty($image)) {
				if (parse_url($image, PHP_URL_HOST) === parse_url(site_url(), PHP_URL_HOST)) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' calling send_media for=' . var_export($image, TRUE));
					$image_id = attachment_url_to_postid($image);
					$this->_api->send_media($image, 0, 0, $image_id);
				}
			}
		}

		if (array_key_exists('divi_logo', $this->_push_data['divi-settings'])) {
			$image = $this->_push_data['divi-settings']['divi_logo'];
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' found image=' . var_export($image, TRUE));

			if (!empty($image)) {
				if (parse_url($image, PHP_URL_HOST) === parse_url(site_url(), PHP_URL_HOST)) {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' calling send_media for=' . var_export($image, TRUE));
					$image_id = attachment_url_to_postid($image);
					$this->_api->send_media($image, 0, 0, $image_id);
				}
			}
		}
	}
/*	private function _get_media($push_data, $api)
	{
SyncDebug::log(__METHOD__ . '() found push data=' . var_export($push_data, TRUE));
		if (array_key_exists('divi_468_image', $push_data['divi-settings'])) {
			$image = $push_data['divi-settings']['divi_468_image'];
SyncDebug::log(__METHOD__ . '() found image=' . var_export($image, TRUE));

			if (!empty($image)) {
				if (parse_url($image, PHP_URL_HOST) === parse_url(site_url(), PHP_URL_HOST)) {
SyncDebug::log(__METHOD__ . '() calling send_media for=' . var_export($image, TRUE));
					$image_id = attachment_url_to_postid($image);
					$api->send_media($image, 0, 0, $image_id);
				}
			}
		}

		if (array_key_exists('divi_favicon', $push_data['divi-settings'])) {
			$image = $push_data['divi-settings']['divi_favicon'];
SyncDebug::log(__METHOD__ . '() found image=' . var_export($image, TRUE));

			if (!empty($image)) {
				if (parse_url($image, PHP_URL_HOST) === parse_url(site_url(), PHP_URL_HOST)) {
SyncDebug::log(__METHOD__ . '() calling send_media for=' . var_export($image, TRUE));
					$image_id = attachment_url_to_postid($image);
					$api->send_media($image, 0, 0, $image_id);
				}
			}
		}

		if (array_key_exists('divi_logo', $push_data['divi-settings'])) {
			$image = $push_data['divi-settings']['divi_logo'];
SyncDebug::log(__METHOD__ . '() found image=' . var_export($image, TRUE));

			if (!empty($image)) {
				if (parse_url($image, PHP_URL_HOST) === parse_url(site_url(), PHP_URL_HOST)) {
SyncDebug::log(__METHOD__ . '() calling send_media for=' . var_export($image, TRUE));
					$image_id = attachment_url_to_postid($image);
					$api->send_media($image, 0, 0, $image_id);
				}
			}
		}
	} */

	/**
	 * Returns the version of the Divi Theme/ Plugin installed
	 * @return string a dotted decimal string representing the version number
	 */
	private function _get_divi_version()
	{
		$vers = get_option('et_core_version');
		return $vers;
	}

	/**
	 * Checks if Strict Mode is activated and if Divi versions match between Source and Target.
	 * Uses the SyncApiResponse parameter to return an error code if versions do not match; otherwise returns
	 * @param SyncApiResponse $response The response object used for the current API call.
	 */
	private function _check_strict_mode($response)
	{
		// check version numbers
		if (1 === SyncOptions::get('strict', 0)) {
			// we're in strict mode - make sure the versions match
			if ($this->_get_divi_version() !== $this->_push_data['divi-version']) {
				$response->error_code(self::ERROR_DIVI_VERSION_MISMATCH);
				$response->send();
			}
		}
	}
}

// EOF
