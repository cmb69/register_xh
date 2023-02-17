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
use Register\Plugin;

Plugin::run();

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
    return Dic::makeHandleUserPreferences()(new Request());
}

/*
 * Show the login or logged in form
 */
function registerloginform(): string
{
    return Dic::makeShowLoginForm()(Dic::makeCurrentUser()->get(), new Request());
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
    return Plugin::handlePageAccess($groupString);
}

function access(string $groupString): string
{
    return Plugin::handlePageAccess($groupString);
}
