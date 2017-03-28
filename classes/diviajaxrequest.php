<?php

/*
 * Allows management of Divi Content and Settings between the Source and Target sites
 * @package Sync
 * @author WPSiteSync
 */

class SyncDiviAjaxRequest extends SyncInput
{
	private static $_instance = NULL;

	/**
	 * Retrieve singleton class instance
	 *
	 * @since 1.0.0
	 * @static
	 * @return null|SyncDiviAdmin instance reference to plugin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Push Divi Settings ajax request
	 *
	 * @since 1.0.0
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return void
	 */
	public function push_divi_settings($resp)
	{
		$api = new SyncApiRequest();
		$api_response = $api->api('pushdivisettings');

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		if (0 === $api_response->get_error_code()) {
SyncDebug::log(' - no error, setting success');
			$resp->success(TRUE);
		} else {
			$resp->success(FALSE);
SyncDebug::log(' - error code: ' . $api_response->get_error_code());
		}

		return TRUE; // return, signaling that we've handled the request
	}

	/**
	 * Push Divi Role Editor ajax request
	 *
	 * @since 1.0.0
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return void
	 */
	public function push_divi_roles($resp)
	{
		$api = new SyncApiRequest();
		$api_response = $api->api('pushdiviroles');

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		if (0 === $api_response->get_error_code()) {
SyncDebug::log(' - no error, setting success');
			$resp->success(TRUE);
		} else {
			$resp->success(FALSE);
SyncDebug::log(' - error code: ' . $api_response->get_error_code());
		}

		return TRUE; // return, signaling that we've handled the request
	}

	/**
	 * Pull Divi Settings ajax request
	 *
	 * @since 1.0.0
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return void
	 */
	public function pull_divi_settings($resp)
	{
		$api = new SyncApiRequest();
		$api_response = $api->api('pulldivisettings');

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		if (0 === $api_response->get_error_code()) {
SyncDebug::log(' - no error, setting success');
			$resp->success(TRUE);
		} else {
			$resp->success(FALSE);
SyncDebug::log(' - error code: ' . $api_response->get_error_code());
		}

		return TRUE; // return, signaling that we've handled the request
	}

	/**
	 * Pull Divi Role Editor ajax request
	 *
	 * @since 1.0.0
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return void
	 */
	public function pull_divi_roles($resp)
	{
		$api = new SyncApiRequest();
		$api_response = $api->api('pulldiviroles');

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		if (0 === $api_response->get_error_code()) {
SyncDebug::log(' - no error, setting success');
			$resp->success(TRUE);
		} else {
			$resp->success(FALSE);
SyncDebug::log(' - error code: ' . $api_response->get_error_code());
		}

		return TRUE; // return, signaling that we've handled the request
	}
}

// EOF
