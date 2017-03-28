<?php

/*
 * Allows syncing of Divi content and settings between the Source and Target sites
 * @package Sync
 * @author WPSiteSync
 */

class SyncDiviApiRequest extends SyncInput
{
	private $_push_data;

	const ERROR_DIVI_SETTINGS_NOT_FOUND = 900;
	const ERROR_DIVI_ROLES_NOT_FOUND = 901;

	const NOTICE_DIVI = 900;

	/**
	 * Checks the API request if the action is to pull/push the settings
	 *
	 * @param array $args The arguments array sent to SyncApiRequest::api()
	 * @param string $action The API requested
	 * @param array $remote_args Array of arguments sent to SyncRequestApi::api()
	 * @return array The modified $args array, with any additional information added to it
	 */
	public function api_request($args, $action, $remote_args)
	{
SyncDebug::log(__METHOD__ . '() action=' . $action);

// @todo enable licensing
		//&& $license->check_license('sync_divi', WPSiteSync_Divi::PLUGIN_KEY, WPSiteSync_Divi::PLUGIN_NAME)
//		$license = WPSiteSyncContent::get_instance()->get_license();
//		if (!$license)
//			return $args;

		if ('pushdivisettings' === $action) {
SyncDebug::log(__METHOD__ . '() args=' . var_export($args, TRUE));

			$push_data = array();

			$push_data['site_key'] = $args['auth']['site_key'];
			$push_data['pull'] = FALSE;
			$push_data['divi-settings'] = $this->_get_gdivi_settings_data($selected);
SyncDebug::log(__METHOD__ . '() push_data=' . var_export($push_data, TRUE));

			$args['push_data'] = $push_data;
		} else if ('pushdiviroles' === $action) {
SyncDebug::log(__METHOD__ . '() args=' . var_export($args, TRUE));

			$push_data = array();

			//$push_data['site_key'] = $args['auth']['site_key'];
			$push_data['pull'] = FALSE;
			$push_data['divi-roles'] = get_option('et_pb_role_settings');
SyncDebug::log(__METHOD__ . '() push_data=' . var_export($push_data, TRUE));

			$args['push_data'] = $push_data;
		}

		// return the filter value
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
SyncDebug::log(__METHOD__ . "() handling '{$action}' action");

// @todo enable licensing
//		$license = WPSiteSyncContent::get_instance()->get_license();
//		if (!$license)
//			return $return;

		if ('pushdivisettings' === $action) {

			$this->_push_data = $this->post_raw('push_data', array());
SyncDebug::log(__METHOD__ . '() found push_data information: ' . var_export($this->_push_data, TRUE));

			if (empty($this->_push_data['divi-settings'])) {
				$response->error_code(SyncDiviApiRequest::ERROR_DIVI_SETTINGS_NOT_FOUND);
				return TRUE;            // return, signaling that the API request was processed
			}

//			foreach ($this->_push_data['divi-settings'] as $setting) {
//				switch ($setting['option_key']) {
//				case 'genesis-settings':
//					$key = apply_filters('genesis_settings_field', $setting['option_key']);
//					break;
//				case 'genesis-seo-settings':
//					$key = apply_filters('genesis_seo_settings_field', $setting['option_key']);
//					break;
//				default:
//					$key = $setting['option_key'];
//					break;
//				}
//			}

			update_option('et_divi', $this->_push_data['divi-settings']);

			$return = TRUE; // tell the SyncApiController that the request was handled
		} else if ('pushdiviroles' === $action) {

			$this->_push_data = $this->post_raw('push_data', array());
SyncDebug::log(__METHOD__ . '() found push_data information: ' . var_export($this->_push_data, TRUE));

			if (empty($this->_push_data['divi-roles'])) {
				$response->error_code(SyncDiviApiRequest::ERROR_DIVI_ROLES_NOT_FOUND);
				return TRUE;            // return, signaling that the API request was processed
			}

			update_option('et_pb_role_settings', $this->_push_data['divi-roles']);

			$return = TRUE; // tell the SyncApiController that the request was handled
		} else if ('pulldivisettings' === $action) {

			$pull_data = array();
			$pull_data['divi-settings'] = $this->_get_divi_settings_data();

			$response->set('pull_data', $pull_data); // add all the post information to the ApiResponse object
			$response->set('site_key', SyncOptions::get('site_key'));
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response data=' . var_export($response, TRUE));

			$return = TRUE; // tell the SyncApiController that the request was handled
		} else if ('pulldiviroles' === $action) {

			$pull_data = array();
			$pull_data['divi-roles'] = get_option('et_pb_role_settings');

			$response->set('pull_data', $pull_data); // add all the post information to the ApiResponse object
			$response->set('site_key', SyncOptions::get('site_key'));
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response data=' . var_export($response, TRUE));

			$return = TRUE; // tell the SyncApiController that the request was handled
		}

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
SyncDebug::log(__METHOD__ . "('{$action}')");

		if ('pushdivisettings' === $action) {
SyncDebug::log(__METHOD__ . '() response from API request: ' . var_export($response, TRUE));

			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '() decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '() no response->response element');
			}

SyncDebug::log(__METHOD__ . '() api response body=' . var_export($api_response, TRUE));

			if (0 === $response->get_error_code()) {
				$response->success(TRUE);
			}
		} else if ('pushdiviroles' === $action) {
SyncDebug::log(__METHOD__ . '() response from API request: ' . var_export($response, TRUE));

			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '() decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '() no response->response element');
			}

SyncDebug::log(__METHOD__ . '() api response body=' . var_export($api_response, TRUE));

			if (0 === $response->get_error_code()) {
				$response->success(TRUE);
			}
		} else if ('pulldivisettings' === $action) {
SyncDebug::log(__METHOD__ . '() response from API request: ' . var_export($response, TRUE));

			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '() decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '() no response->response element');
			}

SyncDebug::log(__METHOD__ . '() api response body=' . var_export($api_response, TRUE));

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

				$args = array(
					'action' => 'pushdivisettings',
					'parent_action' => 'pulldivisettings',
					'site_key' => $site_key,
					'source' => $target_url,
					'response' => $response,
					'auth' => 0,
				);

SyncDebug::log(__METHOD__ . '() creating controller with: ' . var_export($args, TRUE));
				$this->_push_controller = new SyncApiController($args);
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from controller');
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response=' . var_export($response, TRUE));

				$_POST = $save_post;

				if (0 === $response->get_error_code()) {
					$response->success(TRUE);
				}
			} else {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' no data found in Pull response ' . var_export($api_response, TRUE));
			}
		} else if ('pulldiviroles' === $action) {
SyncDebug::log(__METHOD__ . '() response from API request: ' . var_export($response, TRUE));

			$api_response = NULL;

			if (isset($response->response)) {
SyncDebug::log(__METHOD__ . '() decoding response: ' . var_export($response->response, TRUE));
				$api_response = $response->response;
			} else {
SyncDebug::log(__METHOD__ . '() no response->response element');
			}

SyncDebug::log(__METHOD__ . '() api response body=' . var_export($api_response, TRUE));

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

SyncDebug::log(__METHOD__ . '() creating controller with: ' . var_export($args, TRUE));
				$this->_push_controller = new SyncApiController($args);
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from controller');
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - response=' . var_export($response, TRUE));

				$_POST = $save_post;

				if (0 === $response->get_error_code()) {
					$response->success(TRUE);
				}
			} else {
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' no data found in Pull response ' . var_export($api_response, TRUE));
			}
		}
	}
}

// EOF