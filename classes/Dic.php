<?php

/**
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Plib\CsrfProtector;
use Plib\DocumentStore;
use Plib\Random;
use Plib\View;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Mailer;
use Register\Infra\Pages;
use Register\Infra\Password;
use Register\Infra\SystemChecker;
use Register\PHPMailer\PHPMailer;

class Dic
{
    public static function makeMain(): Main
    {
        global $plugin_cf;

        return new Main(
            $plugin_cf["register"],
            new DocumentStore(self::contentFolder()),
            new Pages(),
            new Logger(),
            new LoginManager(),
            self::view()
        );
    }

    public static function makePagesAdmin(): PagesAdmin
    {
        return new PagesAdmin(new Pages(), self::view());
    }

    public static function makeUserAdmin(): UserAdmin
    {
        global $plugin_cf;
        return new UserAdmin(
            $plugin_cf["register"],
            new CsrfProtector(),
            new DocumentStore(self::contentFolder()),
            new Password(),
            new Random(),
            self::makeMailer(),
            self::view()
        );
    }

    public static function makeGroupAdmin(): GroupAdmin
    {
        return new GroupAdmin(
            new CsrfProtector(),
            new DocumentStore(self::contentFolder()),
            new Pages(),
            self::view(),
        );
    }

    public static function makeHandleUserRegistration(): HandleUserRegistration
    {
        global $plugin_cf;

        return new HandleUserRegistration(
            $plugin_cf["register"],
            new Random(),
            self::view(),
            new DocumentStore(self::contentFolder()),
            self::makeMailer(),
            new Password()
        );
    }

    public static function makeForbidden(): Forbidden
    {
        return new Forbidden(self::view());
    }

    public static function makeHandlePasswordForgotten(): HandlePasswordForgotten
    {
        global $plugin_cf;

        return new HandlePasswordForgotten(
            $plugin_cf["register"],
            self::view(),
            new DocumentStore(self::contentFolder()),
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
            new DocumentStore(self::contentFolder()),
            self::view(),
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
            new DocumentStore(self::contentFolder()),
            new LoginManager(),
            new Logger(),
            new Password(),
            self::view()
        );
    }

    public static function makeUserInfo(): UserInfo
    {
        global $plugin_cf;
        return new UserInfo(
            $plugin_cf["register"],
            new DocumentStore(self::contentFolder()),
            self::view()
        );
    }

    public static function makeShowPageDataTab(): ShowPageDataTab
    {
        global $pth;
        return new ShowPageDataTab($pth["folder"]["corestyle"], self::view());
    }

    public static function makeShowPluginInfo(): ShowPluginInfo
    {
        global $pth;
        return new ShowPluginInfo(
            $pth["folder"]["plugins"] . "register/",
            new DocumentStore(self::contentFolder()),
            new SystemChecker(),
            self::view()
        );
    }

    public static function activeUsersController(): ActiveUsersController
    {
        global $plugin_cf;
        return new ActiveUsersController(
            $plugin_cf["register"],
            new DocumentStore(self::contentFolder()),
            self::view()
        );
    }

    private static function contentFolder(): string
    {
        global $pth;
        $folder = $pth["folder"]["content"];
        if ($pth["folder"]["base"] === "../") {
            $folder = dirname($folder) . "/";
        }
        $folder .= "register/";
        return $folder;
    }

    private static function makeMailer(): Mailer
    {
        global $plugin_cf;
        return new Mailer($plugin_cf["register"], new PHPMailer(false));
    }

    private static function view(): View
    {
        global $pth, $plugin_tx;

        return new View("{$pth['folder']['plugins']}register/views/", $plugin_tx['register']);
    }
}
