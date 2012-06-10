<?php
/*
CMSimple Plugin - register_mod_XH version 1.3
modified for CMSimple_XH by Gert Ebersbach - http://www.ge-webdesign.de/cmsimpleplugins/

based on register version 2.4 - written by Carsten Heinelt - http://cmsimple.heinelt.eu
Many thanks to Carsten for permission to modify the plugin for CMSimple_XH

v1.3 2012-03-04
- improved session management for login/logout (for parallel or nested installations)
- new config option $plugin_cf['register']['login_all_subsites']
- help variables for config in language files
- added default language file

v1.2.3 utf-8 2011-07-30
replaced deprecated tags
fixed status "activated"

v1.2.2 utf-8 2011-07-25
Fixed bug activation user

v1.2.1 utf-8 2011-06-15
New user administration

v1.1 utf-8 2010-08-15
all files converted to utf-8 without BOM

v1.0 2010-03-03
- code-cleaning - modified for CMSimple_XH
- register function possible to enable/disable ($plugin_cf['register']['allowed_register'])
- horizontal login-form now for areas with a width of 740px or higher (header or footer)
- you can define a login page in the language settings ($plugin_tx['register']['config_login_page'])

==============================================

CMSimple Plugin - register version 2.4
written by Carsten Heinelt - http://cmsimple.heinelt.eu

Changes:
v2.4 22-July-2007:
- updated version number only
v2.3 31-January-2007:
- fixed add button for IE6
v2.2 25-January-2007:
- changed width of fields in user/group administration
- added classes to fields in user/group administration
v2.1 25-January-2007:
- updated version number only
v2.0 22-January-2007:
- reworked user administration
- added group administration
v1.1 19-January-2007:
- implemented new option to allow encrypted passwords in CSV file
v1.0 18-January-2007:
- version number changed as shown in admin mode
- clean-up of undefined variables
v0.4 17-January-2007:
- changed field width in table when editing users file
v0.3 12-January-2007:
- no changes in this file
v0.2 10-January-2007:
- registerAdminUsersForm(): usage of $pth for images folder
- usage of $pth for reading/writing csvfile
v0.1 10-January-2007:
- initial version

utf-8 marker: äöü
*/

function registerAdminGroupsForm($groups) 
{
	GLOBAL $plugin_tx, $tx, $pth, $sn;
	$plugin = basename(dirname(__FILE__),"/");
	$imageFolder = $pth['folder']['plugins'] . $plugin . '/images';

	$o = '';
	$o .= '<h1>' . $plugin_tx[$plugin]['mnu_group_admin'] . '</h1>'."\n";
	$o .= '<div class="register_admin_main">'."\n";
	$o .= '<form method="POST" action="'.$sn.'?&register">'."\n";
	$o .= tag('input type="hidden" value="savegroups" name="action"')."\n";
	$o .= tag('input type="hidden" value="plugin_main" name="admin"')."\n";
	$o .= '<table cellpadding="1" cellspacing="0" style="background:transparent; float: left; width: 250px; border: 0;">'."\n";
	$o .= 
	'<tr style="background:#E6E6E6">'."\n" .
        '  <td align="left">' . $plugin_tx[$plugin]['groupname'] . '</td>'."\n" .
        '  <td align="right">'."\n" .
        tag('input type="image" src="'.$imageFolder.'/add.png" style="width: 16px; height: 16px;" name="add[0]" value="add" alt="Add entry."')."\n" .
        '  </td>'."\n" . 
        '</tr>'."\n";
	$i = 0;
	foreach($groups as $entry) 
	{
		$o .='<tr>'."\n" . 
		'  <td>'.tag('input type="normal" size="10" value="'.$entry['groupname'].'" name="groupname['.$i.']"').'</td>'."\n" .
		'  <td style="text-align: right;">'."\n" .
		tag('input type="image" src="'.$imageFolder.'/delete.png" style="width: 16px; height: 16px;" name="delete['.$i.']" value="delete" alt="Delete Entry"')."\n" .
		'  </td>'."\n" .
		'</tr>'."\n";
		$i++;
	}
	$o .= '<tr>'."\n";
	$o .= '  <td>'.tag('input class="submit" type="submit" value="'.ucfirst($tx['action']['save']).'" name="send"').'</td>'."\n";
	$o .= '</tr>'."\n";
	$o .= '</table>'."\n";
	$o .= '</form>'."\n".'</div>'.tag('br')."\n";
	return $o;
}

