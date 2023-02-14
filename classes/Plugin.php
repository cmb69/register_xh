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

use Register\Value\User;
use Register\Value\UserGroup;
use Register\Infra\DbService;
use Register\Infra\LoginManager;
use Register\Infra\RedirectResponse;
use Register\Infra\SystemChecker;
use Register\Infra\Session;
use Register\Infra\UserRepository;
use Register\Infra\View;

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
         * @var array<string,array<string,string>> $plugin_tx
         * @var PageDataRouter $pd_router
         * @var array{folder:array<string,string>,file:array<string,string>} $pth
         */
        global $edit, $function, $plugin_cf, $plugin_tx, $pd_router, $pth;

        $pd_router->add_interest("register_access");

        if ($plugin_cf['register']['remember_user']
                && isset($_COOKIE['register_username'], $_COOKIE['register_token']) && !self::currentUser()) {
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
            $controller = Dic::makeLoginController($dbService);
            $controller->loginAction()->trigger();
        }
        if (self::currentUser() && $function === 'registerlogout') {
            $controller = Dic::makeLoginController($dbService);
            $controller->logoutAction()->trigger();
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
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $o, $admin, $action, $plugin_tx;

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
                switch ($action) {
                    case 'editusers':
                        $o .= Dic::makeMainAdminController(new DbService(self::dataFolder()))->editUsersAction();
                        break;
                    case 'saveusers':
                        $o .= Dic::makeMainAdminController(new DbService(self::dataFolder()))->saveUsersAction();
                        break;
                    case 'editgroups':
                        $o .= Dic::makeMainAdminController(new DbService(self::dataFolder()))->editGroupsAction();
                        break;
                    case 'savegroups':
                        $o .= Dic::makeMainAdminController(new DbService(self::dataFolder()))->saveGroupsAction();
                        break;
                }
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

        $controller = new InfoController(
            $pth['folder']['plugins'],
            $plugin_tx['register'],
            self::dataFolder(),
            new SystemChecker(),
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register'])
        );
        return $controller->execute();
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
            return Dic::makeRegisterUser(new DbService(self::dataFolder()))();
        }
        if (isset($_GET['action']) && $_GET['action'] === 'register_activate_user') {
            return Dic::makeActivateUser(new DbService(self::dataFolder()))();
        }
        return Dic::makeShowRegistrationForm(new DbService(self::dataFolder()))();
    }

    public static function handleForgotPassword(): string
    {
        // In case user is logged in, no password forgotten page is shown
        if (self::currentUser()) {
            header('Location: ' . CMSIMPLE_URL);
            exit;
        }
        $controller = Dic::makeForgotPasswordController(new DbService(self::dataFolder()));
        if (isset($_POST['action']) && $_POST['action'] === 'forgotten_password') {
            $action = 'passwordForgottenAction';
        } elseif (isset($_GET['action']) && $_GET['action'] === 'registerResetPassword') {
            $action = 'resetPasswordAction';
        } elseif (isset($_GET['action']) && $_GET['action'] === 'register_change_password') {
            $action = 'changePasswordAction';
        } else {
            $action = 'defaultAction';
        }
        return $controller->{$action}();
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
        $controller = Dic::makeUserPrefsController(new DbService(self::dataFolder()));
        if (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['submit'])) {
            $action = 'editAction';
        } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['delete'])) {
            $action = 'deleteAction';
        } else {
            $action = 'defaultAction';
        }
        return $controller->{$action}();
    }

    public static function handleLoginForm(): string
    {
        $controller = Dic::makeLoginFormController(self::currentUser());
        return $controller->execute();
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

        $session = new Session();
        if (!$user) {
            // it would be nice if XH had an API to get the session name without starting a session
            $sessionfile = $pth['folder']['cmsimple'] . '.sessionname';
            if (is_file($sessionfile) && isset($_COOKIE[file_get_contents($sessionfile)])) {
                $session->start();
            }
            if (isset($_SESSION['username'])) {
                $userRepository = new UserRepository(new DbService(self::dataFolder()));
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
