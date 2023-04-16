<?php

/**
 * Front-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 */

// phpcs:disable PSR1.Files.SideEffects

use Register\Dic;
use Register\Infra\Request;
use Register\Infra\Responder;
use XH\PageDataRouter;

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

const REGISTER_VERSION = "2.0-dev";

/**
 * @var string $su
 * @var PageDataRouter $pd_router
 * @var string $o
 */

$pd_router->add_interest("register_access");
Responder::respond(Dic::makeMain()(Request::current()));

switch ($su) {
    case "register+user":
        $o .= Responder::respond(Dic::makeHandleUserRegistration()(Request::current()));
        break;
    case "register+password":
        $o .= Responder::respond(Dic::makeHandlePasswordForgotten()(Request::current()));
        break;
    case "register+settings":
        $o .= Responder::respond(Dic::makeHandleUserPreferences()(Request::current()));
        break;
}

function registerUser(): string
{
    trigger_error("registerUser() no longer has any effect", E_USER_DEPRECATED);
    return "";
}

function registerForgotPassword(): string
{
    trigger_error("registerForgotPassword() no longer has any effect", E_USER_DEPRECATED);
    return "";
}

function registerUserPrefs(): string
{
    trigger_error("registerUserPrefs() no longer has any effect", E_USER_DEPRECATED);
    return "";
}

/*
 * Show the login or logged in form
 */
function registerloginform(): string
{
    return Responder::respond(Dic::makeShowLoginForm()(Request::current()));
}

/**
 * Show the logged in form, if user is logged in
 *
 * @since 1.5rc1
 */
function Register_loggedInForm(): string
{
    return Responder::respond(Dic::makeShowLoginForm()(Request::current(), true));
}

function register_forbidden(): string
{
    return Responder::respond(Dic::makeForbidden()());
}

function register_active_users(): string
{
    return Responder::respond(Dic::makeActiveUsers()(Request::current()));
}
