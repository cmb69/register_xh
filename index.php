<?php

/**
 * Front-end of Register_XH.
 *
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 */

use Register\Dic;
use Register\Infra\Request;
use Register\Infra\Responder;
use XH\PageDataRouter;

const REGISTER_VERSION = "2.0-dev";

/**
 * @var PageDataRouter $pd_router
 * @var string $o
 */

$pd_router->add_interest("register_access");
Responder::respond(Dic::makeLoginController()(Request::current()));
Responder::respond(Dic::makeHandlePageProtection()(Request::current()));
$o .= Responder::respond(Dic::makeHandleSpecialPages()(Request::current()));

/**
 * Create and handle register form
 */
function registerUser(): string
{
    return Responder::respond(Dic::makeHandleUserRegistration()(Request::current()));
}

/**
 * Create and handle forgotten password form
 */
function registerForgotPassword(): string
{
    return Responder::respond(Dic::makeHandlePasswordForgotten()(Request::current()));
}

/*
 * Create and handle user preferences form
 */
function registerUserPrefs(): string
{
    return Responder::respond(Dic::makeHandleUserPreferences()(Request::current()));
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
