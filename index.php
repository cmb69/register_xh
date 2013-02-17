<?php

/**
 * Front-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2013 Christoph M. Becker (see license.txt)
 */


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


define('REGISTER_VERSION', '1.4pl2');


if (!defined('CMSIMPLE_URL')) {
    define('CMSIMPLE_URL', 'http'
	. (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
	. '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']
	. preg_replace('/index.php$/', '', $_SERVER['PHP_SELF']));
}


if (!class_exists('PasswordHash')) {
    include_once $pth['folder']['plugin'] . 'PasswordHash.php';
}

$_Register_hasher = new PasswordHash(8, true);


/****************************************************************************
 *	Direct Calls															*
 ****************************************************************************/


if($plugin_cf['register']['login_all_subsites'] == 'true')
{
	define('REGISTER_SESSION_NAME', CMSIMPLE_ROOT);
}
else
{
	define('REGISTER_SESSION_NAME', CMSIMPLE_ROOT.$sl);
}

// Start session unless a robot is accessing the page
/*if (preg_match('/Googlebot/i',$_SERVER['HTTP_USER_AGENT']));
else if (preg_match('/MSNbot/i',$_SERVER['HTTP_USER_AGENT']));
else if (preg_match('/slurp/i',$_SERVER['HTTP_USER_AGENT']));
else */if(session_id() == "")
  session_start();

$plugin = basename(dirname(__FILE__),"/");

//// Set default language and config and language definition files to load
//if(!isset($sl)) $sl = $cf['language']['default'];
//$pth['file']['plugins_language'] = $pth['folder']['plugins'] . $plugin . '/languages/' . $sl . '.php';
//$pth['file']['plugins_config']   = $pth['folder']['plugins'] . $plugin . '/config/config.php';

//// Load language and configuration file
//if(!@include($pth['file']['plugins_language'])) die('Plugin Language file ' . $pth['file']['plugins_language'] . ' missing');
//if(!@include($pth['file']['plugins_config'])) die('Plugin config file ' . $pth['file']['plugins_config'] . ' missing');

if(!defined('CAPTCHA_LOADED'))
{
	$captchaInclude = $pth['folder']['plugins'] . $plugin . "/captcha.inc.php";
	if(!@include($captchaInclude)) die('Captcha functions file ' . $captchaInclude . ' missing');
	if(CAPTCHA_LOADED != '1.2') die('Captcha functions already loaded, but of wrong version ' . CAPTCHA_LOADED);
}

// Handling of Captcha Image Generation =========================================
if(isset($_GET['action']))
{
	if($_GET['action'] == 'register_captcha' && isset($_GET['captcha']) && isset($_GET['ip']))
	{
		$fontFolder = $pth['folder']['plugins'] . $plugin . '/font/';
		generateCaptchaImage($_GET['captcha'],
		(int)$plugin_cf[$plugin]['captcha_image_width'],
		(int)$plugin_cf[$plugin]['captcha_image_height'],
		(int)$plugin_cf[$plugin]['captcha_chars'],
		$fontFolder . $plugin_cf[$plugin]['captcha_font'],
		$plugin_cf[$plugin]['captcha_crypt']);
	}
}

// Handling of implicit pages ===================================================
// Please note that all pages listed here have a default variant, but can also
// be defined by the user. In that case the user has to insert the according
// CMSimple scripting functions.

if(!($edit&&$adm) && isset($su))
{
	$pageName = urldecode($su);

	// Handling of registration page
	if($pageName == html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['register']))
	&& $plugin_cf[$plugin]['allowed_register'] == 'true')
	{
		if(!in_array($plugin_tx[$plugin]['register'], $h))
		{
		$title = $plugin_tx[$plugin]['register'];
		$o .= "\n\n".'<h4>' . $title . '</h4>'."\n".'<p>'. $plugin_tx[$plugin]['register_form1'].'</p>'."\n";
		$o .= registerUser();
		}
	// Handling of forgotten password page
	}
	elseif($pageName == html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['forgot_password'])))
	{
		if(!in_array($plugin_tx[$plugin]['forgot_password'], $h))
		{
		$title = $plugin_tx[$plugin]['forgot_password'];
		$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
		$o .= registerForgotPassword();
		}
  // Handling of user preferences page
	} elseif($pageName == html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['user_prefs'])))
	{
		if(!in_array($plugin_tx[$plugin]['user_prefs'], $h))
		{
			$title = $plugin_tx[$plugin]['user_prefs'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= registerUserPrefs();
		}

	// Handling of login error page
	} elseif($pageName == html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['login_error'])))
	{
		header('HTTP/1.1 403 Forbidden');
		if(!in_array($plugin_tx[$plugin]['login_error'], $h))
		{
			$title = $plugin_tx[$plugin]['login_error'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= $plugin_tx[$plugin]['login_error_text'];
		}

	// Handling of logout page
	} elseif($pageName == html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['loggedout'])))
	{
		if(!in_array($plugin_tx[$plugin]['loggedout'], $h))
		{
			$title = $plugin_tx[$plugin]['loggedout'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= $plugin_tx[$plugin]['loggedout_text'];
		}

	// Handling of login page
	}
	elseif($pageName == html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['loggedin'])))
	{
		if(!in_array($plugin_tx[$plugin]['loggedin'], $h))
		{
			$title = $plugin_tx[$plugin]['loggedin'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= $plugin_tx[$plugin]['loggedin_text'];
		}
	} elseif($pageName == html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['access_error'])))
	{
		header('HTTP/1.1 403 Forbidden');
		if(!in_array($plugin_tx[$plugin]['access_error'], $h))
		{
			$title = $plugin_tx[$plugin]['access_error'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= $plugin_tx[$plugin]['access_error_text'];
		}
	}
}

// Handle administrator mode ====================================================
//if(!($adm) &&
//isset($_GET['action']) &&
//$_GET['action'] == "admin_mode" &&
//isset($_SESSION['username'],$_SESSION['fullname'], $_SESSION['email'], $_SESSION['accessgroups'], $_SESSION['sessionnr'], $_SESSION['register_sn']) &&
//$_SESSION['sessionnr'] == session_id() && $_SESSION['register_sn'] == REGISTER_SESSION_NAME &&
//in_array($plugin_cf[$plugin]['group_administrator'], $_SESSION['accessgroups'])===true)
//{
//	setcookie('status','adm');
//	setcookie('passwd',$cf['security']['password']);
//	$adm=true;
//	$edit=true;
//	writelog(date("Y-m-d H:i:s")." from ".sv('REMOTE_ADDR').' logged_in'."\n");
//}

// Handling of login/logout =====================================================
$isSession = session('sessionnr') == session_id() &&
isset($_SESSION['username'],
$_SESSION['fullname'],
$_SESSION['email'],
$_SESSION['accessgroups'],
$_SESSION['sessionnr'],
$_SESSION['register_sn']) &&
$_SESSION['register_sn'] == REGISTER_SESSION_NAME;

if(preg_match('/true/i',$plugin_cf[$plugin]['remember_user']) && isset($_COOKIE['username'], $_COOKIE['password']) && !$isSession) $function = "registerlogin";

if(!$isSession && $function == "registerlogin") registerLogin();
if($isSession && $function == "registerlogout") registerLogout();

if(!($edit&&$adm) && preg_match('/true/i',$plugin_cf[$plugin]['hide_pages']))
{
	if(isset($_SESSION['accessgroups'], $_SESSION['register_sn']) && $_SESSION['register_sn'] == REGISTER_SESSION_NAME) registerRemoveHiddenPages($_SESSION['accessgroups']);
	else
	registerRemoveHiddenPages(array());
}

