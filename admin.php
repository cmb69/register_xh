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
    foreach (array('date', 'gd', 'pcre', 'session') as $ext) {
	$o .= (extension_loaded($ext) ? $ok : ($ext == 'gd' ? $warn : $fail))
	    . '&nbsp;&nbsp;' . sprintf($ptx['syscheck_extension'], $ext) . tag('br');
    }
    $o .= tag('br') . (!get_magic_quotes_runtime() ? $ok : $fail)
	. '&nbsp;&nbsp;' . $ptx['syscheck_magic_quotes'] . tag('br');
    $o .= (strtoupper($tx['meta']['codepage']) == 'UTF-8' ? $ok : $warn)
	. '&nbsp;&nbsp;' . $ptx['syscheck_encoding'] . tag('br');
    $o .= (strtolower($pcf['encrypt_password']) == 'true' ? $ok : $warn)
	. '&nbsp;&nbsp;' . $ptx['syscheck_encryption'] . tag('br') . tag('br');
    foreach (array('config/', 'css/', 'languages/') as $folder) {
	$folders[] = $pth['folder']['plugins'] . 'register/' . $folder;
    }
    $ufdir = dirname($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);
    $gfdir = dirname($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile']);
    $folders[] = $ufdir . '/';
    if ($gfdir != $ufdir) {
	$folders[] = $gfdir . '/';
    }
    foreach ($folders as $folder) {
	$o .= (is_writable($folder) ? $ok : $warn)
	    . '&nbsp;&nbsp;' . sprintf($ptx['syscheck_writable'], $folder) . tag('br');
    }
    return $o;
}


function Register_pageSelectbox($loginpage, $n)
{
    global $cl, $h, $u, $l, $plugin_tx;

    $o = '<select name="grouploginpage[' . $n . ']"><option>' . $plugin_tx['register']['label_none'] . '</option>';
    for ($i = 0; $i < $cl; $i++) {
	$sel = $u[$i] == $loginpage ? ' selected="selected"' : '';
	$o .= '<option value="' . $u[$i] . '"' . $sel . '>'
	    . str_repeat('&nbsp;', 3 * ($l[$i] - 1)) . $h[$i] . '</option>';
    }
    $o .= '</select>';
    return $o;
}


function registerAdminGroupsForm($groups)  {
    global $tx, $pth, $sn;

    $imageFolder = "{$pth['folder']['plugins']}register/images";

	$view = new Register\View('admin-groups');
	$view->actionUrl = "$sn?&register";
	$view->addIcon = "$imageFolder/add.png";
	$view->deleteIcon = "$imageFolder/delete.png";
	$view->saveLabel = ucfirst($tx['action']['save']);
	$view->groups = $groups;
	$selects = [];
	foreach ($groups as $i => $entry) {
		$selects[] = new Register\HtmlString(Register_pageSelectbox($entry['loginpage'], $i));
	}
	$view->selects = $selects;
    return (string) $view;
}


function Register_groupSelectbox()
{
    global $pth, $plugin_tx;

    $ptx = $plugin_tx['register'];
    $groups = registerReadGroups($pth['folder']['base'] . $ptx['config_groupsfile']);
    usort($groups, create_function('$a, $b', 'return strcasecmp($a["groupname"], $b["groupname"]);'));
    $o = '<select id="register_group_selectbox" title="' . $ptx['filter_group'] . '">'
        . '<option value="">' . $ptx['all'] . '</option>';
    foreach ($groups as $group) {
        $o .= '<option value="' . $group['groupname'] . '">' . $group['groupname'] . '</option>';
    }
    $o .= '</select>';
    return $o;
}


/**
 * Returns the status selectbox.
 *
 * @param  string $value  The selected value.
 * @param  int $n  The running number.
 * @return string  The (X)HTML.
 */
