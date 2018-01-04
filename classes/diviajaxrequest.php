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
	 * @return SyncDiviAdmin instance reference to AJAX request class
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Push Divi Settings ajax request
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return boolean TRUE if handled the request
	 */
	public function push_divi_settings($resp)
	{
		$api_response = WPSiteSync_Divi::get_instance()->get_api()->api('pushdivisettings');

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		// TODO: probably don't need to with success included in copy()
###		if (0 === $api_response->get_error_code()) {
###SyncDebug::log(__METHOD__.'():' . __LINE__ . ' no error, setting success');
###			$resp->success(TRUE);
###		} else {
###			$resp->success(FALSE);
###SyncDebug::log(__METHOD__.'():' . __LINE__ . ' error code: ' . $api_response->get_error_code());
###		}

		return TRUE; // return, signaling that we've handled the request
	}

	/**
	 * Push Divi Role Editor ajax request
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return boolean TRUE if handled the request
	 */
	public function push_divi_roles($resp)
	{
		$api_response = WPSiteSync_Divi::get_instance()->get_api()->api('pushdiviroles');

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		// TODO: probably don't need to with success included in copy()
###		if (0 === $api_response->get_error_code()) {
###SyncDebug::log(__METHOD__.'():' . __LINE__ . ' no error, setting success');
###			$resp->success(TRUE);
###		} else {
###			$resp->success(FALSE);
###SyncDebug::log(__METHOD__.'():' . __LINE__ . ' error code: ' . $api_response->get_error_code());
###		}

		return TRUE; // return, signaling that we've handled the request
	}

	/**
	 * Pull Divi Settings ajax request
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return boolean TRUE if handled the request
	 */
	public function pull_divi_settings($resp)
	{
		$api_response = WPSiteSync_Divi::get_instance()->get_api()->api('pulldivisettings');

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		// TODO: probably don't need to with success included in copy()
###		if (0 === $api_response->get_error_code()) {
###SyncDebug::log(__METHOD__.'():' . __LINE__ . ' no error, setting success');
###			$resp->success(TRUE);
###		} else {
###			$resp->success(FALSE);
###SyncDebug::log(__METHOD__.'():' . __LINE__ . ' error code: ' . $api_response->get_error_code());
###		}

		return TRUE; // return, signaling that we've handled the request
	}

	/**
	 * Pull Divi Role Editor ajax request
	 * @param SyncApiResponse $resp The response object after the API request has been made
	 * @return boolean TRUE if handled the request
	 */
	public function pull_divi_roles($resp)
	{
		$api_response = WPSiteSync_Divi::get_instance()->get_api()->api('pulldiviroles');

		// copy contents of SyncApiResponse object from API call into the Response object for AJAX call
SyncDebug::log(__METHOD__ . '():' . __LINE__ . ' - returned from api() call; copying response');
		$resp->copy($api_response);

		// TODO: probably don't need to with success included in copy()
###		if (0 === $api_response->get_error_code()) {
###SyncDebug::log(__METHOD__.'():' . __LINE__ . ' no error, setting success');
###//			$resp->success(TRUE);
###		} else {
###			$resp->success(FALSE);
###SyncDebug::log(__METHOD__.'():' . __LINE__ . ' error code: ' . $api_response->get_error_code());
###		}

		return TRUE; // return, signaling that we've handled the request
	}
}

// EOF
