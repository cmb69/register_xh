<?php

/**
 * Copyright (c) 2012-2023 Christoph M. Becker
 */

use Register\Dic;
use Register\Infra\Request;
use Register\Infra\Responder;

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
}

/** @param array<string,string> $pageData */
function register_pd_view(array $pageData): string
{
    return Responder::respond(Dic::makeShowPageDataTab()(Request::current(), $pageData));
}