function Register_statusSelectbox($value, $n = null)
{
    global $plugin_tx;

    $ptx = $plugin_tx['register'];
    $o = '<select name="status[' . $n . ']">';
    $opts = array('activated' => $ptx['status_activated'],
		  'locked' => $ptx['status_locked']);
    if (empty($value) || array_key_exists($value, $opts)) {
	$opts[''] = $ptx['status_deactivated'];
    } else {
	$opts[$value] = $ptx['status_not_yet_activated'];
    }
    foreach ($opts as $opt => $label) {
	$sel = $opt == $value ? ' selected="selected"' : '';
	$o .= '<option value="' . $opt . '"' . $sel . '>' . $label . '</option>';
    }
    $o .= '</select>';
    return $o;
}


function registerAdminUsersForm($users) {
    global $tx, $pth, $sn, $hjs, $plugin_cf, $plugin_tx;

    $plugin = basename(dirname(__FILE__),"/");
    $ptx = $plugin_tx['register'];
    $imageFolder = $pth['folder']['plugins'] . $plugin . '/images';

    $jsKeys = array('name', 'username', 'password', 'accessgroups', 'status',
		    'email', 'prefsemailsubject');
    $txts = array();
    foreach ($plugin_tx['register'] as $key => $val) {
	$val = addcslashes($val, "\0..\037\"\$");
        if (strpos($key, 'js_') === 0) {
            $txts[] = substr($key, 3) . ':"' . $val . '"';
        } elseif (in_array($key, $jsKeys)) {
	    $txts[] = "$key:\"$val\"";
	}
    }

    $hjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins'] . 'register/admin.js"></script>'
        . '<script type="text/javascript">register.tx={' . implode(',', $txts) . '};'
	. 'register.maxNumberOfUsers=' . Register_maxRecords(7, 4) . ';</script>';

	$view = new Register\View('admin-users');
	$view->saveLabel = ucfirst($tx['action']['save']);
	$view->deleteIcon = "$imageFolder/delete.png";
	$view->mailIcon = "$imageFolder/mail.png";
	$view->defaultGroup = $plugin_cf[$plugin]['group_default'];
	$view->statusSelectActivated = new Register\HtmlString(Register_statusSelectbox('activated'));
	$view->groupSelect = new Register\HtmlString(Register_groupSelectbox());
	$view->actionUrl = "$sn?&register";
	$view->users = $users;
	$groupStrings = $statusSelects = [];	
	foreach ($users as $i => $entry) {
		$groupStrings[] = implode(",", $entry['accessgroups']);
		$statusSelects[] = new Register\HtmlString(Register_statusSelectbox($entry['status'], $i));
	}
	$view->groupStrings = $groupStrings;
	$view->statusSelects = $statusSelects;
    return (string) $view;
}