/****************************************************************************
 *	Function Definitions					*
 ****************************************************************************/

/*
 * Login as user
 */
function registerLogin()
{
	global $_SESSION, $_POST, $_COOKIE, $pth, $plugin_cf, $plugin_tx, $h, $sn, $su, $_Register_hasher;
	$plugin = basename(dirname(__FILE__),"/");
	//$secret = "LoginSecretWord";
	$rememberPeriod = 24*60*60*100;
	$logFile = $pth['folder']['plugins'] . $plugin . '/logfile/logfile.txt';
/*
	if(session('sessionnr') == session_id() &&
	isset(
	$_SESSION['username'],
	$_SESSION['fullname'],
	$_SESSION['email'],
	$_SESSION['accessgroups'],
	$_SESSION['sessionnr'])
	)
    die('A session is already active. You cannot login on top of it!');
*/
	$username = htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : "");
	$password = htmlspecialchars(isset($_POST['password']) ? $_POST['password'] : "");
	$remember = htmlspecialchars(isset($_POST['remember']) ? $_POST['remember'] : "");

	// encrypt password if configured that way
	//if(preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password'])) $password = crypt($password, $password);

	// set username and password in case cookies are set
	if (preg_match('/true/i', $plugin_cf['register']['remember_user'])
	    && isset($_COOKIE['username'], $_COOKIE['password']))
	{
		$username     = $_COOKIE['username'];
		$passwordHash = $_COOKIE['password'];
	}
//	else
//    $passwordHash = md5($secret.$password);

	// read user file in CSV format separated by colons
	$userArray = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);

	// search user in CSV data
	$entry = registerSearchUserArray($userArray, 'username', $username);

	// check password and set session variables
	if ($entry && $entry['username'] == $username
	    && ($entry['status'] == 'activated' || $entry['status'] == 'locked')
	    && (!isset($passwordHash) || $passwordHash == $entry['password'])
	    && (isset($passwordHash)
		|| (preg_match('/true/i', $plugin_cf['register']['encrypt_password'])
		    ? $_Register_hasher->CheckPassword($password, $entry['password'])
		    : $password == $entry['password'])))
	{

// Login Success ------------------------------------------------------------

		// set cookies if requested by user
		if (preg_match('/true/i', $plugin_cf['register']['remember_user'])
		    && isset($_POST['remember']))
		{
			setcookie("username", $username,     time() + $rememberPeriod, "/");
			setcookie("password", $entry['password'], time() + $rememberPeriod, "/");
		}

		$_SESSION['sessionnr']    = session_id();
		$_SESSION['username']     = $entry['username'];
		$_SESSION['fullname']     = $entry['name'];
		$_SESSION['accessgroups'] = $entry['accessgroups'];
		$_SESSION['email']        = $entry['email'];
		$_SESSION['register_sn']  = REGISTER_SESSION_NAME;

		// write line to log-file
		if(preg_match('/true/i',$plugin_cf[$plugin]['logfile']))
		{
		$logfile = fopen($pth['folder']['plugins'].$plugin.'/logfile/logfile.txt', 'a');
		fwrite($logfile, date("Y-m-d H:i:s") . " $username logged in\n");
		fclose($logfile);
		}

		// go to login page if exists or to default page otherwise
		if($plugin_tx[$plugin]['config_login_page'] != '')
		{
			$loginPage = '?'.html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['config_login_page']));
			header('Location: ' . $sn . $loginPage);
			exit;
		}
		else
		{
			$loginPage = '';
			header('Location: ' . $sn . $loginPage);
			exit;
		}

	}
	else
	{
		// Login Error --------------------------------------------------------------
		// clear cookies
		if(isset($_COOKIE['username'], $_COOKIE['password']))
		{
			setcookie("username", "", time() - $rememberPeriod, "/");
			setcookie("password", "", time() - $rememberPeriod, "/");
		}

		// write line to log-file
		if(preg_match('/true/i',$plugin_cf[$plugin]['logfile']))
		{
			$logfile = fopen($logFile, 'a');
			fwrite($logfile, date("Y-m-d H:i:s") . " $username wrong password\n");
			fclose($logfile);
		}

		// go to login error page if exists or to default page otherwise
		$errorTitle = html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['login_error']));
		header('Location: ' . $sn . '?' . $errorTitle);
		exit;
	}
}

/*
 * Logout user
 */
function registerLogout()
{
	global $_SESSION, $_COOKIE, $plugin_cf, $plugin_tx, $sn, $h, $pth;

	$plugin = basename(dirname(__FILE__),"/");
	$rememberPeriod = 24*60*60*100;
	$logFile = $pth['folder']['plugins'] . $plugin . '/logfile/logfile.txt';

	$username = session('username');

	// clear all session variables
	//$_SESSION = array();

	// end session
	unset($_SESSION['username']);
	unset($_SESSION['fullname']);
	unset($_SESSION['email']);
	unset($_SESSION['accessgroups']);
	unset($_SESSION['sessionnr']);
	unset($_SESSION['register_sn']);

	// clear cookies
	if(isset($_COOKIE['username'], $_COOKIE['password']))
	{
		setcookie("username", "", time() - $rememberPeriod, "/");
		setcookie("password", "", time() - $rememberPeriod, "/");
	}
	// write line to log-file
	if(preg_match('/true/i',$plugin_cf[$plugin]['logfile']))
	{
		$logfile = fopen($logFile, 'a');
		fwrite($logfile, date("Y-m-d H:i:s") . " $username logged out\n");
		fclose($logfile);
	}

    // go to logout page if exists or to default page otherwise
	$logoutTitle = html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['loggedout']));
	header('Location: ' . $sn . '?' . $logoutTitle);
	exit;

/*
  // go to logout page if exists or go to page where you came from
  if(in_array($plugin_tx[$plugin]['loggedout'], $h))
    header('Location: '.$sn.'?'. $pageTitle);
  else
    header('Location: ' . $sn);
  exit;
*/

}

/*
 * Remove access restricted pages. Supported are multiple groups per page and
 * multiple user groups.
 */
function registerRemoveHiddenPages($userGroups) {
    global $cl, $c;

    for ($i = 0; $i < $cl; $i++) {
	if (preg_match('/(?:#CMSimple |{{{PLUGIN:)access\((.*?)\);(?:#|}}})/isu', $c[$i], $matches)) {
            if ($arg = trim($matches[1], "\"'")) {
		$groups = array_map('trim', explode(',', $arg));
		unset($_SESSION['page']); // TODO: what's this?
		if (count(array_intersect($groups, $userGroups)) == 0) {
		    $c[$i]= "#CMSimple hide# {{{PLUGIN:access('$arg');}}}";
		}
	    }
	}
    }
}

/*
 * Access function to be called from inside CMSimple scripting tag.
 */
function access($groupString)
{
	global $_SESSION, $plugin_tx, $sn;
	$plugin = basename(dirname(__FILE__),"/");

	// remove spaces etc.
	$groupString = preg_replace("/[ \t\r\n]*/", '', $groupString);
	$groupNames = explode(",", $groupString);

	$o = '';
	if(!isset(
	$_SESSION['username'],
	$_SESSION['fullname'],
	$_SESSION['email'],
	$_SESSION['accessgroups'],
	$_SESSION['sessionnr'],
	$_SESSION['register_sn']) ||
	$_SESSION['sessionnr'] != session_id() ||
	count(array_intersect($groupNames, $_SESSION['accessgroups']))==0 ||
	$_SESSION['register_sn'] != REGISTER_SESSION_NAME)
	{

		// go to access error page
		$pageTitle = html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['access_error']));
		header('Location: '.$sn.'?'. $pageTitle);
		exit;
	}
	return $o;
}

