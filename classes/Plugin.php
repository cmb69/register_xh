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

use Register\Infra\Request;

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
         * @var PageDataRouter $pd_router
         * @var string $o
         */
        global $edit, $function, $plugin_cf, $pd_router, $o;

        $pd_router->add_interest("register_access");

        if ($plugin_cf['register']['remember_user']
                && isset($_COOKIE['register_username'], $_COOKIE['register_token']) && !Dic::makeCurrentUser()->get()) {
            $function = "registerlogin";
        }

        if (!($edit && defined("XH_ADM") && XH_ADM) && $plugin_cf['register']['hide_pages']) {
            if ($temp = Dic::makeCurrentUser()->get()) {
                self::removeHiddenPages($temp->getAccessgroups());
            } else {
                self::removeHiddenPages([]);
            }
        }

        if (!Dic::makeCurrentUser()->get() && $function === 'registerlogin') {
            $controller = Dic::makeLoginController();
            $controller->loginAction(new Request())->fire();
        }
        if (Dic::makeCurrentUser()->get() && $function === 'registerlogout') {
            $controller = Dic::makeLoginController();
            $controller->logoutAction(new Request())->fire();
        }
        if (!(defined("XH_ADM") && XH_ADM && $edit)) {
            $o .= Dic::makeHandleSpecialPages()(new Request)->fire();
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
    
        $user = Dic::makeCurrentUser()->get();
        if ($function !== 'search'
                && (!$user || !count(array_intersect($groupNames, $user->getAccessgroups())))) {
            // go to access error page
            $pageTitle = uenc($plugin_tx['register']['access_error']);
            header('Location: '.CMSIMPLE_URL.'?'. $pageTitle);
            exit;
        }
        return '';
    }
}
