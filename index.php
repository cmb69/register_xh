<?php

/**
 * Front-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 */


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


define('REGISTER_VERSION', '@REGISTER_VERSION@');


$_Register_hasher = new Register\PasswordHash(10, false);


/****************************************************************************
 *	Direct Calls															*
 ****************************************************************************/


if ($plugin_cf['register']['login_all_subsites']) {
	define('REGISTER_SESSION_NAME', CMSIMPLE_ROOT);
} else {
	define('REGISTER_SESSION_NAME', CMSIMPLE_ROOT . $sl);
}

if(session_id() == "")
  session_start();


if(!defined('CAPTCHA_LOADED'))
{
	$captchaInclude = $pth['folder']['plugins'] . "register/captcha.inc.php";
	if(!@include($captchaInclude)) die('Captcha functions file ' . $captchaInclude . ' missing');
	if(CAPTCHA_LOADED != '1.2') die('Captcha functions already loaded, but of wrong version ' . CAPTCHA_LOADED);
}

// Handling of Captcha Image Generation =========================================
if(isset($_GET['action']))
{
	if($_GET['action'] == 'register_captcha' && isset($_GET['captcha']) && isset($_GET['ip']))
	{
		$fontFolder = $pth['folder']['plugins'] . 'register/font/';
		generateCaptchaImage($_GET['captcha'],
		(int)$plugin_cf['register']['captcha_image_width'],
		(int)$plugin_cf['register']['captcha_image_height'],
		(int)$plugin_cf['register']['captcha_chars'],
		$fontFolder . $plugin_cf['register']['captcha_font'],
		$plugin_cf['register']['captcha_crypt']);
	}
}

// Handling of implicit pages ===================================================
// Please note that all pages listed here have a default variant, but can also
// be defined by the user. In that case the user has to insert the according
// CMSimple scripting functions.

