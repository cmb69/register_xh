<?php

/**
 * Front-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 */


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


define('REGISTER_VERSION', '1.6');


if ($plugin_cf['register']['login_all_subsites']) {
    define('REGISTER_SESSION_NAME', CMSIMPLE_ROOT);
} else {
    define('REGISTER_SESSION_NAME', CMSIMPLE_ROOT . $sl);
}

// Handling of login/logout =====================================================

if ($plugin_cf['register']['remember_user']
        && isset($_COOKIE['register_username'], $_COOKIE['register_password']) && !Register_isLoggedIn()) {
    $function = "registerlogin";
}

if (!($edit&&$adm) && $plugin_cf['register']['hide_pages']) {
    if ($temp = Register_currentUser()) {
        registerRemoveHiddenPages($temp->accessgroups);
    } else {
        registerRemoveHiddenPages([]);
    }
}

Register\Plugin::run();
