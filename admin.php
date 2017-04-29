<?php

/**
 * Back-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 */


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


/**
 * Returns the plugin version information view.
 *
 * @return string  The (X)HTML.
 */
function register_version() {
    global $pth;

	$view = new Register\View('info');
	$view->logo = "{$pth['folder']['plugins']}register/register.png";
	$view->version = REGISTER_VERSION;
	$view->checks = (new Register\SystemCheckService)->getChecks();
	return (string) $view;
}


/**
 * Handle the plugin administration.
 */
if (function_exists('XH_wantsPluginAdministration') && XH_wantsPluginAdministration('register')
	|| isset($register) && $register === 'true'
) {
    $o .= print_plugin_admin('off');
    pluginmenu('ROW');
    pluginmenu('TAB', '?&amp;register&amp;admin=plugin_main&amp;action=editgroups', '', $plugin_tx['register']['mnu_group_admin']);
    pluginmenu('TAB', '?&amp;register&amp;admin=plugin_main&amp;action=editusers', '', $plugin_tx['register']['mnu_user_admin']);
    $o .= pluginmenu('SHOW');
    switch ($admin) {
	case '':
	    $o .= register_version();
	    break;
	case 'plugin_main':
		$temp = new Register\MainAdminController;
		ob_start();
	    switch ($action) {
		case 'editusers':
		    $temp->editUsersAction();
		    break;
		case 'saveusers':
		    $temp->saveUsersAction();
		    break;
		case 'editgroups':
			$temp->editGroupsAction();
		    break;
		case 'savegroups':
			$temp->saveGroupsAction();
		    break;
	    }
		$o .= ob_get_clean();
	    break;
	default:
	    $o .= plugin_admin_common($action, $admin, $plugin);
    }
}

?>
