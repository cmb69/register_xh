<?php

/**
 * Front-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 */

use Register\Dic;
use XH\PageDataRouter;

const REGISTER_VERSION = "2.0-dev";

/**
 * @var PageDataRouter $pd_router
 * @var string $o
 */

$pd_router->add_interest("register_access");
Dic::loginController();
Dic::handlePageProtection();
$o .= Dic::handleSpecialPages();

/**
 * Create and handle register form
 */
function registerUser(): string
{
    return Dic::handleUserRegistration();
}

/**
 * Create and handle forgotten password form
 */
function registerForgotPassword(): string
{
    return Dic::handlePasswordForgotten();
}

/*
 * Create and handle user preferences form
 */
function registerUserPrefs(): string
{
    return Dic::handleUserPreferences();
}

/*
 * Show the login or logged in form
 */
function registerloginform(): string
{
    return Dic::showLoginForm();
}

/**
 * Show the logged in form, if user is logged in
 *
 * @since 1.5rc1
 */
function Register_loggedInForm(): string
{
    return Dic::showLoginForm(true);
}

function register_access(string $groupString): string
{
    return Dic::handlePageAccess($groupString);
}

function access(string $groupString): string
{
    trigger_error(
        "access() is deprecated; use register_access() instead,"
        . " or better define the access groups via the page data tab.",
        E_USER_DEPRECATED
    );
    return Dic::handlePageAccess($groupString);
}
