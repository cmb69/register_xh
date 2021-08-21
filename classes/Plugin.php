<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Fa\RequireCommand as RequireFaCommand;

class Plugin
{
    const VERSION = "1.6";

    /**
     * @return void
     */
    public static function run()
    {
        global $edit, $function, $plugin_cf;

        if ($plugin_cf['register']['remember_user']
                && isset($_COOKIE['register_username'], $_COOKIE['register_password']) && !Register_isLoggedIn()) {
            $function = "registerlogin";
        }

        if (!($edit && self::isAdmin()) && $plugin_cf['register']['hide_pages']) {
            if ($temp = Register_currentUser()) {
                registerRemoveHiddenPages($temp->accessgroups);
            } else {
                registerRemoveHiddenPages([]);
            }
        }

        if (class_exists('Fa\\RequireCommand')) {
            (new RequireFaCommand)->execute();
        }
        if (!Register_isLoggedIn() && $function === 'registerlogin') {
            (new LoginController)->loginAction();
        }
        if (Register_isLoggedIn() && $function === 'registerlogout') {
            (new LoginController)->logoutAction();
        }
        if (!(self::isAdmin() && $edit)) {
            self::handleImplicitPages();
        }
        if (self::isAdmin()) {
            if (self::isAdministrationRequested()) {
                self::handleAdministration();
            }
        }
    }

    /**
     * @return void
     */
    private static function handleImplicitPages()
    {
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
            $controller = new SpecialPageController($h, $plugin_cf['register'], $plugin_tx['register']);
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
                $temp = new MainAdminController;
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
        $view = new View('info');
        $view->setData([
            'version' => self::VERSION,
            'checks' => (new SystemCheckService)->getChecks(),
        ]);
        return (string) $view;
    }
}
