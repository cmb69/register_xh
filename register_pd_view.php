<?php

/**
 * Copyright (c) 2012-2023 Christoph M. Becker
 */

// phpcs:disable PSR1.Files.SideEffects

use Register\Dic;
use Register\Infra\Request;

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

/** @param array<string,string> $pageData */
function register_pd_view(array $pageData): string
{
    return Dic::makeShowPageDataTab()(Request::current(), $pageData)();
}
