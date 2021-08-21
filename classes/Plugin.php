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

    public static function run()
    {
        global $edit, $function;

        if (class_exists('Fa\\RequireCommand')) {
            (new RequireFaCommand)->execute();
        }
        if (!Register_isLoggedIn() && $function === 'registerlogin') {
            (new LoginController)->loginAction();
        }
        if (Register_isLoggedIn() && $function === 'registerlogout') {
            (new LoginController)->logoutAction();
        }
        if (!(XH_ADM && $edit)) {
            self::handleImplicitPages();
        }
        if (XH_ADM) {
            if (self::isAdministrationRequested()) {
                self::handleAdministration();
            }
        }
    }

    private static function handleImplicitPages()
    {
        global $o, $su, $plugin_tx;

        switch ($su) {
            case uenc($plugin_tx['register']['register']):
                $o .= self::handleRegistrationPage();
                break;
            case uenc($plugin_tx['register']['forgot_password']):
                $o .= self::handlePasswordForgottenPage();
                break;
            case uenc($plugin_tx['register']['user_prefs']):
                $o .= self::handleUserPrefsPage();
                break;
            case uenc($plugin_tx['register']['login_error']):
                $o .= self::handleLoginErrorPage();
                break;
            case uenc($plugin_tx['register']['loggedout']):
                $o .= self::handleLogoutPage();
                break;
            case uenc($plugin_tx['register']['loggedin']):
                $o .= self::handleLoginPage();
                break;
            case uenc($plugin_tx['register']['access_error']):
                $o .= self::handleAccessErrorPage();
                break;
        }
    }

    private static function handleRegistrationPage()
    {
        global $title, $h, $plugin_cf, $plugin_tx;

        if ($plugin_cf['register']['allowed_register'] && !in_array($plugin_tx['register']['register'], $h)) {
            $title = XH_hsc($plugin_tx['register']['register']);
            return self::preparePageView(
                $plugin_tx['register']['register'],
                $plugin_tx['register']['register_form1'],
                registerUser()
            );
        }
    }

    private static function handlePasswordForgottenPage()
    {
        global $title, $h, $plugin_cf, $plugin_tx;

        if ($plugin_cf['register']['password_forgotten'] && !in_array($plugin_tx['register']['forgot_password'], $h)) {
            $title = XH_hsc($plugin_tx['register']['forgot_password']);
            return self::preparePageView(
                $plugin_tx['register']['forgot_password'],
                $plugin_tx['register']['reminderexplanation'],
                registerForgotPassword()
            );
        }
    }

    private static function handleUserPrefsPage()
    {
        global $title, $h, $plugin_tx;

        if (!in_array($plugin_tx['register']['user_prefs'], $h)) {
            $title = XH_hsc($plugin_tx['register']['user_prefs']);
            return self::preparePageView(
                $plugin_tx['register']['user_prefs'],
                $plugin_tx['register']['changeexplanation'],
                registerUserPrefs()
            );
        }
    }

    private static function handleLoginErrorPage()
    {
        global $title, $h, $plugin_tx;

        header('HTTP/1.1 403 Forbidden');
        if (!in_array($plugin_tx['register']['login_error'], $h)) {
            $title = $plugin_tx['register']['login_error'];
            return self::preparePageView(
                $plugin_tx['register']['login_error'],
                $plugin_tx['register']['login_error_text']
            );
        }
    }

    private static function handleLogoutPage()
    {
        global $title, $h, $plugin_tx;

        if (!in_array($plugin_tx['register']['loggedout'], $h)) {
            $title = $plugin_tx['register']['loggedout'];
            return self::preparePageView(
                $plugin_tx['register']['loggedout'],
                $plugin_tx['register']['loggedout_text']
            );
        }
    }

    private static function handleLoginPage()
    {
        global $title, $h, $plugin_tx;

        if (!in_array($plugin_tx['register']['loggedin'], $h)) {
            $title = $plugin_tx['register']['loggedin'];
            return self::preparePageView(
                $plugin_tx['register']['loggedin'],
                $plugin_tx['register']['loggedin_text']
            );
        }
    }

    private static function handleAccessErrorPage()
    {
        global $title, $h, $plugin_tx;

        header('HTTP/1.1 403 Forbidden');
        if (!in_array($plugin_tx['register']['access_error'], $h)) {
            $title = $plugin_tx['register']['access_error'];
            return self::preparePageView(
                $plugin_tx['register']['access_error'],
                $plugin_tx['register']['access_error_text']
            );
        }
    }

    /**
     * @param string $title
     * @param string $intro
     * @param string $more
     * @return View
     */
    private static function preparePageView($title, $intro, $more = '')
    {
        $view = new View('page');
        $view->title = $title;
        $view->intro = $intro;
        $view->more = new HtmlString($more);
        return $view;
    }

    /**
     * @return bool
     */
    private static function isAdministrationRequested()
    {
        return XH_wantsPluginAdministration('register');
    }

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
                $o .= plugin_admin_common($action, $admin, 'register');
        }
    }
    
    /**
     * @return string
     */
    private static function renderInfo()
    {
        $view = new View('info');
        $view->version = self::VERSION;
        $view->checks = (new SystemCheckService)->getChecks();
        return (string) $view;
    }
}
