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

    public static function makeUserAdminController(DbService $dbService): UserAdminController
    {
        global $pth, $plugin_cf, $plugin_tx, $_XH_csrfProtection, $sn;

        return new UserAdminController(
            "{$pth['folder']['plugins']}register/",
            $plugin_cf["register"],
            $plugin_tx["register"],
            $_XH_csrfProtection,
            $dbService,
            $sn
        );
    }

    public static function makeGroupAdminController(DbService $dbService): GroupAdminController
    {
        global $pth, $plugin_tx, $_XH_csrfProtection, $sn;

        return new GroupAdminController(
            "{$pth['folder']['plugins']}register/",
            $plugin_tx["register"],
            $_XH_csrfProtection,
            $dbService,
            $sn,
            new Pages()
        );
    }

    public static function makeShowRegistrationForm(DbService $dbService): ShowRegistrationForm
    {
        global $pth, $plugin_tx, $sn, $su;

        return new ShowRegistrationForm(
            $sn,
            $su,
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register'])
        );
    }

    public static function makeRegisterUser(DbService $dbService): RegisterUser
    {
        global $pth, $plugin_cf, $plugin_tx, $sn, $su;

        return new RegisterUser(
            $sn,
            $su,
            $plugin_cf["register"],
            $plugin_tx["register"],
            new ValidationService($plugin_tx['register']),
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register']),
            new UserRepository($dbService),
            new MailService()
        );
    }

    public static function makeActivateUser(DbService $dbService): ActivateUser
    {
        global $pth, $plugin_cf, $plugin_tx;

        return new ActivateUser(
            $plugin_cf["register"],
            $plugin_tx["register"],
            new UserRepository($dbService),
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register'])
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

    public static function makeShowPageDataTab(): ShowPageDataTab
    {
        global $pth, $tx, $plugin_tx;

        return new ShowPageDataTab(
            $pth['folder']['corestyle'],
            $tx['editmenu']['help'],
            new View("{$pth['folder']['plugins']}register/", $plugin_tx['register'])
        );
    }

    public static function makeDbService(): DbService
    {
        /**
         * @var array{folder:array<string,string>,file:array<string,string>} $pth
         */
        global $pth;
    
        $folder = $pth["folder"]["content"];
        if ($pth["folder"]["base"] === "../") {
            $folder = dirname($folder) . "/";
        }
        $folder .= "register/";
        return new DbService($folder);
    }
}
