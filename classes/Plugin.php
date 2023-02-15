<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\PageDataRouter;

use Register\Value\User;
use Register\Infra\LoginManager;
use Register\Infra\Request;
use Register\Infra\Session;

class Plugin
{
    const VERSION = "2.0-dev";

    /**
     * @return void
     */
    public static function run()
    {
        /**
         * @var bool $edit
         * @var string $function
         * @var array<string,array<string,string>> $plugin_cf
         * @var PageDataRouter $pd_router
         */
        global $edit, $function, $plugin_cf, $pd_router;

        $pd_router->add_interest("register_access");

        if ($plugin_cf['register']['remember_user']
                && isset($_COOKIE['register_username'], $_COOKIE['register_token']) && !self::currentUser()) {
            $function = "registerlogin";
        }

        if (!($edit && defined("XH_ADM") && XH_ADM) && $plugin_cf['register']['hide_pages']) {
            if ($temp = self::currentUser()) {
                self::removeHiddenPages($temp->getAccessgroups());
            } else {
                self::removeHiddenPages([]);
            }
        }

        if (!self::currentUser() && $function === 'registerlogin') {
            $controller = Dic::makeLoginController();
            $controller->loginAction(new Request())->fire();
        }
        if (self::currentUser() && $function === 'registerlogout') {
            $controller = Dic::makeLoginController();
            $controller->logoutAction(new Request())->fire();
        }
        if (!(defined("XH_ADM") && XH_ADM && $edit)) {
            self::handleImplicitPages();
        }
    }

    /**
     * Remove access restricted pages
     *
     * Supported are multiple groups per page and multiple user groups.
     *
     * @param string[] $userGroups
     * @return void
     */
    private static function removeHiddenPages(array $userGroups)
    {
        /**
         * @var PageDataRouter $pd_router
         * @var array<int,string> $c
         */
        global $pd_router, $c;

        foreach ($pd_router->find_all() as $i => $pd) {
            if (($arg = trim($pd["register_access"] ?? ""))) {
                $groups = array_map('trim', explode(',', $arg));
                if (count(array_intersect($groups, $userGroups)) == 0) {
                    $c[$i]= "#CMSimple hide# {{{PLUGIN:register_access('$arg');}}}";
                }
            }
        }
    }

    /**
     * @return void
     */
    private static function handleImplicitPages()
    {
        /**
         * @var string $o
         * @var string $su
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $o, $su, $plugin_tx;

        switch ($su) {
            case uenc($plugin_tx['register']['register']):
                $method = 'registrationPageAction';
                break;
            case uenc($plugin_tx['register']['forgot_password']):
                $method = 'passwordForgottenPageAction';
                break;
            case uenc($plugin_tx['register']['user_prefs']):
                $method = 'userPrefsPageAction';
                break;
            case uenc($plugin_tx['register']['login_error']):
                $method = 'loginErrorPageAction';
                break;
            case uenc($plugin_tx['register']['loggedout']):
                $method = 'logoutPageAction';
                break;
            case uenc($plugin_tx['register']['loggedin']):
                $method = 'loginPageAction';
                break;
            case uenc($plugin_tx['register']['access_error']):
                $method = 'accessErrorPageAction';
                break;
            default:
                $method = null;
        }
        if ($method !== null) {
            $controller = Dic::makeSpecialPageController();
            ob_start();
            $controller->{$method}();
            $o .= (string) ob_get_clean();
        }
    }

    public static function handlePageAccess(string $groupString): string
    {
        /**
         * @var array<string,array<string,string>> $plugin_tx
         * @var string $function
         */
        global $plugin_tx, $function;
    
        // remove spaces etc.
        $groupString = (string) preg_replace("/[ \t\r\n]*/", '', $groupString);
        $groupNames = explode(",", $groupString);
    
        $user = self::currentUser();
        if ($function !== 'search'
                && (!$user || !count(array_intersect($groupNames, $user->getAccessgroups())))) {
            // go to access error page
            $pageTitle = uenc($plugin_tx['register']['access_error']);
            header('Location: '.CMSIMPLE_URL.'?'. $pageTitle);
            exit;
        }
        return '';
    }

    public static function handleUserRegistration(): string
    {
        // In case user is logged in, no registration page is shown
        if (self::currentUser()) {
            header('Location: ' . CMSIMPLE_URL);
            exit;
        }
        if (isset($_POST['action']) && $_POST['action'] === 'register_user') {
            return Dic::makeRegisterUser()(new Request());
        }
        if (isset($_GET['action']) && $_GET['action'] === 'register_activate_user') {
            return Dic::makeActivateUser()();
        }
        return Dic::makeShowRegistrationForm()(new Request());
    }

    public static function handleForgotPassword(): string
    {
        // In case user is logged in, no password forgotten page is shown
        if (self::currentUser()) {
            header('Location: ' . CMSIMPLE_URL);
            exit;
        }
        if (isset($_POST['action']) && $_POST['action'] === 'forgotten_password') {
            return Dic::makePasswordForgotten()(new Request);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'registerResetPassword') {
            return Dic::makeResetPassword()(new Request());
        } elseif (isset($_GET['action']) && $_GET['action'] === 'register_change_password') {
            return Dic::makeChangePassword()(new Request());
        } else {
            return Dic::makeShowPasswordForgottenForm()(new Request());
        }
    }

    public static function handleUserPrefs(): string
    {
        /**
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $plugin_tx;
    
        if (!self::currentUser()) {
            return XH_message('fail', $plugin_tx['register']['access_error_text']);
        }
        if (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['submit'])) {
            return Dic::makeEditUser()(new Request());
        }
        if (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['delete'])) {
            return Dic::makeUnregisterUser()(new Request());
        }
        return Dic::makeShowUserPreferences()(new Request());
    }

    public static function handleLoginForm(): string
    {
        return Dic::makeShowLoginForm()(self::currentUser(), new Request());
    }

    public static function handleloggedInForm(): string
    {
        return self::currentUser() ? registerloginform() : "";
    }

    private static function currentUser(): ?User
    {
        /**
         * @var array{folder:array<string,string>,file:array<string,string>} $pth
         */
        global $pth;
        static $user = null;

        $session = new Session();
        if (!$user) {
            // it would be nice if XH had an API to get the session name without starting a session
            $sessionfile = $pth['folder']['cmsimple'] . '.sessionname';
            if (is_file($sessionfile) && isset($_COOKIE[file_get_contents($sessionfile)])) {
                $session->start();
            }
            if (isset($_SESSION['username'])) {
                $userRepository = Dic::makeUserRepository();
                $rec = $userRepository->findByUsername($_SESSION['username']);
                if ($rec) {
                    $user = $rec;
                } else {
                    (new LoginManager(time(), $session))->logout();
                    $user = null;
                }
            } else {
                $user = null;
            }
        }
        return $user;
    }
}