/*
 * Get a session variable
 */
function session($s)
{
	global $_SESSION;
	if(isset($_SESSION[$s]))
	return $_SESSION[$s];
	else
	return'';
}

/*
 *  Activate user in user CSV file.
 */
function registerActivateUser($user, $captcha)
{
	GLOBAL $plugin_tx,$plugin_cf,$pth;
	$plugin = basename(dirname(__FILE__),"/");
	$ERROR = '';
	$o ='';

	// read user file in CSV format separated by colons
	$userArray = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);

	// check if user or other user for same email address exists
	$entry = registerSearchUserArray($userArray, 'username', $user);
	if($entry === false)
	{
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_username_notfound'] . $user . '</li>'."\n";
	}
	else
	{
		if(!isset($entry['status']) || $entry['status'] == "")
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_status_empty'] . '</li>'."\n";
		$status = md5_decrypt($captcha, $plugin_cf[$plugin]['captcha_crypt']);
		if($status != $entry['status'])
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_status_invalid'] . "($status&ne;" . $entry['status'] . ')</li>'."\n";
	}

	if($ERROR != "")
	{
		$o .= '<span class="regi_error">' . $plugin_tx[$plugin]['error'] . '</span>'."\n".
		'<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
	}
	else
	{
		$entry['status'] = "activated";
		$entry['accessgroups'] = array($plugin_cf[$plugin]['group_activated']);
		$userArray = registerReplaceUserEntry($userArray, $entry);
		registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'],$userArray);
		$o .= '<b>' . $plugin_tx[$plugin]['activated'] . '</b>'."\n";
	}
  return $o;
}

/*
 *  Read a group csv file into an array.
 */
function registerReadGroups($filename)
{
	GLOBAL $plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");
	$groupArray = array();
	if(is_file($filename))
	{
		$fp = fopen($filename, "r");
		while (!feof($fp))
		{
			$line = fgets($fp, 4096);
			if($line != "" && !strpos($line, "//"))
			{
				list($groupname,$dummy) = explode("\n", $line);
				// line must not start with '//' and all fields must be set
				if(strpos($groupname, "//") === false && $groupname != "")
				{
					$entry = array('groupname' => $groupname);
					$groupArray[] = $entry;
				}
			}
		}
	}
	fclose($fp);
	return $groupArray;
}

/*
 *  Write an array into a group csv file.
 */
function registerWriteGroups($filename, $array)
{
	GLOBAL $plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");

	// remove old backup
	if(is_file($filename . ".bak"))
    unlink($filename . ".bak");
	// create new backup
	$permissions = false;
	$owner = false;
	$group = false;
	if(is_file($filename))
	{
		$owner = fileowner($filename);
		$group = filegroup($filename);
		$permissions = fileperms($filename);
		rename($filename, $filename . ".bak");
	}

	$fp = fopen($filename, "w");
	if($fp === false)
    return false;

	// write comment line to file
	$line = '// Register Plugin Group Definitions'."\n" . '// Line Format:'."\n" . '// groupname'."\n";
	if(!fwrite($fp, $line))
	{
		fclose($fp);
		return false;
	}

	foreach($array as $entry)
	{
		$groupname = $entry['groupname'];
		$line = "$groupname\n";
		if(!fwrite($fp, $line))
		{
			fclose($fp);
			return false;
		}
	}
	fclose($fp);

	// change owner, group and permissions of new file to same as backup file
	if($owner !== false) $chown = chown($filename, $owner);
	if($group !== false) $chgrp = chgrp($filename, $group);
	if($permissions !== false) $chmod = chmod($filename, $permissions);
	return true;
}

/*
 *  Read a csv file into an array.
 */
function registerReadUsers($filename)
{
	GLOBAL $plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");
	$userArray = array();

	if(is_file($filename))
	{
		$fp = fopen($filename, "r");
		while (!feof($fp))
		{
			$line = fgets($fp, 4096);
			if($line != "" && strpos($line, '//')=== false)
			{
				list($username,$password,$accessgroups,$name,$email,$status,$dummy) = preg_split('/[:\n]/', $line);
				// line must not start with '//' and all fields must be set
				if(
				$username != "" &&
				$password != "" &&
				$accessgroups != "" &&
				$name != "" &&
				$email != "" &&
				$status != "")
				{
					$entry = array(
					'username' => $username,
					'password' => $password,
					'accessgroups' => explode(',', $accessgroups),
					'name' => $name,
					'email' => $email,
					'status' => $status);
					$userArray[] = $entry;
				}
			}
		}
	}
	fclose($fp);
	return $userArray;
}

/*
 *  Write an array into a csv file.
 */
function registerWriteUsers($filename, $array)
{
	GLOBAL $plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");

	// remove old backup
	if(is_file($filename . ".bak")) unlink($filename . ".bak");

	// create new backup
	$permissions = false;
	$owner = false;
	$group = false;
	if(is_file($filename))
	{
		$owner = fileowner($filename);
		$group = filegroup($filename);
		$permissions = fileperms($filename);
		rename($filename, $filename . ".bak");
	}

	$fp = fopen($filename, "w");
	if($fp === false)
    return false;

	// write comment line to file
	$line =
	'// Register Plugin user Definitions'."\n" .
	'// Line Format:'."\n" .
	'// login:password:accessgroup1,accessgroup2,...:fullname:email:status'."\n";
	if(!fwrite($fp, $line))
	{
		fclose($fp);
		return false;
	}

	foreach($array as $entry)
	{
		$username = $entry['username'];
		$password = $entry['password'];
		$accessgroups = implode(',', $entry['accessgroups']);
		$fullname = $entry['name'];
		$email = $entry['email'];
		$status = $entry['status'];
		$line = "$username:$password:$accessgroups:$fullname:$email:$status"."\n";
		if(!fwrite($fp, $line))
		{
		fclose($fp);
		return false;
		}
	}
	fclose($fp);

	// change owner, group and permissions of new file to same as backup file
	if($owner !== false) $chown = chown($filename, $owner);
	if($group !== false) $chgrp = chgrp($filename, $group);
	if($permissions !== false) $chmod = chmod($filename, $permissions);
	return true;
}

/*
 *  Add new user to array.
 */
function registerAddUser($array, $username, $password, $accessgroups, $name, $email, $status)
{
	$entry = array(
	'username' => $username,
	'password' => $password,
	'accessgroups' => $accessgroups,
	'name' => $name,
	'email' => $email,
	'status' => $status);

	$array[] = $entry;
	return $array;
}

/*
 *  Search array of user entries for key and value.
 *  Arguments:
 *   $array		array of user entries
 *   $key		key in user entry to look for
 *   $value		value to match user entry key
 *
 *  Returns:
 *   false		in case of no value found
 *   $entry		found user entry
 */
function registerSearchUserArray($array, $key, $value)
{
	foreach($array as $entry)
	{
		if(isset($entry[$key]) && $entry[$key] == $value)
		return $entry;
	}
	return false;
}

