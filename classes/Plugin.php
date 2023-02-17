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
         * @var array<string,array<string,string>> $plugin_cf
         * @var PageDataRouter $pd_router
         * @var string $o
         */
        global $edit, $plugin_cf, $pd_router, $o;

        $pd_router->add_interest("register_access");

        if (!($edit && defined("XH_ADM") && XH_ADM) && $plugin_cf['register']['hide_pages']) {
            Dic::makeHandlePageProtection();
        }
        Dic::makeLoginController()(new Request)->fire();
        if (!(defined("XH_ADM") && XH_ADM && $edit)) {
            $o .= Dic::makeHandleSpecialPages()(new Request)->fire();
        }
    }
}
