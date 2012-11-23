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
	    .'<p class="register_license">Redistribution via the internet is expressly prohibited.</p>'."\n"
	    .'<p class="register_license">THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR'
	    .' IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,'
	    .' FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE'
	    .' AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER'
	    .' LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,'
	    .' OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE'
	    .' SOFTWARE.</p>'."\n";
}


/**
 * Returns the requirements information view.
 *
 * @return string  The (X)HTML.
 */
function register_system_check() { // RELEASE-TODO
    global $pth, $tx, $plugin_cf, $plugin_tx;

    define('REGISTER_PHP_VERSION', '4.3.2');
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
    $o .= '<table cellpadding="1" cellspacing="0">'."\n";
    $o .= '<tr>'."\n"
	    .'  <th>' . $plugin_tx[$plugin]['groupname'] . '</th>'."\n"
	    .'  <th>'."\n"
	    .tag('input type="image" src="'.$imageFolder.'/add.png" style="width: 16px; height: 16px;" name="add[0]" value="add" alt="Add entry."')."\n"
	    .'  </th>'."\n"
	    .'</tr>'."\n";
    $i = 0;
    foreach ($groups as $entry) {
	$o .= '<tr>'."\n"
		.'  <td>'.tag('input type="normal" size="10" value="'.$entry['groupname'].'" name="groupname['.$i.']"').'</td>'."\n"
		.'  <td>'."\n"
		.tag('input type="image" src="'.$imageFolder.'/delete.png" style="width: 16px; height: 16px;" name="delete['.$i.']" value="delete" alt="Delete Entry"')."\n"
		.'  </td>'."\n"
		.'</tr>'."\n";
	$i++;
    }
    $o .= '</table>'."\n";
    $o .= tag('input class="submit" type="submit" value="'.ucfirst($tx['action']['save']).'" name="send"')."\n";
    $o .= '</form>'."\n".'</div>'."\n";
    return $o;
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
        if (strpos($key, 'js_') === 0) {
            $txts[] = substr($key, 3) . ':"' . $val . '"';
        } elseif (in_array($key, $jsKeys)) {
	    $txts[] = "$key:\"$val\"";
	}
    }
    
    $hjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins'] . 'register/admin.js"></script>'
        . '<script type="text/javascript">register.tx={' . implode(',', $txts) . '}</script>';
    
    $o = '';
    $o .= '<h1>' . $plugin_tx[$plugin]['mnu_user_admin'] . '</h1>'."\n";
    $o .= '<div class="register_admin_main">'."\n";

    $o .= '<table><tr id="register_user_template" style="display: none">'
            
            . '<td>' . tag('img src="'.$imageFolder.'/delete.png" alt="' . $ptx['user_delete'] . '" title="' . $ptx['user_delete'] . '" onclick="register.removeRow(this); return false"') . '</td>'
            . '<td>' . tag('input type="text" value="" name="name[]"')  . '</td>'
            . '<td>' . tag('input type="text" value="' . $plugin_cf[$plugin]['group_default'] . '" name="accessgroups[]"') . '</td>'
            . '<td>' . Register_statusSelectbox('activated') . '</td>'
            . '</tr>'
            . '<tr style="display: none">'
            . '<td>' . '</td>'
            . '<td>' . tag('input type="text" value="" name="username[]"') .  '</td>'
            . '<td>' . tag('input type="text" value="" name="email[]"') . '</td>'
            . '<td>'
	    . tag('input type="hidden" value="" name="password[]"')
            . tag('input type="hidden" value="" name="oldpassword[]"'). '</td>'
            . '</tr></table>';
    

    $o .= '<div>';
    $o .= '<button onclick="register.addRow()">' . $ptx['user_add'] . '</button>';
    $o .= tag('input id="register_toggle_details" type="checkbox" onclick="register.toggleDetails()" style="padding-left: 1em"');
    $o .= '<label for="register_toggle_details">' . $ptx['details'] . '</label>';
    $o .= Register_groupSelectbox();
    $o .= '</div>';
    
    $o .= '<form id="register_user_form" method="POST" action="'.$sn.'?&amp;register">'."\n";
    $o .= tag('input type="hidden" value="saveusers" name="action"');
    $o .= tag('input type="hidden" value="plugin_main" name="admin"');

    $o .= '<table id="register_user_table">';
    
    $o .= '<tr>'
        . '<th></th>'
        . '<th onclick="register.sort(this, \'name\')" style="cursor: pointer">' . $plugin_tx[$plugin]['name'] . '</th>'
        . '<th onclick="register.sort(this, \'accessgroups\')" style="cursor: pointer">' . $plugin_tx[$plugin]['accessgroups'] . '</th>'
        . '<th onclick="register.sort(this, \'status\')" style="cursor: pointer">' . $plugin_tx[$plugin]['status'] . '</th>'
        . '</tr>'
        . '<tr class="register_second_row">'
        . '<th></th>'
        . '<th onclick="register.sort(this, \'username\')" style="cursor: pointer">' . $plugin_tx[$plugin]['username'] . '</th>'
        . '<th onclick="register.sort(this, \'email\')" style="cursor: pointer">' . $plugin_tx[$plugin]['email'] . '</th>'
        . '<th>' . $plugin_tx[$plugin]['password'] . '</th>'
        . '</tr>';
                
    $i = 0;
    foreach($users as $entry) {
	$groupString = implode(",", $entry['accessgroups']);
	$o .= '<tr id="register_user_' . $i . '">'
		
                . '<td>' . tag('img src="'.$imageFolder.'/delete.png" alt="' . $ptx['user_delete'] . '" title="' . $ptx['user_delete'] . '" onclick="register.removeRow(this); return false"') . '</td>'
		.'<td>' . tag('input type="text" value="' . $entry['name'] . '" name="name['.$i.']"')  . '</td>'
		.'<td>' . tag('input type="text" value="' . $groupString . '" name="accessgroups['.$i.']"') . '</td>'
		//.'<td>' . tag('input type="text" value="' . $entry['status'] . '" name="status['.$i.']"') . '</td>'
		. '<td>' . Register_statusSelectbox($entry['status'], $i) . '</td>'
                . '</tr>'
                . '<tr class="register_second_row">'
                . '<td>' . '<a href="mailto:cmbecker69@gmx.de" onclick="register.mailTo(this)">'
		. tag('image src="' . $imageFolder . '/mail.png" alt="' . $ptx['email'] . '" title="' . $ptx['email'] . '"') . '</a>' . '</td>'
		.'<td>' . tag('input type="text" value="' . $entry['username'] . '" name="username['.$i.']"') .  '</td>'
		.'<td>' . tag('input type="text" value="' . $entry['email'] . '" name="email['.$i.']"') . '</td>'
		.'<td>' . '<button onclick="register.changePassword(this.nextSibling); return false">' . $plugin_tx['register']['change_password'] . '</button>'
		. tag('input type="hidden" value="' . $entry['password'] . '" name="password['.$i.']"')
                . tag('input type="hidden" value="' . $entry['password'] . '" name="oldpassword['.$i.']"') . '</td>'
		. '</tr>';
	$i++;
    }
    
    $o .= '</table>';

    $o .= tag('input class="submit" type="submit" value="' . ucfirst($tx['action']['save']).  '" name="send"')."\n";
    $o .= '</form>'."\n".'</div>'."\n";
    $o .= '<script type="text/javascript">register.init()</script>';
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
		    if (is_file($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']))  {
                        register_lock_users(dirname($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']), LOCK_SH);
			$users  = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);
                        register_lock_users(dirname($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']), LOCK_UN);
			$o .= registerAdminUsersForm($users);
			$o .= '<div class="register_status">' . count($users) . ' ' . $plugin_tx[$plugin]['entries_in_csv'] .
			$pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . '</div>';
		    } else {
			$o .= '<div class="register_status">' . $plugin_tx[$plugin]['err_csv_missing'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
			'</div>';
		    }
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
			    if(preg_match('/true/i',$plugin_cf[$plugin]['encrypt_password']) && $password[$j] == $oldpassword[$j])
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
					'<ul class="error">'.$ENTRY_ERROR.'</ul></li>'."\n";

			    if (empty($ENTRY_ERROR) && preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']) && $password[$j] != $oldpassword[$j])
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

		    $o .= registerAdminUsersForm($newusers);

		    // In case that nothing got deleted or added, store back (save got pressed)
		    if (!$deleted && !$added && $ERROR == "") {
                        register_lock_users(dirname($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']), LOCK_EX);
			if (!registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $newusers))
			    $ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] .
				    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
				    '</li>'."\n";
                        register_lock_users(dirname($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']), LOCK_UN);

			if ($ERROR != '')
			    $e .= $ERROR;
			else
			    $o .= '<div class="register_status">'  . $plugin_tx[$plugin]['csv_written'] .
				    ' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
				    '.</div>'."\n";
		    }
		    elseif ($ERROR != '')
			$e .= $ERROR;
		    break;
	    }
	    break;
	default:
	    $o .= plugin_admin_common($action, $admin, $plugin);
    }
}

?>
