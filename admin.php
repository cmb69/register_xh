<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

use XH\PageDataRouter;
use Register\Dic;

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

/**
 * @var string $o
 * @var array{folder:array<string,string>,file:array<string,string>} $pth
 * @var PageDataRouter $pd_router
 * @var string $admin
 * @var string $action
 * @var array<string,array<string,string>> $plugin_tx
 */

$pd_router->add_tab(
    $plugin_tx["register"]["label_access"],
    $pth['folder']['plugins'] . "/register/register_pd_view.php"
);

if (XH_wantsPluginAdministration("register")) {
    $o .= print_plugin_admin("off");
    pluginmenu("ROW");
    $temp = "?&register&admin=plugin_main&action=editgroups";
    pluginmenu("TAB", XH_hsc($temp), "", XH_hsc($plugin_tx["register"]["mnu_group_admin"]));
    $temp = "?&register&admin=plugin_main&action=editusers";
    pluginmenu("TAB", XH_hsc($temp), "", XH_hsc($plugin_tx["register"]["mnu_user_admin"]));
    $o .= pluginmenu("SHOW");
    switch ($admin) {
        case "":
            $o .= Dic::makeShowPluginInfo()();
            break;
        case "plugin_main":
            switch ($action) {
                case "editusers":
                    $o .= Dic::makeUserAdminController()->editUsersAction();
                    break;
                case "saveusers":
                    $o .= Dic::makeUserAdminController()->saveUsersAction();
                    break;
                case "editgroups":
                    $o .= Dic::makeGroupAdminController()->editGroupsAction();
                    break;
                case "savegroups":
                    $o .= Dic::makeGroupAdminController()->saveGroupsAction();
                    break;
            }
            break;
        default:
            $o .= plugin_admin_common();
    }
}