if(!($edit&&$adm) && isset($su))
{
	// Handling of registration page
	if($su == uenc($plugin_tx['register']['register'])
	&& $plugin_cf['register']['allowed_register'])
	{
		if(!in_array($plugin_tx['register']['register'], $h))
		{
		$title = $plugin_tx['register']['register'];
		$o .= "\n\n".'<h4>' . $title . '</h4>'."\n".'<p>'. $plugin_tx['register']['register_form1'].'</p>'."\n";
		$o .= registerUser();
		}
	// Handling of forgotten password page
	}
	elseif ($plugin_cf['register']['password_forgotten']
		&& $su == uenc($plugin_tx['register']['forgot_password']))
	{
		if(!in_array($plugin_tx['register']['forgot_password'], $h))
		{
		$title = $plugin_tx['register']['forgot_password'];
		$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
		$o .= (new Register\ForgotPasswordController)->forgotPassword();
		}
  // Handling of user preferences page
	} elseif($su == uenc($plugin_tx['register']['user_prefs']))
	{
		if(!in_array($plugin_tx['register']['user_prefs'], $h))
		{
			$title = $plugin_tx['register']['user_prefs'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= (new Register\UserPrefsController)->registerUserPrefs();
		}

	// Handling of login error page
	} elseif($su == uenc($plugin_tx['register']['login_error']))
	{
		header('HTTP/1.1 403 Forbidden');
		if(!in_array($plugin_tx['register']['login_error'], $h))
		{
			$title = $plugin_tx['register']['login_error'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= $plugin_tx['register']['login_error_text'];
		}

	// Handling of logout page
	} elseif($su == uenc($plugin_tx['register']['loggedout']))
	{
		if(!in_array($plugin_tx['register']['loggedout'], $h))
		{
			$title = $plugin_tx['register']['loggedout'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= $plugin_tx['register']['loggedout_text'];
		}

	// Handling of login page
	}
	elseif($su == uenc($plugin_tx['register']['loggedin']))
	{
		if(!in_array($plugin_tx['register']['loggedin'], $h))
		{
			$title = $plugin_tx['register']['loggedin'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= $plugin_tx['register']['loggedin_text'];
		}
	} elseif($su == uenc($plugin_tx['register']['access_error']))
	{
		header('HTTP/1.1 403 Forbidden');
		if(!in_array($plugin_tx['register']['access_error'], $h))
		{
			$title = $plugin_tx['register']['access_error'];
			$o .= "\n\n".'<h4>' . $title . '</h4>'."\n";
			$o .= $plugin_tx['register']['access_error_text'];
		}
	}
}

function Register_dataFolder()
{
	global $plugin_cf, $pth;

	if ($plugin_cf['register']['login_all_subsites']) {
		$folder = "{$pth['folder']['content']}{$pth['folder']['base']}register/";
	} else {
		$folder = "{$pth['folder']['content']}register/";
	}
	if (!file_exists($folder)) {
		mkdir($folder, 0777, true);
		chmod($folder, 0777);
		registerWriteUsers("{$folder}users.csv", []);
		registerWriteGroups("{$folder}groups.csv", []);
	}
	return $folder;
}

function Register_isLoggedIn()
{
	return isset(
			$_SESSION['username'],
			$_SESSION['fullname'],
			$_SESSION['email'],
			$_SESSION['accessgroups'],
			$_SESSION['sessionnr'],
			$_SESSION['register_sn']
		)
		&& $_SESSION['sessionnr'] == session_id()
		&& $_SESSION['register_sn'] == REGISTER_SESSION_NAME;
}

// Handling of login/logout =====================================================

if ($plugin_cf['register']['remember_user'] && isset($_COOKIE['username'], $_COOKIE['password']) && !Register_isLoggedIn()) {
	$function = "registerlogin";
}
if (!Register_isLoggedIn() && $function == "registerlogin") {
	registerLogin();
}
if (Register_isLoggedIn() && $function == "registerlogout") {
	registerLogout();
}

if (!($edit&&$adm) && $plugin_cf['register']['hide_pages'])
{
	if (Register_isLoggedIn()) {
		registerRemoveHiddenPages($_SESSION['accessgroups']);
	} else {
		registerRemoveHiddenPages(array());
	}
}

/****************************************************************************
 *	Function Definitions					*
 ****************************************************************************/

/*
 * Login as user
 */
function registerLogin()
{
	global $pth, $plugin_cf, $plugin_tx, $h, $sn, $su, $_Register_hasher;

	//$secret = "LoginSecretWord";
	$rememberPeriod = 24*60*60*100;

	$username = htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : "");
	$password = htmlspecialchars(isset($_POST['password']) ? $_POST['password'] : "");
	$remember = htmlspecialchars(isset($_POST['remember']) ? $_POST['remember'] : "");

	// encrypt password if configured that way
	//if(preg_match('/true/i', $plugin_cf['register']['encrypt_password'])) $password = crypt($password, $password);

	// set username and password in case cookies are set
	if ($plugin_cf['register']['remember_user']
	    && isset($_COOKIE['username'], $_COOKIE['password']))
	{
		$username     = $_COOKIE['username'];
		$passwordHash = $_COOKIE['password'];
	}
//	else
//    $passwordHash = md5($secret.$password);

	// read user file in CSV format separated by colons
	register_lock_users(Register_dataFolder(), LOCK_SH);
	$userArray = registerReadUsers(Register_dataFolder() . 'users.csv');
	register_lock_users(Register_dataFolder(), LOCK_UN);

	// search user in CSV data
	$entry = registerSearchUserArray($userArray, 'username', $username);

	// check password and set session variables
	if ($entry && $entry['username'] == $username
	    && ($entry['status'] == 'activated' || $entry['status'] == 'locked')
	    && (!isset($passwordHash) || $passwordHash == $entry['password'])
	    && (isset($passwordHash)
		|| ($plugin_cf['register']['encrypt_password']
		    ? $_Register_hasher->CheckPassword($password, $entry['password'])
		    : $password == $entry['password']))) {

// Login Success ------------------------------------------------------------

		// set cookies if requested by user
		if ($plugin_cf['register']['remember_user']
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

		XH_logMessage('info', 'register', 'login', "$username logged in");

		// go to login page if exists or to default page otherwise
		if ($glp = Register_groupLoginPage($entry['accessgroups'][0])) {
		    $loginPage = '?' . $glp;
		} else {
		    $loginPage = '?'. uenc($plugin_tx['register']['loggedin']);
		}
		header('Location: ' . CMSIMPLE_URL . $loginPage);
		exit;

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

		XH_logMessage('error', 'register', 'login', "$username wrong password");

		// go to login error page
		$errorTitle = uenc($plugin_tx['register']['login_error']);
		header('Location: ' . CMSIMPLE_URL . '?' . $errorTitle);
		exit;
	}
}

/*
 * Logout user
 */
function registerLogout()
{
	global $_COOKIE, $plugin_cf, $plugin_tx, $sn, $h, $pth;

	$rememberPeriod = 24*60*60*100;

	$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

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
	XH_logMessage('info', 'register', 'logout', "$username logged out");

    // go to logout page
	$logoutTitle = uenc($plugin_tx['register']['loggedout']);
	header('Location: ' . CMSIMPLE_URL . '?' . $logoutTitle);
	exit;
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
	global $plugin_tx, $sn;

	// remove spaces etc.
	$groupString = preg_replace("/[ \t\r\n]*/", '', $groupString);
	$groupNames = explode(",", $groupString);

	$o = '';
	if (!Register_isLoggedIn() || empty(array_intersect($groupNames, $_SESSION['accessgroups']))) {
		// go to access error page
		$pageTitle = uenc($plugin_tx['register']['access_error']);
		header('Location: '.CMSIMPLE_URL.'?'. $pageTitle);
		exit;
	}
	return $o;
}


function register_lock_users($dirname, $mode)
{
    static $fps = array();

    $fn = $dirname . '/.lock';
    touch($fn);
    if ($mode != LOCK_UN) {
	$fps[$dirname] = fopen($fn, 'r');
	flock($fps[$dirname], $mode);
    } else {
	flock($fps[$dirname], $mode);
	fclose($fps[$dirname]);
	unset($fps[$dirname]);
    }
}

/*
 *  Read a group csv file into an array.
 */
function registerReadGroups($filename)
{
	global $plugin_cf;

	$groupArray = array();
	if(is_file($filename))
	{
		$fp = fopen($filename, "r");
		while (!feof($fp))
		{
			$line = rtrim(fgets($fp, 4096));
			if(!empty($line) && strpos($line, '//') !== 0)
			{
				$parts = explode('|', $line, 2);
				$groupname = $parts[0];
				$loginpage = isset($parts[1]) ? $parts[1] : '';
				// line must not start with '//' and all fields must be set
				if (strpos($groupname, "//") === false && $groupname != "")
				{
					$entry = array('groupname' => $groupname,
						       'loginpage' => $loginpage);
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
	global $plugin_cf;

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
	$line = '// Register Plugin Group Definitions'."\n" . '// Line Format:'."\n" . '// groupname|loginpage'."\n";
	if(!fwrite($fp, $line))
	{
		fclose($fp);
		return false;
	}

	foreach($array as $entry)
	{
		$groupname = $entry['groupname'];
		$line = "$groupname|$entry[loginpage]\n";
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


function Register_groupLoginPage($group)
{
    global $pth, $plugin_tx;

    $groups = registerReadGroups(Register_dataFolder() . 'groups.csv');
    foreach ($groups as $rec) {
	if ($rec['groupname'] == $group) {
	    return $rec['loginpage'];
	}
    }
    return false;
}

/*
 *  Read a csv file into an array.
 */
function registerReadUsers($filename)
{
	global $plugin_cf;

	$userArray = array();

	if(is_file($filename))
	{
		$fp = fopen($filename, "r");
		while (!feof($fp))
		{
			$line = fgets($fp, 4096);
			if($line != "" && strpos($line, '//')=== false)
			{
				list($username,$password,$accessgroups,$name,$email,$status) = explode(':', rtrim($line));
				// line must not start with '//' and all fields must be set
				if(
				$username != "" &&
				$password != "" &&
				$accessgroups != "" &&
				$name != "" &&
				$email != ""/* &&
				$status != ""*/)
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
	global $plugin_cf;

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


/**
 * Returns the user record, if the user is logged in, otherwise null.
 *
 * @return array
 */
function Register_currentUser()
{
    global $pth, $plugin_tx;

    $ptx = $plugin_tx['register'];
    if (Register_isLoggedIn()) {
	register_lock_users(Register_dataFolder(), LOCK_SH);
	$users = registerReadUsers(Register_dataFolder() . 'users.csv');
	$rec = registerSearchUserArray($users, 'username', $_SESSION['username']);
	register_lock_users(Register_dataFolder(), LOCK_UN);
	return $rec;
    } else {
	return null;
    }
}

/*
 *  Check entry for completeness.
 */
function registerCheckEntry($name, $username, $password1, $password2, $email)
{
	global $plugin_tx,$plugin_cf;

	$ERROR = '';

	// check for empty or illegal/wrong fields
	if(empty($name))
	$ERROR .= '<li>' . $plugin_tx['register']['err_name'] . '</li>'."\n";
	if($username == '')
	$ERROR .= '<li>' . $plugin_tx['register']['err_username'] . '</li>'."\n";
	elseif(!preg_match("/^[A-Za-z0-9_]+$/", $username))
	$ERROR .= '<li>' . $plugin_tx['register']['err_username_illegal'] . '</li>'."\n";
	if($password1 == '')
	$ERROR .= '<li>' . $plugin_tx['register']['err_password'] . '</li>'."\n";
	elseif(!preg_match("/^[A-Za-z0-9_]+$/", $password1))
	$ERROR .= '<li>' . $plugin_tx['register']['err_password_illegal'] . '</li>'."\n";
	if($password2 == '' || $password1 != $password2)
    $ERROR .= '<li>' . $plugin_tx['register']['err_password2'] . '</li>'."\n";
	if($email == '')
    $ERROR .= '<li>' . $plugin_tx['register']['err_email'] . '</li>'."\n";
	elseif (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i",$email))
	$ERROR .= '<li>' . $plugin_tx['register']['err_email_invalid'] . '</li>'."\n";
	return $ERROR;
}

/*
 *  Check entry for contained colons.
 */
function registerCheckColons($name, $username, $password1, $email)
{
	global $plugin_tx,$plugin_cf;

	$ERROR = '';

	if(strpos($name, ":") !== false)
	$ERROR .= '<li>' . $plugin_tx['register']['name'] . ' ' . $plugin_tx['register']['err_colon'] . '</li>'."\n";
	if(strpos($username, ":") !== false)
	$ERROR .= '<li>' . $plugin_tx['register']['username'] . ' ' . $plugin_tx['register']['err_colon'] . '</li>'."\n";
	if(strpos($password1, ":") !== false)
	$ERROR .= '<li>' . $plugin_tx['register']['password'] . ' ' . $plugin_tx['register']['err_colon'] . '</li>'."\n";
	if(strpos($email, ":") !== false)
	$ERROR .= '<li>' . $plugin_tx['register']['email'] . ' ' . $plugin_tx['register']['err_colon'] . '</li>'."\n";
	return $ERROR;
}


/*
 * Function to create and handle register form (Top Level Function).
 *
 */
function registerUser()
{
	return (new Register\RegistrationController)->register();
}

/*
 * Function to create and handle forgotten password form (Top Level Function)
 */
function registerForgotPassword()
{
	return (new Register\ForgotPasswordController)->forgotPassword();
}

/*
 * Function to create and handle user preferences form (Top Level Function).
 *
 */
function registerUserPrefs()
{
	return (new Register\UserPrefsController)->registerUserPrefs();
}

/*
 *  This function creates a link to the "Registration" page (Top Level Function).
 */
function registerloginform()
{
	global $plugin_cf, $plugin_tx, $pth, $sn, $su;

	$imageFolder = "{$pth['folder']['plugins']}register/images";

	// If logged in show user preferences link, otherwise register and forgot email links.

	if (!Register_isLoggedIn()) {
		// Begin register- and loginarea and user fields
		$view = new Register\View('loginform');
		$view->isHorizontal = $plugin_cf['register']['login_layout'] === 'horizontal';
		$view->actionUrl = sv('REQUEST_URI');
		$forgotPasswordUrl = uenc($plugin_tx['register']['forgot_password']);
		$view->hasForgotPasswordLink = $plugin_cf['register']['password_forgotten']
			&& isset($su) && urldecode($su) != $forgotPasswordUrl;
		$view->forgotPasswordUrl = "$sn?$forgotPasswordUrl";
		$view->forgotPasswordIcon = "$imageFolder/forgot_new.png";
		$view->loginIcon = "$imageFolder/submit_new.png";
		$view->hasRememberMe = $plugin_cf['register']['remember_user'];
		$view->isRegisterAllowed = $plugin_cf['register']['allowed_register'];
		$registerUrl = uenc($plugin_tx['register']['register']);
		$view->registerUrl = "$sn?$registerUrl";
	} else {
		// Logout Link and Preferences Link
		$view = new Register\View('loggedin-area');
		$view->isHorizontal = $plugin_cf['register']['login_layout'] === 'horizontal';
		$view->fullName = $_SESSION['fullname'];
		$currentUser = Register_currentUser();
		$userPrefUrl = uenc($plugin_tx['register']['user_prefs']);
		$view->hasUserPrefs = $currentUser['status'] == 'activated' && isset($su)
		    && urldecode($su) != $userPrefUrl;
		$view->userPrefUrl = "?$userPrefUrl";
		$view->userPrefIcon = "$imageFolder/preferences_new.png";
		$view->logoutUrl = "$sn?&function=registerlogout";
		$view->logoutIcon = "$imageFolder/logout_new.png";
	}
	return (string) $view;
}


/**
 * Returns the logged in form, if user is logged in.
 *
 * @since 1.5rc1
 *
 * @return  string
 */
function Register_loggedInForm()
{
    return Register_isLoggedIn() ? registerloginform() : '';
}


/*
 * This function outputs the full name of the current user (Top Level Function).
 */
function registeradminmodelink()
{
    trigger_error('registeradminmodelink() is deprecated', E_USER_WARNING);
    return FALSE;
}

?>
