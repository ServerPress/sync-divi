/*
 * @copyright Copyright (C) 2015-2017 WPSiteSync.com. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author Dave Jesch <hello@SpectrOMtech.com>
 * @url https://www.wpsitesync.com/downloads/
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the SpectrOMtech Proprietary Use License v1.0
 * More info at https://wpsitesync.com/products/
 */

/**
 * Debug logging to the browser console
 * @param {string} msg The message to display
 * @param {object} val Optional object to display
 */
function syncdebug(msg, val)
{
// return
	var fn = '';

	if ('undefined' !== typeof(console.log)) {
		if (null !== syncdebug.caller)
			fn = syncdebug.caller.name + '() ';

		if ('undefined' !== typeof(val)) {
			switch (typeof(val)) {
			case 'string':		msg += ' "' + val + '"';						break;
			case 'object':		msg += ' {' + JSON.stringify(val) + '}';		break;
			case 'number':		msg += ' #' + val;								break;
			case 'boolean':		msg += ' `' + (val ? 'true' : 'false') + '`';	break;
			}
			if (null === val)
				msg += ' `null`';
		}
		console.log(fn + msg);
	}
}
syncdebug('sync-divi.js');

function WPSiteSyncContent_Divi()
{
syncdebug('constructor');
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
	this.page = wpsitesynccontent.get_param('page'); // this.get_param('page');
syncdebug('divi page=' + this.page);

	var _self = this,
		target = document.querySelector('#et_pb_main_container'),
		config = {attributes: false, childList: true, characterData: false, subtree: true},
		observer = new MutationObserver(function (mutations) {
			mutations.forEach(function(mutation) {
				_self.on_content_change();
				observer.disconnect();
			});
		});

	if (null !== target) {
		setTimeout(function () {
			observer.observe(target, config);
		}, 5000);
	}

	switch (this.page) {
	case 'et_divi_options':
		this.show_settings_ui();
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
// TODO: remove after testing
/*WPSiteSyncContent_Divi.prototype.get_param = function(name)
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
};*/

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
WPSiteSyncContent_Divi.prototype.show_settings_ui = function()
{
syncdebug(' showing setting ui');
	var content = jQuery('#sync-divi-ui-container').html();
	if (jQuery('#et_pb_save_plugin'))
		jQuery('#et_pb_save_plugin').after(content);
	else
		jQuery('#epanel-save-top').after(content);
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
	var operation = null;

	switch (this.page) {
	case 'et_divi_options':
		operation = 'pushdivisettings';
		break;
	case 'et_divi_role_editor':
		operation = 'pushdiviroles';
		break;
	}

	if (null !== operation) {
		// TODO: init() already called
//		wpsitesynccontent.inited = true;
		wpsitesynccontent.api(operation, null, jQuery('#sync-divi-settings').text(), jQuery('#sync-divi-success-msg').text());
	} else {
		// TODO: translate
		wpsitesynccontent.set_message('Unrecognized operation', false, true);
	}
};

/**
 * Pulls Divi settings from target site
 */
WPSiteSyncContent_Divi.prototype.pull_divi = function()
{
	var operation = null;

	switch (this.page) {
	case 'et_divi_options':
		operation = 'pulldivisettings';
		break;
	case 'et_divi_role_editor':
		operation = 'pulldiviroles';
		break;
	}

	if (null !== operation) {
		// TODO: removed setting .inited to true. This should be handled in wpss instantiation and not needed here. Verify
//		wpsitesynccontent.inited = true;
		wpsitesynccontent.api(operation, null, jQuery('#sync-divi-settings').text(), jQuery('#sync-divi-success-msg').text());
	}
};

wpsitesynccontent.divi = new WPSiteSyncContent_Divi();

// initialize the WPSiteSync operation on page load
jQuery(document).ready(function()
{
syncdebug('calling init()');
	wpsitesynccontent.divi.init();
});

// EOF
