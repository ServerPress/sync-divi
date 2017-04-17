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
	this.page = false;
	this.disable = false;
}

/**
 * Initialize the Sync Divi Settings UI behaviors
 */
WPSiteSyncContent_Divi.prototype.init = function()
{
	this.inited = true;
	this.page = this.get_param('page');

	var _self = this,
		target = document.querySelector('#et_pb_main_container'),
		config = {attributes: false, childList: true, characterData: false, subtree: true},
		observer = new MutationObserver(function (mutations) {
			mutations.forEach(function(mutation) {
				_self.on_content_change();
				observer.disconnect();
			});
		});

	setTimeout(function () {
		observer.observe(target, config);
	}, 5000);

	switch (this.page) {
	case 'et_divi_options':
		this.show_theme_ui();
		break;
	case 'et_divi_role_editor':
		this.show_role_editor_ui();
		break;
	}
};

/**
 * Get the value of a parameter from the URL
 * @param {string} name Name of the parameter to retrieve
 * @returns {String} The value of the parameter (can be empty) or null if not found
 */
// TODO: this looks like something that other add-ons could use. If not already in the WPSiteSyncContent class, move it there
WPSiteSyncContent_Divi.prototype.get_param = function(name)
{
	var url = window.location.href;
	name = name.replace(/[\[\]]/g, "\\$&");
	var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
		results = regex.exec(url);
	if (!results)
		return null;
	if (!results[2])
		return '';
	return decodeURIComponent(results[2].replace(/\+/g, ' '));
};

/**
 * Disables Sync Button every time the content changes.
 */
WPSiteSyncContent_Divi.prototype.on_content_change = function()
{
	this.disable = true;
	jQuery('#sync-content').attr('disabled', true);
	wpsitesynccontent.set_message(jQuery('#sync-msg-update-changes').html());
	jQuery('#disabled-notice-sync').show();
};


/**
 * Shows the Sync UI area within the Divi Role Editor page
 */
WPSiteSyncContent_Divi.prototype.show_role_editor_ui = function()
{
	jQuery('#et_pb_save_roles').after(jQuery('#sync-divi-ui-container').html());
};

/**
 * Shows the Sync UI area within the Divi Theme Options page
 */
WPSiteSyncContent_Divi.prototype.show_theme_ui = function()
{
	jQuery('#epanel-save-top').after(jQuery('#sync-divi-ui-container').html());
};

/**
 * Callback for when the Push to Target button is clicked
 */
WPSiteSyncContent_Divi.prototype.push_handler = function()
{
	if (false === this.page)
		return;
	this.push_divi();
};

/**
 * Callback for when the Pull from Target button is clicked
 */
WPSiteSyncContent_Divi.prototype.pull_handler = function()
{
	if (false === this.page)
		return;
	this.pull_divi();
};

/**
 * Callback for the Pull from Target button is clicked and the Pull add-on is not active
 */
WPSiteSyncContent_Divi.prototype.pull_notice = function()
{
	this.hide_msgs();
	this.set_message('pull-notice');
};

/**
 * Push Divi settings from target site
 */
WPSiteSyncContent_Divi.prototype.push_divi = function()
{
	var operation;

	switch (this.page) {
	case 'et_divi_options':
		operation = 'pushdivisettings';
		break;
	case 'et_divi_role_editor':
		operation = 'pushdiviroles';
		break;
	}

	wpsitesynccontent.inited = true;
	wpsitesynccontent.api(operation, null, jQuery('#sync-divi-settings').text(), jQuery('#sync-divi-success-msg').text());
};

/**
 * Pulls Divi settings from target site
 */
WPSiteSyncContent_Divi.prototype.pull_divi = function()
{
	var operation;

	switch (this.page) {
	case 'et_divi_options':
		operation = 'pulldivisettings';
		break;
	case 'et_divi_role_editor':
		operation = 'pulldiviroles';
		break;
	}

	wpsitesynccontent.inited = true;
	wpsitesynccontent.api(operation, null, jQuery('#sync-divi-settings').text(), jQuery('#sync-divi-success-msg').text());
};

wpsitesynccontent.divi = new WPSiteSyncContent_Divi();

// initialize the WPSiteSync operation on page load
jQuery(document).ready(function ()
{
	wpsitesynccontent.divi.init();
});
