<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection as CsrfProtector;
use XH\Pages;

use Register\Logic\ValidationService;
use Register\Infra\DbService;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\MailService;
use Register\Infra\Session;
use Register\Infra\SystemChecker;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;

class Dic
{
    public static function makeLoginController(): LoginController
    {
        global $plugin_cf, $plugin_tx;

        return new LoginController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            self::makeUserRepository(),
            new UserGroupRepository(self::makeDbService()),
            new LoginManager(time(), new Session()),
            new Logger(),
            new Session()
        );
    }

    public static function makeSpecialPageController(): SpecialPageController
    {
        global $h, $plugin_cf, $plugin_tx;

        return new SpecialPageController(
            $h,
            $plugin_cf['register'],
            $plugin_tx['register'],
            self::makeView()
        );
    }

    public static function makeUserAdminController(): UserAdminController
    {
        global $pth, $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        return new UserAdminController(
            "{$pth['folder']['plugins']}register/",
            $plugin_cf["register"],
            $plugin_tx["register"],
            $_XH_csrfProtection,
            self::makeDbService()
        );
    }

    public static function makeGroupAdminController(): GroupAdminController
    {
        global $pth, $plugin_tx, $_XH_csrfProtection;

        return new GroupAdminController(
            "{$pth['folder']['plugins']}register/",
            $plugin_tx["register"],
            $_XH_csrfProtection,
            self::makeDbService(),
            new Pages()
        );
    }

    public static function makeShowRegistrationForm(): ShowRegistrationForm
    {
        return new ShowRegistrationForm(self::makeView());
    }

    public static function makeRegisterUser(): RegisterUser
    {
        global $plugin_cf, $plugin_tx;

        return new RegisterUser(
            $plugin_cf["register"],
            $plugin_tx["register"],
            self::makeValidationService(),
            self::makeView(),
            self::makeUserRepository(),
            new MailService()
        );
    }

    public static function makeActivateUser(): ActivateUser
    {
        global $plugin_cf, $plugin_tx;

        return new ActivateUser(
            $plugin_cf["register"],
            $plugin_tx["register"],
            self::makeUserRepository(),
            self::makeView()
        );
    }

    public static function makeShowPasswordForgottenForm(): ShowPasswordForgottenForm
    {
        return new ShowPasswordForgottenForm(self::makeView());
    }

    public static function makePasswordForgotten(): PasswordForgotten
    {
        global $plugin_cf, $plugin_tx;

        return new PasswordForgotten(
            $plugin_cf["register"],
            $plugin_tx["register"],
            time(),
            self::makeView(),
            self::makeUserRepository(),
            new MailService()
        );
    }

    public static function makeResetPassword(): ResetPassword
    {
        global $plugin_tx;

        return new ResetPassword(
            $plugin_tx["register"],
            time(),
            self::makeView(),
            self::makeUserRepository()
        );
    }

    public static function makeChangePassword(): ChangePassword
    {
        global $plugin_cf, $plugin_tx;

        return new ChangePassword(
            $plugin_cf["register"],
            $plugin_tx["register"],
            time(),
            self::makeView(),
            self::makeUserRepository(),
            new MailService()
        );
    }

    public static function makeShowUserPreferences(): ShowUserPreferences
    {
        global $plugin_tx;

        return new ShowUserPreferences(
            $plugin_tx["register"],
            new Session(),
            new CsrfProtector('register_csrf_token', false),
            self::makeUserRepository(),
            self::makeView()
        );
    }

    public static function makeEditUser(): EditUser
    {
        global $plugin_cf, $plugin_tx;

        return new EditUser(
            $plugin_cf["register"],
            $plugin_tx["register"],
            new Session(),
            new CsrfProtector('register_csrf_token', false),
            self::makeValidationService(),
            self::makeUserRepository(),
            self::makeView(),
            new MailService()
        );
    }

    public static function makeUnregisterUser(): UnregisterUser
    {
        global $plugin_tx;

        return new UnregisterUser(
            $plugin_tx["register"],
            new Session(),
            new CsrfProtector('register_csrf_token', false),
            self::makeUserRepository(),
            self::makeView(),
            new LoginManager(time(), new Session()),
            new Logger()
        );
    }

    public static function makeShowLoginForm(): ShowLoginForm
    {
        global $plugin_cf, $plugin_tx;

        return new ShowLoginForm(
            $plugin_cf["register"],
            $plugin_tx["register"],
            self::makeView()
        );
    }

    public static function makeShowPageDataTab(): ShowPageDataTab
    {
        global $pth, $tx;

        return new ShowPageDataTab(
            $pth['folder']['corestyle'],
            $tx['editmenu']['help'],
            self::makeView()
        );
    }

    public static function makeShowPluginInfo(): ShowPluginInfo
    {
        global $pth, $plugin_tx;

        return new ShowPluginInfo(
            $pth['folder']['plugins'],
            $plugin_tx['register'],
            self::makeDbService(),
            new SystemChecker(),
            self::makeView()
        );
    }

    public static function makeUserRepository(): UserRepository
    {
        return new UserRepository(self::makeDbService());
    }

    private static function makeValidationService(): ValidationService
    {
        global $plugin_tx;

        return new ValidationService($plugin_tx["register"]);
    }

    private static function makeDbService(): DbService
    {
        global $pth, $cf, $plugin_cf, $sl;
        static $instance;

        if (!isset($instance)) {
            $folder = $pth["folder"]["content"];
            if ($sl !== $cf["language"]["default"]) {
                $folder = dirname($folder) . "/";
            }
            $folder .= "register/";
            $instance = new DbService($folder, $plugin_cf['register']['group_default']);
        }
        return $instance;
    }

    private static function makeView(): View
    {
        global $pth, $plugin_tx;

        return new View("{$pth['folder']['plugins']}register/", $plugin_tx['register']);
    }
}
