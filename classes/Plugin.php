<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2017 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class Plugin
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $lang;

    public function __construct()
    {
        global $plugin_cf, $plugin_tx;

        $this->config = $plugin_cf['register'];
        $this->lang = $plugin_tx['register'];
    }

    public function run()
    {
        global $edit;

        if (!(XH_ADM && $edit)) {
            $this->handleImplicitPages();
        }
        if (XH_ADM) {
            if ($this->isAdministrationRequested()) {
                $this->handleAdministration();
            }
        }
    }

    private function handleImplicitPages()
    {
        global $o, $su;

        switch ($su) {
            case uenc($this->lang['register']):
                $o .= $this->handleRegistrationPage();
                break;
            case uenc($this->lang['forgot_password']):
                $o .= $this->handlePasswordForgottenPage();
                break;
            case uenc($this->lang['user_prefs']):
                $o .= $this->handleUserPrefsPage();
                break;
            case uenc($this->lang['login_error']):
                $o .= $this->handleLoginErrorPage();
                break;
            case uenc($this->lang['loggedout']):
                $o .= $this->handleLogoutPage();
                break;
            case uenc($this->lang['loggedin']):
                $o .= $this->handleLoginPage();
                break;
            case uenc($this->lang['access_error']):
                $o .= $this->handleAccessErrorPage();
                break;
        }
    }

    private function handleRegistrationPage()
    {
        global $title, $h;

        if ($this->config['allowed_register'] && !in_array($this->lang['register'], $h)) {
            $title = XH_hsc($this->lang['register']);
            return $this->preparePageView(
                $this->lang['register'],
                $this->lang['register_form1'],
                registerUser()
            );
        }
    }

    private function handlePasswordForgottenPage()
    {
        global $title, $h;

        if ($this->config['password_forgotten'] && !in_array($this->lang['forgot_password'], $h)) {
            $title = XH_hsc($this->lang['forgot_password']);
            return $this->preparePageView(
                $this->lang['forgot_password'],
                $this->lang['reminderexplanation'],
                registerForgotPassword()
            );
        }
    }

    private function handleUserPrefsPage()
    {
        global $title, $h;

        if (!in_array($this->lang['user_prefs'], $h)) {
            $title = XH_hsc($this->lang['user_prefs']);
            return $this->preparePageView(
                $this->lang['user_prefs'],
                $this->lang['changeexplanation'],
                registerUserPrefs()
            );
        }
    }

    private function handleLoginErrorPage()
    {
        global $title, $h;

        header('HTTP/1.1 403 Forbidden');
        if (!in_array($this->lang['login_error'], $h)) {
            $title = $this->lang['login_error'];
            return $this->preparePageView(
                $this->lang['login_error'],
                $this->lang['login_error_text']
            );
        }
    }

    private function handleLogoutPage()
    {
        global $title, $h;

        if (!in_array($this->lang['loggedout'], $h)) {
            $title = $this->lang['loggedout'];
            return $this->preparePageView(
                $this->lang['loggedout'],
                $this->lang['loggedout_text']
            );
        }
    }

    private function handleLoginPage()
    {
        global $title, $h;

        if (!in_array($this->lang['loggedin'], $h)) {
            $title = $this->lang['loggedin'];
            return $this->preparePageView(
                $this->lang['loggedin'],
                $this->lang['loggedin_text']
            );
        }
    }

    private function handleAccessErrorPage()
    {
        global $title, $h;

        header('HTTP/1.1 403 Forbidden');
        if (!in_array($this->lang['access_error'], $h)) {
            $title = $this->lang['access_error'];
            return $this->preparePageView(
                $this->lang['access_error'],
                $this->lang['access_error_text']
            );
        }
    }

    /**
     * @param string $title
     * @param string $intro
     * @param string $more
     * @return View
     */
    private function preparePageView($title, $intro, $more = '')
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
    private function isAdministrationRequested()
    {
        global $register;

        return function_exists('XH_wantsPluginAdministration') && XH_wantsPluginAdministration('register')
            || isset($register) && $register === 'true';
    }

    private function handleAdministration()
    {
        global $o, $admin, $action, $plugin_tx;

        $ptx = $plugin_tx['register'];
        $o .= print_plugin_admin('off');
        pluginmenu('ROW');
        pluginmenu('TAB', '?&amp;register&amp;admin=plugin_main&amp;action=editgroups', '', $ptx['mnu_group_admin']);
        pluginmenu('TAB', '?&amp;register&amp;admin=plugin_main&amp;action=editusers', '', $ptx['mnu_user_admin']);
        $o .= pluginmenu('SHOW');
        switch ($admin) {
            case '':
                $o .= $this->renderInfo();
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
    private function renderInfo()
    {
        global $pth;
    
        $view = new View('info');
        $view->logo = "{$pth['folder']['plugins']}register/register.png";
        $view->version = REGISTER_VERSION;
        $view->checks = (new SystemCheckService)->getChecks();
        return (string) $view;
    }
}
