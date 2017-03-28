<?php

/*
 * Allows management of Divi content and settings between the Source and Target sites
 * @package Sync
 * @author WPSiteSync
 */

class SyncDiviAdmin
{
	private static $_instance = NULL;

	private function __construct()
	{
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_footer', array($this, 'print_hidden_div'));
		add_action('spectrom_sync_ajax_operation', array($this, 'check_ajax_query'), 10, 3);
	}

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
	 * Registers Javascript to be used.
	 *
	 * @since 1.0.0
	 * @param $hook Admin page hook
	 * @return void
	 */
	public function admin_enqueue_scripts($hook)
	{
		wp_register_script('sync-divi', WPSiteSync_Divi::get_asset('js/sync-divi.js'), array('sync'), WPSiteSync_Divi::PLUGIN_VERSION, TRUE);
		wp_register_style('sync-divi', WPSiteSync_Divi::get_asset('css/sync-divi.css'), array('sync-admin'), WPSiteSync_Divi::PLUGIN_VERSION);

		if (in_array($hook, array('toplevel_page_et_divi_options', 'divi_page_et_divi_role_editor'))) {

			$theme = wp_get_theme();
			if ('toplevel_page_et_divi_options' === $hook && ('Divi' !== $theme->name && 'Divi' !== $theme->parent_theme)) {
				return;
			}

			wp_enqueue_script('sync-divi');
			wp_enqueue_style('sync-divi');
		}
	}

	/**
	 * Print hidden div for translatable text
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_hidden_div()
	{
		$page = isset($_GET['page']) ? $_GET['page'] : '';
		if (in_array($page, array('et_divi_role_editor', 'et_divi_options'))) {
			?>
			<div id="sync-divi-ui-container" style="display:none">
				<div id="sync-divi-ui">
					<div id="sync-divi-ui-header">
						<img src="<?php echo esc_url(WPSiteSyncContent::get_asset('imgs/wpsitesync-logo-blue.png')); ?>" width="125" height="45"/>
					</div>
					<div id="spectrom_sync" class="sync-divi-contents">
						<?php if (SyncOptions::is_auth()) { ?>
							<p><em>WPSiteSync&#8482; for Divi</em> provides a convenient way to sync your
								Divi Settings between two WordPress sites.</p>
							<p>Target site: <b><?php echo esc_url(SyncOptions::get('target')); ?>:</b></p>
							<div id="sync-message-container" style="display:none">
								<span id="sync-content-anim" style="display:none"><img src="<?php echo esc_url(WPSiteSyncContent::get_asset('imgs/ajax-loader.gif')); ?>"/></span>
								<span id="sync-message"></span>
								<span id="sync-message-dismiss" style="display:none"><span class="dashicons dashicons-dismiss" onclick="wpsitesynccontent.clear_message(); return false"></span></span>
							</div>
							<button class="sync-divi-push button button-primary sync-button" type="button" onclick="wpsitesynccontent.divi.push_handler(); return false;"
								title="<?php esc_html_e('Push Divi Settings to the Target site', 'wpsitesync-divi'); ?>">
								<span class="sync-button-icon dashicons dashicons-migrate"></span>
								<?php esc_html_e('Push Settings to Target', 'wpsitesync-divi'); ?>
							</button>
							<?php
							$pull_active = FALSE;
							if (class_exists('WPSiteSync_Pull') && WPSiteSyncContent::get_instance()->get_license()->check_license('sync_pull', WPSiteSync_Pull::PLUGIN_KEY, WPSiteSync_Pull::PLUGIN_NAME))
								$pull_active = TRUE;
							?>
							<button class="button <?php if ($pull_active) echo 'button-primary sync-divi-pull'; ?> sync-button" type="button"
								onclick="wpsitesynccontent.divi.<?php if ($pull_active) echo 'pull_handler'; else echo 'pull_notice'; ?>(); return false;"
								title="<?php esc_html_e('Pull Divi Settings from the Target site', 'wpsitesync-divi'); ?>">
								<span class="sync-button-icon sync-button-icon-rotate dashicons dashicons-migrate"></span>
								<?php esc_html_e('Pull Settings from Target', 'wpsitesync-divi'); ?>
							</button>

							<div style="display:none">
								<span id="sync-divi-settings"><?php esc_html_e('Synchronizing Divi Settings...', 'wpsitesync-divi'); ?></span>
								<span id="sync-divi-failure-msg"><?php esc_html_e('Failed to Sync Divi Settings.', 'wpsitesync-divi'); ?></span>
								<span id="sync-divi-success-msg"><?php esc_html_e('Successfully Synced Divi Settings.', 'wpsitesync-divi'); ?></span>
								<span id="sync-divi-pull-notice"><?php esc_html_e('Please install the WPSiteSync for Pull plugin to use the Pull features.', 'wpsitesync-divi'); ?></span>
							</div>
						<?php } else { // is_auth() ?>
							<p>WPSiteSync&#8482; for Content is not configured with a valid Target. Please go to the
								<a href="<?php echo esc_url(admin_url('options-general.php?page=sync')); ?>">Settings
									Page</a> to configure.</p>
						<?php } ?>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Checks if the current ajax operation is for this plugin
	 *
	 * @param  boolean $found Return TRUE or FALSE if the operation is found
	 * @param  string $operation The type of operation requested
	 * @param  SyncApiResponse $resp The response to be sent
	 *
	 * @return boolean Return TRUE if the current ajax operation is for this plugin, otherwise return $found
	 */
	public function check_ajax_query($found, $operation, SyncApiResponse $resp)
	{
SyncDebug::log(__METHOD__ . '() operation="' . $operation . '"');

	// @todo enable
//		$license = WPSiteSyncContent::get_instance()->get_license();
//		if (!$license)
//			return $found;

		if ('pushdivisettings' === $operation) {
SyncDebug::log(' - post=' . var_export($_POST, TRUE));

			$ajax = WPSiteSync_Divi::get_instance()->load_class('diviajaxrequest', TRUE);
			$ajax->push_divi_settings($resp);
			$found = TRUE;
		} else if ('pushdiviroles' === $operation) {
SyncDebug::log(' - post=' . var_export($_POST, TRUE));

			$ajax = WPSiteSync_Divi::get_instance()->load_class('diviajaxrequest', TRUE);
			$ajax->push_divi_roles($resp);
			$found = TRUE;
		} else if ('pulldivisettings' === $operation) {
SyncDebug::log(' - post=' . var_export($_POST, TRUE));

			$ajax = WPSiteSync_Divi::get_instance()->load_class('diviajaxrequest', TRUE);
			$ajax->pull_divi_settings($resp);
			$found = TRUE;
		} else if ('pulldiviroles' === $operation) {
SyncDebug::log(' - post=' . var_export($_POST, TRUE));

			$ajax = WPSiteSync_Divi::get_instance()->load_class('diviajaxrequest', TRUE);
			$ajax->pull_divi_roles($resp);
			$found = TRUE;
		}

		return $found;
	}
}

// EOF
