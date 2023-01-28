<?php

/**
 * Global functions of Register_XH
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

use Register\PageDataController;
use Register\Plugin;
use Register\View;

function register_access(string $groupString): string
{
    return Plugin::handlePageAccess($groupString);
}

function access(string $groupString): string
{
    return Plugin::handlePageAccess($groupString);
}

/**
 * Create and handle register form
 */
function registerUser(): string
{
    return Plugin::handleUserRegistration();
}

/**
 * Create and handle forgotten password form
 */
function registerForgotPassword(): string
{
    return Plugin::handleForgotPassword();
}

/*
 * Create and handle user preferences form
 */
function registerUserPrefs(): string
{
    return Plugin::handleUserPrefs();
}

/*
 * Show the login or logged in form
 */
function registerloginform(): string
{
    return Plugin::handleLoginForm();
}

/**
 * Show the logged in form, if user is logged in
 *
 * @since 1.5rc1
 */
function Register_loggedInForm(): string
{
    return Plugin::handleloggedInForm();
}

/**
 * @param array<string,string> $pageData
 * @return string
 */
function register_pd_view(array $pageData)
{
    global $plugin_tx;

    ob_start();
    (new PageDataController($pageData, new View($plugin_tx['register'])))->execute();
    return (string) ob_get_clean();
}
