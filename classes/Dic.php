<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection as CsrfProtector;
use XH\Pages;

use Register\Value\User;
use Register\Logic\ValidationService;
use Register\Infra\DbService;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\MailService;
use Register\Infra\Session;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;

class Dic
{
    public static function makeLoginController(DbService $dbService): LoginController
    {
        global $plugin_cf, $plugin_tx;

        return new LoginController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            new UserRepository($dbService),
            new UserGroupRepository($dbService),
            new LoginManager(time(), new Session()),
            new Logger(),
            new Session()
        );
    }

    public static function makeSpecialPageController(): SpecialPageController
    {
        global $h, $plugin_cf, $plugin_tx, $pth;

        return new SpecialPageController(
            $h,
            $plugin_cf['register'],
            $plugin_tx['register'],
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register'])
        );
    }

    public static function makeMainAdminController(DbService $dbService): MainAdminController
    {
        global $pth, $plugin_cf, $plugin_tx, $_XH_csrfProtection, $sn;

        return new MainAdminController(
            "{$pth['folder']['plugins']}register/",
            $plugin_cf["register"],
            $plugin_tx["register"],
            $_XH_csrfProtection,
            $dbService,
            $sn,
            new Pages()
        );
    }

    public static function makeRegistrationController(DbService $dbService): RegistrationController
    {
        global $pth, $plugin_cf, $plugin_tx;

        return new RegistrationController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            new ValidationService($plugin_tx["register"]),
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register']),
            new UserRepository($dbService),
            new MailService()
        );
    }

    public static function makeForgotPasswordController(DbService $dbService): ForgotPasswordController
    {
        global $pth, $plugin_cf, $plugin_tx;

        return new ForgotPasswordController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            time(),
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register']),
            new UserRepository($dbService),
            new MailService()
        );
    }

    public static function makeUserPrefsController(DbService $dbService): UserPrefsController
    {
        global $pth, $plugin_cf, $plugin_tx, $sn, $su;

        return new UserPrefsController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            new Session(),
            new CsrfProtector('register_csrf_token', false),
            new ValidationService($plugin_tx["register"]),
            new UserRepository($dbService),
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register']),
            new MailService(),
            new LoginManager(time(), new Session()),
            new Logger(),
            "$sn?$su"
        );
    }

    /**
     * @param User|null $user
     */
    public static function makeLoginFormController($user): LoginFormController
    {
        global $pth, $plugin_cf, $plugin_tx, $sn, $su;

        return new LoginFormController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            $sn,
            $su,
            $user,
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register'])
        );
    }

    public static function makePageDataController(): PageDataController
    {
        global $pth, $tx, $plugin_tx;

        return new PageDataController(
            $pth['folder']['corestyle'],
            $tx['editmenu']['help'],
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register'])
        );
    }
}
