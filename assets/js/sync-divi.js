/*
 * @copyright Copyright (C) 2015 SpectrOMtech.com. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author SpectrOMtech.com <hello@SpectrOMtech.com>
 * @url https://www.wpsitesync.com/downloads/
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the SpectrOMtech Proprietary Use License v1.0
 * More info at https://SpectrOMtech.com/products/
 */

function WPSiteSyncContent_Divi()
{
	this.inited = false;
}

/**
 * Initialize the Sync Divi Settings UI behaviors
 */
WPSiteSyncContent_Divi.prototype.init = function ()
{
	this.inited = true;
	var page = this.get_param('page');
	switch (page) {
		case 'et_divi_options':
			this.show_theme_ui();
			break;
		case 'et_divi_role_editor':
			this.show_role_editor_ui();
			break;
	}
}

/**
 * Get the value of a parameter from the URL
 * @param {string} name Name of the parameter to retrieve
 * @returns {String} The value of the parameter (can be empty) or null if not found
 */
WPSiteSyncContent_Divi.prototype.get_param = function (name)
{
	var url = window.location.href;
	name = name.replace(/[\[\]]/g, "\\$&");
	var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);
	if (!results)
		return null;
	if (!results[2])
		return '';
	return decodeURIComponent(results[2].replace(/\+/g, " "));
};

/**
 * Hides all messages within the Divi settings UI area
 * @returns {undefined}
 */
WPSiteSyncContent_Divi.prototype.hide_msgs = function ()
{
	// TODO: rework to have a single message <div> that all messages are written into
	jQuery('.sync-divi-msgs').hide();
	jQuery('.sync-divi-loading-indicator').hide();
	jQuery('.sync-divi-failure-msg').hide();
	jQuery('.sync-divi-success-msg').hide();
	jQuery('.sync-divi-pull-notice').hide();
};

/**
 * Shows the Sync UI area within the Divi Role Editor page
 */
WPSiteSyncContent_Divi.prototype.show_role_editor_ui = function ()
{
	this.hide_msgs();

	jQuery('#et_pb_save_roles').after(jQuery('#sync-divi-ui-container').html());
};

/**
 * Shows the Sync UI area within the Divi Theme Options page
 */
WPSiteSyncContent_Divi.prototype.show_theme_ui = function ()
{
	this.hide_msgs();

	jQuery('#epanel-save-top').after(jQuery('#sync-divi-ui-container').html());
};

wpsitesynccontent.divi = new WPSiteSyncContent_Divi();

// initialize the WPSiteSync operation on page load
jQuery(document).ready(function ()
{
	wpsitesynccontent.divi.init();
});
