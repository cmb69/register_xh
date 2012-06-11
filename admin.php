<?php

/**
 * Back-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012 Christoph M. Becker (see license.txt)
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

    return '<h1><a href="http://3-magi.net/?CMSimple_XH/Register_XH">Register_XH</a></h1>'."\n"
	    .tag('img src="'.$pth['folder']['plugins'].'register/register.png" class="register_plugin_icon"')
	    .'<p>Version: '.REGISTER_VERSION.'</p>'."\n"
	    .'<p>Copyright &copy; 2007 <a href="http://cmsimple.heinelt.eu/">Carsten Heinelt</a>'.tag('br')
	    .'Copyright &copy; 2010-2012 <a href="http://www.ge-webdesign.de/cmsimpleplugins/">Gert Ebersbach</a>'.tag('br')
	    .'Copyright &copy; 2012 <a href="http://3-magi.net/">Christoph M. Becker</a></p>'."\n"
	    .'<p class="register_license">Permission is hereby granted, free of charge, to any person obtaining a copy of'
	    .' this software and associated documentation files (the "Software"), to use, copy'
	    .' and modify the Software, subject to the following conditions:</p>'."\n"
	    .'<p class="register_license">The above copyright notice and this permission notice shall be included in all'
	    .' copies or substantial portions of the Software.</p>'."\n"
	    .'<p class="register_license">Redistribution is expressly prohibited.</p>'."\n"
	    .'<p class="register_license">THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR'
	    .' IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,'
	    .' FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE'
	    .' AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER'
	    .' LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,'
	    .' OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE'
	    .' SOFTWARE.</p>'."\n";
}


function registerAdminGroupsForm($groups)  {
    global $tx, $pth, $sn, $plugin_tx;

    $plugin = basename(dirname(__FILE__),"/");
    $imageFolder = $pth['folder']['plugins'] . $plugin . '/images';

    $o = '';
    $o .= '<h1>' . $plugin_tx[$plugin]['mnu_group_admin'] . '</h1>'."\n";
    $o .= '<div class="register_admin_main">'."\n";
    $o .= '<form method="POST" action="'.$sn.'?&register">'."\n";
    $o .= tag('input type="hidden" value="savegroups" name="action"')."\n";
    $o .= tag('input type="hidden" value="plugin_main" name="admin"')."\n";
    $o .= '<table cellpadding="1" cellspacing="0" style="background:transparent; float: left; width: 250px; border: 0;">'."\n";
    $o .= '<tr style="background:#E6E6E6">'."\n"
	    .'  <td align="left">' . $plugin_tx[$plugin]['groupname'] . '</td>'."\n"
	    .'  <td align="right">'."\n"
	    .tag('input type="image" src="'.$imageFolder.'/add.png" style="width: 16px; height: 16px;" name="add[0]" value="add" alt="Add entry."')."\n"
	    .'  </td>'."\n"
	    .'</tr>'."\n";
    $i = 0;
    foreach ($groups as $entry) {
	$o .= '<tr>'."\n"
		.'  <td>'.tag('input type="normal" size="10" value="'.$entry['groupname'].'" name="groupname['.$i.']"').'</td>'."\n"
		.'  <td style="text-align: right;">'."\n"
		.tag('input type="image" src="'.$imageFolder.'/delete.png" style="width: 16px; height: 16px;" name="delete['.$i.']" value="delete" alt="Delete Entry"')."\n"
		.'  </td>'."\n"
		.'</tr>'."\n";
	$i++;
    }
    $o .= '<tr>'."\n";
    $o .= '  <td>'.tag('input class="submit" type="submit" value="'.ucfirst($tx['action']['save']).'" name="send"').'</td>'."\n";
    $o .= '</tr>'."\n";
    $o .= '</table>'."\n";
    $o .= '</form>'."\n".'</div>'.tag('br')."\n";
    return $o;
}


function registerAdminUsersForm($users) {
    global $tx, $pth, $sn, $plugin_tx;

    $plugin = basename(dirname(__FILE__),"/");
    $imageFolder = $pth['folder']['plugins'] . $plugin . '/images';

    $o = '';
    $o .= '<h1>' . $plugin_tx[$plugin]['mnu_user_admin'] . '</h1>'."\n";
    $o .= '<div class="register_admin_main">'."\n";
//      $o .= '<a href="#bottom">nach unten</a>';
    $o .= '<form method="POST" action="'.$sn.'?&register#bottom">'."\n";
    $o .= '<input type="hidden" value="saveusers" name="action" />'."\n";
    $o .= '<input type="hidden" value="plugin_main" name="admin" />'."\n";

    $i = 0;
    foreach($users as $entry) {
	$groupString = implode(",", $entry['accessgroups']);
	$o .= '<p style="text-align: right; margin-bottom: -6px;"><a href="#bottom">nach unten <span style="font-size: 24px;">&dArr;</span></a></p>'
		.tag('input type="hidden" value="' . $entry['password'] . '" name="oldpassword['.$i.']"')."\n" . tag('br')
		.'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['username'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['username'] . '" name="username['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>'
		.'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['password']. ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['password'] . '" name="password['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>'
		.'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['name'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['name'] . '" name="name['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>'
		.'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['email'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['email'] . '" name="email['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>'
		.'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['accessgroups'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $groupString . '" name="accessgroups['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>'
		.'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['status'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['status'] . '" name="status['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>'
		.tag('input type="image" src="'.$imageFolder.'/delete.png" style="width: 16px; height: 16px;" name="delete['.$i.']" value="delete" alt="Delete Entry"')."\n" . tag('br')
		.tag('hr');
	$i++;
    }

    $o .= '<a name="bottom">'.tag('br').'</a>';
    $o .= tag('input type="image" src="'.$imageFolder.'/add.png" style="float: right; width: 16px; height: 16px;" name="add[0]" value="add" alt="Add entry"')."\n";
    $o .= tag('input class="submit" type="submit" value="' . ucfirst($tx['action']['save']).  '" name="send"')."\n";
    $o .= '</form>'."\n".'</div>'.tag('br')."\n";
    return $o;
}


/**
 * Handle the plugin administration.
 */
