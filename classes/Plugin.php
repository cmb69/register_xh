<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection as CsrfProtector;
use XH\PageDataRouter;

class Plugin
{
    const VERSION = "1.6";

    /**
     * @return void
     */
    public static function run()
    {
        /**
         * @var bool $edit
         * @var string $function
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         * @var PageDataRouter $pd_router
         * @var array{folder:array<string,string>,file:array<string,string>} $pth
         */
        global $edit, $function, $plugin_cf, $plugin_tx, $pd_router, $pth;

        $pd_router->add_interest("register_access");

        if ($plugin_cf['register']['remember_user']
                && isset($_COOKIE['register_username'], $_COOKIE['register_password']) && !self::currentUser()) {
            $function = "registerlogin";
        }

        if (!($edit && self::isAdmin()) && $plugin_cf['register']['hide_pages']) {
            if ($temp = self::currentUser()) {
                self::removeHiddenPages($temp->getAccessgroups());
            } else {
                self::removeHiddenPages([]);
            }
        }

        $dbService = new DbService(self::dataFolder());
        if (!self::currentUser() && $function === 'registerlogin') {
            $controller = new LoginController(
                $plugin_cf["register"],
                $plugin_tx["register"],
                new UserRepository($dbService),
                new UserGroupRepository($dbService),
                new LoginManager(),
                new Logger()
            );
            $controller->loginAction();
        }
        if (self::currentUser() && $function === 'registerlogout') {
            $controller = new LoginController(
                $plugin_cf["register"],
                $plugin_tx["register"],
                new UserRepository($dbService),
                new UserGroupRepository($dbService),
                new LoginManager(),
                new Logger()
            );
            $controller->logoutAction();
        }
        if (!(self::isAdmin() && $edit)) {
            self::handleImplicitPages();
        }
        if (self::isAdmin()) {
            $pd_router->add_tab(
                $plugin_tx["register"]["label_access"],
                "{$pth['folder']['plugins']}/register/register_pd_view.php"
            );
            if (self::isAdministrationRequested()) {
                self::handleAdministration();
            }
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
         * @var array<int,string> $h
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $o, $su, $h, $plugin_cf, $plugin_tx;

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
            $controller = new SpecialPageController($h, $plugin_cf['register'], $plugin_tx['register'], new View());
            ob_start();
            $controller->{$method}();
            $o .= (string) ob_get_clean();
        }
    }

    private static function isAdmin(): bool
    {
        return XH_ADM; // @phpstan-ignore-line
    }

    /**
     * @return bool
     */
    private static function isAdministrationRequested()
    {
        return XH_wantsPluginAdministration('register');
    }

    /**
     * @return void
     */
    private static function handleAdministration()
    {
        /**
         * @var string $o
         * @var string $admin
         * @var string $action
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $o, $admin, $action, $plugin_cf, $plugin_tx;

        $o .= print_plugin_admin('off');
        pluginmenu('ROW');
        pluginmenu(
            'TAB',
            '?&amp;register&amp;admin=plugin_main&amp;action=editgroups',
            '',
            XH_hsc($plugin_tx['register']['mnu_group_admin'])
        );
        pluginmenu(
            'TAB',
            '?&amp;register&amp;admin=plugin_main&amp;action=editusers',
            '',
            XH_hsc($plugin_tx['register']['mnu_user_admin'])
        );
        $o .= pluginmenu('SHOW');
        switch ($admin) {
            case '':
                $o .= self::renderInfo();
                break;
            case 'plugin_main':
                $temp = new MainAdminController(
                    $plugin_cf["register"],
                    $plugin_tx["register"],
                    new View(),
                    new DbService(self::dataFolder())
                );
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
                $o .= plugin_admin_common();
        }
    }
    
    /**
     * @return string
     */
    private static function renderInfo()
    {
        /**
         * @var array{folder:array<string,string>,file:array<string,string>} $pth
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $pth, $plugin_tx;

        $systemCheckService = new SystemCheckService(
            $pth['folder']['plugins'],
            $plugin_tx['register'],
            self::dataFolder()
        );
        ob_start();
        (new InfoController(self::VERSION, $systemCheckService, new View()))->execute();
        return (string) ob_get_clean();
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
        /**
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $plugin_cf, $plugin_tx;

        // In case user is logged in, no registration page is shown
        if (self::currentUser()) {
            header('Location: ' . CMSIMPLE_URL);
            exit;
        }
        $controller = new RegistrationController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            new ValidationService($plugin_tx["register"]),
            new View(),
            new UserRepository(new DbService(self::dataFolder())),
            new MailService()
        );
        if (isset($_POST['action']) && $_POST['action'] === 'register_user') {
            $action = 'registerUserAction';
        } elseif (isset($_GET['action']) && $_GET['action'] === 'register_activate_user') {
            $action = 'activateUserAction';
        } else {
            $action = 'defaultAction';
        }
        ob_start();
        $controller->{$action}();
        return (string) ob_get_clean();
    }

    public static function handleForgotPassword(): string
    {
        /**
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $plugin_cf, $plugin_tx;

        // In case user is logged in, no password forgotten page is shown
        if (self::currentUser()) {
            header('Location: ' . CMSIMPLE_URL);
            exit;
        }
        $controller = new ForgotPasswordController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            new View(),
            new UserRepository(new DbService(self::dataFolder())),
            new MailService()
        );
        if (isset($_POST['action']) && $_POST['action'] === 'forgotten_password') {
            $action = 'passwordForgottenAction';
        } elseif (isset($_GET['action']) && $_GET['action'] === 'registerResetPassword') {
            $action = 'resetPasswordAction';
        } else {
            $action = 'defaultAction';
        }
        ob_start();
        $controller->{$action}();
        return (string) ob_get_clean();
    }

    public static function handleUserPrefs(): string
    {
        /**
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $plugin_cf, $plugin_tx;
    
        if (!self::currentUser()) {
            return XH_message('fail', $plugin_tx['register']['access_error_text']);
        }
        $controller = new UserPrefsController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            new CsrfProtector('register_csrf_token', false),
            new ValidationService($plugin_tx["register"]),
            new UserRepository(new DbService(self::dataFolder())),
            new View(),
            new MailService(),
            new LoginManager(),
            new Logger()
        );
        if (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['submit'])) {
            $action = 'editAction';
        } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['delete'])) {
            $action = 'deleteAction';
        } else {
            $action = 'defaultAction';
        }
        ob_start();
        $controller->{$action}();
        return (string) ob_get_clean();
    }

    public static function handleLoginForm(): string
    {
        /**
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         * @var string $sn
         * @var string $su
         */
        global $plugin_cf, $plugin_tx, $sn, $su;
    
        $controller = new LoginFormController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            $sn,
            $su,
            self::currentUser(),
            new View()
        );
        ob_start();
        $controller->execute();
        return (string) ob_get_clean();
    }

    public static function handleloggedInForm(): string
    {
        return self::currentUser() ? registerloginform() : "";
    }

    /**
     * @return User|null
     */
    private static function currentUser()
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
                $userRepository = new UserRepository(new DbService(self::dataFolder()));
                $rec = $userRepository->findByUsername($_SESSION['username']);
                if ($rec) {
                    $user = $rec;
                } else {
                    (new LoginManager())->logout();
                    $user = null;
                }
            } else {
                $user = null;
            }
        }
        return $user;
    }

    private static function dataFolder(): string
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
}
