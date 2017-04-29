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
    public function run()
    {
        if (XH_ADM) {
            if ($this->isAdministrationRequested()) {
                $this->handleAdministration();
            }
        }
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