/*
 *  Replace user entry in array.
 *  Arguments:
 *   $array		array of user entries
 *   $newentry	user entry to replace
 *
 *  Returns:
 *   $newarray	updated array
 */
function registerReplaceUserEntry($array, $newentry)
{
	$newarray = array();
	$username = $newentry['username'];
	foreach($array as $entry)
	{
	if(isset($entry['username']) && $entry['username'] == $username) $newarray[] = $newentry;
	else
	$newarray[] = $entry;
	}
	return $newarray;
}

/*
 *  Delete user entry in array.
 *  Arguments:
 *   $array		array of user entries
 *   $username	username for which entry should get removed in array
 *
 *  Returns:
 *   $newarray	updated array
 */
function registerDeleteUserEntry($array, $username)
{
	$newarray = array();
	foreach($array as $entry)
	{
		if(isset($entry['username']) && $entry['username'] != $username) $newarray[] = $entry;
	}
	return $newarray;
}

/*
 *  Check entry for completeness.
 */
function registerCheckEntry($name, $username, $password1, $password2, $email)
{
	GLOBAL $plugin_tx,$plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");
	$ERROR = '';

	// check for empty or illegal/wrong fields
	if($name == '' || !preg_match("/^\S+( \S+)+$/", $name))
	$ERROR .= '<li>' . $plugin_tx[$plugin]['err_name'] . '</li>'."\n";
	if($username == '')
	$ERROR .= '<li>' . $plugin_tx[$plugin]['err_username'] . '</li>'."\n";
	elseif(!preg_match("/^[A-Za-z0-9_]+$/", $username))
	$ERROR .= '<li>' . $plugin_tx[$plugin]['err_username_illegal'] . '</li>'."\n";
	if($password1 == '')
	$ERROR .= '<li>' . $plugin_tx[$plugin]['err_password'] . '</li>'."\n";
	elseif(!preg_match("/^[A-Za-z0-9_]+$/", $password1))
	$ERROR .= '<li>' . $plugin_tx[$plugin]['err_password_illegal'] . '</li>'."\n";
	if($password2 == '' || $password1 != $password2)
    $ERROR .= '<li>' . $plugin_tx[$plugin]['err_password2'] . '</li>'."\n";
	if($email == '')
    $ERROR .= '<li>' . $plugin_tx[$plugin]['err_email'] . '</li>'."\n";
	elseif (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i",$email))
	$ERROR .= '<li>' . $plugin_tx[$plugin]['err_email_invalid'] . '</li>'."\n";
	return $ERROR;
}

/*
 *  Check entry for contained colons.
 */
function registerCheckColons($name, $username, $password1, $email)
{
	GLOBAL $plugin_tx,$plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");
	$ERROR = '';

	if(strpos($name, ":") !== false)
	$ERROR .= '<li>' . $plugin_tx[$plugin]['name'] . ' ' . $plugin_tx[$plugin]['err_colon'] . '</li>'."\n";
	if(strpos($username, ":") !== false)
	$ERROR .= '<li>' . $plugin_tx[$plugin]['username'] . ' ' . $plugin_tx[$plugin]['err_colon'] . '</li>'."\n";
	if(strpos($password1, ":") !== false)
	$ERROR .= '<li>' . $plugin_tx[$plugin]['password'] . ' ' . $plugin_tx[$plugin]['err_colon'] . '</li>'."\n";
	if(strpos($email, ":") !== false)
	$ERROR .= '<li>' . $plugin_tx[$plugin]['email'] . ' ' . $plugin_tx[$plugin]['err_colon'] . '</li>'."\n";
	return $ERROR;
}

/*
 *  Create HTML registration form.
 */
function registerForm($code, $name, $username, $password1, $password2, $email)
{
	GLOBAL $plugin_tx, $plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");

  $o = "\n\n" .
	'<form method="post" action="' . htmlspecialchars(sv('REQUEST_URI')) . '" target="_self">' . "\n" .
	'<div class="regi_register">'. "\n" .'<table>' . "\n" .
	'<tr>' . "\n".
	'  <td>'.tag('input type="hidden" name="action" value="register_user"') . "\n".
	tag('input type="hidden" name="captcha" value="' . md5_encrypt($code, $plugin_cf[$plugin]['captcha_crypt']) . '"') . "\n".
	$plugin_tx[$plugin]['name'] . '</td>' . "\n".
	'  <td colspan="2">'.tag('input class="text" name="name" type="text" size="35" value="' . $name . '"').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['username'] . '</td>' . "\n".
	'  <td colspan="2">'.tag('input class="text" name="username" type="text" size="10" value="' . $username . '"').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['password'] . '</td>' . "\n".
	'  <td colspan="2">'.tag('input class="text" name="password1" type="password" size="10" value="' . $password1 . '"').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['password2'] . '</td>' . "\n".
	'  <td colspan="2">'.tag('input class="text" name="password2" type="password" size="10" value="' . $password2 . '"').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['email'] . '</td>' . "\n".
	'  <td colspan="2">'.tag('input class="text" name="email" type="text" size="35" value="' . $email . '"').'</td>' . "\n";

	if($plugin_cf[$plugin]['captcha_mode'] != "none")
	{
		$o .=
		'<tr>' . "\n".
		'  <td>' . $plugin_tx[$plugin]['code'] . '</td>' . "\n".
		'  <td>'.tag('input class="text" name="register_validate" type="text" size="10" value=""').'</td>' . "\n".
		'  <td>' .
		getCaptchaHtml(
		"register_captcha", $code,
		(int)$plugin_cf[$plugin]['captcha_image_width'],
		(int)$plugin_cf[$plugin]['captcha_image_height'],
		$plugin_cf[$plugin]['captcha_crypt'],
		$plugin_cf[$plugin]['captcha_mode']) .
		'</td>' . "\n".
		'</tr>' . "\n";
	}
	$o .=
	'<tr>' . "\n".
	'  <td colspan="3">'.tag('input class="submit" type="submit" value="' . $plugin_tx[$plugin]['register'] . '"') . '</td>' . "\n".'</tr>'."\n".'</table>'."\n";

	$o .= '<p>'.$plugin_tx[$plugin]['register_form2'].'</p>' . "\n" . '</div>' . "\n" . '</form>'."\n";
	return $o;
}


/*
 * Function to create and handle register form (Top Level Function).
 *
 */
function registerUser()
{
	global $su, $pth, $sn, $plugin_tx, $plugin_cf, $_Register_hasher;

	$plugin = basename(dirname(__FILE__),"/");

	// In case user is logged in, no registration page is shown
	if(session('sessionnr') == session_id() &&
	isset(
	$_SESSION['username'],
	$_SESSION['fullname'],
	$_SESSION['email'],
	$_SESSION['accessgroups'],
	$_SESSION['sessionnr'],
	$_SESSION['register_sn']) &&
	$_SESSION['register_sn'] == REGISTER_SESSION_NAME)
	{
		header('Location: ' . $sn);
		exit;
	}

	checkGD();

	$ERROR = '';
	$o = '';

	// Get form data if available
	$action    = isset($_POST['action']) ? $_POST['action'] : "";
	$name      = htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : "");
	$username  = htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : "");
	$password1 = htmlspecialchars(isset($_POST['password1']) ? $_POST['password1'] : "");
	$password2 = htmlspecialchars(isset($_POST['password2']) ? $_POST['password2'] : "");
	$email     = htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : "");
	$captcha   = isset($_POST['captcha']) ? $_POST['captcha'] : "";
	$register_validate  = isset($_POST['register_validate']) ? $_POST['register_validate'] : "";
	$REMOTE_ADDR = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";

	// Form Handling
	if(isset($_POST['action']) && $action == "register_user")
	{
		$ERROR .= registerCheckEntry($name, $username, $password1, $password2, $email);
		if($plugin_cf[$plugin]['captcha_mode'] != "none")
		{
			if($plugin_cf[$plugin]['captcha_mode'] == "image")
			$code = md5_decrypt($captcha, $plugin_cf[$plugin]['captcha_crypt']);
			elseif($plugin_cf[$plugin]['captcha_mode'] == "formula") {
                            $formula = md5_decrypt($captcha, $plugin_cf[$plugin]['captcha_crypt']);
                            $addends = explode('+', $formula);
                            $addends = array_filter($addends, create_function('$x', 'return is_numeric(trim($x));'));
                            $code = array_sum($addends);
			}

			if($register_validate == '' || strtolower($register_validate) != $code)
			$ERROR .= '<li>' . $plugin_tx[$plugin]['err_validation'] . '</li>';
		}

		// check for colons in fields
		$ERROR .= registerCheckColons($name, $username, $password1, $email);

		// read user file in CSV format separated by colons
		$userArray = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);

		// check if user or other user for same email address exists
		if(registerSearchUserArray($userArray, 'username', $username) !== false)
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_username_exists'] . '</li>'."\n";
		if(registerSearchUserArray($userArray, 'email', $email) !== false)
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_email_exists'] . '</li>'."\n";

		// generate another captcha code for the user activation email
		$status = generateRandomCode((int)$plugin_cf[$plugin]['captcha_chars']);
		if(preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']))
		$userArray = registerAddUser($userArray, $username, $_Register_hasher->HashPassword($password1),
		array($plugin_cf[$plugin]['group_default']), $name, $email, $status);
		else
		$userArray = registerAddUser($userArray, $username, $password1,
		array($plugin_cf[$plugin]['group_default']), $name, $email, $status);

		// write CSV file if no errors occurred so far
		if($ERROR=="" && !registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $userArray))
		$ERROR .= '<li>' .
		$plugin_tx[$plugin]['err_cannot_write_csv'] .
		' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
		'</li>'."\n";

		if($ERROR != "")
		{
			$o .= '<span class="regi_error">' . $plugin_tx[$plugin]['error'] . '</span>'."\n" .
			'<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
		}
			else
		{

			// prepare email content for registration activation
			$content = $plugin_tx[$plugin]['emailtext1'] . "\n\n" .
			' ' . $plugin_tx[$plugin]['name'] . ": $name \n" .
			' ' . $plugin_tx[$plugin]['username'] . ": $username \n" .
			//' ' . $plugin_tx[$plugin]['password'] . ": $password1 \n" .
			' ' . $plugin_tx[$plugin]['email'] . ": $email \n" .
			' ' . $plugin_tx[$plugin]['fromip'] . ": $REMOTE_ADDR \n\n" .
			$plugin_tx[$plugin]['emailtext2'] . "\n\n" .
			CMSIMPLE_URL . '?' . $su . '&' .
			'action=registerActivateUser&username='.$username.'&captcha=' .
			md5_encrypt($status, $plugin_cf[$plugin]['captcha_crypt']);

			// send activation email
			register_mail
			(
			$email,
			$plugin_tx[$plugin]['emailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
			$content,
			array('From: ' . $plugin_cf[$plugin]['senderemail'],
			    'Bcc: '  . $plugin_cf[$plugin]['senderemail'])
			);
			$o .= '<b>' . $plugin_tx[$plugin]['registered'] . '</b>';
			return $o;
		}
	} elseif(isset($_GET['action']) && $_GET['action'] == 'registerActivateUser' &&
           isset($_GET['username']) &&
           isset($_GET['captcha']))
	{
    $o .= registerActivateUser($_GET['username'], $_GET['captcha']);
    return $o;
	}

	// Form Creation
	if($captcha == '' || md5_decrypt($captcha, $plugin_cf[$plugin]['captcha_crypt']) == '')
	{
		if($plugin_cf[$plugin]['captcha_mode'] == "image") $code = generateRandomCode((int)$plugin_cf[$plugin]['captcha_chars']);
		else if($plugin_cf[$plugin]['captcha_mode'] == "formula") $code = generateCaptchaFormula((int)$plugin_cf[$plugin]['captcha_chars']);
		else
		$code = '';
	}
	else
	$code = md5_decrypt($captcha, $plugin_cf[$plugin]['captcha_crypt']);
	$o .= registerForm($code, $name, $username, $password1, $password2, $email);
	return $o;
}

