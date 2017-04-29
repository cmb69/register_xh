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
	return (string) $view;
}


/**
 * Returns the requirements information view.
 *
 * @return string  The (X)HTML.
 */
function register_system_check() { // RELEASE-TODO
    global $pth, $tx, $plugin_cf, $plugin_tx;

    define('REGISTER_PHP_VERSION', '5.4.0');
    $pcf = $plugin_cf['register'];
    $ptx = $plugin_tx['register'];
    $imgdir = $pth['folder']['plugins'] . 'register/images/';
    $ok = tag('img src="' . $imgdir . 'ok.png" alt="ok"');
    $warn = tag('img src="' . $imgdir . 'warn.png" alt="warning"');
    $fail = tag('img src="' . $imgdir . 'fail.png" alt="failure"');
    $o = tag('hr') . '<h4>' . $ptx['syscheck_title'] . '</h4>'
	. (version_compare(PHP_VERSION, REGISTER_PHP_VERSION) >= 0 ? $ok : $fail)
	. '&nbsp;&nbsp;' . sprintf($ptx['syscheck_phpversion'], REGISTER_PHP_VERSION)
	. tag('br') .tag('br');
    foreach (array('gd', 'session') as $ext) {
	$o .= (extension_loaded($ext) ? $ok : ($ext == 'gd' ? $warn : $fail))
	    . '&nbsp;&nbsp;' . sprintf($ptx['syscheck_extension'], $ext) . tag('br');
    }
	$o .= tag('br') . (version_compare(CMSIMPLE_XH_VERSION, 'CMSimple_XH 1.6') >= 0 ? $ok : $fail)
		. '&nbsp;&nbsp;' . sprintf($ptx['syscheck_xhversion'], '1.6') . tag('br');
    $o .= ($pcf['encrypt_password'] ? $ok : $warn)
	. '&nbsp;&nbsp;' . $ptx['syscheck_encryption'] . tag('br') . tag('br');
    foreach (array('config/', 'css/', 'languages/') as $folder) {
	$folders[] = $pth['folder']['plugins'] . 'register/' . $folder;
    }
    $folders[] = Register_dataFolder();
    foreach ($folders as $folder) {
	$o .= (is_writable($folder) ? $ok : $warn)
	    . '&nbsp;&nbsp;' . sprintf($ptx['syscheck_writable'], $folder) . tag('br');
    }
    return $o;
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
	    $o .= register_version().tag('hr').register_system_check();
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
