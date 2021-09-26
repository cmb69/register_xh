<?php

/**
 * Global functions of Register_XH
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

use Register\DbService;
use Register\PageDataController;
use Register\Plugin;
use Register\User;
use Register\UserGroup;
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

    if ($sl === $cf['language']['default']) {
        $folder = "{$pth['folder']['content']}register/";
    } else {
        $folder = dirname($pth['folder']['content']) . "/register/";
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
    return Plugin::handlePageAccess($groupString);
}

function access(string $groupString): string
{
    return Plugin::handlePageAccess($groupString);
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
        if ($entry->{"get$key"}() == $value) {
            return $entry;
        }
    }
    return false;
}

/**
 * Returns the user record, if the user is logged in, otherwise null.
 *
 * @return User|null
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
        if (isset($_SESSION['username'])) {
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
    unset($_SESSION['username']);
    if (isset($_COOKIE['register_username'], $_COOKIE['register_password'])) {
        setcookie('register_username', '', 0, CMSIMPLE_ROOT);
        setcookie('register_password', '', 0, CMSIMPLE_ROOT);
    }
}

/**
 * Create and handle register form
 */
function registerUser(): string
{
    return Plugin::handleUserRegistration();
}

/**
 * Create and handle forgotten password form
 */
function registerForgotPassword(): string
{
    return Plugin::handleForgotPassword();
}

/*
 * Create and handle user preferences form
 */
function registerUserPrefs(): string
{
    return Plugin::handleUserPrefs();
}

/*
 * Show the login or logged in form
 */
function registerloginform(): string
{
    return Plugin::handleLoginForm();
}

/**
 * Show the logged in form, if user is logged in
 *
 * @since 1.5rc1
 */
function Register_loggedInForm(): string
{
    return Plugin::handleloggedInForm();
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
