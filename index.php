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
use XH\PageDataRouter;

if (!defined("CMSIMPLE_XH_VERSION")) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

const REGISTER_VERSION = "2.2-dev";

function register(): string
{
    global $function;
    switch ($function) {
        default:
            return Dic::makeShowLoginForm()(Request::current())();
        case "register_user":
            return Dic::makeHandleUserRegistration()(Request::current())();
        case "register_password":
            return Dic::makeHandlePasswordForgotten()(Request::current())();
        case "register_settings":
            return Dic::makeHandleUserPreferences()(Request::current())();
    }
}

function register_user_info(string $pageUrl): string
{
    return Dic::makeUserInfo()(Request::current(), $pageUrl)();
}

function register_active_users(): string
{
    return Dic::makeActiveUsers()(Request::current())();
}

function register_forbidden(): string
{
    return Dic::makeForbidden()()();
}

/** @deprecated */
function registerUser(): string
{
    trigger_error("registerUser() no longer has any effect", E_USER_DEPRECATED);
    return "";
}

/** @deprecated */
function registerForgotPassword(): string
{
    trigger_error("registerForgotPassword() no longer has any effect", E_USER_DEPRECATED);
    return "";
}

/** @deprecated */
function registerUserPrefs(): string
{
    trigger_error("registerUserPrefs() no longer has any effect", E_USER_DEPRECATED);
    return "";
}

/** @deprecated */
function registerloginform(): string
{
    trigger_error("registerloginform() is deprecated; use register() instead", E_USER_DEPRECATED);
    return register();
}

/** @deprecated */
function Register_loggedInForm(string $pageUrl): string
{
    trigger_error(
        "Register_loggedInForm() no longer has any effect; use register_user_info() instead",
        E_USER_DEPRECATED
    );
    return "";
}

/** @var PageDataRouter $pd_router */

$pd_router->add_interest("register_access");
Dic::makeMain()(Request::current())();
