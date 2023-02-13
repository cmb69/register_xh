<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

use Register\PageDataController;
use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
}

/** @param array<string,string> $pageData */
function register_pd_view(array $pageData): string
{
    global $pth, $tx, $plugin_tx, $sn, $su;

    $controller = new PageDataController(
        $pth['folder']['corestyle'],
        $tx['editmenu']['help'],
        new View("{$pth['folder']['plugins']}register/", $plugin_tx['register'])
    );
    return $controller($pageData, $sn . "?" . $su);
}
