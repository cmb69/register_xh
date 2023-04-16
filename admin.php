<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

use Register\Dic;
use Register\Infra\Request;
use Register\Infra\Responder;
use XH\PageDataRouter;

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

/**
 * @var string $o
 * @var array{folder:array<string,string>,file:array<string,string>} $pth
 * @var PageDataRouter $pd_router
 * @var string $admin
 * @var array<string,array<string,string>> $plugin_tx
 */

$temp = [
    "users_url" => Request::current()->url()->withPage("register")->with("admin", "users")->with("normal")->relative(),
    "groups_url" => Request::current()->url()->withPage("register")->with("admin", "groups")->with("normal")->relative(),
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
            $o .= Responder::respond(Dic::makeShowPluginInfo()());
            break;
        case "groups":
            $o .= Responder::respond(Dic::makeGroupAdmin()(Request::current()));
            break;
        case "users":
            $o .= Responder::respond(Dic::makeUserAdmin()(Request::current()));
            break;
        default:
            $o .= plugin_admin_common();
    }
}

$temp = null;
