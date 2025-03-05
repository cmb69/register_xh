<?php

const CMSIMPLE_XH_VERSION = "CMSimple_XH 1.7.5";
const CMSIMPLE_URL = "http://example.com/";
const CMSIMPLE_ROOT = "/";
const XH_URICHAR_SEPARATOR = "|";
const REGISTER_VERSION = "2.2-dev";

require_once './vendor/autoload.php';

require_once '../../cmsimple/functions.php';
require_once "../../cmsimple/classes/PageDataRouter.php";

spl_autoload_register(function (string $className) {
    $parts = explode("\\", $className);
    if ($parts[0] !== "Register") {
        return;
    }
    if (count($parts) === 3) {
        $parts[1] = strtolower($parts[1]);
    }
    $filename = implode("/", array_slice($parts, 1)) . ".php";
    if (is_readable("./classes/$filename")) {
        include_once "./classes/$filename";
    } elseif (is_readable("./tests/$filename")) {
        include_once "./tests/$filename";
    }
});
