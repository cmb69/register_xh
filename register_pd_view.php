<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

use Register\Dic;
use Register\Infra\Request;

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
}

/** @param array<string,string> $pageData */
function register_pd_view(array $pageData): string
{
    return Dic::makeShowPageDataTab()($pageData, new Request)->fire();
}
