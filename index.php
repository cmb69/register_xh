<?php

/**
 * Front-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

use Register\Infra\Request;
use Register\Dic;
use XH\PageDataRouter;

const REGISTER_VERSION = "2.0-dev";

/**
 * @var PageDataRouter $pd_router
 * @var string $o
 */

$pd_router->add_interest("register_access");
Dic::makeHandlePageProtection()(new Request)->fire();
Dic::makeLoginController()(new Request)->fire();
$o .= Dic::makeHandleSpecialPages()(new Request)->fire();

/**
 * Create and handle register form
 */
function registerUser(): string
{
    return Dic::makeHandleUserRegistration()(new Request())->fire();
}

/**
 * Create and handle forgotten password form
 */
function registerForgotPassword(): string
{
    return Dic::makeHandlePasswordForgotten()(new Request())->fire();
}

/*
 * Create and handle user preferences form
 */
function registerUserPrefs(): string
{
    return Dic::makeHandleUserPreferences()(new Request())->fire();
}

/*
 * Show the login or logged in form
 */
function registerloginform(): string
{
    return Dic::makeShowLoginForm()(new Request())->fire();
}

/**
 * Show the logged in form, if user is logged in
 *
 * @since 1.5rc1
 */
function Register_loggedInForm(): string
{
    return Dic::makeCurrentUser()->get() ? registerloginform() : "";
}

function register_access(string $groupString): string
{
    return Dic::makeHandlePageAccess()($groupString, new Request)->fire();
}

function access(string $groupString): string
{
    return Dic::makeHandlePageAccess()($groupString, new Request)->fire();
}