/*
 * Create form to request reminder email for user/password.
 */
function registerForgotForm($email)
{
	GLOBAL $plugin_tx,$plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");

	$o =
    '<form method="post" action="' . htmlspecialchars(sv('REQUEST_URI')) . '" target="_self">' . "\n" .
    '<table class="regi_register">' . "\n" .
    '<tr>' . "\n".
    '  <td>'.tag('input type="hidden" name="action" value="forgotten_password"') . $plugin_tx[$plugin]['email'] . '</td>' . "\n".
    '  <td>'.tag('input class="text" name="email" type="text" size="35" value="' . $email . '"').'</td>' . "\n".
    '  <td>'.tag('input class="submit" type="submit" value="' . $plugin_tx[$plugin]['send'] . '"').'</td>' . "\n".
    '</tr>'."\n".'</table>'."\n".'</form>' . "\n";
	return $o;
}

/*
 * Function to create and handle forgotten password form (Top Level Function)
 */
function registerForgotPassword()
{
	global $pth, $sn, $su, $plugin_tx, $plugin_cf, $_Register_hasher;

	$plugin = basename(dirname(__FILE__),"/");

	// In case user is logged in, no registration page is shown
	if(session('sessionnr') == session_id() &&
	isset
	(
	$_SESSION['username'],
	$_SESSION['fullname'],
	$_SESSION['email'],
	$_SESSION['accessgroups'],
	$_SESSION['sessionnr'],
	$_SESSION['register_sn']
	) &&
	$_SESSION['register_sn'] == REGISTER_SESSION_NAME
	)
	{
		header('Location: ' . $sn);
		exit;
	}

	checkGD();

	$ERROR = '';
	$o = '<p>' . $plugin_tx[$plugin]['reminderexplanation'] . '</p>'."\n";

	// Get form data if available
	$action    = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
	$email     = htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : "");

	// Form Handling
	if(isset($_POST['action']) && $action == "forgotten_password")
	{
		if($email == '') $ERROR .= '<li>' . $plugin_tx[$plugin]['err_email'] . '</li>'."\n";
		elseif (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i",$email))
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_email_invalid'] . '</li>'."\n";

		// read user file in CSV format separated by colons
		$userArray = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);

		// search user for email
		$user = registerSearchUserArray($userArray, 'email', $email);
		if(!$user) $ERROR .= '<li>' . $plugin_tx[$plugin]['err_email_does_not_exist'] . '</li>'."\n";

		// in case of encrypted password a new random password will be generated
		// and its value be written back to the CSV file
		//if($ERROR=="" && preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']))
		//{
		//	$password = generateRandomCode(8);
		//	$user['password'] = crypt($password, $password);
		//	$userArray = registerReplaceUserEntry($userArray, $user);
		//	if(!registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $userArray))
		//	$ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] .
		//	' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
		//	'</li>'."\n";
		//}
		//else
		$password = $user['password'];

		if($ERROR != "")
		{
		$o .= '<span class="regi_error">' . $plugin_tx[$plugin]['error'] . '</span>'."\n" .
		'<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
		}
		else
		{
			// prepare email content for user data email
			$content = $plugin_tx[$plugin]['emailtext1'] . "\n\n"
			    . ' ' . $plugin_tx[$plugin]['name'] . ": " . $user['name'] . "\n"
			    . ' ' . $plugin_tx[$plugin]['username'] . ": " . $user['username'] . "\n";
			if (!preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password'])) {
			    $content .= ' ' . $plugin_tx[$plugin]['password'] . ": " . $password . "\n";
			}
			$content .= ' ' . $plugin_tx[$plugin]['email'] . ": " . $user['email'] . "\n";
			if (preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password'])) {
			    $content .= "\n" . $plugin_tx[$plugin]['emailtext3'] ."\n\n"
				. CMSIMPLE_URL . '?' . $su . '&'
				. 'action=registerResetPassword&username=' . urlencode($user['username']) . '&captcha='
				. urlencode($user['password']);
			}

			// send reminder email
			register_mail
			(
			$email,
			$plugin_tx[$plugin]['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
			$content,
			array('From: ' . $plugin_cf[$plugin]['senderemail'])
			);
			$o .= '<b>' . $plugin_tx[$plugin]['remindersent'] . '</b>';
			return $o;
		}
	} elseif (isset($_GET['action']) && $action == 'registerResetPassword'
		  && preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']))
	{
		// read user file in CSV format separated by colons
		$userArray = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);

		// search user for email
		$user = registerSearchUserArray($userArray, 'username', $_GET['username']);
		if(!$user) $ERROR .= '<li>' . $plugin_tx[$plugin]['err_username_does_not_exist'] . '</li>'."\n";

		if ($user['password'] != stsl($_GET['captcha'])) {
		    $ERROR .= '<li>' . $plugin_tx[$plugin]['err_status_invalid'] . '</li>';
		}

		// in case of encrypted password a new random password will be generated
		// and its value be written back to the CSV file
		if($ERROR=="")
		{
			$password = generateRandomCode(8);
			$user['password'] = $_Register_hasher->HashPassword($password);
			$userArray = registerReplaceUserEntry($userArray, $user);
			if(!registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $userArray))
			$ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
			'</li>'."\n";
		}

		if($ERROR != "")
		{
		$o .= '<span class="regi_error">' . $plugin_tx[$plugin]['error'] . '</span>'."\n" .
		'<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
		}
		else
		{
			// prepare email content for user data email
			$content = $plugin_tx[$plugin]['emailtext1'] . "\n\n"
			    . ' ' . $plugin_tx[$plugin]['name'] . ": " . $user['name'] . "\n"
			    . ' ' . $plugin_tx[$plugin]['username'] . ": " . $user['username'] . "\n"
			    . ' ' . $plugin_tx[$plugin]['password'] . ": " . $password . "\n"
			    . ' ' . $plugin_tx[$plugin]['email'] . ": " . $user['email'] . "\n";

			// send reminder email
			register_mail
			(
			$user['email'],
			$plugin_tx[$plugin]['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
			$content,
			array('From: ' . $plugin_cf[$plugin]['senderemail'])
			);
			$o .= '<b>' . $plugin_tx[$plugin]['remindersent'] . '</b>';
			return $o;
		}
	}

	// Form Creation
	$o .= registerForgotForm($email);
	return $o;
}

