<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

/** @var array{folder:array<string,string>,file:array<string,string>} $pth */

$temp = $pth["folder"]["cmsimple"] . ".sessionname";
if (is_file($temp) && isset($_COOKIE[file_get_contents($temp)])) {
    XH_startSession();
}