function registerAdminUsersForm($users) 
{
	GLOBAL $plugin_tx, $tx, $pth, $sn;
	$plugin = basename(dirname(__FILE__),"/");
	$imageFolder = $pth['folder']['plugins'] . $plugin . '/images';

	$o = '';
	$o .= '<h1>' . $plugin_tx[$plugin]['mnu_user_admin'] . '</h1>'."\n";
	$o .= '<div class="register_admin_main">'."\n";
//	$o .= '<a href="#bottom">nach unten</a>';
	$o .= '<form method="POST" action="'.$sn.'?&register#bottom">'."\n";
	$o .= '<input type="hidden" value="saveusers" name="action" />'."\n";
	$o .= '<input type="hidden" value="plugin_main" name="admin" />'."\n";

	$i = 0;
	foreach($users as $entry) 
	{
		$groupString = implode(",", $entry['accessgroups']);
			$o .= '<p style="text-align: right; margin-bottom: -6px;"><a href="#bottom">nach unten <span style="font-size: 24px;">&dArr;</span></a></p>' . 
		tag('input type="hidden" value="' . $entry['password'] . '" name="oldpassword['.$i.']"')."\n" . tag('br') . 
		'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['username'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['username'] . '" name="username['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>' . 
		'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['password']. ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['password'] . '" name="password['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>' . 
		'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['name'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['name'] . '" name="name['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>' . 
		'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['email'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['email'] . '" name="email['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>' . 
		'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['accessgroups'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $groupString . '" name="accessgroups['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>' . 
		'<div><p style="width: 10em; float: left; clear: both; padding: 0; margin: 0;">' . $plugin_tx[$plugin]['status'] . ':</p>' . "\n" . tag('input type="normal" size="12" style="width: 200px;" value="' . $entry['status'] . '" name="status['.$i.']"')."\n" .  tag('br') . '<div style="clear: both;"></div></div>' . 
		tag('input type="image" src="'.$imageFolder.'/delete.png" style="width: 16px; height: 16px;" name="delete['.$i.']" value="delete" alt="Delete Entry"')."\n" . tag('br') .
		tag('hr');
		$i++;
	}
	
	$o .= '<a name="bottom">'.tag('br').'</a>';
	
	$o .= 
	tag('input type="image" src="'.$imageFolder.'/add.png" style="float: right; width: 16px; height: 16px;" name="add[0]" value="add" alt="Add entry"')."\n";

	$o .= tag('input class="submit" type="submit" value="' . ucfirst($tx['action']['save']).  '" name="send"')."\n";
	$o .= '</form>'."\n".'</div>'.tag('br')."\n";
	return $o;
}

if(isset($register))
{
	global $sn,$sv,$sl,$pth,$plugin;
	if(isset($_POST['admin'])) $admin = $_POST['admin'];
	elseif(isset($_GET['admin'])) $admin = $_GET['admin'];
	else
    $admin = '';

	if(isset($_POST['action'])) $action = $_POST['action'];
	elseif(isset($_GET['action'])) $action = $_GET['action'];
	else
	$action = '';
 
	$ERROR = '';
 
	$o .= print_plugin_admin('on');
	if($admin<>'plugin_main') $o .= plugin_admin_common($action,$admin,$plugin);
	if($admin=='')
	$o .= 
	'<p>Register plugin <b>register_mod_XH 1.3</b></p>
	<p>by Gert Ebersbach: <a href="http://www.ge-webdesign.de/cmsimple/">ge-webdesign.de/cmsimple/</a></p>'."\n".
	'<p>Released: 2012-03-04</p>
	<hr />
	<p><b>based on:</b></p>
	<p>Register plugin version 2.4</p>
	<p>by Carsten Heinelt: <a href="http://cmsimple.heinelt.eu">cmsimple.heinelt.eu</a></p>';

	if ($admin == 'plugin_main') 
	{
		$o .= 
		"\n".'<table class="edit" cellpadding="1" cellspacing="0" style="width: 100%; border: 1px solid;">'."\n" .
		'<tr>'."\n" .
		'  <td><a href="'.$sn.'?&amp;'.$plugin.'&amp;admin=plugin_main&amp;action=editgroups">' . $plugin_tx[$plugin]['mnu_group_admin'] . '</a></td>'."\n" .
		'  <td><a href="'.$sn.'?&amp;'.$plugin.'&amp;admin=plugin_main&amp;action=editusers">' . $plugin_tx[$plugin]['mnu_user_admin'] . '</a></td>'."\n" .
		'</tr>'."\n" .
		'</table>'."\n";
	}
	
	if ($admin == 'plugin_main' && $action == "editusers") 
	{
		// read user file in CSV format separated by colons
		$o .= '<br />'."\n".'<table>'."\n";
		if(is_file($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'])) 
		{
			$users  = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= registerAdminUsersForm($users);
			$o .= '  </td>'."\n".'</tr>'."\n";
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= '<b>' . count($users) . ' ' . $plugin_tx[$plugin]['entries_in_csv'] .
			$pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . '</b>'."\n";
			$o .= '  </td>'."\n".'</tr>'."\n";
		} 
		else 
		{
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= '<b>' . $plugin_tx[$plugin]['err_csv_missing'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' . 
			'</b>'."\n";
			$o .= '  </td>'."\n".'</tr>'."\n";
		}
		$o .= '</table>'."\n";
	} 
	elseif ($admin == 'plugin_main' && $action == "editgroups") 
	{
		// read user file in CSV format separated by colons
		$o .= '<br />'."\n".'<table>'."\n";
		if(is_file($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'])) 
		{
			$groups = registerReadGroups($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile']);
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= registerAdminGroupsForm($groups);
			$o .= '  </td>'."\n".'</tr>'."\n";
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= '<b>' . count($groups) . ' ' . $plugin_tx[$plugin]['entries_in_csv'] .
            $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . '</b>'."\n";
			$o .= '  </td>'."\n".'</tr>'."\n";
			} 
			else 
			{
			$o .= '<tr>'."\n".'  <td>'."\n";
			$o .= '<b>' . $plugin_tx[$plugin]['err_csv_missing'] .
            ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' . 
            '</b>'."\n";
			$o .= '  </td>'."\n".'</tr>'."\n";
			}
		$o .= '</table>'."\n";
	} 
	elseif($admin == 'plugin_main' and $action == 'savegroups') 
	{
		// == Edit Groups =============================================================
		$delete      = isset($_POST['delete'])       ? $_POST['delete']       : '';
		$add         = isset($_POST['add'])          ? $_POST['add']          : '';
		$groupname   = isset($_POST['groupname'])    ? $_POST['groupname']    : '';

		$deleted = false;
		$added   = false;

		$newgroups = array();
		foreach($groupname as $j => $i) 
		{
			if(!preg_match("/^[A-Za-z0-9_-]+$/", $groupname[$j])) 
			$ERROR = '<li>' . $plugin_tx[$plugin]['err_group_illegal'] . '</li>'."\n";

			if(!isset($delete[$j]) || $delete[$j] == '') 
			{
				$entry = array('groupname' => $groupname[$j]);
				$newgroups[] = $entry;
			} 
			else
			{
				$deleted = true;
			}
		}
		if($add <> '') 
		{ 
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
		if(!$deleted && !$added && $ERROR == "") 
		{
			if(!registerWriteGroups($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'], $newgroups)) 
			$ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] . 
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
			'</li>'."\n";

			if($ERROR != '')
			$o .= '<b class="error">' . $plugin_tx[$plugin]['error'] . '</b>'."\n" . '<ul class="error">'."\n".$ERROR.'</ul>'."\n";
			else
			$o .= '<b>'  . $plugin_tx[$plugin]['csv_written'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'] . ')' .
			'.</b>'."\n";
		} 
		elseif($ERROR != '')
		$o .= '<b>' . $plugin_tx[$plugin]['error'] . '</b>'."\n" . '<ul class="error">'."\n".$ERROR.'</ul>'."\n";
		$o .= '  </td>'."\n".'</tr>'."\n";
		$o .= '</table>'."\n";
	} 
	elseif($admin == 'plugin_main' and $action == 'saveusers') 
	{
		// == Edit Users ==============================================================
		if(is_file($pth['folder']['base'] . $plugin_tx['register']['config_groupsfile'])) 
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
		foreach($username as $j => $i)
		{
			if(!isset($delete[$j]) || $delete[$j] == '') 
			{
				$userGroups = explode(",", $groupString[$j]);
				// Error Checking
				$ENTRY_ERROR = '';
				if(preg_match('/true/i',$plugin_cf[$plugin]['encrypt_password']))
				$ENTRY_ERROR .= registerCheckEntry($name[$j], $username[$j], "dummy", "dummy", $email[$j]);
				else
				$ENTRY_ERROR .= registerCheckEntry($name[$j], $username[$j], $password[$j], $password[$j], $email[$j]);
				$ENTRY_ERROR .= registerCheckColons($name[$j], $username[$j], $password[$j], $email[$j]);
				if(registerSearchUserArray($newusers, 'username', $username[$j]) !== false)
				$ENTRY_ERROR .= '<li>' . $plugin_tx[$plugin]['err_username_exists'] . '</li>'."\n";
				if(registerSearchUserArray($newusers, 'email', $email) !== false)
				$ENTRY_ERROR .= '<li>' . $plugin_tx[$plugin]['err_email_exists'] . '</li>'."\n";
				foreach($userGroups as $groupName) 
				{
					if(!in_array($groupName, $groupIds))
					$ENTRY_ERROR .= '<li>' . $plugin_tx[$plugin]['err_group_does_not_exist'] . ' (' . $groupName . ')</li>'."\n";    
				}
				if($ENTRY_ERROR != '')
				$ERROR .= '<li>' . $plugin_tx[$plugin]['error_in_user'] . '"' . $username[$j] . '"' . 
				'<ul class="error"><li>'.$ENTRY_ERROR.'</li></ul></li>'."\n";

				if(preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']) && $password[$j] != $oldpassword[$j])
				$password[$j] = crypt($password[$j], $password[$j]);
				$entry = array(
				'username'     => $username[$j],
				'password'     => $password[$j],
				'accessgroups' => $userGroups,
				'name'         => $name[$j],
				'email'        => $email[$j],
				'status'       => $status[$j]);
				$newusers[] = $entry;
			} 
			else
			{
			$deleted = true;
			}
		}
		if($add <> '') 
		{ 
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
		if(!$deleted && !$added && $ERROR == "") 
		{
			if(!registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $newusers))
			$ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] . 
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' . 
			'</li>'."\n";   

			if($ERROR != '')
			$o .= '<b>' . $plugin_tx[$plugin]['error'] . '</b>'."\n" .
			'<ul class="error"><li>'.$ERROR.'</li></ul>'."\n";
			else
			$o .= '<b>'  . $plugin_tx[$plugin]['csv_written'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
			'.</b>'."\n";
		} 
		elseif($ERROR != '')
		$o .= '<b>' . $plugin_tx[$plugin]['error'] . '</b>'."\n" .
		'<ul class="error"><li>'.$ERROR.'</li></ul>'."\n";
		$o .= '  </td>'."\n".'</tr>'."\n";
		$o .= '</table>'."\n";
	}
}
?>