/*
 *  Create HTML user preferences form.
 */

function registerUserPrefsForm($name, $email)
{
	GLOBAL $plugin_tx,$plugin_cf;
	$plugin = basename(dirname(__FILE__),"/");

	$o = '<p>' . $plugin_tx[$plugin]['changeexplanation'] . '</p>'."\n";
	$o .=
	'<div class="regi_settings">' . "\n".
	'<form method="post" action="' . htmlspecialchars(sv('REQUEST_URI')) . '" target="_self">' ."\n".
	tag('input type="hidden" name="action" value="edit_user_prefs"') . "\n".
	'<table style="margin: auto;">' . "\n" .
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['name'] . '</td>' . "\n".
	'  <td>'.tag('input class="text" name="name" type="text" size="35" value="' . $name . '"').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['oldpassword'] . '</td>' . "\n".
	'  <td>'.tag('input class="text" name="oldpassword" type="password" size="10" value=""').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['password'] . '</td>' . "\n".
	'  <td>'.tag('input class="text" name="password1" type="password" size="10" value=""').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['password2'] . '</td>' . "\n".
	'  <td>'.tag('input class="text" name="password2" type="password" size="10" value=""').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td>' . $plugin_tx[$plugin]['email'] . '</td>' . "\n".
	'  <td>'.tag('input class="text" name="email" type="text" size="35" value="' . $email . '"').'</td>' . "\n".
	'</tr>' . "\n".
	'<tr>' . "\n".
	'  <td colspan="2">'."\n" .
	tag('input class="submit" name="submit" type="submit" value="'.$plugin_tx[$plugin]['change'].'"')."\n".
	tag('input class="submit" name="delete" type="submit" value="'.$plugin_tx[$plugin]['user_delete'].'"')."\n".
	'  </td>'."\n".
	'</tr>'."\n".
	'</table>'."\n".'</form>'."\n".'</div>';
	return $o;
}

/*
 * Function to create and handle user preferences form (Top Level Function).
 *
 */
