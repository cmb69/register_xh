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
                && isset($_COOKIE['register_username'], $_COOKIE['register_password']) && !Register_isLoggedIn()) {
            $function = "registerlogin";
        }

        if (!($edit && self::isAdmin()) && $plugin_cf['register']['hide_pages']) {
            if ($temp = Register_currentUser()) {
                self::removeHiddenPages($temp->getAccessgroups());
            } else {
                self::removeHiddenPages([]);
            }
        }

        $dbService = new DbService(Register_dataFolder());
        if (!Register_isLoggedIn() && $function === 'registerlogin') {
            $controller = new LoginController(
                $plugin_cf["register"],
                $plugin_tx["register"],
                $dbService,
                new UserGroupRepository($dbService)
            );
            $controller->loginAction();
        }
        if (Register_isLoggedIn() && $function === 'registerlogout') {
            $controller = new LoginController(
                $plugin_cf["register"],
                $plugin_tx["register"],
                $dbService,
                new UserGroupRepository($dbService)
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
                    new DbService(Register_dataFolder())
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
        $view = new View();
        return $view->render('info', [
            'version' => self::VERSION,
            'checks' => (new SystemCheckService)->getChecks(),
        ]);
    }
}
