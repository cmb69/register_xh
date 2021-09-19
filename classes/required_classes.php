<?php

/**
 * Global functions of Register_XH
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

use Register\DbService;
use Register\ForgotPasswordController;
use Register\PageDataController;
use Register\RegistrationController;
use Register\User;
use Register\UserGroup;
use Register\UserPrefsController;
use Register\View;

function Register_dataFolder(): string
{
    /**
     * @var string $sl
     * @var array<string,array<string,string>> $cf
     * @var array<string,array<string,string>> $plugin_cf
     * @var array{folder:array<string,string>,file:array<string,string>} $pth
     */
    global $sl, $cf, $plugin_cf, $pth;

    if ($plugin_cf['register']['login_all_subsites']) {
        if ($sl === $cf['language']['default']) {
            $folder = "{$pth['folder']['content']}register/";
        } else {
            $folder = dirname($pth['folder']['content']) . "/register/";
        }
    } else {
        $folder = "{$pth['folder']['content']}register/";
    }
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
        chmod($folder, 0777);
    }
    if (!is_file("{$folder}users.csv")) {
        (new DbService($folder))->writeUsers([]);
    }
    if (!is_file("{$folder}groups.csv")) {
        (new DbService($folder))->writeGroups(
            [new UserGroup($plugin_cf['register']['group_default'], '')]
        );
    }
    return $folder;
}

/**
 * @return bool
 */
function Register_isLoggedIn()
{
    return (bool) Register_currentUser();
}

function register_access(string $groupString): string
{
    /**
     * @var array<string,array<string,string>> $plugin_tx
     * @var string $function
     */
    global $plugin_tx, $function;

    // remove spaces etc.
    $groupString = preg_replace("/[ \t\r\n]*/", '', $groupString);
    $groupNames = explode(",", $groupString);

    $user = Register_currentUser();
    if ($function !== 'search'
            && (!Register_isLoggedIn() || !count(array_intersect($groupNames, $user->accessgroups)))) {
        // go to access error page
        $pageTitle = uenc($plugin_tx['register']['access_error']);
        header('Location: '.CMSIMPLE_URL.'?'. $pageTitle);
        exit;
    }
    return '';
}

function access(string $groupString): string
{
    return register_access($groupString);
}

/**
 * @return string|false
 */
function Register_groupLoginPage(string $group)
{
    $groups = (new DbService(Register_dataFolder()))->readGroups();
    foreach ($groups as $rec) {
        if ($rec->groupname == $group) {
            return $rec->loginpage;
        }
    }
    return false;
}

/**
 * Add new user to array
 *
 * @param User[] $array
 * @param string[] $accessgroups
 * @return User[]
 */
function registerAddUser(
    array $array,
    string $username,
    string $password,
    array $accessgroups,
    string $name,
    string $email,
    string $status
) {
    $entry = new User(
        $username,
        $password,
        $accessgroups,
        $name,
        $email,
        $status
    );

    $array[] = $entry;
    return $array;
}

/**
 * Search array of user entries for key and value.
 *
 * @param User[] $array
 * @param mixed $value
 * @return User|false
 */
function registerSearchUserArray(array $array, string $key, $value)
{
    foreach ($array as $entry) {
        if (isset($entry->{$key}) && $entry->{$key} == $value) {
            return $entry;
        }
    }
    return false;
}

/**
 * Replace user entry in array.
 *
 * @param User[] $array
 * @return User[]
 */
function registerReplaceUserEntry(array $array, User $newentry): array
{
    $newarray = array();
    $username = $newentry->username;
    foreach ($array as $entry) {
        if (isset($entry->username) && $entry->username == $username) {
            $newarray[] = $newentry;
        } else {
            $newarray[] = $entry;
        }
    }
    return $newarray;
}

/**
 * Delete user entry in array.
 *
 * @param User[] $array
 * @return User[]
 */
function registerDeleteUserEntry(array $array, string $username): array
{
    $newarray = array();
    foreach ($array as $entry) {
        if (isset($entry->username) && $entry->username != $username) {
            $newarray[] = $entry;
        }
    }
    return $newarray;
}

/**
 * Returns the user record, if the user is logged in, otherwise null.
 *
 * @return ?stdClass
 */
function Register_currentUser()
{
    /**
     * @var array{folder:array<string,string>,file:array<string,string>} $pth
     */
    global $pth;
    static $user = null;

    if (!$user) {
        // it would be nice if XH had an API to get the session name without starting a session
        $sessionfile = $pth['folder']['cmsimple'] . '.sessionname';
        if (is_file($sessionfile) && isset($_COOKIE[file_get_contents($sessionfile)])) {
            XH_startSession();
        }
        if (isset($_SESSION['username'], $_SESSION['register_sn'])
                && $_SESSION['register_sn'] == Register_sessionName()) {
            $dbService = new DbService(Register_dataFolder());
            $dbService->lock(LOCK_SH);
            $users = $dbService->readUsers();
            $rec = registerSearchUserArray($users, 'username', $_SESSION['username']);
            $dbService->lock(LOCK_UN);
            if ($rec) {
                $user = $rec;
            } else {
                Register_logout();
                $user = null;
            }
        } else {
            $user = null;
        }
    }
    return $user;
}