function registerUserPrefs()
{
	GLOBAL $plugin_tx,$plugin_cf,$pth,$_SESSION, $sn, $_Register_hasher;
	$plugin = basename(dirname(__FILE__),"/");

	$ERROR = '';
	$o = '';

	if(!isset($_SESSION['username'],$_SESSION['fullname'],$_SESSION['email'],$_SESSION['sessionnr'],$_SESSION['register_sn']) ||
	session('sessionnr') != session_id() || $_SESSION['register_sn'] != REGISTER_SESSION_NAME)
	{
		return $plugin_tx[$plugin]['access_error_text'];
	}

	// Get form data if available
	$action    = isset($_POST['action']) ? $_POST['action'] : "";
	$oldpassword  = htmlspecialchars(isset($_POST['oldpassword']) ? $_POST['oldpassword'] : "");
	$name      = htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : "");
	$password1 = htmlspecialchars(isset($_POST['password1']) ? $_POST['password1'] : "");
	$password2 = htmlspecialchars(isset($_POST['password2']) ? $_POST['password2'] : "");
	$email     = htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : "");
	$REMOTE_ADDR = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";

	// set user name from session
	$username = isset($_SESSION['username']) ? $_SESSION['username'] : "";

	// read user file in CSV format separated by colons
	$userArray = registerReadUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile']);

	// search user in CSV data
	$entry = registerSearchUserArray($userArray, 'username', $username);
	if($entry === false)
	die($plugin_tx[$plugin]['err_username_does_not_exist'] . " ('" . $username . "')");

	// Test if user is locked
	if ($entry['status'] == "locked")
	{
	$o .= '<span class=regi_error>' . $plugin_tx[$plugin]['user_locked'] . ':' .$username.'</span>'."\n";
	return $o;
	}

	// Form Handling - Change User ================================================
	if($username!="" && isset($_POST['submit']) && $action == "edit_user_prefs")
	{
		// check that old password got entered correctly
		if(!preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']) &&
		$oldpassword != $entry['password'])
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_old_password_wrong'] . '</li>'."\n";
		elseif(preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']) &&
		!$_Register_hasher->CheckPassword($oldpassword, $entry['password']))
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_old_password_wrong'] . '</li>'."\n";

		if($password1 == "" && $password2 == "")
		{
			$password1 = $oldpassword;
			$password2 = $oldpassword;
		}
		if($email == "") $email = $entry['email'];
		if($name == "") $name = $entry['name'];

		$ERROR .= registerCheckEntry($name, $username, $password1, $password2, $email);

		// check for colons in fields
		$ERROR .= registerCheckColons($name, $username, $password1, $email);
		$oldemail = $entry['email'];

		// read user entry, update it and write it back to CSV file
		if($ERROR=="")
		{
			if(preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']))
			$entry['password'] = $_Register_hasher->HashPassword($password1);
			else
			$entry['password'] = $password1;
			$entry['email']    = $email;
			$entry['name']     = $name;
			$userArray = registerReplaceUserEntry($userArray, $entry);

			// write CSV file if no errors occurred so far
			if(!registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $userArray))
			$ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
			'</li>'."\n";
		}

		if($ERROR != '')
		{
			$o .= '<span class="regi_error">' . $plugin_tx[$plugin]['error'] . '</span>'."\n" .
			'<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
		}
		else
		{

			// update session variables
			$_SESSION['email'] = $email;
			$_SESSION['fullname'] = $name;

			// prepare email for user information about updates
			$content = $plugin_tx[$plugin]['emailprefsupdated'] . "\n\n" .
			' ' . $plugin_tx[$plugin]['name'] . ': '.$name."\n" .
			' ' . $plugin_tx[$plugin]['username'] . ': '.$username."\n" .
			//' ' . $plugin_tx[$plugin]['password'] . ': '.$password1."\n" .
			' ' . $plugin_tx[$plugin]['email'] . ': '.$email."\n" .
			' ' . $plugin_tx[$plugin]['fromip'] . ': '.$REMOTE_ADDR."\n";

			// send update email
			register_mail
			(
			$email,
			$plugin_tx[$plugin]['prefsemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
			$content,
			array('From: ' . $plugin_cf[$plugin]['senderemail'],
			    'Cc: '  . $oldemail,
			    'Bcc: '  . $plugin_cf[$plugin]['senderemail'])
			);
			$o .= '<b>' . $plugin_tx[$plugin]['prefsupdated'] . '</b>';
			return $o;
		}
	}
	elseif($username!='' && isset($_POST['delete']) && $action == "edit_user_prefs")
	{

		// Form Handling - Delete User ================================================
		// check that old password got entered correctly
		if(!preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password']) && $oldpassword != $entry['password'])
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_old_password_wrong'] . '</li>'."\n";
		elseif(preg_match('/true/i', $plugin_cf[$plugin]['encrypt_password'])
		       && !$_Register_hasher->CheckPassword($oldpassword, $entry['password']))
		$ERROR .= '<li>' . $plugin_tx[$plugin]['err_old_password_wrong'] . '</li>'."\n";

		// read user entry, update it and write it back to CSV file
		if($ERROR=="")
		{
			$userArray = registerDeleteUserEntry($userArray, $username);
			if(!registerWriteUsers($pth['folder']['base'] . $plugin_tx['register']['config_usersfile'], $userArray))
			$ERROR .= '<li>' . $plugin_tx[$plugin]['err_cannot_write_csv'] .
			' (' . $pth['folder']['base'] . $plugin_tx['register']['config_usersfile'] . ')' .
			'</li>'."\n";
		}
		// write CSV file if no errors occurred so far

		if($ERROR != "")
		{
		$o .= '<span class="regi_error">' . $plugin_tx[$plugin]['error'] . '</span>'."\n" .
        '<ul class="regi_error">'."\n".$ERROR.'</ul>'."\n";
		}
		else
		{
			$rememberPeriod = 24*60*60*100;
			$logFile = $pth['folder']['plugins'] . $plugin . '/logfile/logfile.txt';

			$username = session('username');

			// clear all session variables
			//$_SESSION = array();

			// end session
			unset($_SESSION['username']);
			unset($_SESSION['fullname']);
			unset($_SESSION['email']);
			unset($_SESSION['accessgroups']);
			unset($_SESSION['sessionnr']);
			unset($_SESSION['register_sn']);

			// clear cookies
			if(isset($_COOKIE['username'], $_COOKIE['password']))
			{
				setcookie("username", "", time() - $rememberPeriod, "/");
				setcookie("password", "", time() - $rememberPeriod, "/");
			}

			// write line to log-file
			if(preg_match('/true/i',$plugin_cf[$plugin]['logfile']))
			{
			$logfile = fopen($logFile, 'a');
			fwrite($logfile, date("Y-m-d H:i:s") . " $username deleted and logged out\n");
			fclose($logfile);
			}

			$o .= '<b>' . $plugin_tx[$plugin]['user_deleted'] . ': '.$username.'</b>'."\n";
			return $o;
		}
	}
	else
	{
		$email = $entry['email'];
		$name  = $entry['name'];
	}

	// Form Creation
	$o .= registerUserPrefsForm($name, $email);
	return $o;
}

/*
 *  This function creates a link to the "Registration" page (Top Level Function).
 */
function registerloginform()
{
	GLOBAL $plugin_cf, $plugin_tx, $pth, $sn, $su, $fieldsize;
	$plugin = basename(dirname(__FILE__),"/");
	$imageFolder = $pth['folder']['plugins'] . $plugin . "/images";

	$o = '';

	// If logged in show user preferences link, otherwise register and forgot email links.

	if(!isset($_SESSION['username'],$_SESSION['sessionnr'],$_SESSION['register_sn']) || session('sessionnr') != session_id() || $_SESSION['register_sn'] != REGISTER_SESSION_NAME)
	{

		// Begin register- and loginarea and user fields

		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .= "\n".
		'<div class="regi_regloginarea_hor">'."\n".
		'<form action="'.preg_replace('&','&amp;',htmlspecialchars(sv('REQUEST_URI'))).'" method="post">'."\n".
		tag('input type="hidden" name="function" value="registerlogin"')."\n".
		'<div class="regi_user_hor">' . $plugin_tx[$plugin]['username'].'</div>'."\n".
		'<div class="regi_userfield_hor">'.tag('input class="regi_userfield_hor" type="text" name="username"').'</div>'."\n";
		else
		$o .= "\n".
		'<div class="regi_regloginarea_ver">'."\n".
		'<form action="'.str_replace('&','&amp;',htmlspecialchars(sv('REQUEST_URI'))).'" method="post">'."\n".
		tag('input type="hidden" name="function" value="registerlogin"')."\n".
		'<div class="regi_user_ver">'.$plugin_tx[$plugin]['username'].'</div>'."\n".
		'<div class="regi_userfield_ver">'.tag('input class="regi_userfield_ver" type="text" name="username"').'</div>'."\n";

		// password
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .= '<div class="regi_password_hor">'.$plugin_tx[$plugin]['password'].'</div>'."\n";
		else
		$o .= '<div class="regi_password_ver">'.$plugin_tx[$plugin]['password'].'</div>'."\n";

		// Forgot password link
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .= '<div class="regi_forgotpw_hor">'."\n";
		else
		$o .= '<div class="regi_forgotpw_ver">'."\n";

		if(isset($su) && urldecode($su) != html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['forgot_password'])))
		$o .=
		'<a href="'.$sn.'?'.html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['forgot_password'])).'">'."\n" .
		tag('img src="'.$imageFolder.'/'.$plugin_cf[$plugin]['image_forgot_password'].'" class="regi_forgotpwimage" alt="'.$plugin_tx[$plugin]['forgot_password'].'" title="'.$plugin_tx[$plugin]['forgot_password'].'"')."\n".'</a>'."\n".'</div>'."\n";
		else
		$o .= '</div>'."\n";

		// password field & image
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .=
		'<div class="regi_passwordfield_hor">'."\n".
		tag('input type="password" name="password"')."\n".
		'</div>'."\n" .
		'<div class="regi_loginbutton_hor">'."\n".
		tag('input class="regi_loginbutton_hor" type="image" name="login" src="'.$imageFolder.'/'. $plugin_cf[$plugin]['image_login'].'" alt="'.$plugin_tx[$plugin]['login'].'" title="'. $plugin_tx[$plugin]['login'].'"')."\n".'</div>'."\n";
		else
		$o .=
		'<div class="regi_passwordfield_ver">'."\n" .
		tag('input type="password" name="password" size="'.$fieldsize.'"')."\n".
		'</div>'."\n".
		'<div class="regi_loginbutton_ver">'."\n".
		tag('input class="regi_loginbutton_ver" type="image" name="login" src="'.$imageFolder.'/'.$plugin_cf[$plugin]['image_login'].'" alt="'.$plugin_tx[$plugin]['login'].'" title="'. $plugin_tx[$plugin]['login'].'"')."\n".
		'</div>'."\n";

		// Remember Me
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .= '<div class="regi_remember_hor">'."\n";
		else
		$o .= '<div class="regi_remember_ver">'.tag('hr')."\n";
		if(preg_match('/true/i',$plugin_cf[$plugin]['remember_user']) && ($plugin_cf[$plugin]['login_layout'] == 'horizontal'))
		$o .=  tag('input type="checkbox" name="remember" class="regi_remember_hor"').$plugin_tx[$plugin]['remember'].'<div style="clear: both;"></div>'."\n";
		if(preg_match('/true/i',$plugin_cf[$plugin]['remember_user']) && ($plugin_cf[$plugin]['login_layout'] != 'horizontal'))
		$o .=  tag('input type="checkbox" name="remember" class="regi_remember_ver"').$plugin_tx[$plugin]['remember']."\n".'</div>'."\n";
		else $o .= '</div>'."\n";


		// Register Link
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .= '<div class="regi_register_hor">'."\n";
		else
		$o .= '<div class="regi_register_ver">'."\n";
		if(preg_match('/true/i',$plugin_cf[$plugin]['allowed_register']))
		$o .= '<a href="'.$sn.'?'.html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['register'])).'">'.$plugin_tx[$plugin]['register'].'</a>'."\n".'</div>'."\n".'</form>'."\n".'<div style="clear: both;"></div>'."\n".'</div>'."\n";
		else
		$o .= '</div>'."\n".'</form>'."\n".'<div style="clear: both;"></div>'."\n".'</div>'."\n";
	}
	else
	{

		// Logout Link and Preferences Link

		// loggedin user
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .= "\n".'<div class="regi_loggedin_loggedinarea_hor">'."\n".'<div class="regi_loggedin_user_hor">'.$plugin_tx[$plugin]['loggedin_welcometext'].' '.$_SESSION['fullname'].',&nbsp; </div>'."\n";
		else
		$o .= "\n".'<div class="regi_loggedin_loggedinarea_ver">'."\n".'<div class="regi_loggedin_user_ver">'.$plugin_tx[$plugin]['loggedin_welcometext'].' '.$_SESSION['fullname'].',&nbsp; </div>'."\n";

		// loggedin loggedin
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .='<div class="regi_loggedin_loggedin_hor">'.$plugin_tx['register']['loggedin'].'</div>'."\n";
		else
		$o .='<div class="regi_loggedin_loggedin_ver">'.$plugin_tx['register']['loggedin'].'</div>'."\n";

		// loggedin settings
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .='<div class="regi_loggedin_settings_hor">'."\n";
		else
		$o .='<div class="regi_loggedin_settings_ver">'."\n";

		if(isset($su) && urldecode($su) != html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['user_prefs'])))
		$o .= '<a href="'.$sn.'?'.html_entity_decode(preg_replace("/ /", "_", $plugin_tx[$plugin]['user_prefs'])).'">'.tag('img class="regi_settingsimage" src="'.$imageFolder.'/'.$plugin_cf[$plugin]['image_user_preferences'].'" alt="'.$plugin_tx[$plugin]['user_prefs'].'"').$plugin_tx[$plugin]['user_prefs'].'</a>'."\n".
		'</div>'."\n";
		else
		$o .= '</div>'."\n";

		// loggedin logout
		if($plugin_cf[$plugin]['login_layout'] == 'horizontal')
		$o .= '<div class="regi_loggedin_logout_hor">'."\n".'<a href="'.$sn.'?&amp;function=registerlogout">'.tag('img  class="regi_logoutimage" src="'.$imageFolder.'/'.$plugin_cf[$plugin]['image_logout'].'" alt="'.$plugin_tx[$plugin]['logout'].'"').$plugin_tx[$plugin]['logout'].'</a>'."\n".'</div>'."\n".'<div style="clear: both;"></div>'."\n".'</div>'."\n";
		else
		$o .= '<div class="regi_loggedin_logout_ver">'."\n".'<a href="'.$sn.'?&amp;function=registerlogout">'.tag('img  class="regi_logoutimage" src="'.$imageFolder.'/'.$plugin_cf[$plugin]['image_logout'].'" alt="'.$plugin_tx[$plugin]['logout'].'"').$plugin_tx[$plugin]['logout'].'</a>'."\n".'</div>'."\n".'<div style="clear: both;"></div>'."\n".'</div>'."\n";
	}
  return $o;
}

