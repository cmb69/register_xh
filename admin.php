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
use Register\Infra\Request;

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

$temp = [
    "users_url" => (new Request)->url()->withPage("register")->withParams(["admin" => "users"])->relative(),
    "groups_url" => (new Request)->url()->withPage("register")->withParams(["admin" => "groups"])->relative(),
];

XH_registerPluginMenuItem("register", $plugin_tx["register"]["mnu_user_admin"], $temp["users_url"]);
XH_registerPluginMenuItem("register", $plugin_tx["register"]["mnu_group_admin"], $temp["groups_url"]);
XH_registerStandardPluginMenuItems(false);

$pd_router->add_tab(
    $plugin_tx["register"]["label_access"],
    $pth['folder']['plugins'] . "/register/register_pd_view.php"
);

if (XH_wantsPluginAdministration("register")) {
    $o .= print_plugin_admin("off");
    pluginmenu("ROW");
    pluginmenu("TAB", XH_hsc($temp["users_url"]), "", XH_hsc($plugin_tx["register"]["mnu_user_admin"]));
    pluginmenu("TAB", XH_hsc($temp["groups_url"]), "", XH_hsc($plugin_tx["register"]["mnu_group_admin"]));
    $o .= pluginmenu("SHOW");
    switch ($admin) {
        case "":
            $o .= Dic::makeShowPluginInfo()()->fire();
            break;
        case "groups":
            $o .= Dic::makeGroupAdminController()(new Request)->fire();
            break;
        case "users":
            $o .= Dic::makeUserAdminController()(new Request)->fire();
            break;
        default:
            $o .= plugin_admin_common();
    }
}

$temp = null;