/**
 * @return void
 */
function Register_logout()
{
    XH_startSession();
    session_regenerate_id(true);
    unset($_SESSION['username'], $_SESSION['register_sn']);
    if (isset($_COOKIE['register_username'], $_COOKIE['register_password'])) {
        setcookie('register_username', '', 0, CMSIMPLE_ROOT);
        setcookie('register_password', '', 0, CMSIMPLE_ROOT);
    }
}

/*
 * Function to create and handle register form (Top Level Function).
 *
 */
function registerUser(): string
{
    // In case user is logged in, no registration page is shown
    if (Register_isLoggedIn()) {
        header('Location: ' . CMSIMPLE_URL);
        exit;
    }
    $controller = new RegistrationController(new View());
    if (isset($_POST['action']) && $_POST['action'] === 'register_user') {
        $action = 'registerUserAction';
    } elseif (isset($_GET['action']) && $_GET['action'] === 'register_activate_user') {
        $action = 'activateUserAction';
    } else {
        $action = 'defaultAction';
    }
    ob_start();
    $controller->{$action}();
    return ob_get_clean();
}

/**
 * Function to create and handle forgotten password form (Top Level Function)
 *
 * @return string
 */
function registerForgotPassword()
{
    // In case user is logged in, no password forgotten page is shown
    if (Register_isLoggedIn()) {
        header('Location: ' . CMSIMPLE_URL);
        exit;
    }
    $controller = new ForgotPasswordController(new View());
    if (isset($_POST['action']) && $_POST['action'] === 'forgotten_password') {
        $action = 'passwordForgottenAction';
    } elseif (isset($_GET['action']) && $_GET['action'] === 'registerResetPassword') {
        $action = 'resetPasswordAction';
    } else {
        $action = 'defaultAction';
    }
    ob_start();
    $controller->{$action}();
    return ob_get_clean();
}

/*
 * Function to create and handle user preferences form (Top Level Function).
 *
 */
function registerUserPrefs(): string
{
    /**
     * @var array<string,array<string,string>> $plugin_tx
     */
    global $plugin_tx;

    if (!Register_isLoggedIn()) {
        return XH_message('fail', $plugin_tx['register']['access_error_text']);
    }
    $controller = new UserPrefsController(new View());
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['submit'])) {
        $action = 'editAction';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['delete'])) {
        $action = 'deleteAction';
    } else {
        $action = 'defaultAction';
    }
    ob_start();
    $controller->{$action}();
    return ob_get_clean();
}

/*
 *  This function creates a link to the "Registration" page (Top Level Function).
 */
function registerloginform(): string
{
    /**
     * @var array<string,array<string,string>> $plugin_cf
     * @var array<string,array<string,string>> $plugin_tx
     * @var string $sn
     * @var string $su
     */
    global $plugin_cf, $plugin_tx, $sn, $su;

    // If logged in show user preferences link, otherwise register and forgot email links.

    if (!Register_isLoggedIn()) {
        // Begin register- and loginarea and user fields
        $view = new View();
        $forgotPasswordUrl = uenc($plugin_tx['register']['forgot_password']);
        $registerUrl = uenc($plugin_tx['register']['register']);
        $data = [
            'actionUrl' => sv('REQUEST_URI'),
            'hasForgotPasswordLink' => $plugin_cf['register']['password_forgotten']
                && urldecode($su) != $forgotPasswordUrl,
            'forgotPasswordUrl' => "$sn?$forgotPasswordUrl",
            'hasRememberMe' => $plugin_cf['register']['remember_user'],
            'isRegisterAllowed' => $plugin_cf['register']['allowed_register'],
            'registerUrl' => "$sn?$registerUrl",
        ];
        return $view->render('loginform', $data);
    } else {
        // Logout Link and Preferences Link
        $view = new View();
        $user = Register_currentUser();
        $userPrefUrl = uenc($plugin_tx['register']['user_prefs']);
        $data = [
            'fullName' => $user->name,
            'hasUserPrefs' => $user->status == 'activated' &&
                urldecode($su) != $userPrefUrl,
            'userPrefUrl' => "?$userPrefUrl",
            'logoutUrl' => "$sn?&function=registerlogout",
        ];
        return $view->render('loggedin-area', $data);
    }
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

function Register_sessionName(): string
{
    /**
     * @var string $sl
     * @var array<string,array<string,string>> $plugin_cf
     */
    global $sl, $plugin_cf;

    if ($plugin_cf['register']['login_all_subsites']) {
        return CMSIMPLE_ROOT;
    } else {
        return CMSIMPLE_ROOT . $sl;
    }
}

/**
 * @param array<string,string> $pageData
 * @return string
 */
function register_pd_view(array $pageData)
{
    ob_start();
    (new PageDataController($pageData, new View()))->execute();
    return (string) ob_get_clean();
}