/*
 * This function outputs the full name of the current user (Top Level Function).
 */
function registeradminmodelink()
{
    trigger_error('registeradminmodelink() is deprecated', E_USER_WARNING);
    return FALSE;
//	global $plugin_cf, $plugin_tx, $adm, $_SESSION, $sn, $su;
//	$plugin = basename(dirname(__FILE__),"/");
//	$isSession = isset(
//	$_SESSION['username'],
//	$_SESSION['fullname'],
//	$_SESSION['email'],
//	$_SESSION['accessgroups'],
//	$_SESSION['sessionnr'],
//	$_SESSION['register_sn']) &&
//	$_SESSION['sessionnr'] == session_id() &&
//	$_SESSION['register_sn'] == REGISTER_SESSION_NAME;
//	$isAdmin = in_array($plugin_cf[$plugin]['group_administrator'], $_SESSION['accessgroups']);
//
//	if((!isset($adm) || !$adm) && $isSession && $isAdmin)
//    return '<a href="' . $sn . '?' . $su . '&amp;action=admin_mode">' . $plugin_tx[$plugin]['admin_mode'] . '</a>'."\n";
//	else
//    return '';
}


function register_mail($to, $subject, $message, $headers)
{
    global $plugin_cf;

    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/plain; charset=UTF-8';
    $sep = strtolower($plugin_cf['register']['fix_mail_headers']) == 'true' ? "\n" : "\r\n";
    return mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, implode($sep, $headers));
}

?>