if (isset($register) && $register == 'true') {
    $ERROR = '';

    $o .= print_plugin_admin('on');
    pluginmenu('ROW');
    pluginmenu('TAB', '?&amp;register&amp;admin=plugin_main&amp;action=editgroups', '', $plugin_tx[$plugin]['mnu_group_admin']);
    pluginmenu('TAB', '?&amp;register&amp;admin=plugin_main&amp;action=editusers', '', $plugin_tx[$plugin]['mnu_user_admin']);
    $o .= pluginmenu('SHOW');
    switch ($admin) {
	case '':
	    $o .= register_version();
	    break;
	case 'plugin_main':
	    switch ($action) {
		case 'editusers':
		    // read user file in CSV format separated by colons
		    $o .= '<br />'."\n".'<table>'."\n";
		    if (is_file($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']))  {
			$users  = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= registerAdminUsersForm($users);
			$o .= '  </td>'."\n".'</tr>'."\n";
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= '<b>' . count($users) . ' ' . $plugin_tx[$plugin]['entries_in_csv'] .
			$pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . '</b>'."\n";
			$o .= '  </td>'."\n".'</tr>'."\n";
		    } else {
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= '<b>' . $plugin_tx[$plugin]['err_csv_missing'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
			'</b>'."\n";
			$o .= '  </td>'."\n".'</tr>'."\n";
		    }
		    $o .= '</table>'."\n";
		    break;
		case 'editgroups':
		    // read user file in CSV format separated by colons
		    $o .= '<br />'."\n".'<table>'."\n";
		    if (is_file($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'])) {
			$groups = registerReadGroups($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile']);
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= registerAdminGroupsForm($groups);
			$o .= '  </td>'."\n".'</tr>'."\n";
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= '<b>' . count($groups) . ' ' . $plugin_tx[$plugin]['entries_in_csv'] .
				$pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . '</b>'."\n";
			$o .= '  </td>'."\n".'</tr>'."\n";
		    } else {
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= '<b>' . $plugin_tx[$plugin]['err_csv_missing'] .
				' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
				'</b>'."\n";
			$o .= '  </td>'."\n".'</tr>'."\n";
		    }
		    $o .= '</table>'."\n";
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
			    $entry = array('groupname' => $groupname[$j]);
			    $newgroups[] = $entry;
			} else {
			    $deleted = true;
			}
		    }
		    if($add <> '') {
			$entry = array('groupname' => "NewGroup");
			$newgroups[] = $entry;
			$added = true;
		    }

		    $o .= tag('br').'<table>'."\n";
		    $o .= '<tr>'."\n".'  <td>'."\n";
		    $o .= registerAdminGroupsForm($newgroups);
		    $o .= '  </td>'."\n".'</tr>'."\n";

		    // In case that nothing got deleted or added, store back (save got pressed)
		    $o .= '<tr>'."\n".'  <td>'."\n";
		    if(!$deleted && !$added && $ERROR == "") {
			if (!registerWriteGroups($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'], $newgroups))
			    $ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] .
				    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
				    '</li>'."\n";

			if($ERROR != '')
			    $o .= '<b class="error">' . $plugin_tx[$plugin]['error'] . '</b>'."\n" . '<ul class="error">'."\n".$ERROR.'</ul>'."\n";
			else
			    $o .= '<b>'  . $plugin_tx[$plugin]['csv_written'] .
				    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
				    '.</b>'."\n";
		    } elseif ($ERROR != '')
			$o .= '<b>' . $plugin_tx[$plugin]['error'] . '</b>'."\n" . '<ul class="error">'."\n".$ERROR.'</ul>'."\n";
		    $o .= '  </td>'."\n".'</tr>'."\n";
		    $o .= '</table>'."\n";
		    break;
		case 'saveusers':
		    // == Edit Users ==============================================================
		    if (is_file($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile']))
			$groups = registerReadGroups($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile']);
		    else
			$ERROR .= '<li>' . $plugin_tx[$plugin]['err_csv_missing'] .
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
			    if(preg_match('/true/i',$plugin_cf[$plugin]['encrypt_password']))
				$ENTRY_ERROR .= registerCheckEntry($name[$j], $username[$j], "dummy", "dummy", $email[$j]);
			    else
				$ENTRY_ERROR .= registerCheckEntry($name[$j], $username[$j], $password[$j], $password[$j], $email[$j]);
			    $ENTRY_ERROR .= registerCheckColons($name[$j], $username[$j], $password[$j], $email[$j]);
			    if (registerSearchUserArray($newusers, 'username', $username[$j]) !== false)
				$ENTRY_ERROR .= '<li>' . $plugin_tx[$plugin]['err_username_exists'] . '</li>'."\n";
			    if (registerSearchUserArray($newusers, 'email', $email) !== false)
				$ENTRY_ERROR .= '<li>' . $plugin_tx[$plugin]['err_email_exists'] . '</li>'."\n";
			    foreach ($userGroups as $groupName) {
				if (!in_array($groupName, $groupIds))
				    $ENTRY_ERROR .= '<li>' . $plugin_tx[$plugin]['err_group_does_not_exist'] . ' (' . $groupName . ')</li>'."\n";
			    }
			    if ($ENTRY_ERROR != '')
				$ERROR .= '<li>' . $plugin_tx[$plugin]['error_in_user'] . '"' . $username[$j] . '"' .
					'<ul class="error"><li>'.$ENTRY_ERROR.'</li></ul></li>'."\n";

			    if (preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']) && $password[$j] != $oldpassword[$j])
				$password[$j] = crypt($password[$j], $password[$j]);
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
				'accessgroups' => array($plugin_cf[$plugin]['group_default']),
				'name'         => "Name Lastname",
				'email'        => "user@domain.com",
				'status'       => "activated");
			$newusers[] = $entry;
			$added = true;
		    }

		    $o .= tag('br').'<table>'."\n";
		    $o .= '<tr>'."\n".'  <td>'."\n";
		    $o .= registerAdminUsersForm($newusers);
		    $o .= '  </td>'."\n".'</tr>'."\n";

		    // In case that nothing got deleted or added, store back (save got pressed)
		    $o .= '<tr>'."\n".'  <td>'."\n";
		    if (!$deleted && !$added && $ERROR == "") {
			if (!registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $newusers))
			    $ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] .
				    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
				    '</li>'."\n";

			if ($ERROR != '')
			    $o .= '<b>' . $plugin_tx[$plugin]['error'] . '</b>'."\n" .
				    '<ul class="error"><li>'.$ERROR.'</li></ul>'."\n";
			else
			    $o .= '<b>'  . $plugin_tx[$plugin]['csv_written'] .
				    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
				    '.</b>'."\n";
		    }
		    elseif ($ERROR != '')
			$o .= '<b>' . $plugin_tx[$plugin]['error'] . '</b>'."\n" .
				'<ul class="error"><li>'.$ERROR.'</li></ul>'."\n";
		    $o .= '  </td>'."\n".'</tr>'."\n";
		    $o .= '</table>'."\n";
		    break;
	    }
	    break;
	default:
	    $o .= plugin_admin_common($action, $admin, $plugin);
    }
}

?>