function Register_administrateUsers()
{
    global $action, $pth, $e, $plugin_cf, $plugin_tx, $_Register_hasher;

    $ptx = $plugin_tx['register'];
    $fn = $pth['folder']['base'] . $ptx['config_usersfile'];
    $o = '';
    $ERROR = '';
    if ($action != 'saveusers') {
	if (is_file($fn))  {
	    register_lock_users(dirname($fn), LOCK_SH);
	    $users  = registerReadUsers($fn);
	    register_lock_users(dirname($fn), LOCK_UN);
	    $o .= registerAdminUsersForm($users);
	    $o .= '<div class="register_status">' . count($users) . ' ' . $ptx['entries_in_csv'] .
		$fn . '</div>';
	} else {
	    $o .= '<div class="register_status">' . $ptx['err_csv_missing']
		. ' (' . $fn . ')' . '</div>';
	}
    } else {
	// == Edit Users ==============================================================
	if (is_file($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile']))
	    $groups = registerReadGroups($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile']);
	else
	    $ERROR .= '<li>' . $plugin_tx['register']['err_csv_missing'] .
		    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
		    '</li>'."\n";

	// put all available group Ids in an array for easier handling
	$groupIds = array();
	foreach($groups as $entry)
	$groupIds[] = $entry['groupname'];

	$delete      = isset($_POST['delete'])       ? $_POST['delete']       : '';
	$add         = isset($_POST['add'])          ? $_POST['add']          : '';
	$username    = isset($_POST['username'])     ? $_POST['username']     : '';
	$password    = isset($_POST['password'])     ? $_POST['password']     : '';
	$oldpassword = isset($_POST['oldpassword'])  ? $_POST['oldpassword']  : '';
	$name        = isset($_POST['name'])         ? $_POST['name']         : '';
	$email       = isset($_POST['email'])        ? $_POST['email']        : '';
	$groupString = isset($_POST['accessgroups']) ? $_POST['accessgroups'] : '';
	$status      = isset($_POST['status'])       ? $_POST['status']       : '';

	$deleted = false;
	$added   = false;

	$newusers = array();
	foreach ($username as $j => $i) {
	    if (!isset($delete[$j]) || $delete[$j] == '') {
		$userGroups = explode(",", $groupString[$j]);
		// Error Checking
		$ENTRY_ERROR = '';
		if(preg_match('/true/i',$plugin_cf['register']['encrypt_password']) && $password[$j] == $oldpassword[$j])
		    $ENTRY_ERROR .= registerCheckEntry($name[$j], $username[$j], "dummy", "dummy", $email[$j]);
		else
		    $ENTRY_ERROR .= registerCheckEntry($name[$j], $username[$j], $password[$j], $password[$j], $email[$j]);
		$ENTRY_ERROR .= registerCheckColons($name[$j], $username[$j], $password[$j], $email[$j]);
		if (registerSearchUserArray($newusers, 'username', $username[$j]) !== false)
		    $ENTRY_ERROR .= '<li>' . $plugin_tx['register']['err_username_exists'] . '</li>'."\n";
		if (registerSearchUserArray($newusers, 'email', $email) !== false)
		    $ENTRY_ERROR .= '<li>' . $plugin_tx['register']['err_email_exists'] . '</li>'."\n";
		foreach ($userGroups as $groupName) {
		    if (!in_array($groupName, $groupIds))
			$ENTRY_ERROR .= '<li>' . $plugin_tx['register']['err_group_does_not_exist'] . ' (' . $groupName . ')</li>'."\n";
		}
		if ($ENTRY_ERROR != '')
		    $ERROR .= '<li>' . $plugin_tx['register']['error_in_user'] . '"' . $username[$j] . '"' .
			    '<ul class="error">'.$ENTRY_ERROR.'</ul></li>'."\n";

		if (empty($ENTRY_ERROR) && preg_match('/true/i', $plugin_cf['register']['encrypt_password']) && $password[$j] != $oldpassword[$j])
		    $password[$j] = $_Register_hasher->HashPassword($password[$j]);
		$entry = array(
			'username'     => $username[$j],
			'password'     => $password[$j],
			'accessgroups' => $userGroups,
			'name'         => $name[$j],
			'email'        => $email[$j],
			'status'       => $status[$j]);
		$newusers[] = $entry;
	    } else {
		$deleted = true;
	    }
	}
	if ($add <> '') {
	    $entry = array(
		    'username'     => "NewUser",
		    'password'     => "",
		    'accessgroups' => array($plugin_cf['register']['group_default']),
		    'name'         => "Name Lastname",
		    'email'        => "user@domain.com",
		    'status'       => "activated");
	    $newusers[] = $entry;
	    $added = true;
	}

	$o .= registerAdminUsersForm($newusers);

	// In case that nothing got deleted or added, store back (save got pressed)
	if (!$deleted && !$added && $ERROR == "") {
	    register_lock_users(dirname($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']), LOCK_EX);
	    if (!registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $newusers))
		$ERROR .= '<li>' . $plugin_tx['register']['err_cannot_write_csv'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
			'</li>'."\n";
	    register_lock_users(dirname($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']), LOCK_UN);

	    if ($ERROR != '')
		$e .= $ERROR;
	    else
		$o .= '<div class="register_status">'  . $plugin_tx['register']['csv_written'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
			'.</div>'."\n";
	}
	elseif ($ERROR != '')
	    $e .= $ERROR;
    }
    return $o;
}


/**
 * Handle the plugin administration.
 */
if (isset($register) && $register == 'true') {
    $ERROR = '';

    $o .= print_plugin_admin('off');
    pluginmenu('ROW');
    pluginmenu('TAB', '?&amp;register&amp;admin=plugin_main&amp;action=editgroups', '', $plugin_tx[$plugin]['mnu_group_admin']);
    pluginmenu('TAB', '?&amp;register&amp;admin=plugin_main&amp;action=editusers', '', $plugin_tx[$plugin]['mnu_user_admin']);
    $o .= pluginmenu('SHOW');
    switch ($admin) {
	case '':
	    $o .= register_version().tag('hr').register_system_check();
	    break;
	case 'plugin_main':
	    switch ($action) {
		case 'editusers':
		    $o .= Register_administrateUsers();
		    break;
		case 'editgroups':
		    // read user file in CSV format separated by colons
		    if (is_file($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'])) {
			$groups = registerReadGroups($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile']);
			$o .= registerAdminGroupsForm($groups);
			$o .= '<div class="register_status">' . count($groups) . ' ' . $plugin_tx[$plugin]['entries_in_csv'] .
				$pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . '</div>'."\n";
		    } else {
			$o .= '<div class="register_status">' . $plugin_tx[$plugin]['err_csv_missing'] .
				' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
				'</div>'."\n";
		    }
		    break;
		case 'savegroups':
		    // == Edit Groups =============================================================
		    $delete      = isset($_POST['delete'])       ? $_POST['delete']       : '';
		    $add         = isset($_POST['add'])          ? $_POST['add']          : '';
		    $groupname   = isset($_POST['groupname'])    ? $_POST['groupname']    : '';

		    $deleted = false;
		    $added   = false;

		    $newgroups = array();
		    foreach ($groupname as $j => $i) {
			if(!preg_match("/^[A-Za-z0-9_-]+$/", $groupname[$j]))
			$ERROR = '<li>' . $plugin_tx[$plugin]['err_group_illegal'] . '</li>'."\n";

			if (!isset($delete[$j]) || $delete[$j] == '') {
			    $entry = array('groupname' => $groupname[$j],
					   'loginpage' => stsl($_POST['grouploginpage'][$j]));
			    $newgroups[] = $entry;
			} else {
			    $deleted = true;
			}
		    }
		    if($add <> '') {
			$entry = array('groupname' => "NewGroup",
				       'loginpage' => '');
			$newgroups[] = $entry;
			$added = true;
		    }

		    $o .= registerAdminGroupsForm($newgroups);

		    // In case that nothing got deleted or added, store back (save got pressed)
		    if(!$deleted && !$added && $ERROR == "") {
			if (!registerWriteGroups($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'], $newgroups))
			    $ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] .
				    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
				    '</li>'."\n";

			if($ERROR != '')
			    $e .= $ERROR;
			else
			    $o .= '<div class="register_status">'  . $plugin_tx[$plugin]['csv_written'] .
				    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
				    '.</div>'."\n";
		    } elseif ($ERROR != '')
			$e .= $ERROR;
		    break;
		case 'saveusers':
		    $o .= Register_administrateUsers();
		    break;
	    }
	    break;
	default:
	    $o .= plugin_admin_common($action, $admin, $plugin);
    }
}

/**
 * Returns the maximum number of records that may be successfully submitted from
 * a form.
 *
 * Takes into account <var>max_input_vars</var>,
 * <var>suhosin.post.max_vars</var> and <var>suhosin.request.max_vars</var>.
 * If none of these is set, <var>PHP_INT_MAX</var> is returned.
 *
 * @param int $varsPerRecord  Number of POST variables per record.
 * @param int $additionalVars Number of additional POST and GET variables.
 *
 * @return int
 */
function Register_maxRecords($varsPerRecord, $additionalVars)
{
    $miv = ini_get('max_input_vars');
    $pmv = ini_get('suhosin.post.max_vars');
    $rmv = ini_get('suhosin.request.max_vars');
    foreach (array('miv', 'pmv', 'rmv') as $var) {
        if (!$$var) {
            $$var = PHP_INT_MAX;
        }
    }
    $maxVars = min($miv, $pmv, $rmv);
    $maxRecords = intval(($maxVars - $additionalVars) / $varsPerRecord);
    return $maxRecords;
}

?>
