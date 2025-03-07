<?php

/**
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\ActivityRepository;
use Register\Infra\CsrfProtector;
use Register\Infra\DbService;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Mailer;
use Register\Infra\Pages;
use Register\Infra\Password;
use Register\Infra\Random;
use Register\Infra\SystemChecker;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\PHPMailer\PHPMailer;

class Dic
{
    public static function makeMain(): Main
    {
        global $plugin_cf;

        return new Main(
            $plugin_cf["register"],
            self::makeUserRepository(),
            self::makeActivityRepository(),
            new Pages(),
            new Logger(),
            new LoginManager(),
            self::makeView()
        );
    }

    public static function makePagesAdmin(): PagesAdmin
    {
        return new PagesAdmin(new Pages(), self::makeView());
    }

    public static function makeUserAdmin(): UserAdmin
    {
        global $plugin_cf;
        return new UserAdmin(
            $plugin_cf["register"],
            new CsrfProtector(),
            self::makeUserRepository(),
            self::makeUserGroupRepository(),
            new Password(),
            new Random(),
            self::makeMailer(),
            self::makeView()
        );
    }

    public static function makeGroupAdmin(): GroupAdmin
    {
        return new GroupAdmin(
            new CsrfProtector(),
            self::makeUserGroupRepository(),
            new Pages(),
            self::makeView(),
        );
    }

    public static function makeHandleUserRegistration(): HandleUserRegistration
    {
        global $plugin_cf;

        return new HandleUserRegistration(
            $plugin_cf["register"],
            new Random(),
            self::makeView(),
            self::makeUserRepository(),
            self::makeMailer(),
            new Password()
        );
    }

    public static function makeForbidden(): Forbidden
    {
        return new Forbidden(self::makeView());
    }

    public static function makeHandlePasswordForgotten(): HandlePasswordForgotten
    {
        global $plugin_cf;

        return new HandlePasswordForgotten(
            $plugin_cf["register"],
            self::makeView(),
            self::makeUserRepository(),
            new Password(),
            self::makeMailer(),
            new LoginManager(),
            new Logger()
        );
    }

    public static function makeHandleUserPreferences(): HandleUserPreferences
    {
        global $plugin_cf;

        return new HandleUserPreferences(
            $plugin_cf["register"],
            new CsrfProtector(),
            self::makeUserRepository(),
            self::makeView(),
            self::makeMailer(),
            new Logger(),
            new Password()
        );
    }

    public static function makeShowLoginForm(): ShowLoginForm
    {
        global $plugin_cf;

        return new ShowLoginForm(
            $plugin_cf["register"],
            self::makeUserRepository(),
            self::makeUserGroupRepository(),
            self::makeActivityRepository(),
            new LoginManager(),
            new Logger(),
            new Password(),
            self::makeView()
        );
    }

    public static function makeUserInfo(): UserInfo
    {
        global $plugin_cf;
        return new UserInfo(
            $plugin_cf["register"],
            self::makeUserRepository(),
            self::makeView()
        );
    }

    public static function makeShowPageDataTab(): ShowPageDataTab
    {
        global $pth;
        return new ShowPageDataTab($pth["folder"]["corestyle"], self::makeView());
    }

    public static function makeShowPluginInfo(): ShowPluginInfo
    {
        global $pth;
        return new ShowPluginInfo(
            $pth["folder"]["plugins"] . "register/",
            self::makeDbService(),
            new SystemChecker(),
            self::makeView()
        );
    }

    public static function makeActiveUsers(): ActiveUsers
    {
        global $plugin_cf;
        return new ActiveUsers(
            $plugin_cf["register"],
            self::makeActivityRepository(),
            self::makeView()
        );
    }

    private static function makeUserRepository(): UserRepository
    {
        return new UserRepository(self::makeDbService());
    }

    private static function makeUserGroupRepository(): UserGroupRepository
    {
        return new UserGroupRepository(self::makeDbService());
    }

    private static function makeActivityRepository(): ActivityRepository
    {
        return new ActivityRepository(self::makeDbService());
    }

    private static function makeDbService(): DbService
    {
        global $pth, $plugin_cf;
        static $instance;

        if (!isset($instance)) {
            $folder = $pth["folder"]["content"];
            if ($pth["folder"]["base"] === "../") {
                $folder = dirname($folder) . "/";
            }
            $folder .= "register/";
            $instance = new DbService($folder, $plugin_cf['register']['group_default'], new Random());
        }
        return $instance;
    }

    private static function makeMailer(): Mailer
    {
        global $plugin_cf;
        return new Mailer($plugin_cf["register"], new PHPMailer(false));
    }

    private static function makeView(): View
    {
        global $pth, $plugin_tx;

        return new View("{$pth['folder']['plugins']}register/views/", $plugin_tx['register']);
    }
}
